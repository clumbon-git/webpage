<?php
//お問合せ管理
session_start();

//管理ページno
$G_gimmickno = 2;

//基本共通設定値
require_once __DIR__.'/../basic_setting.php';
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

//post値取得
$P_gimmick_type = "disp"; //操作種別
$P_id_ask = 0;            //お問合せid
$P_res_title = "";         //返信入力テキスト
$P_res_text = "";         //対応、返信入力テキスト

isset($_POST["gimmick_type"]) && $P_gimmick_type = $_POST["gimmick_type"];
isset($_POST["info_id"]) && $P_id_ask = $_POST["info_id"];
isset($_POST["res_title"]) && $P_res_title = F_h($_POST["res_title"]);
isset($_POST["res_text"]) && $P_res_text = F_h($_POST["res_text"]);

$P_window_position = 0;	//windowスクロール位置
isset($_POST["p_window_position"]) && $P_window_position = $_POST["p_window_position"];

//操作実行
$G_workmes = "";
$G_asks = array();  //お問合せ情報格納
$G_ask = array();   //指定お問合せ情報格納
$G_reses = array(); //対応情報格納

if($P_gimmick_type == "info_del"){
	//お問合せ削除----------------------------------------------
	//お問合せ情報削除
	$stmt = $pdo -> prepare('UPDATE information_ask SET del_flg = 1 WHERE id_information_ask = :ask_id');
	$stmt -> bindValue(':ask_id', $P_id_ask, PDO::PARAM_INT);
	$stmt -> execute();
	//対応情報削除
	$stmt = $pdo -> prepare('UPDATE information_res SET del_flg = 1 WHERE fk_id_information_ask = :ask_id');
	$stmt -> bindValue(':ask_id', $P_id_ask, PDO::PARAM_INT);
	$stmt -> execute();

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_id_ask);

	$G_workmes = '<span class="col_blue">削除しました</span>';
	$P_gimmick_type = "disp";
} elseif($P_gimmick_type == "info_res") {
	//対応追加入力-----------------------------------------------
	//指定お問合せ取得
	$stmt = $pdo -> prepare('SELECT * FROM information_ask WHERE id_information_ask = :ask_id');
	$stmt -> bindValue(':ask_id', $P_id_ask, PDO::PARAM_INT);
	$stmt -> execute();
	$G_ask = $stmt -> fetchAll()[0];

	//指定お問合せ対応取得
	$stmt = $pdo -> prepare('SELECT * FROM information_res WHERE fk_id_information_ask = :ask_id');
	$stmt -> bindValue(':ask_id', $P_id_ask, PDO::PARAM_INT);
	$stmt -> execute();
	$G_reses = $stmt -> fetchAll();

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_id_ask);

} elseif($P_gimmick_type == "info_res_add"){
	//対応登録実行------------------------------------------------
	$member_id = 0;
	$staff_id = 0;
	if($G_login['kind'] == 1){
		$member_id = $G_login['id'];
	}elseif($G_login['kind'] == 2){
		$staff_id = $G_login['id'];
	}
	$stmt = $pdo -> prepare('
		INSERT INTO information_res(fk_id_information_ask, text_res, fk_id_member_home, fk_id_staff)
		values(:ask_id, :res_text, :member_id, :staff_id)'
	);
	$stmt -> bindValue(':ask_id', $P_id_ask, PDO::PARAM_INT);
	$stmt -> bindValue(':res_text', $P_res_text, PDO::PARAM_STR);
	$stmt -> bindValue(':member_id', $member_id, PDO::PARAM_INT);
	$stmt -> bindValue(':staff_id', $staff_id, PDO::PARAM_INT);
	$stmt -> execute();

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_id_ask, $P_res_text);

	$G_workmes = '<span class="col_blue">対応を登録しました</span>';
	$P_gimmick_type = "disp";
} elseif($P_gimmick_type == "info_mail") {
	//メール返信入力-------------------------------------------------
	//指定お問合せ取得
	$stmt = $pdo -> prepare('SELECT * FROM information_ask WHERE id_information_ask = :ask_id');
	$stmt -> bindValue(':ask_id', $P_id_ask, PDO::PARAM_INT);
	$stmt -> execute();
	$G_ask = $stmt -> fetchAll()[0];

	//指定お問合せ対応取得
	$stmt = $pdo -> prepare('SELECT * FROM information_res WHERE fk_id_information_ask = :ask_id');
	$stmt -> bindValue(':ask_id', $P_id_ask, PDO::PARAM_INT);
	$stmt -> execute();
	$G_reses = $stmt -> fetchAll();

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_id_ask);

}elseif($P_gimmick_type == "info_mail_send"){
	//返信送信実行-------------------------------------------------
	//指定お問合せ取得
	$stmt = $pdo -> prepare('SELECT * FROM information_ask WHERE id_information_ask = :ask_id');
	$stmt -> bindValue(':ask_id', $P_id_ask, PDO::PARAM_INT);
	$stmt -> execute();
	$G_ask = $stmt -> fetchAll()[0];

	//対応登録
	$member_id = 0;
	$staff_id = 0;
	if($G_login['kind'] == 1){
		$member_id = $G_login['id'];
	}elseif($G_login['kind'] == 2){
		$staff_id = $G_login['id'];
	}
	$stmt = $pdo -> prepare('
		INSERT INTO information_res(fk_id_information_ask, text_res, fk_id_member_home, fk_id_staff)
		values(:ask_id, :res_text, :member_id, :staff_id)'
	);
	$stmt -> bindValue(':ask_id', $P_id_ask, PDO::PARAM_INT);
	$stmt -> bindValue(':res_text', "======== メール返信 ========\r\n" . $P_res_title . "\r\n\r\n" . $P_res_text, PDO::PARAM_STR);
	$stmt -> bindValue(':member_id', $member_id, PDO::PARAM_INT);
	$stmt -> bindValue(':staff_id', $staff_id, PDO::PARAM_INT);
	$stmt -> execute();

	//送信ログ記録
	$t_mail = array();
	$t_mail[] = array("mail" => $G_ask['mail_ask'], "name" => $G_ask['mail_ask']);
	$t_mail[] = array("mail" => $GV_mail_admin, "name" => "管理者");

	$stmt = $pdo -> prepare('
	INSERT INTO `mail_log`(`title_mail`, `text_mail`, `scheduled_count`)
	VALUES(:title, :text, :count)
	');
	$stmt -> bindvalue(':title', $P_res_title, PDO::PARAM_STR);
	$stmt -> bindvalue(':text', $P_res_text, PDO::PARAM_STR);
	$stmt -> bindvalue(':count', count($t_mail), PDO::PARAM_INT);
	$stmt -> execute();
	$t_lastid = $pdo -> lastInsertId();

	//送信先記録
	$stmt = $pdo -> prepare('
	INSERT INTO `mail_items`(`fk_id_mail`, `label`, `mail`)
	VALUES(:id, :label, :mail)
	');
	foreach($t_mail as $mails => $mail){
		$stmt -> bindvalue(':id', $t_lastid, PDO::PARAM_INT);
		$stmt -> bindvalue(':label', $mail["name"], PDO::PARAM_STR);
		$stmt -> bindvalue(':mail', $mail["mail"], PDO::PARAM_STR);
		$stmt -> execute();
	}

	//送信実行(別スレッド)
	$cmd = 'nohup php mail_function.php > /dev/null &';
	exec($cmd);

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_id_ask, $P_res_title, $P_res_text);

	$G_workmes = '<span class="col_blue">メールを送信しました</span>';
	$P_gimmick_type = "disp";
}

//===============================================================

if($P_gimmick_type == "disp"){
	//お問合せ一覧表示
	//お問合せ取得
	$stmt = $pdo -> query('
	SELECT id_information_ask, text_ask, mail_ask, regist_time
	FROM information_ask
	WHERE del_flg = 0 ORDER BY  regist_time DESC
	');
	$G_asks = $stmt -> fetchAll();
	//対応取得
	$stmt = $pdo -> query('
	SELECT T_infoRes.fk_id_information_ask, T_infoRes.text_res, T_infoRes.regist_time, T_infoRes.`fk_id_member_home`, T_infoRes.`fk_id_staff`,
	T_parent.`surname` as surname_member,
	T_staff.`surname` as surname_staff
	FROM information_res as T_infoRes
	LEFT JOIN (
		SELECT * FROM `members_parent`
		WHERE `delegate_flg` = 1
	) AS T_parent
	ON T_infoRes.`fk_id_member_home` = T_parent.`fk_id_member_home`
	LEFT JOIN `staff` as T_staff
	ON T_infoRes.`fk_id_staff` = T_staff.`id_staff`
	WHERE del_flg = 0
	ORDER BY  regist_time ASC
	');
	$t_reses = $stmt -> fetchAll();
	foreach($t_reses as $val){
		$G_reses[$val['fk_id_information_ask']][]=$val;
	}

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type);

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
<script src="./js/conf_information.js"></script>
<script src="/js/jquery-3.4.1.min.js"></script>
<script type="text/javascript">$(document).ready(function(){$(window).scrollTop(<?php echo $P_window_position; ?>);});</script>
<title><?php echo $G_npo_items['name_npo']; ?> お問合せ管理</title>
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
		<h3>お問合せ管理</h3>
		<p>
			<a href = "./configure_menu.php" target = "_self">・管理トップへ戻る</a><br>
			<a href = "" target = "_self">・お問合せ管理トップへ戻る</a><br>
		</p>
		<hr>
<?php
echo $G_workmes;

if($P_gimmick_type == "disp"){
	//一覧表示----------------------------------------------
	foreach($G_asks as $val_ask){
		$t_resButton = "";	//返信ボタン
		$t_resmailadd = "なし";
		if($val_ask['mail_ask']){
			$t_resButton = '<input type="button" value="　返信　" onclick="f_resMailInput(' . $val_ask['id_information_ask'] . ');">';
			$t_resmailadd = $val_ask['mail_ask'];
		}
		$t_resflg = "";
		if(!isset($G_reses[$val_ask['id_information_ask']])){
			$t_resflg = '<span class="col_red">未対応</span><br>';
		}
?>
		<p>
			<table class="info">
				<tr>
					<td class="inquity_top">
					<?php
					if($G_pmsnLevel > 0){
					?>
					<input type="button" value="削除" onclick="f_del(<?php echo $val_ask['id_information_ask']; ?>);">
					<?php
					}
					?>
					お問合せ日時[<?php echo $val_ask['regist_time']; ?>]
					</td>
				</tr>
				<tr>
					<td>
<?php
	echo $t_resflg;
?>
						[返信先]<?php echo $t_resmailadd; ?><br>
						<?php echo nl2br($val_ask['text_ask']); ?>
					</td>
				</tr>
<?php
		if(isset($G_reses[$val_ask['id_information_ask']])){
			$t_reses = $G_reses[$val_ask['id_information_ask']];
			foreach($G_reses[$val_ask['id_information_ask']] as $val_res){
				$t_resPersonId = 0;
				$t_resPersonName = "";
				$t_resPersonText = "";
				if($val_res['fk_id_member_home']){
					$t_resPersonId = $val_res['fk_id_member_home'];
					$t_resPersonName = $val_res['surname_member'];
					$t_resPersonText .= "(会員No{$t_resPersonId}:{$t_resPersonName})";
				}elseif($val_res['fk_id_staff']){
					$t_resPersonId = $val_res['fk_id_staff'];
					$t_resPersonName = $val_res['surname_staff'];
					$t_resPersonText .= "(職員No{$t_resPersonId}:{$t_resPersonName})";
				}
?>
				<tr><td>対応記録日時[<?php echo $val_res['regist_time']; ?>]<?php echo $t_resPersonText; ?></td></tr>
				<tr><td>[<span class="col_blue">対応内容</span>]<br><?php echo nl2br($val_res['text_res']); ?></td></tr>
<?php
			}
		}
?>
			</table>
			<?php
			if($G_pmsnLevel > 0){
			?>
			<input type="button" value="対応追加" onclick="f_res(<?php echo $val_ask['id_information_ask']; ?>);">
			<?php
			echo "　" . $t_resButton;
			}
			?>
		</p>
		<hr>
<?php
	}
}else if($P_gimmick_type == "info_res"){
	//対応入力-------------------------------------------------
	$t_resmailadd = "なし";
	!$G_ask['mail_ask'] || $t_resmailadd = $G_ask['mail_ask'];
?>
		<table class="info">
			<tr>
				<td>
<?php
if($G_pmsnLevel > 0){
?>
				<input type="button" value="削除" onclick="f_del(<?php echo $G_ask['id_information_ask']; ?>);">
<?php
}
?>
				お問合せ日時[<?php echo $G_ask['regist_time']; ?>]
				</td>
			</tr>
			<tr>
				<td>
					[返信先]<?php echo $t_resmailadd; ?><br>
					<?php echo nl2br($G_ask['text_ask']); ?>
				</td>
			</tr>
<?php
		if(isset($G_reses)){
			foreach($G_reses as $val_res){
?>
				<tr><td>対応記録日時[<?php echo $val_res['regist_time']; ?>]</td></tr>
				<tr><td>[<span class="col_blue">対応内容</span>]<br><?php echo nl2br($val_res['text_res']); ?></td></tr>
<?php
			}
		}
?>
		</table>
		<h3>対応記録</h3>
		<textarea rows="15" cols="48" id="res_input" name="res_input" placeholder="対応内容"></textarea><br>
		<input type="button" value="対応追加" onclick="f_res_add(<?php echo $G_ask['id_information_ask']; ?>);">
		<hr>
<?php
}else if($P_gimmick_type == "info_mail"){
	//返信入力-------------------------------------------------
	$t_resmailadd = "なし";
	!$G_ask['mail_ask'] || $t_resmailadd = $G_ask['mail_ask'];
?>
		<table class="info">
			<tr>
				<td>
<?php
if($G_pmsnLevel > 0){
?>
				<input type="button" value="削除" onclick="f_del(<?php echo $G_ask['id_information_ask']; ?>);">
<?php
}
?>
				お問合せ日時[<?php echo $G_ask['regist_time']; ?>]
				</td>
			</tr>
			<tr>
				<td>
					[返信先]<?php echo $t_resmailadd; ?><br>
					<?php echo nl2br($G_ask['text_ask']); ?>
				</td>
			</tr>
<?php
		if(isset($G_reses)){
			foreach($G_reses as $val_res){
?>
				<tr><td>対応記録日時[<?php echo $val_res['regist_time']; ?>]</td></tr>
				<tr><td>[<span class="col_blue">対応内容</span>]<br><?php echo nl2br($val_res['text_res']); ?></td></tr>
<?php
			}
		}
?>
		</table>
		<h3>メール返信</h3>
		タイトル:
		<input type="text" id="res_title" name="res_title" size="30" value="" placeholder="メールタイトル"><br>
		本文:<br>
		<textarea rows="15" cols="48" id="res_input" name="res_input" placeholder="メール返信内容"></textarea><br>
		<input type="button" value="メール返信" onclick="fresMailSend(<?php echo $G_ask['id_information_ask']; ?>);">
		<hr>
<?php
}
?>
	<form name="fm_info" id="fm_info" action="" method="post">
		<input type="hidden" name="gimmick_type" value="">
		<input type="hidden" name="info_id" value=0>
		<input type="hidden" name="res_title" value="">
		<input type="hidden" name="res_text" value="">
		<input type="hidden" name="p_window_position" value=0>
	</form>

	</div>

<!-- footer -->
<?php
include "./../foot.php";
?>

</div>
</body>
</html>
