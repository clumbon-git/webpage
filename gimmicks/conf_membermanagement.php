<?php
//会員情報管理
session_start();

//管理ページno
$G_gimmickno = 8;

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
$G_home = array();  //在籍会員情報、会員保護者 arr[所属館id][]=会員情報
$G_children = array();  //在籍児童情報 arr[会員ベースid][]=児童情報
$G_sections = array();	//役務情報
$G_charge = array();		//担当役務情報 arr[会員ベースid][]=役務情報

//都道府県
$G_ken = array('北海道','青森県','岩手県','宮城県','秋田県','山形県','福島県','茨城県','栃木県','群馬県','埼玉県','千葉県','東京都','神奈川県','新潟県','富山県','石川県','福井県','山梨県','長野県','岐阜県','静岡県','愛知県','三重県','滋賀県','京都府','大阪府','兵庫県','奈良県','和歌山県','鳥取県','島根県','岡山県','広島県','山口県','徳島県','香川県','愛媛県','高知県','福岡県','佐賀県','長崎県','熊本県','大分県','宮崎県','鹿児島県','沖縄県');
//続柄
$G_relation = array('父', '母', '祖父', '祖母');
//性別
$G_gender = array(0 => '男', 1 => '女', 2 => 'その他');

//post値格納変数初期化
$P_gimmick_type = "disp_all";	//操作種別
//disp_all=会員一覧表示 details=指定会員詳細表示 add_section=役務登録 delbase=除籍処理 mailsend=パスワード登録メール送信
$P_mail = "";				//ログインid併用メアド
$P_zip = "";				//郵便番号
$P_ken = "埼玉県";		//都道府県
$P_address = "";		//住所
$P_hall_no = 0;			//所属館
$P_name1 = "";			//保護者名字
$P_name2 = "";			//保護者名前
$P_kana1 = "";			//保護者名字カナ
$P_kana2 = "";			//保護者名前カナ
$P_c_name1 = "";		//児童名字
$P_c_name2 = "";		//児童名前
$P_c_kana1 = "";		//児童名字カナ
$P_c_kana2 = "";		//児童名前カナ
$P_phone = "";			//電話番号
$P_relation_select = "";	//続柄選択
$P_relation_input = "";		//続柄入力
$P_ent_school = "";	//小学1年生入学年
$P_birthday = "";		//児童誕生日
$P_gender = -1;			//性別 0=男、1=女、2=その他
$P_ent_hall = "";		//学童入所日

$P_disp = "conf";	//リスト画面、機能画面切替 conf=機能、list=リスト
$P_section = 0;		//役務No.
$P_year = date('Y');			//役務登録年度
$P_homeid = 0;		//会員ベースid

//post値取得
isset($_POST["gimmick_type"]) && $P_gimmick_type = $_POST["gimmick_type"];
isset($_POST["zip"]) && $P_zip = F_h($_POST["zip"]);
isset($_POST["ken"]) && $P_ken = F_h($_POST["ken"]);
isset($_POST["address"]) && $P_address = F_h($_POST["address"]);
isset($_POST["mail"]) && $P_mail = F_h($_POST["mail"]);
isset($_POST["hall"]) && $G_select_hall = $P_hall_no = $_POST["hall"];
isset($_POST["name1"]) && $P_name1 = F_h($_POST["name1"]);
isset($_POST["name2"]) && $P_name2 = F_h($_POST["name2"]);
isset($_POST["kana1"]) && $P_kana1 = F_h($_POST["kana1"]);
isset($_POST["kana2"]) && $P_kana2 = F_h($_POST["kana2"]);
isset($_POST["c_name1"]) && $P_c_name1 = F_h($_POST["c_name1"]);
isset($_POST["c_name2"]) && $P_c_name2 = F_h($_POST["c_name2"]);
isset($_POST["c_kana1"]) && $P_c_kana1 = F_h($_POST["c_kana1"]);
isset($_POST["c_kana2"]) && $P_c_kana2 = F_h($_POST["c_kana2"]);
isset($_POST["tel"]) && $P_phone = F_h($_POST["tel"]);
isset($_POST["relation_select"]) && $P_relation_select = $_POST["relation_select"];
isset($_POST["relation_input"]) && $P_relation_input = F_h($_POST["relation_input"]);
if(!$P_relation_input && $P_relation_select){
	$P_relation_input = $P_relation_select;
}
$P_relation_select = "";

