<?php
//アンケート表示
session_start();

//基本共通設定値
require_once __DIR__.'/../basic_setting.php';
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

$G_reses = array(); //回答情報格納

//回答取得
$stmt = $pdo -> prepare('SELECT * FROM questionnaire ORDER BY id_question DESC');
$stmt -> execute();
$G_reses = $stmt -> fetchAll();

//館情報取得
$stmt = $pdo -> prepare('SELECT `id_hall`, `name_hall` FROM `halls`');
$stmt -> execute();
$tmp = $stmt -> fetchAll();
$hall_names = array();
foreach($tmp as $val){
	$hall_names[$val['id_hall']] = $val['name_hall'];
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
<script src="./js/conf_information.js"></script>
<script src="/js/jquery-3.4.1.min.js"></script>
<script type="text/javascript">$(document).ready(function(){$(window).scrollTop(<?php echo $P_window_position; ?>);});</script>
<title><?php echo $G_npo_items['name_npo']; ?> アンケート回答表示</title>
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
		<h3>アンケート回答表示</h3>
<table class="info">
	<?php
	foreach($G_reses as $val){
	?>
	<tr>
	<td class="inquity_top">[<?php echo $hall_names[$val['hall']]; ?>]　<?php echo $val['name']; ?></td>
	</tr>
	<tr>
		<td>[<?php echo $val['regist_time']; ?>]<br>
<?php echo nl2br($val['text']); ?></td>
	</tr>
	<?php
	}
	?>

</table>


	</div>

<!-- footer -->
<?php
include "./../foot.php";
?>

</div>
</body>
</html>
