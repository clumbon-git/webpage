<?php
//館追加削除管理
session_start();

//管理ページno
$G_gimmickno = 22;

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

//都道府県
$G_ken = array('北海道','青森県','岩手県','宮城県','秋田県','山形県','福島県','茨城県','栃木県','群馬県','埼玉県','千葉県','東京都','神奈川県','新潟県','富山県','石川県','福井県','山梨県','長野県','岐阜県','静岡県','愛知県','三重県','滋賀県','京都府','大阪府','兵庫県','奈良県','和歌山県','鳥取県','島根県','岡山県','広島県','山口県','徳島県','香川県','愛媛県','高知県','福岡県','佐賀県','長崎県','熊本県','大分県','宮崎県','鹿児島県','沖縄県');

//操作対象館id
$P_hall_id = 0;

//post値取得
$P_gimmick_type = ""; //操作種別
$P_name = "";     //館名称
$P_zip = "";      //館郵便番号
$P_ken = "埼玉県";      //館都道府県
$P_address = "";  //館住所
$P_tel = "";      //館電話番号
$P_fax = "";      //館FAX番号
$P_open = "";     //館設立日
$P_map = "";      //GoogleMapコード

isset($_POST["hall_id"]) && $P_hall_id = F_h($_POST["hall_id"]);
isset($_POST["gimmick_type"]) && $P_gimmick_type = $_POST["gimmick_type"];
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

