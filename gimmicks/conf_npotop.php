<?php
//npoページ管理
session_start();

//管理ページno
$G_gimmickno = 4;

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

//都道府県
$G_ken = array('北海道','青森県','岩手県','宮城県','秋田県','山形県','福島県','茨城県','栃木県','群馬県','埼玉県','千葉県','東京都','神奈川県','新潟県','富山県','石川県','福井県','山梨県','長野県','岐阜県','静岡県','愛知県','三重県','滋賀県','京都府','大阪府','兵庫県','奈良県','和歌山県','鳥取県','島根県','岡山県','広島県','山口県','徳島県','香川県','愛媛県','高知県','福岡県','佐賀県','長崎県','熊本県','大分県','宮崎県','鹿児島県','沖縄県');

//post値取得
$P_gimmick_type = ""; //操作種別
$P_info_input = "";   //npo紹介文

$P_topicsTitle_top = "";	//npoトピックス上部固定タイトル
$P_topicsText_top = "";		//npoトピックス上部固定本文
$P_topicsTitle_foot = "";	//npoトピックス下部固定タイトル
$P_topicsText_foot = "";	//npoトピックス下部固定本文

$P_name_npo = "";     //npo名称
$P_zip_npo = "";      //npo郵便番号
$P_ken_npo = "";      //npo都道府県
$P_address_npo = "";  //npo住所
$P_tel_npo = "";      //npo電話番号
$P_fax_npo = "";      //npoFAX番号
$P_open_npo = "";     //npo設立日
$P_map_npo = "";      //GoogleMapコード

isset($_POST["gimmick_type"]) && $P_gimmick_type = $_POST["gimmick_type"];
isset($_POST["info_input"]) && $P_info_input = F_h($_POST["info_input"]);

isset($_POST["topicsTitle_top"]) && $P_topicsTitle_top = F_h($_POST["topicsTitle_top"]);
isset($_POST["topicsText_top"]) && $P_topicsText_top = F_h($_POST["topicsText_top"]);
isset($_POST["topicsTitle_foot"]) && $P_topicsTitle_foot = F_h($_POST["topicsTitle_foot"]);
isset($_POST["topicsText_foot"]) && $P_topicsText_foot = F_h($_POST["topicsText_foot"]);

isset($_POST["npo_name"]) && $P_name_npo = F_h($_POST["npo_name"]);
isset($_POST["npo_zip"]) && $P_zip_npo = F_h($_POST["npo_zip"]);
isset($_POST["npo_ken"]) && $P_ken_npo = F_h($_POST["npo_ken"]);
isset($_POST["npo_address"]) && $P_address_npo = F_h($_POST["npo_address"]);
isset($_POST["npo_tel"]) && $P_tel_npo = F_h($_POST["npo_tel"]);
isset($_POST["npo_fax"]) && $P_fax_npo = F_h($_POST["npo_fax"]);
isset($_POST["npo_open"]) && $P_open_npo = F_h($_POST["npo_open"]);
isset($_POST["info_gmap"]) && $P_map_npo = ($_POST["info_gmap"]);

//操作実行
$G_workmes = "";
$G_err_flg = false;

if($P_gimmick_type == "top_change"){
	//トピック上部固定表示文編集
	$stmt = $pdo -> prepare('
UPDATE `npo` SET
`title_npo_topics_top` = :title ,
`text_npo_topics_top` = :text
	');
	$stmt -> bindValue(':title', $P_topicsTitle_top, PDO::PARAM_STR);
	$stmt -> bindValue(':text', $P_topicsText_top, PDO::PARAM_STR);
	$stmt -> execute();
	$G_workmes = '<span class="col_blue">トピック上部固定表示文を更新しました</span>';

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_topicsTitle_top, $P_topicsText_top);

}elseif($P_gimmick_type == "foot_change"){
		//トピック下部固定表示文編集
		$stmt = $pdo -> prepare('
	UPDATE `npo` SET
	`title_npo_topics_foot` = :title ,
	`text_npo_topics_foot` = :text
		');
		$stmt -> bindValue(':title', $P_topicsTitle_foot, PDO::PARAM_STR);
		$stmt -> bindValue(':text', $P_topicsText_foot, PDO::PARAM_STR);
		$stmt -> execute();
		$G_workmes = '<span class="col_blue">トピック下部固定表示文を更新しました</span>';

			F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_topicsTitle_foot, $P_topicsText_foot);

} elseif($P_gimmick_type == "access_change") {
	//npo連絡先編集
	//入力値チェック 設立日
	$t_datecheck = true;
	if($P_open_npo != ""){
		$t_datecheck = F_isDate($P_open_npo);
	}
	if($t_datecheck === false){
		$G_err_flg = true;
		$G_workmes .= '<span class="col_red">NPO設立日の入力値を確認してください</span>';
	} else {
		$stmt = $pdo -> prepare('update npo set
				`name_npo`    = :name
			, `zip_npo`     = :zip
			, `prefecture_npo` = :ken
			, `address_npo` = :address
			, `tel_npo`     = :tel
			, `fax_npo`         = :fax
			, `open_npo`        = :open
			, `gmap_code`   = :gmap');
			$stmt -> bindValue(':name', $P_name_npo, PDO::PARAM_STR);
			$stmt -> bindValue(':zip', $P_zip_npo, PDO::PARAM_STR);
			$stmt -> bindValue(':ken', $P_ken_npo, PDO::PARAM_STR);
			$stmt -> bindValue(':address', $P_address_npo, PDO::PARAM_STR);
			$stmt -> bindValue(':tel', $P_tel_npo, PDO::PARAM_STR);
			$stmt -> bindValue(':fax', $P_fax_npo, PDO::PARAM_STR);
			if($P_open_npo == ""){
				$stmt -> bindValue(':open', null, PDO::PARAM_NULL);
			} else{
				$stmt -> bindValue(':open', $P_open_npo, PDO::PARAM_STR);
			}
			$stmt -> bindValue(':gmap', $P_map_npo, PDO::PARAM_STR);
			$stmt -> execute();
			$G_workmes .= '<span class="col_blue">学童基本情報を変更しました</span>';

				F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_name_npo, $P_address_npo, $P_tel_npo, $P_fax_npo, $P_map_npo);

	}

	if($G_err_flg){
		$G_workmes .= '<br><span class="col_red">学童基本情報は変更されていません</span>';
	}
}

