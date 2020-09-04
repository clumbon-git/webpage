<?php
//児童在所管理
session_start();
//管理ページno
$G_gimmickno = 21;

//基本設定関数
require_once __DIR__ . '/../basic_function.php';
require_once __DIR__ . '/../basic_class.php';
require_once __DIR__ . '/../basic_setting.php';
//DB接続
require_once __DIR__ . '/../dbip.php';
//ヘッダDB情報ロード
require_once __DIR__ . '/../head_dbread.php';

//ログイン確認
//array('id' => 個人識別id, 'kind' => 会員種別(1 会員, 2 職員), 'hall' => 所属館id)
if(!$G_login){
	header('Location: /', true, 301);
}

//権限確認 Array[ギミックページid]=array( [hall] =>他館許可値 0=許可、1=制限 [kind] =>権限値 0=参照、1=参照、更新　2=管理者)
$G_arrPmsn = F_pmsnCheck($G_login);
if(!isset($G_arrPmsn[$G_gimmickno])){
	header('Location: /', true, 301);
}

$G_pmsnLevel = 0;	//権限レベル
if(isset($G_arrPmsn[$G_gimmickno])){
	$G_pmsnLevel = $G_arrPmsn[$G_gimmickno]['kind'];
}
$G_hallLevel = 1;	//他館許可 0=許可 1=制限
if(isset($G_arrPmsn[$G_gimmickno])){
	$G_hallLevel = $G_arrPmsn[$G_gimmickno]['hall'];
}

//当日
$today_date = date('Ymd');

$P_gimmick_type = "";	//操作種別 set=登録, delete=削除
$P_status = 0;				//所在種別 1=登所 2=出所(帰宅) 3=出所(塾遊他)　1→在所、他→出所
$P_id_child = 0;			//児童id
$P_id_location = 0;		//所在状況id(誤登録削除時使用)
$P_barcode = "";			//バーコード値
$P_window_position = 0;	//windowスクロール位置

//post値取得
isset($_POST['p_status']) && $P_status = $_POST['p_status'];
isset($_POST['p_id_child']) && $P_id_child = $_POST['p_id_child'];
isset($_POST['p_id_location']) && $P_id_location = $_POST['p_id_location'];
isset($_POST["gimmick_type"]) && $P_gimmick_type = $_POST["gimmick_type"];
isset($_POST["p_window_position"]) && $P_window_position = $_POST["p_window_position"];

$Get_list = "";	//画面内容フラグ list=バーコード一覧
isset($_GET['list']) && $Get_list = $_GET['list'];

//情報格納

//館情報 arr[館id]=館名
$G_halls = array();
//館情報取得
$stmt = $pdo -> query('SELECT `id_hall`, `name_hall` FROM `halls` WHERE `activity_flg` = 1 ORDER BY `id_hall` ASC');
$stmt -> execute();
$t_halls = $stmt -> fetchAll();
foreach($t_halls as $hall){
	$G_halls[$hall['id_hall']] = $hall['name_hall'];
}