isset($_POST["ent_school"]) && $P_ent_school = $_POST["ent_school"];
isset($_POST["birthday"]) && $P_birthday = F_h($_POST["birthday"]);
isset($_POST["gender"]) && $P_gender = $_POST["gender"];
isset($_POST["ent_hall"]) && $P_ent_hall = F_h($_POST["ent_hall"]);

isset($_POST["disp"]) && $P_disp = $_POST["disp"];
isset($_POST["section"]) && $P_section = $_POST["section"];
isset($_POST["year"]) && $P_year = $_POST["year"];
isset($_POST["homeid"]) && $P_homeid = $_POST["homeid"];

$P_window_position = 0;	//windowスクロール位置
isset($_POST["p_window_position"]) && $P_window_position = $_POST["p_window_position"];

//他館制限チェック
if($G_pmsnLevel == 0){
	$P_disp = "list";
}

//操作実行
$G_workmes = "";
$G_err_flg = false;

if($P_gimmick_type == "add_section"){
	//役務登録
	$stmt = $pdo -> prepare('
	INSERT INTO `section_member`(`fk_id_section`, `fk_id_member_home`, `year_section_member`)
	VALUES(:id_section, :id_member, :year)
	');
	$stmt -> bindvalue(':id_section', $P_section, PDO::PARAM_INT);
	$stmt -> bindvalue(':id_member', $P_homeid, PDO::PARAM_INT);
	$stmt -> bindvalue(':year', $P_year, PDO::PARAM_INT);
	$stmt -> execute();

	$G_workmes .= '<span class="col_blue">指定会員を役務登録しました</span>';

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_section, $P_homeid, $P_year);

		$P_gimmick_type = "disp_all";
}elseif($P_gimmick_type == "delbase"){
	//除籍処理
	$stmt = $pdo -> prepare('
	UPDATE `members_home` SET `activity_flg` = 0 WHERE `id_member_home` = :id
	');
	$stmt -> bindvalue(':id', $P_homeid, PDO::PARAM_INT);
	$stmt -> execute();

	$G_workmes .= '<span class="col_blue">指定会員を除籍しました</span>';

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_homeid);

	$P_gimmick_type = "disp_all";
}elseif($P_gimmick_type == "mailsend"){
	//パスワード登録メール送信
	//送信状態準備
	$t_key = sprintf('%06d', $P_homeid) . F_mkRandStr(10);

	$stmt = $pdo -> prepare('
	UPDATE `members_home` SET `change_pass_key` = :key
	WHERE `id_member_home` = :id
	');
	$stmt -> bindvalue(':key', $t_key, PDO::PARAM_STR);
	$stmt -> bindvalue(':id', $P_homeid, PDO::PARAM_INT);
	$stmt -> execute();
	//送信メアド取得
	$stmt = $pdo -> prepare('
	SELECT t_home.`login_id`, t_home.`change_pass_key`, t_parent.`surname`
	FROM `members_home` AS t_home
	LEFT JOIN `members_parent` AS t_parent
	ON t_home.`id_member_home` = t_parent.`fk_id_member_home`
	WHERE t_home.`id_member_home` = :id AND t_parent.`delegate_flg` = 1
	');
	$stmt -> bindvalue(':id', $P_homeid, PDO::PARAM_INT);
	$stmt -> execute();
	$t_data = $stmt -> fetchAll()[0];
	$t_mail = array();
	$t_mail[] = $t_data['login_id'];
	$t_key = $t_data['change_pass_key'];
	$t_name = $t_data['surname'];

	//送信時間記録
	$stmt = $pdo -> prepare('
	UPDATE `members_home` SET `change_pass_send` = :time
	WHERE `id_member_home` = :id
	');
	$stmt -> bindvalue(':time', date('Y-m-d H:i:s'), PDO::PARAM_STR);
	$stmt -> bindvalue(':id', $P_homeid, PDO::PARAM_INT);
	$stmt -> execute();

	//送信内容作成
	$t_search = array('__name__', '__cpk__', '__club__', '__url__');
	$t_replace = array($t_name, $t_key, $G_npo_items['name_npo'], (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST']);
	$t_title = $G_npo_items['name_npo'] . '、システムよりお知らせ';
	$t_text = file_get_contents(__DIR__ . '/template/mail_pass.tpl');
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

	$G_workmes .= '<span class="col_blue">指定会員('. $t_name . 'さん)にパスワード登録促しメールを送信しました</span>';

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_homeid);

	$P_gimmick_type = "disp_all";
}
//===============================================================

if($P_gimmick_type == "disp_all"){
	//会員一覧表示
	//全会員ベース情報取得
	$stmt = $pdo -> prepare('SELECT
t_b.`id_member_home`, t_b.`fk_id_hall`, t_b.`change_pass`, t_b.`change_pass_send`,t_b.`change_pass`,
t_p.`surname`, t_p.`firstname`, t_p.`surname_kana`, t_p.`firstname_kana`, t_p.`relation`, t_p.`delegate_flg`,
t_h.`name_hall`
FROM `members_home` AS t_b
LEFT JOIN `members_parent` AS t_p
ON t_b.`id_member_home` = t_p.`fk_id_member_home`
LEFT JOIN `halls` AS t_h
ON t_b.`fk_id_hall` = t_h.`id_hall`
WHERE t_b.`activity_flg` = 1 AND t_p.`activity_flg` = 1
ORDER BY t_p.`delegate_flg` DESC, t_p.`surname_kana`, t_p.`firstname_kana`
	');
	$stmt -> execute();
	$t_home = $stmt -> fetchAll();
	foreach($t_home as $val){
		!$val['fk_id_hall'] && $val['fk_id_hall'] = 0;
		!isset($G_home[$val['fk_id_hall']]) && $G_home[$val['fk_id_hall']] = array();
		!isset($G_home[$val['fk_id_hall']][$val['id_member_home']]) && $G_home[$val['fk_id_hall']][$val['id_member_home']] = array();
		$G_home[$val['fk_id_hall']][$val['id_member_home']][] = $val;
	}

	//会員児童情報取得
	$stmt = $pdo -> prepare('SELECT *
	FROM `members_child`
	WHERE `activity_flg` = 1
	ORDER BY `birthday` DESC
	');
	$stmt -> execute();
	$t_child = $stmt -> fetchAll();

	foreach($t_child as $val){
	!isset($G_children[$val['fk_id_member_home']]) && $G_children[$val['fk_id_member_home']] = array();
	$G_children[$val['fk_id_member_home']][] = $val;
	}
}

//館情報取得
$stmt = $pdo -> prepare('SELECT `id_hall`, `name_hall` FROM `halls` WHERE `activity_flg` = 1 ORDER BY `id_hall` ASC');
$stmt -> execute();
$hall_names = $stmt -> fetchAll();
array_unshift($hall_names, array("id_hall" => 0, "name_hall" => "未定"));

//役務情報取得
$stmt = $pdo -> prepare('
SELECT t_kind.`id_section`, t_kind.`name_section`, t_item.`name_section_item`
FROM `section_kind` as t_kind
LEFT JOIN `section_items` as t_item
ON t_kind.`fk_id_section_item` = t_item.`id_section_item`
WHERE t_kind.`del_flg` <> 1 AND t_item.`del_flg` <> 1
ORDER BY t_item.`order_section_item` ASC, t_kind.`order_section` ASC
');
$stmt -> execute();
$G_sections = $stmt -> fetchAll();

//役務選択ddl
$t_select_section = '<option value="">-役務選択-</option>';
$t_item="";
foreach($G_sections as $val){
	if($t_item != $val['name_section_item']){
		$t_item = $val['name_section_item'];
		$t_select_section .= '<option value="">【' . $t_item . '】</option>';
	}
	$t_select_section .= "<option value=\"{$val['id_section']}\">{$val['name_section']}</option>";
}
$t_select_section .= "</select>";

//担当役務情報取得
$stmt = $pdo -> prepare('
SELECT t_sk.`name_section`, t_si.`name_section_item`, t_mh.`id_member_home`
FROM `section_member` as t_sm
LEFT JOIN `section_kind` as t_sk ON t_sm.`fk_id_section` = t_sk.`id_section`
LEFT JOIN `section_items` as t_si ON t_sk.`fk_id_section_item` = t_si.`id_section_item`
LEFT JOIN `members_home` as t_mh ON t_sm.`fk_id_member_home` = t_mh.`id_member_home`
WHERE t_sm.`special_flg` = 1 AND t_sm.`del_flg` <> 1 AND t_sk.`del_flg` <> 1 AND t_si.`del_flg` <> 1 AND t_mh.`activity_flg` = 1
ORDER BY t_si.`order_section_item` ASC, t_sk.`order_section` ASC
');
$stmt -> execute();
$t_charge = $stmt -> fetchAll();

foreach($t_charge as $val){
	!isset($G_charge[$val['id_member_home']]) && $G_charge[$val['id_member_home']] = array();
	$G_charge[$val['id_member_home']][] = $val;
}

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
<script src="./js/conf_membermanagement.js?<?php echo date("Ymd-Hi"); ?>"></script>
<script src="/js/jquery-3.4.1.min.js"></script>
<script type="text/javascript">$(document).ready(function(){$(window).scrollTop(<?php echo $P_window_position; ?>);});</script>
<title><?php echo $G_npo_items['name_npo']; ?>会員一覧、編集</title>
</head>
<body>
<div id="pagebody">

<!-- header -->
<?php
include "./../head.php";
?>

	<!-- info left -->
<?php
include "./../left.php";
?>

	<!-- info main -->
	<div id="info">

<!-- 入力 -->
		<h3>会員一覧、編集</h3>
		<p>
			<a href = "./configure_menu.php" target = "_self">・管理トップへ戻る</a><br>
			<a href = "./conf_memberregist.php" target = "_self">・新規会員登録へ移動</a><br>
			<a href = "./<?php echo basename(__FILE__); ?>" target = "_self">・会員一覧、編集トップへ戻る</a><br>
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
				<h3>会員一覧</h3>
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
				(アイウエオ順表示、保護者名に表示される「◯」は会員単位の代表者です)
				<?php
					foreach($hall_names as $v_hall){
						if($G_hallLevel == 1 && $v_hall['id_hall'] != $G_login['hall']){
							continue;
						}
						if(!isset($G_home[$v_hall['id_hall']])){
							continue;
						}
						echo "<h2>【　" . $v_hall['name_hall'] . "　】</h2>";
				?>
				<table class="config">
					<tr><th>保護者</th><th>児童</th></tr>
					<?php
						foreach($G_home[$v_hall['id_hall']] as $v_key => $v_home){
					?>
					<tr>
						<td><?php echo F_makedisp_parents($v_key, $v_home); ?></td>
						<td>
						<?php
							if(isset($G_children[$v_key])){
								echo F_makedisp_child($G_children[$v_key]);
							}
						?>
						</td>
					</tr>
					<?php
						}
					?>
				</table>
				<?php
					}
				?>
				<input type="hidden" name="gimmick_type" value="details">
				<input type="hidden" name="homeid" value=0>
				<input type="hidden" name="section" value=0>
				<input type="hidden" name="year" value="<?php echo date('Y'); ?>">
				<input type="hidden" name="disp" value="<?php echo $P_disp; ?>">
				<input type="hidden" name="p_window_position" value=0>
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
一覧用児童情報表示作成

引数
$f_children  児童情報arr
	[id_child] => 児童id
	[fk_id_member_home] => 会員ベースid
	[fk_id_hall] => 所属館id
	[surname] => 氏
	[firstname] => 名
	[surname_kana] => 氏カナ
	[firstname_kana] => 名カナ
	[enterschool_year] => 小学校入学年yyyy
	[enterhall_date] => 入所日yyyy-mm-dd
	[birthday] => 誕生日yyyy-mm-dd
	[gender] => 性別0=男, 1=女, 2=その他
	[note] => メモ
	[activity_flg] => 在籍フラグ1=在籍
	[leaving_date] => 退所日yyyy-mm-dd

戻り値
児童情報テキスト
:::::::::::::::::::::::::::::::*/
function F_makedisp_child($f_children){
	$ret_tex = "";
	$t_gender = array(0 => "男", 1 => "女", 2 => "その他");

	foreach($f_children as $v_c){
		$t_sg = F_calc_schoolgrade($v_c['enterschool_year']);
		$t_bc = "";	//誕生月文字色
		$t_bm = "";	//誕生月マーク
		if(date("n") == date("n", strtotime($v_c['birthday']))){
			$t_bc = "col_blue";
			$t_bm = "★";
		}
		$ret_tex && $ret_tex .= "<br>---------------------------------<br>";

		$ret_tex .= "<b>{$v_c['surname']} {$v_c['firstname']}</b>";
		$ret_tex .= "({$v_c['surname_kana']} {$v_c['firstname_kana']}){$t_gender[$v_c['gender']]}";
		$ret_tex .= "<br>";
		$ret_tex .= "{$t_sg}年生 <span class=\"{$t_bc}\">{$t_bm}誕生日{$v_c['birthday']}</span>";
		$ret_tex .= "<br>";
		if($v_c['note']){
			$ret_tex .= "「" . nl2br($v_c['note']) . "」";
		}
	}
	return $ret_tex;
}

/*:::::::::::::::::::::::::::::::
一覧用保護者情報表示作成

引数
$f_idno 会員ベースid
$f_parents[]  保護者情報arr
	[id_member_home] => 会員ベースid
	[fk_id_member_home] => 会員ベースid
	[fk_id_hall] => 所属館id
	[change_pass] => ログイン用pass変更フラグ 1=変更済
	[change_pass_send] => ログイン用pass変更キー送信時間
	[surname] => 氏
	[firstname] => 名
	[surname_kana] => 氏カナ
	[firstname_kana] => 名カナ
	[relation] => 児童との続柄
	[delegate_flg] => 会員home単位代表者フラグ 1=代表者
	[name_hall] => 所属館名
	[activity_flg] => 在籍フラグ1=在籍

戻り値
保護者情報テキスト
:::::::::::::::::::::::::::::::*/
function F_makedisp_parents($f_idno, $f_parents){
	global $t_bclass;
	global $t_select_section;
	global $P_year;
	global $G_charge;
	global $G_pmsnLevel;

	//詳細ページボタン
	if($G_pmsnLevel > 0){
		$b_detail = '<span class="' . $t_bclass . '"><input type="button" value="詳細、編集、追加" onClick="f_jump_details(this.form, ' . $f_idno . ');"></span>';
	}else{
		$b_detail = '<span><input type="button" value="詳細" onClick="f_jump_details(this.form, ' . $f_idno . ');"></span>';
	}
	//除籍ボタン
	$b_del = '　<span class="' . $t_bclass . '"><input type="button" value="除籍" onClick="f_delbase(this.form, ' . $f_idno . ');"><br></span>';
	$t_ddl = '<select name="sec_' . $f_idno . '" class="' . $t_bclass . '">' . $t_select_section;
	//パスワードメール送信状況
	//会員ベース情報
	$t_base = $f_parents[0];
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
	$b_send = '<span class="' . $t_bclass . '"><input type="button" value="' . $t_send_title . '" onClick="f_mailsend(this.form, ' . $f_idno . ');"></span><br>';

	$ret_tex = "";
	$t_dispid = "{$t_send_tex}{$b_send}{$t_pass_change}<br>{$b_detail}{$b_del}会員基本No.[{$f_idno}]";
	foreach($f_parents as $v_p){
		$ret_tex .= $t_dispid;
		$t_dispid = "";
		$t_delegate = "　";	//代表者マーク
		$t_section = ""; //役務登録
		if($v_p['delegate_flg']){
			if(isset($G_charge[$f_idno])){
				foreach($G_charge[$f_idno] as $v_c){
					$t_section .= "<br>【{$v_c['name_section_item']}】{$v_c['name_section']}";
				}
			}
			$t_delegate = "◯";
			$t_section .= '<span class="' . $t_bclass . '"><br><input type="text" name="y_' . $f_idno . '" value="' . $P_year . '" size="4">年度</span>';
			$t_section .= " " . $t_ddl;
			$t_section .= ' <input type="button" value="役務登録" onClick="f_add_section(this.form, ' . $f_idno . ');" class="' . $t_bclass . '">';
		}
		$ret_tex .= "<br>{$t_delegate}<b>{$v_p['surname']} {$v_p['firstname']}</b>({$v_p['relation']})";
		$ret_tex .= $t_section;
	}
	return $ret_tex;
}

?>
