<?php
session_start();

//基本設定関数
require_once __DIR__.'/basic_function.php';
//基本設定値
require_once __DIR__.'/basic_setting.php';
//DB接続
require_once __DIR__.'/dbip.php';
//ヘッダDB情報ロード
require_once __DIR__ . '/head_dbread.php';

//格納
$top_pics = array();	//トップ画像名
$top_pic_name = '';		//表示トップ画像名
$main_text = array();	//メインコンテンツ
//title_main タイトル、text_main テキスト

//トピック画像格納
//arr[トピックid][]=array(main_textテーブルカラム名=>値,…)
$G_main_pics = array();

//画像格納パス
$G_path = "/topic_pics/";

//トップ画像名取得
$stmt = $pdo -> query('SELECT pic_top FROM npo_top_pics WHERE disp_flg = 1 AND del_flg = 0');
$results = $stmt -> fetchAll();
foreach($results as $val){
	$top_pics[] = $val['pic_top'];
}
if($i = count($top_pics)){
	$top_pic_name = $top_pics[rand(0, --$i)];
}

//トピック表示開始ページ
$disp_pageNo = 1;
isset($_GET['pn']) && $disp_pageNo = $_GET['pn'];

//トピックス取得
$stmt = $pdo -> query('SELECT * FROM main_text where disp_flg = 1 AND del_flg = 0 AND fk_id_hall = 0 ORDER BY order_text ASC');
$main_text = $stmt -> fetchAll();

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
<meta name="description" content="<?php echo $G_npo_items['name_npo'] . $G_npo_items['text_npo_topics_top']; ?>">
<meta name="keywords" content="">
<link rel="stylesheet" href="/css/base.css?<?php echo date("Ymd-Hi"); ?>">
<link rel="stylesheet" media="screen and (max-width:800px)" href="/css/base_sp.css?<?php echo date("Ymd-Hi"); ?>">
<link rel="stylesheet" href="/lightbox/css/lightbox.css">
<script src="/js/jquery-3.4.1.min.js"></script>
<title><?php echo $G_npo_items['name_npo']; ?></title>
</head>
<body>
<div id="pagebody">
<!-- header -->
<?php
include "./head.php";
?>

	<!-- main picture -->
	<div id="img_index">
<?php
if($top_pic_name){
	echo '<img src="./pic/' . $top_pic_name . '" alt="' . $G_npo_items['name_npo'] . 'トップ画像">';
}
?>
	</div>

	<!-- info left -->
<?php
include "./left.php"
?>

	<!-- info main -->
	<div id="info">
<?php
//npo topicks固定上
if($G_npo_items['title_npo_topics_top'] || $G_npo_items['text_npo_topics_top']){
	echo "<h3>" . $G_npo_items['title_npo_topics_top'] . "</h3>";
	echo "<p>";
	echo nl2br($G_npo_items['text_npo_topics_top']);
	echo "</p>";
	echo "<hr>";
}

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
			$t_dispCount = 0;
			$picDispStyle = "";
			foreach($G_main_pics[$t_topic_id] as $val_pic){
				(++$t_dispCount > $GV_topic_npo_sheets) && $picDispStyle = "display:none;";
				$pic_link = $G_path . $val_pic['folder_yyyymm'] . '/' . $val_pic['name_pic_thum'];
				$pic_link_main = $G_path . $val_pic['folder_yyyymm'] . '/' . $val_pic['name_pic_main'];
				$pic_no = $val_pic['id_pic'];
		?><div class="thumbnail">
			<a href="<?php echo $pic_link_main; ?>" data-lightbox="group<?php echo $t_topic_id; ?>"><img src="<?php echo $pic_link; ?>" style="<?php echo $picDispStyle; ?>"></a>
		</div><?php
			}
			echo "<br><span class=\"t80per\">(画像{$t_dispCount}枚 タップで拡大)</span>";
		}
		echo "<p>";
		echo nl2br($val['text_main']);
		echo "</p>";
		echo "<hr>";
	}
	echo '<div style="text-align:center">' . $page . '</div>';
}
//npo topicks固定下
if($G_npo_items['title_npo_topics_foot'] || $G_npo_items['text_npo_topics_foot']){
	echo "<h3>" . $G_npo_items['title_npo_topics_foot'] . "</h3>";
	echo "<p>";
	echo nl2br($G_npo_items['text_npo_topics_foot']);
	echo "</p>";
	echo "<hr>";
}
?>
<!-- map -->
		<h3>アクセス</h3>
		<p>
			<div class="infoimg_subpage">
<?php
	if(isset($G_npo_items['gmap_code'])){
		echo $G_npo_items['gmap_code'];
	}
?>
			</div>
		</p>
		<div class="remarks">
			<h4>【住所】</h4>
			<?php echo $G_npo_items['name_npo']; ?><br>
			〒<?php echo substr($G_npo_items['zip_npo'], 0, 3).'-'.substr($G_npo_items['zip_npo'], 3); ?><br>
			<?php echo $G_npo_items['prefecture_npo'].$G_npo_items['address_npo']; ?>
		</div>
		<hr>
	</div>

<!-- footer -->
<?php
include "./foot.php";
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
