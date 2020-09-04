<?php
session_start();
//基本設定関数
require_once __DIR__.'/../basic_function.php';
//基本設定値
require_once __DIR__.'/../basic_setting.php';
//DB接続
require_once __DIR__ . '/../dbip.php';
//ヘッダDB情報ロード
require_once __DIR__ . '/../head_dbread.php';

//館シリアル
define("HALL_ID", 2);

//格納
$main_text = array();	//メインコンテンツ
//title_main タイトル、text_main テキスト

$hall_items = array();   //館情報
//name_hall 館名、zip_hall 郵便番号、prefecture_hall 都道府県、address_hall 住所、tel_hall 電話番号、fax_hall fax番号、open_hall 設立日、text_hall 紹介文、pic_link リンク画像ファイル名、gmap_code GoogleMapコード
//トピック画像格納
//arr[トピックid][]=array(main_textテーブルカラム名=>値,…)
$G_main_pics = array();

//画像格納パス
$G_path = "/topic_pics/";

//トピック表示開始ページ
$disp_pageNo = 1;
isset($_GET['pn']) && $disp_pageNo = $_GET['pn'];

//トピックス取得
$stmt = $pdo -> prepare("SELECT * FROM main_text where fk_id_hall = ? AND disp_flg = 1 AND del_flg = 0 ORDER BY order_text ASC");
$stmt -> execute([HALL_ID]);
$main_text = $stmt -> fetchAll();

//館情報取得
$stmt = $pdo -> prepare("SELECT * FROM halls where id_hall = ? AND disp_flg = 1 AND activity_flg = 1");
$stmt -> execute([HALL_ID]);
$hall_items = $stmt -> fetchAll()[0];

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
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="description" content="<?php echo $G_npo_items['name_npo'] . $hall_items['name_hall']; ?>">
<meta name="keywords" content="">
<link rel="stylesheet" href="/css/base.css?<?php echo date("Ymd-Hi"); ?>">
<link rel="stylesheet" media="screen and (max-width:800px)" href="/css/base_sp.css?<?php echo date("Ymd-Hi"); ?>">
<link rel="stylesheet" href="/lightbox/css/lightbox.css">
<script src="/js/jquery-3.4.1.min.js"></script>
<title><?php echo $G_npo_items['name_npo'] . $hall_items['name_hall']; ?></title>
</head>
<body>
<div id="pagebody">

<?php
include "./../head.php";
?>

	<!-- main picture -->

	<!-- info left -->
<?php
include "./../left.php"
?>

	<!-- info main -->
	<div id="info">
<?php
//館紹介
echo "<h3>" . $hall_items['name_hall'] . "のご紹介</h3>";
echo "<p>";
echo nl2br($hall_items['text_hall']);
echo "</p>";
echo "<hr>";

//トピックス
$infoCount = count($main_text);
if($infoCount){
	if($disp_pageNo < 1){
		$disp_pageNo = 1;
	}
	$max_page = ceil($infoCount / GV_numberOfEvents_innerInfo);
	if($disp_pageNo > $max_page){
		$disp_pageNo = $max_page;
	}

	$page = F_makePaging($infoCount, GV_numberOfEvents_innerInfo, $disp_pageNo, "", "topicsTop", "pn");
	$i_start = ($disp_pageNo -1) * GV_numberOfEvents_innerInfo;
	$i_last = $i_start + GV_numberOfEvents_innerInfo -1;
	echo '<div style="text-align:center" id="topicsTop">' . $page . '</div>';

	$i_countNow = -1;
	foreach($main_text as $val){
		++$i_countNow;
		if($i_countNow < $i_start){
			continue;
		}
		if($i_countNow > $i_last){
			break;
		}

		$t_topic_id = $val['id_main_text'];
		echo "<h3>" . $val['title_main'] . "</h3>";
		//画像表示
		if(isset($G_main_pics[$t_topic_id])){
			foreach($G_main_pics[$t_topic_id] as $val_pic){
				$pic_link = $G_path . $val_pic['folder_yyyymm'] . '/' . $val_pic['name_pic_thum'];
				$pic_link_main = $G_path . $val_pic['folder_yyyymm'] . '/' . $val_pic['name_pic_main'];
				$pic_no = $val_pic['id_pic'];
		?><div class="thumbnail">
			<a href="<?php echo $pic_link_main; ?>" data-lightbox="group<?php echo $t_topic_id; ?>"><img src="<?php echo $pic_link; ?>"></a>
		</div><?php
			}
		}
		echo "<p>";
		echo nl2br($val['text_main']);
		echo "</p>";
		echo "<hr>";
	}
	echo '<div style="text-align:center">' . $page . '</div>';
}
?>

		<h3>アクセス</h3>
		<p>
			<div class="infoimg_subpage">
<?php
	if(isset($hall_items['gmap_code'])){
		echo $hall_items['gmap_code'];
	}
?>
			</div>
		</p>
		<div class="remarks">
			<h4>【住所】</h4>
			<?php echo $G_npo_items['name_npo'] . "　" . $hall_items['name_hall']; ?><br>
			〒<?php echo substr($hall_items['zip_hall'], 0, 3).'-'.substr($hall_items['zip_hall'], 3); ?><br>
			<?php echo $hall_items['prefecture_hall'].$hall_items['address_hall']; ?>
		</div>
		<hr>
	</div>

<?php
include "./../foot.php";
?>

</div>
<script src="/lightbox/js/lightbox.js"></script>
<script>lightbox.option({
'wrapAround': true,
'alwaysShowNavOnTouchDevices': true,
'fadeDuration': 100,
'imageFadeDuration': 100,
'resizeDuration': 100,
'showImageNumberLabel': true,
'positionFromTop': 30,
})</script>
</body>
</html>
