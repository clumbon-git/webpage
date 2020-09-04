<?php
/***********************************
メール送信システム

送信機能ページより別スレッド呼び出しで使用
DBに予め送信内容、対象を登録

mail_logテーブルにタイトル、本文、送信数をセット
mail_itemsテーブルにmail_logのid、宛名(ログ用)、メアドを送信数分セット

呼び出し方
$cmd = 'nohup php mail_function.php > /dev/null &';
exec($cmd);

注記
argvで送信ログidを取得したかったが、xreaサーバで取得できず(ローカルはok)
php.ini register_argc_argv = On指定でもだめ
→DB登録済で未送信mail_log情報を取得、送信に変更
************************************/
//ログテキスト
$t_log = "";
//ログファイルパス
$t_file = __DIR__ . '/log/mail.log';

require_once 'Mail.php';
require_once __DIR__ . '/../mailip.php';
//DB接続
require_once __DIR__ . '/../dbip.php';

mb_language("Japanese");
mb_internal_encoding("UTF-8");

//未送信メール取得
$stmt = $pdo -> prepare('
	SELECT `id_mail`
	FROM `mail_log`
	WHERE `finish_flg` = 0 AND `scheduled_count` > 0 AND `send_count` = 0
	ORDER BY `id_mail` DESC LIMIT 1
');
$stmt -> execute();
$a_mail_logs = $stmt -> fetchAll()[0];

if(!isset($a_mail_logs['id_mail'])){
	//未送信無し
	exit;
}

$mail_id = $a_mail_logs['id_mail'];	//メールログid

//送信中フラグセット
$stmt = $pdo -> prepare('
	UPDATE `mail_log`
	SET `finish_flg` = 2
	WHERE `id_mail` = :id
');
$stmt -> bindvalue(':id' , $mail_id, PDO::PARAM_INT);
$stmt -> execute();

//メール情報取得(タイトル、本文)
$stmt = $pdo -> prepare('
	SELECT `title_mail`, `text_mail`
	FROM `mail_log`
	WHERE `id_mail` = :id
');
$stmt -> bindvalue(':id' , $mail_id, PDO::PARAM_INT);
$stmt -> execute();
$a_mail_log = $stmt -> fetchAll()[0];
$mail_title = $a_mail_log['title_mail'];
$mail_body = $a_mail_log['text_mail'];

//メール情報取得(送信先)
$stmt = $pdo -> prepare('
	SELECT `id_mail_items`, `mail`
	FROM `mail_items`
	WHERE `fk_id_mail` = :id
	ORDER BY `id_mail_items` ASC
');
$stmt -> bindvalue(':id' , $mail_id, PDO::PARAM_INT);
$stmt -> execute();
$a_mail_items = $stmt -> fetchAll();

//メール送信、個別送信ログ更新(pdoテンプレ)
$stmt_items = $pdo -> prepare('
	UPDATE `mail_items`
	SET `send_flg` = :err_flg, `err_mes` = :err_msg
	WHERE `id_mail_items` = :id
');
//メール送信、送信数更新(pdoテンプレ)
$stmt_log = $pdo -> prepare('
	UPDATE `mail_log`
	SET `send_count` = :count
	WHERE `id_mail` = :mail_id
');

$t_send_count = 0;

$mail_params = array(
"host" => $MAIL_HOST,
"port" => $MAIL_PORT,
"auth" => true,
"username" => $MAIL_USERNAME,
"password" => $MAIL_PASS,
"persist" => true
);

$mail_body = mb_convert_encoding($mail_body, "ISO-2022-JP-MS", "UTF-8");
$mail_body = html_entity_decode($mail_body, ENT_QUOTES);

$mailObject = Mail::factory("smtp", $mail_params);
$log_tex = '';
$ok = 0;
$ng = 0;

foreach($a_mail_items as $val){
	$t_id = $val['id_mail_items'];
	$t_mail = $val['mail'];
	$t_err_flg = 1;
	$t_err_mes = "";

	//送信
	$headers = array(
	"MIME-Version" => "1.0",
	"Content-Type" => "text/plain; charset=ISO-2022-JP",
	"Content-Transfer-Encoding" => "7bit",
	"To" => $t_mail,
	"From" => $MAIL_FROM,
	"Subject" => mb_encode_mimeheader(mb_convert_encoding($mail_title, "ISO-2022-JP-MS", "UTF-8")),
	"Return-Path" => $MAIL_FROM
	);

//テスト運用メール送信停止
/*
	$status = $mailObject->send($t_mail, $headers, $mail_body);
	if(PEAR::isError($status)){
		++$ng;
		$t_err_flg = 2;
		$t_err_mes = $status -> getMessage();
	}else{
		++$ok;
	}
*/
//テスト運用中インクリメント
	++$ok;

	//個別ログ更新
	$stmt_items -> bindvalue(':id' , $t_id, PDO::PARAM_INT);
	$stmt_items -> bindvalue(':err_flg' , $t_err_flg, PDO::PARAM_INT);
	$stmt_items -> bindvalue(':err_msg' , $t_err_mes, PDO::PARAM_STR);
	$stmt_items -> execute();
	//送信数更新
	$stmt_log -> bindvalue(':count' , ++$t_send_count, PDO::PARAM_INT);
	$stmt_log -> bindvalue(':mail_id' , $mail_id, PDO::PARAM_INT);
	$stmt_log -> execute();
}

//送信完了フラグセット
$stmt = $pdo -> prepare('
	UPDATE `mail_log`
	SET `finish_flg` = 1
	WHERE `id_mail` = :id
');
$stmt -> bindvalue(':id' , $mail_id, PDO::PARAM_INT);
$stmt -> execute();

$t_log .= date("Y/m/d H:i:s") . " ";
$t_log .= "mail_log id_mail={$mail_id} ";
$t_log .= "ng={$ng} ok={$ok}\n";

file_put_contents($t_file, $t_log, FILE_APPEND | LOCK_EX);

?>
