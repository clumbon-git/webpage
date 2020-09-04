<?php
//npoページ、各館ページトピック管理
session_start();

//管理ページno
$G_gimmickno = 5;

//操作対象館idセット
$P_hall_id = -1;  //編集対象 -1=指定無し 0=NPO 1<=各館id

//基本設定関数
require_once __DIR__.'/../basic_function.php';
//基本設定値
require __DIR__.'/../basic_setting.php';
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

//設定値
$G_hall_name = "";  //操作対象館名

//トピック格納
//arr[]=array(main_textテーブルカラム名=>値,…)
$G_main_text = array();

//トピック画像格納
//arr[トピックid][]=array(main_textテーブルカラム名=>値,…)
$G_main_pics = array();

//画像格納パス
$G_path = "./../topic_pics/";

//get値取得
isset($_GET["g"]) && $P_hall_id = $_GET["g"];

//post値取得
$P_gimmick_type = "";	//操作種別
$P_topic_id = 0;			//トピックid
$P_title = "";				//トピックタイトル
$P_text = "";					//トピック本文
$P_pic_id = 0;				//画像id

isset($_POST["hall_id"]) && $P_hall_id = $_POST["hall_id"];
isset($_POST["gimmick_type"]) && $P_gimmick_type = F_h($_POST["gimmick_type"]);
isset($_POST["topic_id"]) && $P_topic_id = $_POST["topic_id"];
isset($_POST["topic_title"]) && $P_title = F_h($_POST["topic_title"]);
isset($_POST["topic_text"]) && $P_text = F_h($_POST["topic_text"]);
isset($_POST["pic_id"]) && $P_pic_id = $_POST["pic_id"];

$P_window_position = 0;	//windowスクロール位置
isset($_POST["p_window_position"]) && $P_window_position = $_POST["p_window_position"];

//編集対象館未指定エラー
if($P_hall_id == -1){
	header('Location: /', true, 301);
}
//所属館制限チェック
if($G_hallLevel == 1 && $P_hall_id != $G_login['hall']){
	header('Location: /', true, 301);
}

//トピック表示開始ページ
$disp_pageNo = 1;
isset($_GET['pn']) && $disp_pageNo = $_GET['pn'];

//操作実行
$G_workmes = "";
$G_err_flg = false;