if($P_gimmick_type == "addHall") {
	//新館登録

	//館名入力確認
	if($P_name == ""){
		$G_err_flg = true;
		$G_workmes .= '<span class="col_red">※館名を入力してください</span><br>';
	}

	//郵便番号確認
	if($P_zip == ""){
		$G_err_flg = true;
		$G_workmes .= '<span class="col_red">※郵便番号を入力してください</span><br>';
	}

	//都道府県確認
	if($P_ken == ""){
		$G_err_flg = true;
		$G_workmes .= '<span class="col_red">※都道府県を選択してください</span><br>';
	}

	//住所確認
	if($P_address == ""){
		$G_err_flg = true;
		$G_workmes .= '<span class="col_red">※住所を入力してください</span><br>';
	}

	//入力値チェック 設立日
	$t_datecheck = true;
	if($P_open != ""){
		$t_datecheck = F_isDate($P_open);
	}
	if($t_datecheck === false){
		$G_err_flg = true;
		$G_workmes .= '<span class="col_red">館設立日の入力値を確認してください<br></span>';
	}

	if(!$G_err_flg){
		//登録実行
		//DB登録
$stmt = $pdo -> prepare('
INSERT INTO `halls`(
	`name_hall`,
	`zip_hall`,
	`prefecture_hall`,
	`address_hall`,
	`tel_hall`,
	`fax_hall`,
	`open_hall`,
	`gmap_code`,
	`folder_hall`)
VALUES(
	:name,
	:zip,
	:ken,
	:address,
	:tel,
	:fax,
	:open,
	:gmap,
	"temp")
');
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
$stmt -> execute();
$t_lastid = $pdo -> lastInsertId();

		//館情報格納雛型フォルダコピー
		if($t_lastid){
			//DB登録成功
			//格納フォルダ値更新
			$newHallFolderName = "hall_" . $t_lastid;
$stmt = $pdo -> prepare('
UPDATE `halls`
SET `folder_hall` = :folder
WHERE `id_hall` = :id
');
$stmt -> bindValue(':folder', $newHallFolderName, PDO::PARAM_STR);
$stmt -> bindValue(':id', $t_lastid, PDO::PARAM_INT);
$stmt -> execute();

			//フォルダ作成
			$newHallFolderPath = "./../" . $newHallFolderName;
			if(F_dir_copy("./../hall_model", $newHallFolderPath)){
				$G_workmes .= '<span class="col_blue">新館フォルダを作成しました</span><br>';
				//ソース置換
				$flg = F_textReplacement($newHallFolderPath . "/index.php", "__hallid__", $t_lastid);
				if($flg){
					$G_workmes .= '<span class="col_blue">新館のファイルを作成しました</span><br>';
				}else{
					$G_err_flg = true;
					$G_workmes .= '<span class="col_red">新館のファイル作成に失敗しました</span><br>';
				}
			}else{
				$G_err_flg = true;
				$G_workmes .= '<span class="col_red">新館のフォルダ作成に失敗しました</span><br>';
			}
		}else{
			//DB登録失敗
			$G_err_flg = true;
			$G_workmes .= '<span class="col_red">新館のDB登録に失敗しました</span><br>';
		}

	}

	if($G_err_flg){
		$G_workmes .= '<span class="col_red">新館は登録されていません</span><br>';
	}else{
		$G_workmes .= '<span class="col_blue">新館を登録しました</span><br>';

			F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_name, $newHallFolderPath);

	}

}elseif($P_gimmick_type == "dispChange"){
	$stmt = $pdo -> prepare('
UPDATE `halls`
SET `disp_flg` = 1 - `disp_flg`
WHERE `id_hall` = :id
	');
	$stmt -> bindValue(':id', $P_hall_id, PDO::PARAM_INT);
	$stmt -> execute();
	$G_workmes .= '<span class="col_blue">指定館の公開状態を切替えました</span><br>';

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_hall_id);

}elseif($P_gimmick_type == "hallDelete"){
	$stmt = $pdo -> prepare('
UPDATE `halls`
SET `activity_flg` = 0
WHERE `id_hall` = :id
	');
	$stmt -> bindValue(':id', $P_hall_id, PDO::PARAM_INT);
	$stmt -> execute();
	$G_workmes .= '<span class="col_blue">指定館を削除しました</span><br>';

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_hall_id);

}

//各館リンク情報取得(館リンク画像更新)
$stmt = $pdo -> query('SELECT id_hall, name_hall, folder_hall, pic_link FROM halls WHERE disp_flg = 1 AND activity_flg = 1 ORDER BY regist_time ASC');
$hall_links = $stmt -> fetchAll();

//各館リンク情報取得(運営一覧用更新)
$stmt = $pdo -> query('SELECT * FROM halls WHERE activity_flg = 1 ORDER BY regist_time ASC');
$hall_links_gimmicks = $stmt -> fetchAll();

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
<script src="./js/conf_halladd.js"></script>
<title><?php echo $G_npo_items['name_npo']; ?> 館追加削除</title>
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
		<h3>館登録削除</h3>
		<p>
			<a href = "./configure_menu.php" target = "_self">・管理トップへ戻る</a><br>
			<a href = "" target = "_self">・館登録削除トップへ戻る</a><br>
		</p>
		<hr>
		<h3>運営館一覧</h3>
		<?php echo $G_workmes; ?>
		<form name="fm_list" action="" method="post" onsubmit="return false;">
		<table class="config">
			<tr><th>館id</th><th>館名</th><th>外部公開</th><th>閉館</th></tr>
		<?php
		foreach($hall_links_gimmicks as $valHall){
			$button_disp = '<input type="button" value="公開切替" onClick="f_dispChange(this.form, ' . $valHall['id_hall'] . ');">';
			$button_close = '<input type="button" value="館削除" onClick="f_closeHall(this.form, ' . $valHall['id_hall'] . ', \'' . $valHall['name_hall'] . '\');">';
			$tex_open = '<span class="col_red">非公開</span>';
			if($valHall['disp_flg']){
				$tex_open = '<span class="col_blue">公開中</span>';
			}
		?>
			<tr>
				<td>id<?php echo $valHall['id_hall']; ?></td>
				<td><a href="conf_halltop.php?g=<?php echo $valHall['id_hall']; ?>" target="_self">(編集)</a>　<?php echo $valHall['name_hall']; ?></td>
				<td><?php echo $tex_open; ?>　<?php echo $button_disp; ?></td>
				<td><?php echo $button_close; ?></td>
			</tr>
		<?php
		}
		?>
		</table>
		<input type="hidden" name="gimmick_type" value="">
		<input type="hidden" name="hall_id" value=0>
		</form>
		<hr>
<?php
//都道府県セレクト
$t_select_ken = '<select name="ken">';
	$t_select_ken .= '<option value="">都道府県</option>';
foreach($G_ken as $val){
	$t_selected = "";
	if($val == $P_ken){ $t_selected = " selected"; };
	$t_select_ken .= "<option value=\"{$val}\"{$t_selected}>{$val}</option>";
}
$t_select_ken .= "</select>";
?>
		<p>
			<form name="fm_data" action="" method="post" onsubmit="return false;">
				<h3>新館登録</h3>
				<span class="col_red">※は入力必須です</span>
				<table class = "config">
					<tr>
						<td>館名<span class="col_red">※</span></td>
						<td>
							<input type="text" name="name" size="50" value="<?php echo $P_name; ?>" placeholder="館名">
						</td>
					</tr>
					<tr>
						<td>住所<span class="col_red">※</span></td>
						<td>
							〒<input type="text" name="zip" size="7" value="<?php echo $P_zip; ?>">(ハイフン無し数字のみ入力)<br>
							<?php echo $t_select_ken; ?><br>
							<input type="text" name="address" size="70" value="<?php echo $P_address; ?>">
						</td>
					</tr>
					<tr>
						<td>電話番号<span class="col_red">※</span></td>
						<td>
							<input type="text" name="tel" size="20" value="<?php echo $P_tel; ?>">
						</td>
					</tr>
					<tr>
						<td>FAX番号</td>
						<td>
							<input type="text" name="fax" size="20" value="<?php echo $P_fax; ?>">
						</td>
					</tr>
					<tr>
						<td>館設立日</td>
						<td>
							<input type="text" name="open" size="20" value="<?php echo $P_open; ?>">(入力例、2019-7-1　2019/7/1　20190701)
						</td>
					</tr>
					<tr>
						<td>GoogleMapコード</td>
						<td>
<textarea rows="8" cols="70" name="info_gmap" placeholder="GoogleMapコード">
<?php echo $P_map; ?>
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
				<input type="button" value="登録実行" onClick="submit();">
				<?php
				}
				?>
				<input type="hidden" name="gimmick_type" value="addHall">
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
