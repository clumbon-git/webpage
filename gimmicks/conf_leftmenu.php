<?php
//お知らせメニュー管理
session_start();

//管理ページno
$G_gimmickno = 1;

//基本設定関数
require_once __DIR__.'/../basic_function.php';
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

//ファイル格納パス
$G_path = "./../hangar";
$G_files = array();	//アップファイル格納

//post値取得
$G_menu_id = 0; //menu_id
$G_gimmick_type = ""; //操作種別 disp_change 表示切替
$G_title = "";  //変更タイトル取得
$G_link = "";   //変更リンク取得
$G_menu_id_hr = 0;  //表示順上位menu_id
$G_rank = 0;    //選択メニュー表示順位
$G_rank_hr = 0; //上位メニュー表示順位
$G_file_no = 0;	//ファイル番号

isset($_POST["menu_id"]) && $G_menu_id = $_POST["menu_id"];
isset($_POST["menu_id_hr"]) && $G_menu_id_hr = $_POST["menu_id_hr"];
isset($_POST["gimmick_type"]) && $G_gimmick_type = $_POST["gimmick_type"];
isset($_POST["menu_title"]) && $G_title = F_h($_POST["menu_title"]);
isset($_POST["menu_link"]) && $G_link = F_h($_POST["menu_link"]);
isset($_POST["menu_rank"]) && $G_rank = $_POST["menu_rank"];
isset($_POST["menu_rank_hr"]) && $G_rank_hr = $_POST["menu_rank_hr"];
isset($_POST["file_no"]) && $G_file_no = $_POST["file_no"];

