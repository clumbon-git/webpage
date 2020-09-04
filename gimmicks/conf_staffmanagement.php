<?php
//職員情報管理
session_start();

//管理ページno
$G_gimmickno = 12;

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

//情報格納
$G_staff = array();	//在籍職員情報 arr[所属館id][]=職員情報
$G_posts = array();	//職種情報

//post値格納変数初期化
$P_gimmick_type = "disp_all";	//操作種別
//disp_all=職員一覧表示 details=指定職員詳細表示 mailsend=パスワード登録メール送信
$P_staffid = 0;			//職員id
$P_disp = "conf";	//リスト画面、機能画面切替 conf=機能、list=リスト

//post値取得
isset($_POST["gimmick_type"]) && $P_gimmick_type = $_POST["gimmick_type"];
isset($_POST["staffid"]) && $P_staffid = $_POST["staffid"];

isset($_POST["disp"]) && $P_disp = $_POST["disp"];

//他館制限チェック
if($G_pmsnLevel == 0){
	$P_disp = "list";
}

//操作実行
$G_workmes = "";
$G_err_flg = false;

//===============================================================

if($P_gimmick_type == "mailsend"){
	//パスワード登録メール送信
	//送信状態準備
	$t_key = sprintf('%06d', $P_staffid) . F_mkRandStr(10);

	$stmt = $pdo -> prepare('
	UPDATE `staff` SET `change_pass_key` = :key
	WHERE `id_staff` = :id
	');
	$stmt -> bindvalue(':key', $t_key, PDO::PARAM_STR);
	$stmt -> bindvalue(':id', $P_staffid, PDO::PARAM_INT);
	$stmt -> execute();
	//送信メアド取得
	$stmt = $pdo -> prepare('
	SELECT `login_id`, `change_pass_key`, `surname`
	FROM `staff`
	WHERE `id_staff` = :id AND `activity_flg` = 1
	');
	$stmt -> bindvalue(':id', $P_staffid, PDO::PARAM_INT);
	$stmt -> execute();
	$t_data = $stmt -> fetchAll()[0];
	$t_mail = array();
	$t_mail[] = $t_data['login_id'];
	$t_key = $t_data['change_pass_key'];
	$t_name = $t_data['surname'];

	//送信時間記録
	$stmt = $pdo -> prepare('
	UPDATE `staff` SET `change_pass_send` = :time
	WHERE `id_staff` = :id
	');
	$stmt -> bindvalue(':time', date('Y-m-d H:i:s'), PDO::PARAM_STR);
	$stmt -> bindvalue(':id', $P_staffid, PDO::PARAM_INT);
	$stmt -> execute();

	//送信内容作成
  $t_search = array('__name__', '__cpk__', '__club__', '__url__');
	$t_replace = array($t_name, $t_key, $G_npo_items['name_npo'], (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST']);
  $t_title = $G_npo_items['name_npo'] . '、システムよりお知らせ';
	$t_text = file_get_contents(__DIR__ . '/template/mail_pass_staff.tpl');
	$t_text = str_replace($t_search, $t_replace, $t_text);

	//送信ログ記録
	$stmt = $pdo -> prepare('
	INSERT INTO `mail_log`(`title_mail`, `text_mail`, `scheduled_count`)
	VALUES(:title, :text, :count)
	');
	$stmt -> bindvalue(':title', $t_title, PDO::PARAM_STR);
	$stmt -> bindvalue(':text', $t_text, PDO::PARAM_STR);
	$stmt -> bindvalue(':count', count($t_mail), PDO::PARAM_INT);
	$stmt -> execute();
	$t_lastid = $pdo -> lastInsertId();

	//送信先記録
	$stmt = $pdo -> prepare('
	INSERT INTO `mail_items`(`fk_id_mail`, `label`, `mail`)
	VALUES(:id, :label, :mail)
	');
	$stmt -> bindvalue(':id', $t_lastid, PDO::PARAM_INT);
	$stmt -> bindvalue(':label', $t_name, PDO::PARAM_STR);
	$stmt -> bindvalue(':mail', $t_mail[0], PDO::PARAM_STR);
	$stmt -> execute();

	//送信実行(別スレッド)
	$cmd = 'nohup php mail_function.php > /dev/null &';
	exec($cmd);

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_staffid, $t_title, $t_text);

	$G_workmes .= '<span class="col_blue">指定職員('. $t_name . 'さん)にパスワード登録促しメールを送信しました</span>';
	$P_gimmick_type = "disp_all";
}

//---------------------------------------------------

if($P_gimmick_type == "disp_all"){
	//職員一覧表示
	//全職員ベース情報取得
	$stmt = $pdo -> prepare('SELECT
t_s.`id_staff`, t_s.`surname`, t_s.`firstname`, t_s.`surname_kana`, t_s.`firstname_kana`, t_s.`change_pass`, t_s.`change_pass_send`, t_s.`enterhall_date`, t_s.`nickname`, t_s.`comment`,
t_p.`post`,
t_h.`id_hall`, t_h.`name_hall`
FROM `staff` AS t_s
LEFT JOIN `staff_posts` AS t_p
ON t_s.`fk_id_staff_post` = t_p.`id_staff_post`
LEFT JOIN `halls` AS t_h
ON t_s.`fk_id_hall` = t_h.`id_hall`
WHERE t_s.`activity_flg` = 1
ORDER BY t_s.`enterhall_date` ASC, t_s.`surname_kana`, t_s.`firstname_kana`
	');
	$stmt -> execute();
	$t_staffs = $stmt -> fetchAll();

	foreach($t_staffs as $val){
		!$val['id_hall'] && $val['id_hall'] = 0;
		!isset($G_staff[$val['id_hall']]) && $G_staff[$val['id_hall']] = array();
		$G_staff[$val['id_hall']][] = $val;
	}
}

//館情報取得
$stmt = $pdo -> prepare('SELECT `id_hall`, `name_hall` FROM `halls` WHERE `activity_flg` = 1 ORDER BY `id_hall` ASC');
$stmt -> execute();
$hall_names = $stmt -> fetchAll();
array_unshift($hall_names, array("id_hall" => 0, "name_hall" => "未定"));

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
<script src="./js/conf_staffmanagement.js?<?php echo date("Ymd-Hi"); ?>"></script>
<title><?php echo $G_npo_items['name_npo']; ?>職員一覧、編集</title>
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
		<h3>職員一覧、編集</h3>
		<p>
			<a href = "./configure_menu.php" target = "_self">・管理トップへ戻る</a><br>
			<a href = "./conf_staffregist.php" target = "_self">・新規職員登録へ移動</a><br>
			<a href = "./<?php echo basename(__FILE__); ?>" target = "_self">・職員一覧、編集トップへ戻る</a><br>
		</p>
		<hr>
<?php
echo $G_workmes;
?>
<?php
if($P_gimmick_type == "disp_all"){
?>
		<p>
			<form name="fm_disp" action="" method="post">
				<h3>職員一覧</h3>
				<?php
				//トグル表示
				$dis_list = $dis_conf = $t_bclass = "";
				if($P_disp == "list"){
					$dis_list = ' disabled="disabled"';
					$t_bclass = "dnone";
				}elseif($P_disp == "conf"){
					$dis_conf = ' disabled="disabled"';
				}
				if($G_pmsnLevel > 0){
				?>
				<input type="button" value="リスト表示" onClick="f_disp_toggle(this.form, 'list');"<?php echo $dis_list; ?>>
				<input type="button" value="機能表示" onClick="f_disp_toggle(this.form, 'conf');"<?php echo $dis_conf; ?>>
				<?php
				}
				?>
				(入所日、アイウエオ順表示)
				<?php
					foreach($hall_names as $v_hall){
						if($G_hallLevel == 1 && $v_hall['id_hall'] != $G_login['hall']){
							continue;
						}
						if(!isset($G_staff[$v_hall['id_hall']])){
							continue;
						}
						echo "<h2>【　" . $v_hall['name_hall'] . "　】</h2>";
				?>
				<table class="config">
					<tr><th>職員</th></tr>
					<?php
						foreach($G_staff[$v_hall['id_hall']] as $v_staff){
					?>
					<tr>
						<td><?php echo F_makedisp_staffs($v_staff); ?></td>
					</tr>
					<?php
						}
					?>
				</table>
				<?php
					}
				?>
				<input type="hidden" name="gimmick_type" value="details">
				<input type="hidden" name="staffid" value=0>
				<input type="hidden" name="disp" value="<?php echo $P_disp; ?>">
			</form>
		</p>
		<hr>
<?php
}
?>

	</div>

<!-- footer -->
<?php
include "./../foot.php";
?>

</div>
</body>
</html>

<?php

/*:::::::::::::::::::::::::::::::
一覧用職員情報表示作成

引数
$f_staff[]  職員情報arr

戻り値
職員情報テキスト
:::::::::::::::::::::::::::::::*/
function F_makedisp_staffs($f_staff){
	global $t_bclass;

	$t_idno = $f_staff['id_staff'];
	$t_nickname = "未定";
	$f_staff['nickname'] && $t_nickname = $f_staff['nickname'];
	//パスワードメール送信状況
	//会員ベース情報
	$t_base = $f_staff;
	//パスメール送信状況
	$t_send_tex = "<span class=\"col_blue\">登録メール送信済[{$t_base['change_pass_send']}]</span>";
	//パスメール送信ボタンvalue
	$t_send_title = "再送信";
	//パスワード登録状態
	$t_pass_change = '<span class="col_blue">パスワード登録済</span>';

	if(!$t_base['change_pass_send']){
		//登録メール未送信
		$t_send_tex = '<span class="col_red">登録メール未送信</span>';
		$t_send_title = "送信";
	}
	if(!$t_base['change_pass']){
		//パスワード未登録
		$t_pass_change = '<span class="col_red">パスワード未登録</span>';
	}

	//登録メール送信ボタン
	$b_send = '<span class="' . $t_bclass . '"><input type="button" value="' . $t_send_title . '" onClick="f_mailsend(this.form, ' . $t_idno . ');"></span>';


	$ret_tex = "";
	$ret_tex .= "{$t_pass_change} {$t_send_tex} {$b_send}<br>";
	$ret_tex .= "職員No.[{$t_idno}]";
	$ret_tex .= "({$f_staff['post']})<br>";
	$ret_tex .= '<span class="' . $t_bclass . '"><input type="button" value="詳細、編集" onClick="f_jump_details(this.form, ' . $t_idno . ');"> </span>';
	$ret_tex .= "<b>{$f_staff['surname']} {$f_staff['firstname']}</b>";
	$ret_tex .= "(あだ名:{$t_nickname})";

	return $ret_tex;
}

?>
