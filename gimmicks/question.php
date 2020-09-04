<?php
//基本設定関数
require_once __DIR__.'/../basic_function.php';
//DB接続
require_once __DIR__ . '/../dbip.php';

//ヘッダDB情報ロード
require_once __DIR__ . '/../head_dbread.php';

//post値格納変数初期化
$P_gimmick_type = ""; //操作種別
$P_name = "";			 //名前
$P_hall_no = 0;		//所属館
$P_note = "";	//回答

//post値取得
isset($_POST["gimmick_type"]) && $P_gimmick_type = $_POST["gimmick_type"];
isset($_POST["name"]) && $P_name = F_h($_POST["name"]);
isset($_POST["hall"]) && $P_hall_no = F_h($_POST["hall"]);
isset($_POST["note"]) && $P_note = F_h($_POST["note"]);

//操作実行
$G_workmes = "";
$G_err_flg = false;

if($P_gimmick_type == "ans"){
	//回答登録
	//入力値チェック
	//名前確認
	if(!$P_hall_no){
		$G_err_flg = true;
		$G_workmes .= '<span class="col_red">※所属館を選択してください</span><br>';
	}
	if(!$P_name){
		$G_err_flg = true;
		$G_workmes .= '<span class="col_red">※児童氏名を入力してください</span><br>';
	}
	if(!$P_note){
		$G_err_flg = true;
		$G_workmes .= '<span class="col_red">※回答を入力してください</span><br>';
	}

	if($G_err_flg){
		//入力値エラーあり
		$G_gimmick_type = "";
		$G_workmes .= '<span class="col_red">回答は登録はされていません</span>';
	}else{
		//回答登録実行
		$stmt = $pdo -> prepare('INSERT INTO `questionnaire`
		(`name`, `ip`, `hall`, `text`)
		VALUES(:name, IF(IS_IPV6(:ip), INET6_ATON(:ip), INET_ATON(:ip)), :hall, :text)');
		$stmt -> bindValue(':name', $P_name, PDO::PARAM_STR);
		$stmt -> bindValue(':hall', $P_hall_no, PDO::PARAM_INT);
		$stmt -> bindValue(':ip', $G_ip, PDO::PARAM_STR);
		$stmt -> bindValue(':text', $P_note, PDO::PARAM_STR);
		$stmt -> execute();
		$set_id = $pdo -> lastInsertId();

		$G_workmes = '<span class="col_blue">以下の内容で回答を登録しました<br>このまま画面を閉じてください</span>';
		$P_gimmick_type = "fin";
	}
}

//館情報取得
$stmt = $pdo -> prepare('SELECT `id_hall`, `name_hall` FROM `halls` WHERE `activity_flg` = 1 ORDER BY `id_hall` ASC');
$stmt -> execute();
$hall_names = $stmt -> fetchAll();
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
<title><?php echo $G_npo_items['name_npo']; ?>アンケート</title>
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
		<h3>アンケート</h3>
<?php
echo $G_workmes;

//所属館セレクト
$t_select_hall = '<select name="hall">';
	$t_selected = "";
	!$P_hall_no && $t_selected = " selected";
	$t_select_hall .= '<option value=0' . $t_selected . '>選択してください</option>';
foreach($hall_names as $val){
	$t_selected = "";
	if($val['id_hall'] == $P_hall_no){ $t_selected = " selected"; };
	$t_select_hall .= "<option value=\"{$val['id_hall']}\"{$t_selected}>{$val['name_hall']}</option>";
}
$t_select_hall .= "</select>";


?>
		<p>
			<form name="fm_data" action="" method="post" onsubmit="return false;">
				<table class = "config">
					<tr>
						<td>所属館</td>
						<td>
							<?php echo $t_select_hall; ?>
						</td>
					</tr>
					<tr>
						<td>児童氏名</td>
						<td>
							<input type="text" name="name" size="40" value="<?php echo $P_name; ?>" placeholder="児童氏名">
						</td>
					</tr>
					<tr>
						<td>回答</td>
						<td>
<textarea rows="20" cols="70" name="note" placeholder="ここに回答を記入してください"><?php echo $P_note; ?></textarea>
						</td>
					</tr>
				</table>
				<?php
				if($P_gimmick_type != "fin"){
				?>
				<input type="button" value="アンケート登録" onClick="submit();">
				<input type="hidden" name="gimmick_type" value="ans">
				<?php
				}
				?>
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