if($P_gimmick_type == "disp_change"){
	//表示切替
	$stmt = $pdo -> prepare('update main_text set `disp_flg` = 1 - `disp_flg` where id_main_text = :id');
	$stmt -> bindValue(':id', $P_topic_id, PDO::PARAM_INT);
	$stmt -> execute();
	$G_workmes = '<span class="col_blue">表示を切り替えました</span>';

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_hall_id, $P_topic_id);

} elseif($P_gimmick_type == "topic_del") {
	//トピック削除
	$stmt = $pdo -> prepare('update main_text set `del_flg` = 1 where id_main_text = :id');
	$stmt -> bindValue(':id', $P_topic_id, PDO::PARAM_INT);
	$stmt -> execute();

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_hall_id, $P_topic_id);

		//既存トピック表示順振り直し
	$stmt = $pdo -> prepare('
	SET @i := 0;
	UPDATE `main_text` SET order_text = (@i := @i + 1)
	WHERE `fk_id_hall` = :id AND `del_flg` <> 1
	ORDER BY `order_text` ASC
	');
	$stmt -> bindValue(':id', $P_hall_id, PDO::PARAM_INT);
	$stmt -> execute();
	$G_workmes = '<span class="col_blue">指定トピックを削除しました</span>';
} elseif($P_gimmick_type == "update"){
	//トピック編集
	$stmt = $pdo -> prepare('update main_text set `title_main` = :title, `text_main` = :text where id_main_text = :id');
	$stmt -> bindValue(':title', $P_title, PDO::PARAM_STR);
	$stmt -> bindValue(':text', $P_text, PDO::PARAM_STR);
	$stmt -> bindValue(':id', $P_topic_id, PDO::PARAM_INT);
	$stmt -> execute();
	$G_workmes = '<span class="col_blue">指定トピックを変更しました</span>';

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_hall_id, $P_topic_id, $P_title, $P_text);

} elseif($P_gimmick_type == "topic_add"){
	//トピック追加
		//既存トピック表示順振り直し
	$stmt = $pdo -> prepare('
	SET @i := 1;
	UPDATE `main_text` SET order_text = (@i := @i + 1)
	WHERE `fk_id_hall` = :id AND `del_flg` <> 1
	ORDER BY `order_text` ASC
	');
	$stmt -> bindValue(':id', $P_hall_id, PDO::PARAM_INT);
	$stmt -> execute();
	//新規トピック表示順位1位で追加
	$stmt = $pdo -> prepare('
	insert into `main_text`
	(`fk_id_hall`, `title_main`, `text_main`, `order_text`, `disp_flg`)
	values(:id, :title, :text, 1, 0)');
	$stmt -> bindValue(':id', $P_hall_id, PDO::PARAM_INT);
	$stmt -> bindValue(':title', $P_title, PDO::PARAM_STR);
	$stmt -> bindValue(':text', $P_text, PDO::PARAM_STR);
	$stmt -> execute();
	$G_workmes = '<span class="col_blue">新規トピックを追加しました</span>';

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_hall_id, 0, $P_title, $P_text);

} elseif($P_gimmick_type == "rank_change"){
	//表示位置下げ
	//既存トピック表示順振り直し
	$stmt = $pdo -> prepare('
	SET @i := 0;
	UPDATE `main_text` SET `order_text` = (@i := @i + 1)
	WHERE `fk_id_hall` = :id AND `del_flg` <> 1
	ORDER BY `order_text` ASC
	');
	$stmt -> bindValue(':id', $P_hall_id, PDO::PARAM_INT);
	$stmt -> execute();
	//表示順入れ替え
	//次トピック繰り上げ
	$stmt = $pdo -> prepare('
	UPDATE `main_text` SET `order_text` = `order_text` - 1
	WHERE `fk_id_hall` = :id_hall AND `del_flg` <> 1 AND `order_text` >
	(SELECT tmp.`order_text` FROM
		(SELECT `order_text` FROM `main_text` WHERE `id_main_text` = :id_text) AS tmp
	)
	ORDER BY `order_text` ASC
	limit 1
	');
	$stmt -> bindValue(':id_hall', $P_hall_id, PDO::PARAM_INT);
	$stmt -> bindValue(':id_text', $P_topic_id, PDO::PARAM_INT);
	$stmt -> execute();
	//対象トピック繰り下げ
	$stmt = $pdo -> prepare('
	UPDATE `main_text` SET `order_text` = `order_text` + 1
	WHERE `id_main_text` = :id_text
	');
	$stmt -> bindValue(':id_text', $P_topic_id, PDO::PARAM_INT);
	$stmt -> execute();

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_hall_id, $P_topic_id);

}elseif($P_gimmick_type == "pic_add"){
	//画像追加
	if(isset($_FILES['newpic' . $P_topic_id]) && is_uploaded_file($_FILES['newpic' . $P_topic_id]['tmp_name'])){
		//画像格納フォルダ設置
		$folder_ym = date("Ym");
		$mk_dirName = $G_path . $folder_ym;

		$flg_dir = false;
		if(file_exists($mk_dirName)){
			$flg_dir = true;
		}else{
			$flg_dir = mkdir($mk_dirName, 0755);
		}

		if($flg_dir){
			//格納ディレクトリ存在
			$flg_upMainPic = false;
			//メイン画像格納
			$name_ymdhis = date("YmdHis") . mt_rand();
			$name_main = 'm_' . $name_ymdhis;
			list($flg_up, $mess_up) = F_picUp('newpic' . $P_topic_id, $mk_dirName, $name_main, $GV_topic_x, $GV_topic_y);
			if($flg_up){
				$flg_upMainPic = true;
				$filename_mainComp = $mess_up;
				$G_workmes .= '<span class="col_blue">メイン画像を登録しました</span><br>';
			}else{
				$G_workmes .= '<span class="col_red">(メイン画像)' . $mess_up . '</span>';
			}

			if($flg_upMainPic){
				//メイン画像格納成功
				$flg_upThumPic = false;
				//サムネール格納
				$name_thum = 's_' . $name_ymdhis;
				list($flg_up, $mess_up) = F_picUp('newpic' . $P_topic_id, $mk_dirName, $name_thum, $GV_topic_x_s, $GV_topic_y_s);
				if($flg_up){
					$flg_upThumPic = true;
					$filename_thumComp = $mess_up;
					$G_workmes .= '<span class="col_blue">サムネールを登録しました</span><br>';
				}else{
					$G_workmes .= '<span class="col_red">(サムネール)' . $mess_up . '</span>';
				}

				if($flg_upThumPic){
					//サムネール格納成功
					//画像リンクDB登録
					$stmt = $pdo -> prepare('
INSERT INTO `main_pic`(`fk_id_main_text`, `folder_yyyymm`, `name_pic_main`, `name_pic_thum`)
VALUES(:id, :folder, :pic_main, :pic_thum)
					');
					$stmt -> bindValue(':id', $P_topic_id, PDO::PARAM_INT);
					$stmt -> bindValue(':folder', $folder_ym, PDO::PARAM_STR);
					$stmt -> bindValue(':pic_main', $filename_mainComp, PDO::PARAM_STR);
					$stmt -> bindValue(':pic_thum', $filename_thumComp, PDO::PARAM_STR);
					$stmt -> execute();

					$G_workmes .= '<span class="col_blue">リンクをDBに記録しました</span>';

						F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_hall_id, $P_topic_id, $folder_ym, $filename_mainComp, $filename_thumComp);

				}
			}
		}else{
			$G_workmes = '<span class="col_red">格納ディレクトリ設置エラー</span>';
		}

	}else{
		$G_workmes = '<span class="col_red">画像追加エラー</span>';
	}
}elseif($P_gimmick_type == "pic_delete"){
	//画像削除
	$stmt = $pdo -> prepare('
UPDATE `main_pic`
SET `del_flg` = 1
WHERE `id_pic` = :id
	');
	$stmt -> bindValue(':id', $P_pic_id, PDO::PARAM_INT);
	$stmt -> execute();
	$G_workmes = '<span class="col_blue">指定画像を削除しました</span>';

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_hall_id, $P_pic_id);

}

//指定館セット
if($P_hall_id == 0){
	$G_hall_name = $G_npo_items['name_npo'];
}else{
	$stmt = $pdo -> prepare('SELECT `name_hall` FROM `halls` WHERE `id_hall` = :hall_id');
	$stmt -> bindValue(':hall_id', $P_hall_id, PDO::PARAM_INT);
	$stmt -> execute();
	$hall_items = $stmt -> fetchAll()[0]; //ヘッダ表示館名
	$G_hall_name = $hall_items['name_hall'];  //タイトル表示館名
}

//メインコンテンツ取得
$stmt = $pdo -> prepare('SELECT * FROM main_text where del_flg = 0 AND fk_id_hall = :hall_id ORDER BY order_text ASC');
$stmt -> bindValue(':hall_id', $P_hall_id, PDO::PARAM_INT);
$stmt -> execute();
$G_main_text = $stmt -> fetchAll();

//画像取得
$stmt = $pdo -> query('
SELECT * FROM `main_pic`
WHERE `del_flg` = 0
ORDER BY `id_pic` ASC
');
$stmt -> execute();
$t_main_pics = $stmt -> fetchAll();
foreach($t_main_pics as $val){
	!isset($G_main_pics[$val['fk_id_main_text']]) && $G_main_pics[$val['fk_id_main_text']] = array();
	$G_main_pics[$val['fk_id_main_text']][] = $val;
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta NAME=”ROBOTS” CONTENT=”NOINDEX,NOFOLLOW,NOARCHIVE”>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="description" content="<?php echo $G_npo_items['name_npo']; ?>">
<meta name="keywords" content="">
<link rel="stylesheet" href="/css/base.css?<?php echo date("Ymd-Hi"); ?>">
<link rel="stylesheet" media="screen and (max-width:800px)" href="/css/base_sp.css?<?php echo date("Ymd-Hi"); ?>">
<link rel="stylesheet" href="./css/gimmicks.css?<?php echo date("Ymd-Hi"); ?>">
<script src="./js/conf_topics.js"></script>
<script src="/js/jquery-3.4.1.min.js"></script>
<script type="text/javascript">$(document).ready(function(){$(window).scrollTop(<?php echo $P_window_position; ?>);});</script>
<title><?php echo $G_hall_name; ?> トピック管理</title>
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

<!-- 入力 -->
		<h3><?php echo $G_hall_name; ?>　トピック管理</h3>
		<p>
			<a href = "./configure_menu.php" target = "_self">・管理トップへ戻る</a><br>
			<a href = "" target = "_self">・トピック管理トップへ戻る</a><br>
		</p>
		<hr>
<?php
echo $G_workmes;
if($G_pmsnLevel > 0){
?>
		<form name="fm_add" action="" method="post">
			<h3>
				<input type="text" name="topic_title" size="70" value="" placeholder="新規トピックタイトル">
			</h3>
			<p>
<textarea rows="15" cols="85" name="topic_text" placeholder="新規トピック本文"></textarea><br>
			<input type="button" value="新規トピック追加" onclick="f_add(this.form);"><br>
			※追加後に表示切替するとページに反映されます
			</p>
			<hr>
			<input type="hidden" name="gimmick_type" value="">
		</form>
<?php
}
//トピック
echo '<form name="fm_table" id="fm_table" action="" method="post" enctype="multipart/form-data">';
$t_count = 0;
$infoCount = $t_max = count($G_main_text);
if($infoCount){

	if($disp_pageNo < 1){
		$disp_pageNo = 1;
	}
	$max_page = ceil($infoCount / GV_numberOfEvents_innerInfo);
	if($disp_pageNo > $max_page){
		$disp_pageNo = $max_page;
	}

	$page = F_makePaging($infoCount, GV_numberOfEvents_innerInfo, $disp_pageNo, "", "topicsTop", "g={$P_hall_id}&pn");
	$i_start = ($disp_pageNo -1) * GV_numberOfEvents_innerInfo;
	$i_last = $i_start + GV_numberOfEvents_innerInfo -1;
	echo '<div style="text-align:center" id="topicsTop">' . $page . '</div>';

	$i_countNow = -1;

	foreach($G_main_text as $val){
		++$i_countNow;
		if($i_countNow < $i_start){
			continue;
		}
		if($i_countNow > $i_last){
			break;
		}

		$t_topic_id = $val['id_main_text'];
		$t_disptype = $val['disp_flg']?'<span class="col_blue">トピック表示中</span>':'<span class="col_red">トピック非表示</span>';
	?>
			<h3>
				<?php echo ++$t_count + (($disp_pageNo - 1) * GV_numberOfEvents_innerInfo); ?>
				<input type="text" id="topic_title<?php echo $t_topic_id; ?>" size="70" value="<?php echo $val['title_main']; ?>" placeholder="トピックタイトル">
	<?php
	$button_dispChange = '';
	if($G_pmsnLevel > 0){
		$button_dispChange = '<input type="button" value="表示切替" onclick="f_disp(this.form, ' . $t_topic_id . ');">';
	}
	if($t_count + (($disp_pageNo - 1) * GV_numberOfEvents_innerInfo) < $t_max && $G_pmsnLevel > 0){
		echo '<input type="button" value="下へ" onclick="f_movedown(this.form, ' . $t_topic_id . ');">';
	} else {
		echo "　　";
	}

	if($G_pmsnLevel > 0){
	?>
				<input type="button" value="削除" onclick="f_del(this.form, <?php echo $t_topic_id; ?>, <?php echo $t_count; ?>);">
	<?php
	}
	?>
			</h3>
			<p>
				<?php echo $t_disptype . $button_dispChange; ?><br>
	<?php
	//画像表示
	if(isset($G_main_pics[$t_topic_id])){
		foreach($G_main_pics[$t_topic_id] as $val_pic){
			$pic_link = $G_path . $val_pic['folder_yyyymm'] . '/' . $val_pic['name_pic_thum'];
			$pic_no = $val_pic['id_pic'];
	?>
	<div class="thumbnail">
		<img src="<?php echo $pic_link; ?>"><br>
			<?php
			if($G_pmsnLevel > 0){
			?>
			<input type="button" value="No.<?php echo $pic_no; ?>画像削除" onClick="f_picDelete(this.form, <?php echo $pic_no; ?>);">
			<?php
			}
			?>
	</div>
	<?php
		}
		echo '<br>';
	}
	 ?>
<textarea rows="15" cols="85" id="topic_text<?php echo $t_topic_id; ?>" placeholder="トピック本文"><?php echo $val['text_main']; ?></textarea><br>
	<?php
	if($G_pmsnLevel > 0){
	?>
	<input type="button" value="　タイトル、本文更新　" onclick="f_edit(this.form, <?php echo $t_topic_id; ?>);"><br>
	<input type="file" name="newpic<?php echo $t_topic_id; ?>" accept=".jpg, .jpeg, .png, .gif">
	<input type="button" value="画像追加" onclick="f_up(this.form, <?php echo $t_topic_id; ?>);">
	<?php
	}
	?>
			</p>
			<hr>
	<?php
	}
	echo '<div style="text-align:center">' . $page . '</div>';
}
?>
	<input type="hidden" name="gimmick_type" value="">
	<input type="hidden" name="hall_id" value=<?php echo $P_hall_id; ?>>
	<input type="hidden" name="topic_id" value=0>
	<input type="hidden" name="topic_title" value="">
	<input type="hidden" name="topic_text" value="">
	<input type="hidden" name="pic_id" value=0>
	<input type="hidden" name="p_window_position" value=0>
</form>

	</div>

<!-- footer -->
<?php
include "./../foot.php";
?>

</div>
</body>
</html>
