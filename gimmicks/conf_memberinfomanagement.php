<?php
//学童内お知らせ管理
session_start();
//管理ページno
$G_gimmickno = 19;

//基本設定関数
require_once __DIR__ . '/../basic_function.php';
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

//学童内お知らせ表示開始ページ
$disp_pageNo = 1;

isset($_GET['pn']) && $disp_pageNo = $_GET['pn'];
isset($_POST['pn']) && $disp_pageNo = $_POST['pn'];

//post値取得
$P_gimmick_type = ""; //操作種別 dispChange=表示切替, delete=削除
$P_infoId = 0;	//投稿id
$P_dispType = 1;	//表示切替 1=表示,0=非表示
$P_window_position = 0;	//windowスクロール位置

isset($_POST["p_window_position"]) && $P_window_position = $_POST["p_window_position"];
isset($_POST["gimmick_type"]) && $P_gimmick_type = $_POST["gimmick_type"];
isset($_POST["infoId"]) && $P_infoId = $_POST["infoId"];
isset($_POST["dispType"]) && $P_dispType = $_POST["dispType"];

//情報格納
//役務種別 arr[役務id]=役務名
$G_sections = array();
//館情報 arr[館id]=館名
$G_halls = array();
//職種 arr[職種id]=職種名
$G_posts = array();

//館情報取得
$stmt = $pdo -> prepare('SELECT `id_hall`, `name_hall` FROM `halls` WHERE `activity_flg` = 1 ORDER BY `id_hall` ASC');
$stmt -> execute();
$t_halls = $stmt -> fetchAll();
foreach($t_halls as $hall){
	$G_halls[$hall['id_hall']] = $hall['name_hall'];
}

