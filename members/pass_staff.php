<?php
//職員パスワード登録

//基本設定関数
require_once __DIR__.'/../basic_function.php';
//DB接続
require_once __DIR__ . '/../dbip.php';

//post値格納変数初期化
$P_gimmick_type = "";	//操作種別
$P_pk = "";						//登録キー
$P_pass = "";					//入力パスワード
$P_pass2 = "";				//確認

//get値取得
isset($_GET["pk"]) && $P_pk = F_h($_GET["pk"]);

//post値取得
isset($_POST["gimmick_type"]) && $P_gimmick_type = $_POST["gimmick_type"];
isset($_POST["pk"]) && $P_pk = F_h($_POST["pk"]);
isset($_POST["pass"]) && $P_pass = F_h($_POST["pass"]);
isset($_POST["pass2"]) && $P_pass2 = F_h($_POST["pass2"]);

//操作実行
$G_workmes = "";
$G_err_flg = false;

//左メニューログイン表示抑制 true=ログイン部非表示
$G_left_off = "off";

//登録キー確認
$t_key = $P_pk;	//登録キー
$t_id = (int)substr($t_key, 0, 6);	//登録者id

$stmt = $pdo -> prepare('
	SELECT COUNT(*) FROM `staff`
	WHERE `id_staff` = :id AND `change_pass_key` = :key
');
$stmt -> bindvalue(':id', $t_id, PDO::PARAM_INT);
$stmt -> bindvalue(':key', $t_key, PDO::PARAM_STR);
$stmt -> execute();
$t_count = $stmt -> fetchColumn();

if($t_count != 1 && $P_gimmick_type != "sign_up"){
	//キーエラー且つ登録前
	$P_gimmick_type = "err_key";
	$G_workmes .= '<span class="col_red">パスワード設定キーが確認できませんでした。<br>もう一度お試しいただくか、<a href="/info_information/" target="_self">「お問合せ」</a>からお問合せください。</span>';

	F_workLog($t_id, 2, __FILE__, 0, $P_gimmick_type, $t_key);

}

//-------------------------------------
if($P_gimmick_type == "sign_up"){
	//登録実行
	$stmt = $pdo -> prepare('
		UPDATE `staff`
		SET `login_pass` = :pass, `change_pass` = 1, `change_pass_key` = :null
		WHERE `id_staff` = :id
	');
	$stmt -> bindvalue(':pass', password_hash($P_pass, PASSWORD_DEFAULT), PDO::PARAM_STR);
	$stmt -> bindvalue(':null', null, PDO::PARAM_NULL);
	$stmt -> bindvalue(':id', $t_id, PDO::PARAM_INT);
	$stmt -> execute();

	$G_workmes .= '<span class="col_blue">パスワードを登録しました。<br>こちらよりログインできます。</span>';
	$G_workmes .= '<form name="fm_login" action="/members/mypage.php" method="post" onsubmit="return false;">
			<input type="text" name="id_left" size="17" value="" placeholder="id(メールアドレス)"><br>
			<input type="password" name="pass_left" id="pass_left" size="17" value="" placeholder="パスワード"><br>
			<label for="passdisp_left"><input type="checkbox" id="passdisp_left"><span class="t80per">パスワード表示</span></label><br>
			<label for="passsave_left"><input type="checkbox" name="passsave_left" id="passsave_left"><span class="t80per">ログイン状態記憶</span></label><br>
			<input type="button" id="b_id" value="職員login" onClick="f_leftLogin(this.form, 2);">
			<input type="hidden" name="work_flg" value="">
			<input type="hidden" name="kind_left" value=0>
		</form>
';

	F_workLog($t_id, 2, __FILE__, 0, $P_gimmick_type);

}
$bak_gimmick = $P_gimmick_type;
//ヘッダDB情報ロード
require_once __DIR__ . '/../head_dbread.php';
$P_gimmick_type = $bak_gimmick;

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
<link rel="stylesheet" href="./css/members.css?<?php echo date("Ymd-Hi"); ?>">
<title><?php echo $G_npo_items['name_npo']; ?>職員パスワード登録</title>
<script src="./js/pass.js?<?php echo date("Ymd-Hi"); ?>"></script>
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
		<p>

<?php
	if($P_gimmick_type == "err_key"){
		//登録キーエラー
		echo $G_workmes;
	}elseif($P_gimmick_type == "sign_up"){
		//登録完了
		echo $G_workmes;
	}else{
		//入力
?>
<!-- 入力 -->
			<form name="fm_data" action="" method="post" onsubmit="return false;">
				<h3>職員パスワード登録</h3>
				<span class="col_red">※</span>登録する新規パスワード<br>
				<input type="password" name="pass" id="pass" size="50" value="<?php echo $P_pass; ?>" placeholder="新規パスワード"><br>
				<span class="col_red">※</span>新規パスワード確認</td><br>
				<input type="password" name="pass2" id="pass2" size="50" value="" placeholder="確認入力"><br>
				<label for="passdisp"><input type="checkbox" id="passdisp" />パスワード表示</label><br>
				<span class="t80per">使用できる文字はアルファベットと数字です[a - z][A - Z][0 - 9]<br>4～30文字で入力してください、大文字と小文字は区別されます</span><br>
				<input type="button" value="パスワード登録" onClick="f_signup(this.form);">
				<input type="hidden" name="gimmick_type" value="sign_up">
				<input type="hidden" name="pk" value="<?php echo $P_pk; ?>">
			</form>
<?php
	}
?>
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