//操作実行
$G_workmes = "";
if($G_gimmick_type == "disp_change"){
	//表示切替
	$stmt = $pdo -> prepare('
	UPDATE `left_menu` AS t1, `left_menu` AS t2
	SET t1.`disp_flg` = NOT(t2.`disp_flg` AND t2.`link_blank`),
	t1.`link_blank` = t2.`disp_flg` XOR t2.`link_blank`
	WHERE t1.id_left_menu = t2.id_left_menu
	AND t1.id_left_menu = :menu_id');
	$stmt -> bindValue(':menu_id', $G_menu_id, PDO::PARAM_INT);
	$stmt -> execute();

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $G_gimmick_type, $G_menu_id);

}elseif($G_gimmick_type == "title_change"){
	//タイトル変更
	$stmt = $pdo -> prepare('update left_menu set `title_menu` = :menu_title where id_left_menu = :menu_id');
	$stmt -> bindValue(':menu_title', $G_title, PDO::PARAM_STR);
	$stmt -> bindValue(':menu_id', $G_menu_id, PDO::PARAM_INT);
	$stmt -> execute();

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $G_gimmick_type, $G_menu_id, $G_title);

}elseif($G_gimmick_type == "link_change"){
	//リンク変更
	$stmt = $pdo -> prepare('update left_menu set `link_menu` = :menu_link where id_left_menu = :menu_id');
	$stmt -> bindValue(':menu_link', $G_link, PDO::PARAM_STR);
	$stmt -> bindValue(':menu_id', $G_menu_id, PDO::PARAM_INT);
	$stmt -> execute();

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $G_gimmick_type, $G_menu_id, $G_link);

}elseif($G_gimmick_type == "rank_change"){
	//表示順変更
	$stmt = $pdo -> prepare('update left_menu set `order_menu` = :menu_rank where id_left_menu = :menu_id');
	$stmt -> bindValue(':menu_rank', $G_rank_hr, PDO::PARAM_INT);
	$stmt -> bindValue(':menu_id', $G_menu_id, PDO::PARAM_INT);
	$stmt -> execute();
	$stmt -> bindValue(':menu_rank', $G_rank, PDO::PARAM_INT);
	$stmt -> bindValue(':menu_id', $G_menu_id_hr, PDO::PARAM_INT);
	$stmt -> execute();

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $G_gimmick_type, $G_menu_id);

}elseif($G_gimmick_type == "menu_del"){
	//削除
	$stmt = $pdo -> prepare('update left_menu set `del_flg` = 1 where id_left_menu = :menu_id');
	$stmt -> bindValue(':menu_id', $G_menu_id, PDO::PARAM_INT);
	$stmt -> execute();

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $G_gimmick_type, $G_menu_id);

}elseif($G_gimmick_type == "list_add"){
	//追加
	$stmt = $pdo -> prepare('
	insert into left_menu(`title_menu`, `link_menu`, `order_menu`)
	select
		:menu_title,
		:menu_link,
		case
			when max(`order_menu`) is null then 1
			else max(`order_menu`) + 1
		end
	from left_menu
	');
	$stmt -> bindValue(':menu_title', $G_title, PDO::PARAM_STR);
	$stmt -> bindValue(':menu_link', $G_link, PDO::PARAM_STR);
	$stmt -> execute();

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $G_gimmick_type, 0, $G_title, $G_link);

}elseif($G_gimmick_type == "file_up"){
	//ファイルアップ
	if(isset($_FILES['newfile']) && is_uploaded_file($_FILES['newfile']['tmp_name'])){
		//ファイル格納
		$name_main = date("YmdHis") . mt_rand();
		list($flg_up, $mess_up) = F_fileUp('newfile', $G_path, $name_main);

		if($flg_up){
			//ファイル情報DB記録
			$stmt = $pdo -> prepare('
INSERT INTO `left_file`(`name_file_link`, `name_file_disp`)
VALUES(:name_link, :name_disp)
			');
			$stmt -> bindValue(':name_link', $mess_up, PDO::PARAM_STR);
			$stmt -> bindValue(':name_disp', $_FILES['newfile']['name']);
			$stmt -> execute();

			$G_workmes = '<span class="col_blue">ファイルをアップしました</span>';
		}else{
			$G_workmes = '<span class="col_red">' . $mess_up . '<br>ファイルはアップされていません</span>';
		}
	}else{
		$G_workmes = '<span class="col_red">ファイルアップエラー<br>ファイルはアップされていません</span>';
	}
}elseif($G_gimmick_type == "file_del"){
	//ファイル削除
	$stmt = $pdo -> prepare('
UPDATE `left_file`
SET `del_flg` = 1
WHERE `id_file` = :file_id
	');
	$stmt -> bindValue(':file_id', $G_file_no, PDO::PARAM_INT);
	$stmt -> execute();

	$G_workmes = '<span class="col_blue">ファイルを削除しました</span>';
}

//アップファイル情報取得
$stmt = $pdo -> query('
SELECT *
FROM `left_file`
WHERE `del_flg` = 0 ORDER BY `regist_time` DESC
');
$G_files = $stmt -> fetchAll();
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
<script src="./js/conf_leftmenu.js"></script>
<title><?php echo $G_npo_items['name_npo']; ?> お知らせメニュー管理</title>
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
<h3>お知らせメニュー管理</h3>
<p>
	<a href = "./configure_menu.php" target = "_self">・管理トップへ戻る</a><br>
</p>
<hr>
<?php
echo $G_workmes;
?>
<p>
<span class="t80per">
※表示種<br>
<span class="col_blue">表示(同)</span>→現在のタブでリンクを開く<br>
<span class="col_blue">表示(新)</span>→新タブでリンクを開く<br>
<span class="col_red">非表示</span>→お知らせに表示しない
</span>
<form name="fm" action="" method="post">
	<table class = "config">
		<tr>
			<td></td><td>表示切替</td><td>タイトル</td><td>リンク</td>
			<?php
			if($G_pmsnLevel > 0){
				echo "<td>移動</td><td>削除</td>";
			}
			?>
		</tr>
<?php
$t_count = 0; //表示行数
$fr_id_hr = 0;    //上位メニューid
$fr_rank_hr = 0;  //上位メニュー表示順位
$tb_move = "";  //上へ移動ボタン
foreach($menu_items as $val){
	$fr_id = $val['id_left_menu'];  //メニューid
	$fr_disp = $val['disp_flg'];    //表示フラグ
	$fr_rank = $val['order_menu'];  //表示順位
	//表示状態
	$fr_disp_val = "";
	if($val['disp_flg']){
		if($val['link_blank']){
			$fr_disp_val = "<span class=\"col_blue\">表示(新)</span>";
		}else{
			$fr_disp_val = "<span class=\"col_blue\">表示(同)</span>";
		}
	}else{
		$fr_disp_val = "<span class=\"col_red\">非表示</span>";
	}

	//上へボタン
	if($t_count++ > 0){
		$tb_move = "<input type=\"button\" value=\"上へ\" onclick=\"f_moveup({$fr_id}, {$fr_rank}, {$fr_id_hr}, {$fr_rank_hr});\">";
	}
	echo "
	<tr>
		<td>{$t_count}</td>
		<td>{$fr_disp_val}";
	if($G_pmsnLevel > 0){
		echo "<input type=\"button\" value=\"切替\" onclick=\"f_dispchange({$fr_id});\">";
	}
	echo "
		</td>
		<td>";
	if($G_pmsnLevel > 0){
		echo "<input type=\"text\" id=\"title_{$fr_id}\" size=\"17\" value=\"{$val["title_menu"]}\" placeholder=\"全角18文字以内\" required>
		<input type=\"button\" value=\"変更\" onclick=\"f_titlechange({$fr_id}, 'title_{$fr_id}');\">";
	}else{
		echo $val["title_menu"];
	}
	echo "
		</td>
		<td>";
	if($G_pmsnLevel > 0){
		echo "<input type=\"text\" id=\"link_{$fr_id}\" size=\"38\" value=\"{$val["link_menu"]}\" placeholder=\"\" required>
		<input type=\"button\" value=\"変更\" onclick=\"f_linkchange({$fr_id}, 'link_{$fr_id}');\">";
	}else{
		echo $val["link_menu"];
	}
	echo "</td>";
	if($G_pmsnLevel > 0){
		echo "<td>{$tb_move}</td>
		<td><input type=\"button\" value=\"削除\" onclick=\"f_del({$fr_id}, {$t_count});\"></td>";
	}
	echo "</tr>";
	$fr_id_hr = $fr_id;
	$fr_rank_hr = $fr_rank;
}

?>
	</table>
	<input type="hidden" name="menu_id" value=0>
	<input type="hidden" name="menu_id_hr" value=0>
	<input type="hidden" name="gimmick_type" value="">
	<input type="hidden" name="menu_title" value="">
	<input type="hidden" name="menu_link" value="">
	<input type="hidden" name="menu_rank" value=0>
	<input type="hidden" name="menu_rank_hr" value=0>
</form>
</p>
<hr>
<?php
if($G_pmsnLevel > 0){
?>
<p>
<form name="fm_add" action="" method="post">
	<table class = "config">
		<tr>
			<td></td><td>タイトル</td><td>リンク</td><td>追加</td>
		</tr>
		<tr>
			<td>new</td>
			<td><input type="text" id="menu_title" name="menu_title" size="20" value="" placeholder="全角18文字以内" required></td>
			<td><input type="text" id="menu_link" name="menu_link" size="45" value="" placeholder="" required></td>
			<td><input type="button" value="追加" onclick="f_add();"></td>
		</tr>
	</table>
	<input type="hidden" name="gimmick_type" value="">
</form>
</p>
<hr>

<p>
お知らせ表示ファイルアップロード(PDFのみアップ可能)
<form name="fm_file" id="fm_file" action="" method="post" enctype="multipart/form-data">
	<input type="file" name="newfile" accept=".pdf, .PDF">
	<input type="button" value="ファイルアップロード" onclick="f_up(this.form);">
	<input type="hidden" name="gimmick_type" value="">
</form>
</p>

<?php
if($G_files){
?>
<p>
リンク名内容をリンク欄にコピペしてください
<form name="file_disp" action="" method="post">
	<table class="config">
		<tr>
			<td>No.</td><td>アップ日時</td><td>ファイル名</td><td>リンク名</td><td>削除</td>
		</tr>
			<?php
			foreach($G_files as $val){
				$t_id = $val['id_file'];
				$t_day = $val['regist_time'];
				$t_disp = $val['name_file_disp'];
				$t_link = '/hangar/' . $val['name_file_link'];
				$t_del_but = '<input type="button" value="削除" onclick="f_delfile(this.form, ' . $val['id_file'] . ');">';
			?>
			<tr>
				<td><?php echo $t_id; ?></td><td><?php echo $t_day; ?></td><td><?php echo $t_disp; ?></td><td><?php echo $t_link; ?></td><td><?php echo $t_del_but; ?></td>
			</tr>

			<?php
			}
			?>
		</tr>

	</table>
	<input type="hidden" name="gimmick_type" value="">
	<input type="hidden" name="file_no" value=0>
</form>
</p>
<?php
}
?>
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
