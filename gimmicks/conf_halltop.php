<?php
//npoページ管理
session_start();

//管理ページno
$G_gimmickno = 6;

//操作対象館idセット
$P_hall_id = -1;  //編集対象 -1=指定無し 1<=各館id

//基本設定関数
require_once __DIR__.'/../basic_function.php';
//基本設定値
require_once __DIR__.'/../basic_setting.php';

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

//都道府県
$G_ken = array('北海道','青森県','岩手県','宮城県','秋田県','山形県','福島県','茨城県','栃木県','群馬県','埼玉県','千葉県','東京都','神奈川県','新潟県','富山県','石川県','福井県','山梨県','長野県','岐阜県','静岡県','愛知県','三重県','滋賀県','京都府','大阪府','兵庫県','奈良県','和歌山県','鳥取県','島根県','岡山県','広島県','山口県','徳島県','香川県','愛媛県','高知県','福岡県','佐賀県','長崎県','熊本県','大分県','宮崎県','鹿児島県','沖縄県');

//get値取得
isset($_GET["g"]) && $P_hall_id = $_GET["g"];

//post値取得
$P_gimmick_type = ""; //操作種別
$P_info_input = "";   //館紹介文
$P_name = "";     //館名称
$P_zip = "";      //館郵便番号
$P_ken = "";      //館都道府県
$P_address = "";  //館住所
$P_tel = "";      //館電話番号
$P_fax = "";      //館FAX番号
$P_open = "";     //館設立日
$P_map = "";      //GoogleMapコード

//編集対象館未指定エラー
if($P_hall_id == -1){
	header('Location: /', true, 301);
}
//所属館制限チェック
if($G_hallLevel == 1 && $P_hall_id != $G_login['hall']){
	header('Location: /', true, 301);
}

isset($_POST["hall_id"]) && $P_hall_id = F_h($_POST["hall_id"]);
isset($_POST["gimmick_type"]) && $P_gimmick_type = $_POST["gimmick_type"];
isset($_POST["info_input"]) && $P_info_input = F_h($_POST["info_input"]);
isset($_POST["name"]) && $P_name = F_h($_POST["name"]);
isset($_POST["zip"]) && $P_zip = F_h($_POST["zip"]);
isset($_POST["ken"]) && $P_ken = F_h($_POST["ken"]);
isset($_POST["address"]) && $P_address = F_h($_POST["address"]);
isset($_POST["tel"]) && $P_tel = F_h($_POST["tel"]);
isset($_POST["fax"]) && $P_fax = F_h($_POST["fax"]);
isset($_POST["open"]) && $P_open = F_h($_POST["open"]);
isset($_POST["info_gmap"]) && $P_map = ($_POST["info_gmap"]);

//操作実行
$G_workmes = "";
$G_err_flg = false;

