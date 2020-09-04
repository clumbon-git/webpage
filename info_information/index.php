<?php
//基本共通設定値
require_once __DIR__.'/../basic_setting.php';
//基本設定関数
require_once __DIR__.'/../basic_function.php';
//DB接続
require_once __DIR__.'/../dbip.php';
//ヘッダDB情報ロード
require_once __DIR__ . '/../head_dbread.php';

//post値取得
$G_gimmick_type = ""; //操作種別 info_disp 確認表示
$G_info_mail = "";		//連絡先メアド
$G_info_val = "";			//問合せ内容

isset($_POST["gimmick_type"]) && $G_gimmick_type = $_POST["gimmick_type"];
isset($_POST["info_mail"]) && $G_info_mail = F_h($_POST["info_mail"]);
isset($_POST["info_val"]) && $G_info_val = F_h($_POST["info_val"]);

$G_errms = "";	//エラーメッセージ

if($G_gimmick_type == "info_disp"){
	//問合せ確認

	//メアド確認
	if($G_info_mail != "" && !F_isEmail($G_info_mail)){
		$G_errms = "<span style=\"color: #ff3333\">※メールアドレスをご確認ください</span>";
		$G_gimmick_type = "";
	}
}else if($G_gimmick_type == "info_send"){
	//問合せ記録
	$stmt = $pdo -> prepare('
	insert into `information_ask`(`text_ask`, `mail_ask`, `ip_ask`)
	values(:ask_text, :ask_mail, IF(IS_IPV6(:ask_ip), INET6_ATON(:ask_ip), INET_ATON(:ask_ip)))
	');
	$stmt -> bindValue(':ask_text', $G_info_val, PDO::PARAM_STR);
	$stmt -> bindValue(':ask_mail', $G_info_mail, PDO::PARAM_STR);
	$stmt -> bindValue(':ask_ip', $G_ip, PDO::PARAM_STR);
	$stmt -> execute();
	//IF(LENGTH(ip_address) = 16 ,INET6_NTOA(ip_address), INET_NTOA(ip_address)) AS ip_address

	//権限者宛てメール送信
	//権限者メアド取得
	$t_toMails = array();	//送信先メアド格納 arr[メアド]=array('mail'=>メアド,'name'=>宛名)
	//役務対象取得
	$stmt = $pdo -> query('
	SELECT t_memhom.`login_id` as `mail`, t_memprt.`surname` as `name`
	FROM `access_link_section` as t_acsec
	LEFT JOIN `section_kind` as t_secknd
	ON t_acsec.`fk_id_section` = t_secknd.`id_section`
	LEFT JOIN `section_member` as t_secmem
	ON t_secknd.`id_section` = t_secmem.`fk_id_section`
	LEFT JOIN `members_home` as t_memhom
	ON t_secmem.`fk_id_member_home` = t_memhom.`id_member_home`
	LEFT join `members_parent` as t_memprt
	ON t_memhom.`id_member_home` = t_memprt.`fk_id_member_home`
	WHERE
	t_acsec.`fk_id_gimic_page` = 2
	AND t_acsec.`kind_process` > 0
	AND t_secknd.`del_flg` <> 1
	AND t_secmem.`del_flg` <> 1
	AND t_memhom.`activity_flg` = 1
	AND t_memprt.`activity_flg` = 1
	AND t_memprt.`delegate_flg` = 1
	');
	$t_mails = $stmt -> fetchAll();
	foreach($t_mails as $t_mail){
		$t_toMails[$t_mail['mail']] = $t_mail;
	}

	//職種対象取得
	$stmt = $pdo -> query('
	SELECT t_staff.`login_id` as `mail`, t_staff.`surname` as `name`
	FROM `access_link_posts` as t_acpst
	LEFT JOIN `staff_posts` as t_stfpst
	ON t_acpst.`fk_id_staff_post` = t_stfpst.`id_staff_post`
	LEFT JOIN `staff` as t_staff
	ON t_stfpst.`id_staff_post` = t_staff.`fk_id_staff_post`
	WHERE
	t_acpst.`fk_id_gimic_page` = 2
	AND t_acpst.`kind_process` > 0
	AND t_stfpst.`del_flg` <> 1
	AND t_staff.`activity_flg` = 1
	');
	$t_mails = $stmt -> fetchAll();
	$t_toMails[$GV_mail_admin] = array("mail" => $GV_mail_admin, "name" => "管理者");
	foreach($t_mails as $t_mail){
		$t_toMails[$t_mail['mail']] = $t_mail;
	}

	//送信ログ記録
	$title_mail = "[学童システム]お問合わせがありました";
	$text_mail = "ホームページよりお問い合わせがありました。
お知らせ管理ページよりご確認ください。
" . (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . "/gimmicks/conf_information.php";
	$stmt = $pdo -> prepare('
	INSERT INTO `mail_log`(`title_mail`, `text_mail`, `scheduled_count`)
	VALUES(:title, :text, :count)
	');
	$stmt -> bindvalue(':title', $title_mail, PDO::PARAM_STR);
	$stmt -> bindvalue(':text', $text_mail, PDO::PARAM_STR);
	$stmt -> bindvalue(':count', count($t_toMails), PDO::PARAM_INT);
	$stmt -> execute();
	$t_lastid = $pdo -> lastInsertId();

	//送信先記録
	$stmt = $pdo -> prepare('
	INSERT INTO `mail_items`(`fk_id_mail`, `label`, `mail`)
	VALUES(:id, :label, :mail)
	');
	foreach($t_toMails as $mails => $mail){
		$stmt -> bindvalue(':id', $t_lastid, PDO::PARAM_INT);
		$stmt -> bindvalue(':label', $mail["name"], PDO::PARAM_STR);
		$stmt -> bindvalue(':mail', $mail["mail"], PDO::PARAM_STR);
		$stmt -> execute();
	}

	//送信実行(別スレッド)
	$mailFunctionPath = realpath("../gimmicks/mail_function.php");
	$cmd = 'nohup php ' . $mailFunctionPath . ' > /dev/null &';
	exec($cmd);
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="description" content="<?php echo $G_npo_items['name_npo']; ?>">
<meta name="keywords" content="">
<link rel="stylesheet" href="/css/base.css?<?php echo date("Ymd-Hi"); ?>">
<link rel="stylesheet" href="./css/information.css?<?php echo date("Ymd-Hi"); ?>">
<link rel="stylesheet" media="screen and (max-width:800px)" href="/css/base_sp.css?<?php echo date("Ymd-Hi"); ?>">
<script src="./js/information.js"></script>
<title><?php echo $G_npo_items['name_npo']; ?>　お問合せ</title>
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
<h3>お問合せ</h3>
<p>
<?php
if($G_gimmick_type == "info_disp"){
	//問合せ確認
?>
<div class="mail_attention">
当学童クラブへのお問合せはこちらからどうぞ。<br>
返信をご希望の方はメールアドレスをご入力ください。<br>
返信ご希望でキャリアメール(docomo,au等)をご利用の方は<br>
「@<?php echo $_SERVER['HTTP_HOST']; ?>」ドメインの受信許可設定をお願いします。<br>
返信までしばらくお時間をいただく場合があります、ご了承ください。<br>
メールエラー等で返信できない場合は、トップページのお知らせにて回答する場合がございますので、ホームページもご確認ください。
</div>
	<hr>
<form name="fm_confirm" action="" method="post">
	<h4>お問合せ内容</h4>
<?php echo nl2br($G_info_val); ?><br><br>
<?php
if($G_info_mail){
	echo "<h4>ご連絡先メールアドレス</h4>";
	echo $G_info_mail . "<br><br>";
}
?>
<input type="button" value="入力画面に戻る" onclick="f_back();">
　<input type="button" value="上記内容で送信" onclick="f_send();">
	<input type="hidden" name="gimmick_type" value="">
	<input type="hidden" name="info_mail" value="<?php echo $G_info_mail; ?>">
	<input type="hidden" name="info_val" value="<?php echo $G_info_val; ?>">
</form>
<?php
}elseif($G_gimmick_type == "info_send"){
	//問合せ送信
?>
お問合せを送信しました。<br>
返信をご希望の場合は、返信までしばらくお時間をいただく場合があります、ご了承ください。<br>
メールエラー等で返信できない場合は、トップページのお知らせにて回答する場合がございますので、ホームページもご確認ください。
	<a href="/" target="_self">-トップページへ-</a>
<?PHP
}else{
	//問合せ入力
?>
<div class="mail_attention">
当学童クラブへのお問合せはこちらからどうぞ。<br>
返信をご希望の方はメールアドレスをご入力ください。<br>
返信ご希望でキャリアメール(docomo,au等)をご利用の方は<br>
「@<?php echo $_SERVER['HTTP_HOST']; ?>」ドメインの受信許可設定をお願いします。<br>
返信までしばらくお時間をいただく場合があります、ご了承ください。<br>
メールエラー等で返信できない場合は、トップページのお知らせにて回答する場合がございますので、ホームページもご確認ください。
</div>
<hr>
<form name="fm_confirm" action="" method="post">
	<h4>お問合せ内容</h4>
<textarea rows="15" cols="48" id="info_val" name="info_val" placeholder="お問合せ内容"><?php echo $G_info_val; ?></textarea><br>
<h4>ご連絡先メールアドレス</h4>
<input type="text" size="30" id="info_mail" name="info_mail" placeholder="◯◯◯@◯◯.◯◯" value = "<?php echo $G_info_mail; ?>"><br>
<?php echo $G_errms . '<br>'; ?>
<input type="button" value="　確認　" onclick="f_confirm();">
		<input type="hidden" name="gimmick_type" value="">
	</form>
<?php
}
?>
</p>
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
			<h4>住所</h4>
【<?php echo $G_npo_items['name_npo']; ?>】<br>
			〒<?php echo substr($G_npo_items['zip_npo'], 0, 3).'-'.substr($G_npo_items['zip_npo'], 3); ?><br>
			<?php echo $G_npo_items['prefecture_npo'].$G_npo_items['address_npo']; ?>
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