//児童情報
//arr[館id][]=[カラム名=>値, ・・・](members_childテーブル)
$G_children = array();
//児童情報取得
$stmt = $pdo -> query('
SELECT `t_child`.*
FROM `members_home` as `t_mem`
LEFT JOIN `members_child` as `t_child`
ON `t_mem`.`id_member_home` = `t_child`.`fk_id_member_home`
WHERE `t_mem`.`activity_flg` = 1 AND `t_child`.`activity_flg` = 1
ORDER BY `t_child`.`fk_id_hall` ASC, `t_child`.`enterhall_date` DESC, `t_child`.`surname_kana` ASC
');
$stmt -> execute();
$t_children = $stmt -> fetchAll();
foreach($t_children as $val){
	!isset($G_children[$val['fk_id_hall']]) && $G_children[$val['fk_id_hall']]=array();
	$G_children[$val['fk_id_hall']][] = $val;
}

$G_mess = "";	//作業メッセージ
if($P_gimmick_type == "set"){
	//出退登録===========================================
	$t_idmember = 0;
	$t_idstaff = 0;
	if($G_login['kind'] == 1){
		$t_idmember = $G_login['id'];
	}elseif($G_login['kind'] == 2){
		$t_idstaff = $G_login['id'];
	}

	$stmt = $pdo -> prepare('
INSERT INTO `child_location`
(`fk_id_child`, `existance_status`, `fk_id_member_home_set`, `fk_id_staff_set`)
VALUES
(:id_child, :status, :id_member, :id_staff)
	');
	$stmt -> bindValue(':id_child', $P_id_child, PDO::PARAM_INT);
	$stmt -> bindValue(':status', $P_status, PDO::PARAM_INT);
	$stmt -> bindValue(':id_member', $t_idmember, PDO::PARAM_INT);
	$stmt -> bindValue(':id_staff', $t_idstaff, PDO::PARAM_INT);
	$stmt ->execute();

	$G_mess = '';	//機能追加があれば使用

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_id_child, $P_status);

}elseif($P_gimmick_type == "del"){
	//出退削除=============================================
	$t_idmember = 0;
	$t_idstaff = 0;
	if($G_login['kind'] == 1){
		$t_idmember = $G_login['id'];
	}elseif($G_login['kind'] == 2){
		$t_idstaff = $G_login['id'];
	}

	$stmt = $pdo -> prepare('
UPDATE `child_location`
SET `del_flg` = 1, `fk_id_member_home_del` = :id_member, `fk_id_staff_del` = :id_staff
WHERE `id_child_location` = :id
	');
	$stmt -> bindValue(':id', $P_id_location, PDO::PARAM_INT);
	$stmt -> bindValue(':id_member', $t_idmember, PDO::PARAM_INT);
	$stmt -> bindValue(':id_staff', $t_idstaff, PDO::PARAM_INT);
	$stmt -> execute();

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_id_location);

}


//在所情報
//arr[児童id][]=array[カラム値=>値, ・・・];(child_locationテーブル)
$G_location = array();
//在所情報取得
$stmt = $pdo -> prepare('
SELECT *, DATE_FORMAT(`regist_time`, "%H時%i分") as `time`
FROM `child_location`
WHERE `del_flg` = 0
AND DATE_FORMAT(`regist_time`, "%Y%m%d") = :Ymd
ORDER BY `regist_time` ASC
');
$stmt -> bindValue(':Ymd', $today_date, PDO::PARAM_STR);
$stmt -> execute();
$t_location = $stmt -> fetchAll();
foreach($t_location as $val){
	!isset($G_location[$val['fk_id_child']]) && $G_location[$val['fk_id_child']] = array();
	$G_location[$val['fk_id_child']][] = $val;
}

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="googlebot" content="noindex">
<meta NAME=”robots” CONTENT=”noindex,NOFOLLOW,NOARCHIVE”>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="description" content="<?php echo $G_npo_items['name_npo']; ?>">
<meta name="keywords" content="">
<link rel="stylesheet" href="/css/base.css?<?php echo date("Ymd-Hi"); ?>">
<link rel="stylesheet" media="screen and (max-width:800px)" href="/css/base_sp.css?<?php echo date("Ymd-Hi"); ?>">
<link rel="stylesheet" href="./css/gimmicks.css?<?php echo date("Ymd-Hi"); ?>">
<link rel="stylesheet" href="./../members/css/members.css?<?php echo date("Ymd-Hi"); ?>">
<script src="/js/jquery-3.4.1.min.js"></script>
<script src="./js/conf_childlocation.js?<?php echo date("Ymd-Hi"); ?>"></script>
<script type="text/javascript">$(document).ready(function(){$(window).scrollTop(<?php echo $P_window_position; ?>);});</script>
<title><?php echo $G_npo_items['name_npo']; ?> 児童在所管理</title>
</head>
<body>
<div id="pagebody">
<!-- header -->
<?php
include "./../head.php";
?>

	<!-- info left -->
<?php
include "./../left.php"
?>

	<!-- info main -->
	<div id="info">
		<h3>児童在所管理</h3>
		<p>
			<a href = "./configure_menu.php" target = "_self">・管理トップへ戻る</a><br>
			<a href = "?" target = "_self">・登下所登録トップへ移動</a><br>
			<a href = "?list=list" target = "_self">・バーコード一覧</a><br>
		</p>
		<hr>

<?php
if($Get_list == "list"){
	//児童バーコード一覧
?>
		<p>
<?php
$hall_link = "";
foreach($G_halls as $t_hallid => $t_hallname){
	$hall_link .= '　<a href="#hall' . $t_hallid . '" target="_self">' . $t_hallname . '</a>';
}
$temp_hall = 0;
foreach($G_children as $id_hall => $val){
	if($temp_hall != $id_hall){
		//所属館変わりで館名表示
		$temp_hall = $id_hall;
		echo '<span id="hall' . $temp_hall . '">-----' . $G_halls[$temp_hall] . '-----</span>';
		echo $hall_link . '<br>';
	}

	$C_b = new C_barcode;
	foreach($val as $t_child){
		//キャラ画像
		$t_chara_pic = "画像未登録";
		if($t_child['chara_pic'] && file_exists('./../members/pic/' . $t_child['chara_pic'])){
			$t_chara_pic = '<img border="1" src="/members/pic/' . $t_child['chara_pic'] . '" class="child_pic"><br>';
		}

		//履歴----------------------------------------------
		$t_now_text = "(不在)";
		$t_col = "col_red";
		$t_history = "";
		if(isset($G_location[$t_child['id_child']])){
			if($G_location[$t_child['id_child']][count($G_location[$t_child['id_child']]) - 1]['existance_status'] == 1	){
				$t_now_text ="(在所)";
				$t_col = "col_blue";
			}
			foreach($G_location[$t_child['id_child']] as $t_val){
				$t_history .= $t_val['time'] . $GV_status_select[$t_val['existance_status']] . "<br>";
			}
		}

		echo '<table class="children"><tr><th colspan="2">　</th></tr>';

		//氏名----------------------------------------------
		echo '<tr>';
		echo '<td rowspan="10">';
		echo '<div>';
		echo "<span class=\"{$t_col}\" id=\"cid{$t_child['id_child']}\">{$t_now_text}</span>";
		echo "{$t_child['surname']} {$t_child['firstname']}<br>";
		echo $t_chara_pic;
		echo '</div>';
		echo '<div>' . $t_history . '</div>';
		echo '</td>';

		//バーコード-----------------------------------------
		$t_child_id = sprintf('%06d', $t_child['id_child']);
		$gender = 'm';	//キャラ男女
		if($t_child['gender'] == 1){
			$gender = 'f';
		}

		$t_barcode = F_confBarcode($C_b, $GV_cb_head . $GV_cb_in . $t_child_id);
		?>
		<td class="barcode">
		<div>
		<div class="barcode_base">
		<div class="barcode_pic"><img src="./pic/<?php echo $gender; ?>1.png"></div>
		<div class='barcode_disp'>
		<?php echo $t_barcode[0]; ?></div>
		</div>
		</td>
		</tr>
		<?php
		$t_barcode = F_confBarcode($C_b, $GV_cb_head . $GV_cb_go . $t_child_id);
		?>
		<tr>
		<td class="barcode">
		<div>
		<div class="barcode_base">
			<div class="barcode_pic"><img src="./pic/<?php echo $gender; ?>3.png"></div>
		<div class='barcode_disp'>
		<?php echo $t_barcode[0]; ?></div>
		</div>
		</td>
		</tr>
		<?php
		$t_barcode = F_confBarcode($C_b, $GV_cb_head . $GV_cb_out . $t_child_id);
		?>
		<tr>
		<td class="barcode">
		<div>
		<div class="barcode_base">
			<div class="barcode_pic"><img src="./pic/<?php echo $gender; ?>2.png"></div>
		<div class='barcode_disp'>
		<?php echo $t_barcode[0]; ?></div>
		</div>
		</td>
		</tr>
		<?php
	}
	echo '</table><br>';
}
?>
		</p>
		<form name="barcode" action="" method="post" onSubmit="return false;">
			<input type="hidden" name="gimmick_type" value="set">
			<input type="hidden" name="p_status" value=0>
			<input type="hidden" name="p_id_child" value=0>
			<input type="hidden" name="p_window_position" value=0>
		</form>
		<hr>
<?php
}else{
	//入退所管理

?>
		<p>
			<form action="" method="post" onSubmit="return false;">
				<h3 name="infoplace" id="infoplace">登下所登録</h3>
				<span class="t_80per">※学年、あいうえお順</span><br>
<?php
	$hall_link = "";
	foreach($G_halls as $t_hallid => $t_hallname){
		$hall_link .= '　<a href="#hall' . $t_hallid . '" target="_self">' . $t_hallname . '</a>';
	}
	$temp_hall = 0;
	foreach($G_children as $id_hall => $val){
		if($temp_hall != $id_hall){
			//所属館変わりで館名表示
			$temp_hall = $id_hall;
			echo '<span id="hall' . $temp_hall . '">-----' . $G_halls[$temp_hall] . '-----</span>';
			echo $hall_link . '<br>';
		}
		echo '<table class="children"><tr><th>氏名</th><th>履歴</th></tr>';
		foreach($val as $t_child){
			$t_now_text = "(不在)";
			$t_now_num = 0;	//ボタン有効切替用、0=登所のみON 1=出のみON
			$t_col = "col_red";
			$t_history = "";
			if(isset($G_location[$t_child['id_child']])){
				if($G_location[$t_child['id_child']][count($G_location[$t_child['id_child']]) - 1]['existance_status'] == 1	){
					$t_now_text ="(在所)";
					$t_now_num = 1;
					$t_col = "col_blue";
				}
				foreach($G_location[$t_child['id_child']] as $t_val){
					$t_history .= '<input type="button" value="削" onClick="f_del(this.form, ' . $t_val['id_child_location'] . ', \'' . $t_val['time'] . strip_tags($GV_status_select[$t_val['existance_status']]) . '\', ' . $t_child['id_child'] . ');">';
					$t_history .= $t_val['time'] . $GV_status_select[$t_val['existance_status']] . "<br>";
				}
			}
			echo '<tr><td>';
			//氏名----------------------------------------------
			echo "<span class=\"{$t_col}\" id=\"cid{$t_child['id_child']}\">{$t_now_text}</span>{$t_child['surname']} {$t_child['firstname']}<br>";
			echo Fl_makeLocationButton($t_child['id_child'], $t_now_num);
			echo '</td><td>';
			//履歴----------------------------------------------
			echo $t_history;
			echo '</td></tr>';
		}
		echo '</table><br>';
	}

 ?>
			<input type="hidden" name="gimmick_type" value="">
			<input type="hidden" name="p_status" value=0>
			<input type="hidden" name="p_id_child" value=0>
			<input type="hidden" name="p_id_location" value=0>
			<input type="hidden" name="p_window_position" value=0>
			</form>
		</p>
		<hr>
<?php
}
 ?>
	</div>
<!-- footer -->
<?php
include "./../foot.php";
?>

</div>
</body>
</html>

<?php
/*:::::::::::::::::::::::::::::::
登下所登録ボタン作成

引数
$f_idChild	児童id
$f_nowNum 現在在所状況 0=不在,1=在所

戻り値
状態登録ボタン
:::::::::::::::::::::::::::::::*/
function Fl_makeLocationButton($f_idChild, $f_nowNum){
	global $GV_status_select;
	$ret = "";
	$br = "<br>";
	foreach($GV_status_select as $set_flg => $kind){
		$buttonDesabled = "";
		if($f_nowNum == 1 && $set_flg == 1){
			$buttonDesabled = "disabled";
		}elseif($f_nowNum == 0 && $set_flg >1){
			$buttonDesabled = "disabled";
		}
		$ret .= "<input type=\"button\" value=\"" . strip_tags($kind) . "\" onClick=\"f_locationSet(this.form, {$f_idChild}, {$set_flg});\" " . $buttonDesabled . "> ";
		$ret .= $br;
		$br = "";
	}
	return $ret;
}
?>