if($P_gimmick_type == "info_change"){
	//館紹介編集
	$stmt = $pdo -> prepare('update halls set `text_hall` = :info_input where `id_hall` = :id');
	$stmt -> bindValue(':info_input', $P_info_input, PDO::PARAM_STR);
	$stmt -> bindValue(':id', $P_hall_id, PDO::PARAM_INT);
	$stmt -> execute();
	$G_workmes = '<span class="col_blue">紹介文を更新しました</span>';

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_hall_id, $P_info_input);

} elseif($P_gimmick_type == "access_change") {
	//館連絡先編集
	//入力値チェック 設立日
	$t_datecheck = true;
	if($P_open != ""){
		$t_datecheck = F_isDate($P_open);
	}
	if($t_datecheck === false){
		$G_err_flg = true;
		$G_workmes .= '<span class="col_red">館設立日の入力値を確認してください</span>';
	} else {
		$stmt = $pdo -> prepare('update halls set
				`name_hall`    = :name
			, `zip_hall`     = :zip
			, `prefecture_hall` = :ken
			, `address_hall` = :address
			, `tel_hall`     = :tel
			, `fax_hall`         = :fax
			, `open_hall`        = :open
			, `gmap_code`   = :gmap
			 where `id_hall` = :id');
			$stmt -> bindValue(':name', $P_name, PDO::PARAM_STR);
			$stmt -> bindValue(':zip', $P_zip, PDO::PARAM_STR);
			$stmt -> bindValue(':ken', $P_ken, PDO::PARAM_STR);
			$stmt -> bindValue(':address', $P_address, PDO::PARAM_STR);
			$stmt -> bindValue(':tel', $P_tel, PDO::PARAM_STR);
			$stmt -> bindValue(':fax', $P_fax, PDO::PARAM_STR);
			if($P_open == ""){
				$stmt -> bindValue(':open', null, PDO::PARAM_NULL);
			} else{
				$stmt -> bindValue(':open', $P_open, PDO::PARAM_STR);
			}
			$stmt -> bindValue(':gmap', $P_map, PDO::PARAM_STR);
			$stmt -> bindValue(':id', $P_hall_id, PDO::PARAM_INT);
			$stmt -> execute();
			$G_workmes .= '<span class="col_blue">館基本情報を変更しました</span>';

				F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_hall_id, $P_name, $P_address, $P_tel, $P_open);

	}

	if($G_err_flg){
		$G_workmes .= '<br><span class="col_red">基本情報は変更されていません</span>';
	}
}elseif($P_gimmick_type == "link_pic_up"){
	//館画像格納フォルダ取得
	$stmt = $pdo -> prepare('
SELECT `folder_hall` FROM `halls` WHERE `id_hall` = :id
	');
	$stmt -> bindValue(':id', $P_hall_id, PDO::PARAM_INT);
	$stmt -> execute();
	$t_pic_folder = $stmt -> fetchAll()[0];
	//館リンク画像格納パス
	$G_path = "./../{$t_pic_folder['folder_hall']}/pic";

	//館リンク画像更新
	if(isset($_FILES['link_pic']) && is_uploaded_file($_FILES['link_pic']['tmp_name'])){
		$flg_upLinkPic = false;
		//館リンク画像格納
		$name_ymdhis = date("YmdHis") . mt_rand();
		$name_link = 'hall_link_' . $name_ymdhis;
		list($flg_up, $mess_up) = F_picUp('link_pic', $G_path, $name_link, $GV_hall_link_x, $GV_hall_link_y, 2, 1);
		if($flg_up){
			$flg_upLinkPic = true;
			$filename_hallLink = $mess_up;
			$G_workmes .= '<span class="col_blue">館リンク画像を登録しました</span><br>';
		}else{
			$G_workmes .= '<span class="col_red">(館リンク画像)' . $mess_up . '</span>';
		}

		if($flg_upLinkPic){
			//館リンク画像格納成功
			//画像リンクDB登録
			$stmt = $pdo -> prepare('
UPDATE `halls` SET `pic_link` = :link WHERE `id_hall` = :id
			');
			$stmt -> bindValue(':id', $P_hall_id, PDO::PARAM_INT);
			$stmt -> bindValue(':link', $filename_hallLink, PDO::PARAM_STR);
			$stmt -> execute();

			$G_workmes .= '<span class="col_blue">リンクをDBに記録しました</span>';
		}

		//各館リンク情報取得(館リンク画像更新)
		$stmt = $pdo -> query('SELECT id_hall, name_hall, folder_hall, pic_link FROM halls WHERE disp_flg = 1 AND activity_flg = 1 ORDER BY regist_time ASC');
		$hall_links = $stmt -> fetchAll();

			F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_hall_id, $filename_hallLink);

	}else{
		$G_workmes = '<span class="col_red">館リンク画像更新エラー</span>';
	}
}




//指定館変更後情報取得
$stmt = $pdo -> prepare('SELECT * FROM `halls` WHERE `id_hall` = :hall_id');
$stmt -> bindValue(':hall_id', $P_hall_id, PDO::PARAM_INT);
$stmt -> execute();
$hall_items = $stmt -> fetchAll()[0]; //ヘッダ表示館名
$G_hall_name = $hall_items['name_hall'];  //タイトル表示館名

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta NAME=”ROBOTS” CONTENT=”NOINDEX,NOFOLLOW,NOARCHIVE”>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="description" content="<?php echo $G_npo_items['name_npo']; ?><?php echo $G_hall_name; ?>">
<meta name="keywords" content="">
<link rel="stylesheet" href="/css/base.css?<?php echo date("Ymd-Hi"); ?>">
<link rel="stylesheet" media="screen and (max-width:800px)" href="/css/base_sp.css?<?php echo date("Ymd-Hi"); ?>">
<link rel="stylesheet" href="./css/gimmicks.css?<?php echo date("Ymd-Hi"); ?>">
<script src="./js/conf_halltop.js"></script>
<title><?php echo $G_npo_items['name_npo']; ?> <?php echo $G_hall_name; ?>ページ情報管理</title>
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
		<h3><?php echo $G_hall_name; ?>ページ情報管理</h3>
		<p>
			<a href = "./configure_menu.php" target = "_self">・管理トップへ戻る</a><br>
			<a href = "?g=<?php echo $P_hall_id; ?>" target = "_self">・<?php echo $G_hall_name; ?>ページ情報管理トップへ戻る</a><br>
		</p>
		<hr>
<?php
	//リンク画像
	$pic_hallLink = '<span class="col_red">リンク画像未登録</span>';
	if($hall_items['pic_link']){
		$pic_hallLink = "<img src=\"/{$hall_items['folder_hall']}/pic/{$hall_items['pic_link']}\">";
	}
?>
		<?php echo $G_workmes; ?>
		<h3><?php echo $G_hall_name; ?>リンク画像</h3>
		<form name="fm_link" action="" method="post" enctype="multipart/form-data">
		<?php echo $pic_hallLink; ?><br>
「表示サイズは<?php echo $GV_hall_link_x; ?>x<?php echo $GV_hall_link_y; ?>ピクセル」<br>
アップ画像は比率を保ち、このサイズに隙間なく表示されるよう左上を基準に拡大縮小されます。<br>
はみ出した部分はカットされます。<br>
		<input type="file" name="link_pic" accept=".jpg, .jpeg, .png, .gif">
		<input type="button" value="館リンク画像更新" onclick="f_linkPicUp(this.form);">
		<input type="hidden" name="hall_id" value="<?php echo $P_hall_id; ?>">
		<input type="hidden" name="gimmick_type" value="link_pic_up">
		</form>
		<hr>
<?php
//都道府県セレクト
$t_select_ken = '<select name="ken">';
	$t_selected = "";
	!$hall_items['prefecture_hall'] && $t_selected = " selected";
	$t_select_ken .= '<option value=""' . $t_selected . '>都道府県</option>';
foreach($G_ken as $val){
	$t_selected = "";
	if($val == $hall_items['prefecture_hall']){ $t_selected = " selected"; };
	$t_select_ken .= "<option value=\"{$val}\"{$t_selected}>{$val}</option>";
}
$t_select_ken .= "</select>";
?>
		<p>
			<form name="fm_data" action="" method="post" onsubmit="return false;">
				<h3><?php echo $G_hall_name; ?>基本情報編集</h3>
				<table class = "config">
					<tr>
						<td>館名</td>
						<td>
							<input type="text" name="name" size="50" value="<?php echo $hall_items['name_hall']; ?>" placeholder="館名">
						</td>
					</tr>
					<tr>
						<td>住所</td>
						<td>
							〒<input type="text" name="zip" size="7" value="<?php echo $hall_items['zip_hall']; ?>">(ハイフン無し数字のみ入力)<br>
							<?php echo $t_select_ken; ?><br>
							<input type="text" name="address" size="70" value="<?php echo $hall_items['address_hall']; ?>">
						</td>
					</tr>
					<tr>
						<td>電話番号</td>
						<td>
							<input type="text" name="tel" size="20" value="<?php echo $hall_items['tel_hall']; ?>">
						</td>
					</tr>
					<tr>
						<td>FAX番号</td>
						<td>
							<input type="text" name="fax" size="20" value="<?php echo $hall_items['fax_hall']; ?>">
						</td>
					</tr>
					<tr>
						<td>館設立日</td>
						<td>
							<input type="text" name="open" size="20" value="<?php echo $hall_items['open_hall']; ?>">(入力例、2019-7-1　2019/7/1　20190701)
						</td>
					</tr>
					<tr>
						<td>GoogleMapコード</td>
						<td>
<textarea rows="8" cols="70" name="info_gmap" placeholder="GoogleMapコード">
<?php echo $hall_items['gmap_code']; ?>
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
			<form name="fm_npo" action="" method="post" onsubmit="return false;">
				<h3><?php echo $hall_items['name_hall']; ?>のご紹介</h3>
				<textarea rows="20" cols="85" name="info_input" placeholder="館紹介文">
<?php echo $hall_items['text_hall']; ?>
</textarea><br>
<?php
if($G_pmsnLevel > 0){
?>
				<input type="button" value="変更" onClick="submit();">
<?php
}
?>
				<input type="hidden" name="gimmick_type" value="info_change">
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