//編集実行後にnpo情報再取得(初回取得はhead_dbread.php)
if($P_gimmick_type){
	//学童コンテンツ情報取得
	$stmt = $pdo -> query('SELECT * FROM npo');
	$G_npo_items = $stmt -> fetchAll()[0];
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
<title><?php echo $G_npo_items['name_npo']; ?> NPOページ情報管理</title>
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
		<h3>NPOページ情報管理</h3>
		<p>
			<a href = "./configure_menu.php" target = "_self">・管理トップへ戻る</a><br>
			<a href = "" target = "_self">・NPOページ情報管理トップへ戻る</a><br>
		</p>
		<hr>
<?php
echo $G_workmes;
//都道府県セレクト
$t_select_ken = '<select name="npo_ken">';
	$t_selected = "";
	!$G_npo_items['prefecture_npo'] && $t_selected = " selected";
	$t_select_ken .= '<option value=""' . $t_selected . '>都道府県</option>';
foreach($G_ken as $val){
	$t_selected = "";
	if($val == $G_npo_items['prefecture_npo']){ $t_selected = " selected"; };
	$t_select_ken .= "<option value=\"{$val}\"{$t_selected}>{$val}</option>";
}
$t_select_ken .= "</select>";
?>
		<p>
			<form name="fm_npodata" action="" method="post" onsubmit="return false;">
				<h3>学童基本情報編集</h3>
				<table class = "config">
					<tr>
						<td>NPO団体名</td>
						<td>
							<input type="text" name="npo_name" size="50" value="<?php echo $G_npo_items['name_npo']; ?>" placeholder="NPO団体名">
						</td>
					</tr>
					<tr>
						<td>住所</td>
						<td>
							〒<input type="text" name="npo_zip" size="7" value="<?php echo $G_npo_items['zip_npo']; ?>">(ハイフン無し数字のみ入力)<br>
							<?php echo $t_select_ken; ?><br>
							<input type="text" name="npo_address" size="70" value="<?php echo $G_npo_items['address_npo']; ?>">
						</td>
					</tr>
					<tr>
						<td>電話番号</td>
						<td>
							<input type="text" name="npo_tel" size="20" value="<?php echo $G_npo_items['tel_npo']; ?>">
						</td>
					</tr>
					<tr>
						<td>FAX番号</td>
						<td>
							<input type="text" name="npo_fax" size="20" value="<?php echo $G_npo_items['fax_npo']; ?>">
						</td>
					</tr>
					<tr>
						<td>NPO設立日</td>
						<td>
							<input type="text" name="npo_open" size="20" value="<?php echo $G_npo_items['open_npo']; ?>">(入力例、2019-7-1　2019/7/1　20190701)
						</td>
					</tr>
					<tr>
						<td>GoogleMapコード</td>
						<td>
<textarea rows="8" cols="70" name="info_gmap" placeholder="GoogleMapコード">
<?php echo $G_npo_items['gmap_code']; ?>
</textarea><br>
							所在地のGoogleMap地図をページに埋め込むコードです。<br>
							GoogleMapページの[メニュー]-[地図を共有または埋め込む]-[地図を埋め込む]<br>
							からHTMLをコピーします。(推奨カスタムサイズは300x200)
						</td>
					</tr>
				</table>
				<?php
				if($G_pmsnLevel > 0){
				?>
				<input type="button" value="変更" onClick="submit();">
				<?php
				}
				?>
				<input type="hidden" name="gimmick_type" value="access_change">
			</form>
		</p>
		<hr>
		<p>
			<form name="fm_top" action="" method="post" onsubmit="return false;">
				トピック上部固定テキスト
				<h3><input type="text" name="topicsTitle_top" size="80" value="<?php echo $G_npo_items['title_npo_topics_top']; ?>" placeholder="トピック上部固定表示タイトル"></h3>
				<textarea rows="10" cols="80" name="topicsText_top" placeholder="トピック上部固定表示文、NPO紹介文等">
<?php echo $G_npo_items['text_npo_topics_top']; ?>
</textarea><br>
<?php
if($G_pmsnLevel > 0){
?>
				<input type="button" value="変更" onClick="submit();">
<?php
}
?>
				<input type="hidden" name="gimmick_type" value="top_change">
			</form>
		</p>
		<hr>
		<p>
			<form name="fm_foot" action="" method="post" onsubmit="return false;">
				トピック下部固定テキスト
				<h3><input type="text" name="topicsTitle_foot" size="80" value="<?php echo $G_npo_items['title_npo_topics_foot']; ?>" placeholder="トピック下部固定タイトル"></h3>
				<textarea rows="10" cols="80" name="topicsText_foot" placeholder="トピック下部固定表示文、父母の声等">
<?php echo $G_npo_items['text_npo_topics_foot']; ?>
</textarea><br>
<?php
if($G_pmsnLevel > 0){
?>
				<input type="button" value="変更" onClick="submit();">
<?php
}
?>
				<input type="hidden" name="gimmick_type" value="foot_change">
			</form>
		</p>
		<hr>
	</div>

<!-- footer -->
<?php
include "./../foot.php";
?>

</div>
</body>
</html>
