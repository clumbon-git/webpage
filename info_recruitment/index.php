<?php
//基本設定関数
require_once __DIR__.'/../basic_function.php';
//DB接続
require_once __DIR__ . '/../dbip.php';
//ヘッダDB情報ロード
require_once __DIR__ . '/../head_dbread.php';

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="description" content="<?php echo $G_npo_items['name_npo']; ?>">
<meta name="keywords" content="">
<link rel="stylesheet" href="/css/base.css?<?php echo date("Ymd-Hi"); ?>">
<link rel="stylesheet" media="screen and (max-width:800px)" href="/css/base_sp.css?<?php echo date("Ymd-Hi"); ?>">
<title><?php echo $G_npo_items['name_npo']; ?>　指導員さん募集</title>
</head>
<body>
<div id="pagebody">

<!-- header -->
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
	<!-- トピック -->
		<div>
			<h3>指導員さん募集</h3>
			<p>
			<div class="remarks">
				子どもが好きな方、保育に興味のある方、資格は問いません！<br>
				学童での勤務が初めてという方ばかりです。<br>
				まずはお気軽にご応募・お問い合わせ下さい。<br>
				お待ちしております♪<br>
				<br>
				<br>
				＜補助指導員（パート勤務）募集要項＞<br>
				<br>
				■ 勤務時間<br>
				★週3～5日勤務（パート勤務）<br>
				〈通常〉13：30～18：30<br>
				〈小学校の夏休み冬休み等〉8：00～18：30の間で4～5時間<br>
				※子ども達の下校に合わせた勤務時間となっています。<br>
				※勤務日数 ・ 勤務時間はご相談下さい！<br>
				<br>
				■ 給与<br>
				時給　1,000円～<br>
				※試用期間３カ月は時給 930円です。試用期間終了後 1,000円に昇給となります。<br>
				<br>
				■福利厚生<br>
				通勤費支給（月額上限1万円）、年次有給休暇あり<br>
				予防接種費用補助あり<br>
				コミュニケーション向上費用補助あり<br>
			</div>
			<div class="content_pic">
				<img src="./pic/recruitment.png" alt="指導員さん募集！">
			</div>
			</p>
		</div>
		<hr>


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
【<?php echo $G_npo_items['name_npo']; ?>】<br>
			〒<?php echo substr($G_npo_items['zip_npo'], 0, 3).'-'.substr($G_npo_items['zip_npo'], 3); ?><br>
			<?php echo $G_npo_items['prefecture_npo'].$G_npo_items['address_npo']; ?>
			<h4>【電話番号】</h4>
			048-000-0000
		</div>
		<hr>
	</div>

<!-- footer -->
<?php
include "./../foot.php";
?>

</div>
</body>
</html>