//役務取得
$stmt = $pdo -> prepare('SELECT `id_section`, `name_section` FROM `section_kind`
WHERE `del_flg` <> 1 ORDER BY `order_section` ASC');
$stmt -> execute();
$t_section = $stmt -> fetchall();
foreach($t_section as $val){
	$G_sections[$val['id_section']] = $val['name_section'];
}

//職種情報取得
$stmt = $pdo -> prepare('SELECT `id_staff_post`, `post` FROM `staff_posts` WHERE `del_flg` <> 1 ORDER BY `id_staff_post` ASC');
$stmt -> execute();
$t_staff = $stmt -> fetchAll();
foreach($t_staff as $staff){
	$G_posts[$staff['id_staff_post']] = $staff['post'];
}

$G_mess = "";	//作業メッセージ
if($P_gimmick_type == "delete"){
	//投稿削除===========================================
	$stmt = $pdo -> prepare('
	UPDATE `member_news_text`
	SET `del_flg` = 1
	WHERE `id_news` = :id
	');
	$stmt -> bindValue(':id', $P_infoId, PDO::PARAM_INT);
	$stmt ->execute();

	$G_mess = '<span class="col_blue">投稿No.' . $P_infoId . 'を削除しました</span>';

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_infoId);

}elseif($P_gimmick_type == "dispChange"){
	//表示切替===========================================
	$stmt = $pdo -> prepare('
	UPDATE `member_news_text`
	SET `disp_flg` = :disptype
	WHERE `id_news` = :id
	');
	$stmt -> bindValue(':disptype', (int)$P_dispType, PDO::PARAM_INT);
	$stmt -> bindValue(':id', $P_infoId, PDO::PARAM_INT);
	$stmt -> execute();

	$G_mess = '<span class="col_blue">投稿No.' . $P_infoId . 'の表示設定を切替えました</span>';

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_infoId, $P_dispType);

}

//投稿取得===========================================
//お知らせ情報格納配列
//arr[お知らせid(member_news_text/id_news)]=member_news_textテーブル内容、登録者名
$G_if_info = array();
$stmt = $pdo -> query('
SELECT t_text.*, t_parent.`surname` as `parent_name`, t_staff.`surname` as `staff_name`
FROM `member_news_text` as t_text
LEFT JOIN `members_home` as t_mhome
ON t_text.`fk_id_member_home` = t_mhome.`id_member_home`
LEFT JOIN
(SELECT `fk_id_member_home`, `surname`
FROM `members_parent`
WHERE `delegate_flg` = 1) as t_parent
ON t_mhome.`id_member_home` = t_parent.`fk_id_member_home`
LEFT JOIN `staff` as t_staff
ON t_text.`fk_id_staff` = t_staff.`id_staff`
WHERE t_text.`del_flg` <> 1
ORDER BY t_text.`top_flg` DESC, t_text.`id_news` DESC
');
$stmt -> execute();
$t_info = $stmt -> fetchAll();
$infoCount = $stmt -> rowCount();

if($infoCount){
	foreach($t_info as $val){
		$G_if_info[$val['id_news']] = $val;
	}
}
//表示対象取得===========================================
//表示対象情報格納配列
//arr[お知らせid]=arr[x][x対象id][]=対象館id(0=全館)
//x	→	'member'=会員、対象idは1固定
//		'staff'=職員、対象idは1固定
//		'section'=役務
//		'post'=職種
$G_if_dest = array();
$stmt = $pdo -> query('
SELECT t_target.*
FROM `member_news_text` as t_text
INNER JOIN `member_news_target` as t_target
ON t_text.`id_news` = t_target.`fk_id_news`
WHERE t_text.`del_flg` <> 1
ORDER BY
t_target.`fk_id_news` DESC,
t_target.`target_member_flg` DESC,
t_target.`target_staff_flg` DESC,
t_target.`fk_id_staff_post` ASC,
t_target.`fk_id_section` ASC,
t_target.`fk_id_hall` ASC
');
$stmt -> execute();
$t_target = $stmt -> fetchAll();

foreach($t_target as $val){
	if(!isset($G_if_dest[$val['fk_id_news']])){
		$G_if_dest[$val['fk_id_news']] = array();
	}
	if($val['target_member_flg']){
		//会員宛て
		!isset($G_if_dest[$val['fk_id_news']]['member']) &&
			$G_if_dest[$val['fk_id_news']]['member'] = array();
		!isset($G_if_dest[$val['fk_id_news']]['member'][$val['target_member_flg']]) &&
			$G_if_dest[$val['fk_id_news']]['member'][$val['target_member_flg']] = array();
			$G_if_dest[$val['fk_id_news']]['member'][$val['target_member_flg']][] = $val['fk_id_hall'];
	}
	if($val['target_staff_flg']){
		//職員宛て
		!isset($G_if_dest[$val['fk_id_news']]['staff']) &&
			$G_if_dest[$val['fk_id_news']]['staff'] = array();
		!isset($G_if_dest[$val['fk_id_news']]['staff'][$val['target_staff_flg']]) &&
			$G_if_dest[$val['fk_id_news']]['staff'][$val['target_staff_flg']] = array();
			$G_if_dest[$val['fk_id_news']]['staff'][$val['target_staff_flg']][] = $val['fk_id_hall'];
	}
	if($val['fk_id_section']){
		//役務宛て
		!isset($G_if_dest[$val['fk_id_news']]['section']) &&
			$G_if_dest[$val['fk_id_news']]['section'] = array();
		!isset($G_if_dest[$val['fk_id_news']]['section'][$val['fk_id_section']]) &&
			$G_if_dest[$val['fk_id_news']]['section'][$val['fk_id_section']] = array();
			$G_if_dest[$val['fk_id_news']]['section'][$val['fk_id_section']][] = $val['fk_id_hall'];
	}
	if($val['fk_id_staff_post']){
		//職種宛て
		!isset($G_if_dest[$val['fk_id_news']]['post']) &&
			$G_if_dest[$val['fk_id_news']]['post'] = array();
		!isset($G_if_dest[$val['fk_id_news']]['post'][$val['fk_id_staff_post']]) &&
			$G_if_dest[$val['fk_id_news']]['post'][$val['fk_id_staff_post']] = array();
			$G_if_dest[$val['fk_id_news']]['post'][$val['fk_id_staff_post']][] = $val['fk_id_hall'];
	}
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
<script src="./js/conf_memberinfomanagement.js?<?php echo date("Ymd-Hi"); ?>"></script>
<script src="/js/jquery-3.4.1.min.js"></script>
<script type="text/javascript">$(document).ready(function(){$(window).scrollTop(<?php echo $P_window_position; ?>);});</script>
<title><?php echo $G_npo_items['name_npo']; ?> 学童内お知らせ管理</title>
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
		<h3>学童内お知らせ管理</h3>
		<p>
			<a href = "./configure_menu.php" target = "_self">・管理トップへ戻る</a><br>
			<a href = "" target = "_self">・学童内お知らせ管理トップへ戻る</a><br>
		</p>
		<hr>
		<p>
			<form action="" method="post" onSubmit="return false;">
				<h3 name="infoplace" id="infoplace">お知らせ一覧</h3>
				<?php echo $G_mess; ?>
<?php
if($infoCount){
	if($disp_pageNo < 1){
		$disp_pageNo = 1;
	}
	$max_page = ceil($infoCount / GV_numberOfEvents_innerInfo);
	if($disp_pageNo > $max_page){
		$disp_pageNo = $max_page;
	}

	$page = F_makePaging($infoCount, GV_numberOfEvents_innerInfo, $disp_pageNo, "", "infoplace", "pn");
	$i_start = ($disp_pageNo -1) * GV_numberOfEvents_innerInfo;
	$i_last = $i_start + GV_numberOfEvents_innerInfo -1;
	echo '<div style="text-align:center">' . $page . '</div>';

//一覧ここから--------------------------------------------------
	$i_countNow = -1;
	foreach($G_if_info as $key => $val){
		++$i_countNow;
		if($i_countNow < $i_start){
			continue;
		}
		if($i_countNow > $i_last){
			break;
		}

		$disp_status = "";	//表示状態(表示中、非表示)
		if($val['disp_flg'] == 1){
			$disp_status = '<span class="col_blue">== 投稿No.' . $key . ' 表示中 ==</span>';
		}else{
			$disp_status = '<span class="col_red">== 投稿No.' . $key . ' 非表示 ==</span>';
		}

		//表示切替ボタン
		$button_disp = '<input type="button" value="表示切替" onClick="f_dispChange(this.form, ' . $key . ', ' . $val['disp_flg'] . ');">';

		//削除ボタン
		$button_del = '<input type="button" value="削除" onClick="f_delete(this.form, ' . $key . ');">';

		$contributor = "";	//投稿者
		if($val['parent_name']){
			$contributor = $val['parent_name'] . "(会員)";
		}
		if($val['staff_name']){
			$contributor .= $val['staff_name'] . "(職員)";
		}

		$destination = "";	//宛先

		//会員
		if(isset($G_if_dest[$key]['member'])){
			foreach($G_if_dest[$key]['member'] as $d_key => $d_val){
				$t_targetHall = Fl_hallDispcolor($G_halls, $d_val);
				$destination .= "会員({$t_targetHall})<br>";
			}
		}
		//職員
		if(isset($G_if_dest[$key]['staff'])){
			foreach($G_if_dest[$key]['staff'] as $d_key => $d_val){
				$t_targetHall = Fl_hallDispcolor($G_halls, $d_val);
				$destination .= "職員({$t_targetHall})<br>";
			}
		}
		//役務
		if(isset($G_if_dest[$key]['section'])){
			$destination .= "-----役職-----<br>";
			foreach($G_if_dest[$key]['section'] as $d_key => $d_val){
				$t_targetHall = Fl_hallDispcolor($G_halls, $d_val);
				$destination .= $G_sections[$d_key] . "({$t_targetHall})<br>";
			}
		}
		//職種
		if(isset($G_if_dest[$key]['post'])){
			$destination .= "-----職種-----<br>";
			foreach($G_if_dest[$key]['post'] as $d_key => $d_val){
				$t_targetHall = Fl_hallDispcolor($G_halls, $d_val);
				$destination .= $G_posts[$d_key] . "({$t_targetHall})<br>";
			}
		}
?>
<div class="infobox2">
	<span class="box-title"><?php echo $val['regist_time']; ?></span>
	<p>
<?php
	echo $disp_status . "<br>";
	echo "{$button_disp}　{$button_del}<br>";
	echo "<b>「" . $val['title_news'] . "」</b><br>";
	echo nl2br($val['text_news']);
	echo "<br><span class='col_blue'>[---投稿者---]</span><br>" . $contributor;
	echo "<br><span class='col_blue'>[---対象者---]</span><br>" . $destination;
?>
	</p>
</div>
<?php
//一覧ここまで--------------------------------------------------

	}

	echo '<div style="text-align:center">' . $page . '</div>';

}else{
?>
<div class="infobox2">
	<p>お知らせはありません</p>
</div>
<?php
}
?>
			<input type="hidden" name="gimmick_type" value="">
			<input type="hidden" name="pn" value="<?php echo $disp_pageNo; ?>">
			<input type="hidden" name="infoId" value=0>
			<input type="hidden" name="dispType" value=1>
			<input type="hidden" name="p_window_position" value=0>
			</form>
			<hr>
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
対象館表示色セット

引数
$f_halls 館情報配列 arr[館id]=館名
$f_targetsh 表示対象館配列 arr[]=対象館id(0=全館)

戻り値
対象館色指定表示テキスト
:::::::::::::::::::::::::::::::*/
function Fl_hallDispColor($f_halls, $f_targets){
	$ret = "";
	$all_flg = false;
	$col = "col_blue";
	in_array(0, $f_targets) && $all_flg = true;
	foreach($f_halls as  $id => $hall){
		if(!$all_flg){
			in_array($id, $f_targets)?$col = "col_blue":$col = "col_gray";
		}
		$ret .= "<span class=\"{$col}\">{$hall}</span>";
	}
	return $ret;
}
?>