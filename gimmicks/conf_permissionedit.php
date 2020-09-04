<?php
//コンテンツ管理権限設定
session_start();
//管理ページno
$G_gimmickno = 17;

//基本設定関数
require_once __DIR__ . '/../basic_function.php';
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
$P_gimmick_type = ""; //操作種別
$P_itemid = 0;				//コンテンツページid
$P_pmsnType = "";			//権限対象(group, section等)
$P_targetid = 0;			//権限対象id(役務、個人id等)
$P_pmsnLevel = -1;		//権限レベル 0=参照, 1=参照、更新, 2=参照、更新、承認
$P_pmsnHall = 0;			//他館アクセスフラグ 0=許可, 1=制限
$G_members = array();	//会員情報(ddl用)
$G_staffs = array();	//職員情報(ddl用)

isset($_POST["gimmick_type"]) && $P_gimmick_type = $_POST["gimmick_type"];
isset($_POST["item_id"]) && $P_itemid = $_POST["item_id"];
isset($_POST["pmsn_type"]) && $P_pmsnType = $_POST["pmsn_type"];
isset($_POST["target_id"]) && $P_targetid = $_POST["target_id"];
isset($_POST["pmsn_level"]) && $P_pmsnLevel = $_POST["pmsn_level"];
isset($_POST["pmsn_hall"]) && $P_pmsnHall = $_POST["pmsn_hall"];

$P_window_position = 0;	//windowスクロール位置
isset($_POST["p_window_position"]) && $P_window_position = $_POST["p_window_position"];

//権限タイトル
$G_pmsnTitle = array(0 => "参照", 1 => "参照、更新", 2 => "参照、更新、承認");
//他館アクセスタイトル
$G_acscTitle = array(0 => "許可", 1 => "制限");

//権限更新
$G_mess = "";	//作業メッセージ
if($P_gimmick_type == "set_pmsn"){

	if($P_pmsnType == "group"){
		//会員、職員グループ
		//対象権限削除
		$stmt = $pdo -> prepare('
		DELETE FROM `access_link_group`
		WHERE `fk_id_gimic_page` = :gimic AND `kind_group` = :group
		');
		$stmt -> bindValue(':gimic', $P_itemid, PDO::PARAM_INT);
		$stmt -> bindValue(':group', $P_targetid, PDO::PARAM_INT);
		$stmt -> execute();

		if($P_pmsnLevel >= 0){
			//指定権限登録
			$stmt = $pdo -> prepare('
			INSERT INTO `access_link_group`(`fk_id_gimic_page`, `kind_group`, `limit_hall`, `kind_process`)
			VALUES(:gimic, :id, :hall, :process)
			');
			$stmt -> bindValue(':gimic', $P_itemid, PDO::PARAM_INT);
			$stmt -> bindValue(':id', $P_targetid, PDO::PARAM_INT);
			$stmt -> bindValue(':hall', (int)$P_pmsnHall, PDO::PARAM_INT);
			$stmt -> bindValue(':process', $P_pmsnLevel, PDO::PARAM_INT);
			$stmt -> execute();
		}

			F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_pmsnType, $P_itemid, $P_targetid, $P_pmsnHall, $P_pmsnLevel);

	}

	if($P_pmsnType == "section"){
		//役務
		//対象権限削除
		$stmt = $pdo -> prepare('
		DELETE FROM `access_link_section`
		WHERE `fk_id_gimic_page` = :gimic AND `fk_id_section` = :id
		');
		$stmt -> bindValue(':gimic', $P_itemid, PDO::PARAM_INT);
		$stmt -> bindValue(':id', $P_targetid, PDO::PARAM_INT);
		$stmt -> execute();

		if($P_pmsnLevel >= 0){
			//指定権限登録
			$stmt = $pdo -> prepare('
			INSERT INTO `access_link_section`(`fk_id_gimic_page`, `fk_id_section`, `limit_hall`, `kind_process`)
			VALUES(:gimic, :id, :hall, :process)
			');
			$stmt -> bindValue(':gimic', $P_itemid, PDO::PARAM_INT);
			$stmt -> bindValue(':id', $P_targetid, PDO::PARAM_INT);
			$stmt -> bindValue(':hall', (int)$P_pmsnHall, PDO::PARAM_INT);
			$stmt -> bindValue(':process', $P_pmsnLevel, PDO::PARAM_INT);
			$stmt -> execute();
		}

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_pmsnType, $P_itemid, $P_targetid, $P_pmsnHall, $P_pmsnLevel);

	}

	if($P_pmsnType == "posts"){
		//職種
		//対象権限削除
		$stmt = $pdo -> prepare('
		DELETE FROM `access_link_posts`
		WHERE `fk_id_gimic_page` = :gimic AND `fk_id_staff_post` = :id
		');
		$stmt -> bindValue(':gimic', $P_itemid, PDO::PARAM_INT);
		$stmt -> bindValue(':id', $P_targetid, PDO::PARAM_INT);
		$stmt -> execute();

		if($P_pmsnLevel >= 0){
			//指定権限登録
			$stmt = $pdo -> prepare('
			INSERT INTO `access_link_posts`(`fk_id_gimic_page`, `fk_id_staff_post`, `limit_hall`, `kind_process`)
			VALUES(:gimic, :id, :hall, :process)
			');
			$stmt -> bindValue(':gimic', $P_itemid, PDO::PARAM_INT);
			$stmt -> bindValue(':id', $P_targetid, PDO::PARAM_INT);
			$stmt -> bindValue(':hall', (int)$P_pmsnHall, PDO::PARAM_INT);
			$stmt -> bindValue(':process', $P_pmsnLevel, PDO::PARAM_INT);
			$stmt -> execute();
		}

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_pmsnType, $P_itemid, $P_targetid, $P_pmsnHall, $P_pmsnLevel);

	}

	if($P_pmsnType == "member"){
		//会員
		//対象権限削除
		$stmt = $pdo -> prepare('
		DELETE FROM `access_link_member`
		WHERE `fk_id_gimic_page` = :gimic AND `fk_id_member_home` = :id
		');
		$stmt -> bindValue(':gimic', $P_itemid, PDO::PARAM_INT);
		$stmt -> bindValue(':id', $P_targetid, PDO::PARAM_INT);
		$stmt -> execute();

		if($P_pmsnLevel >= 0){
			//指定権限登録
			$stmt = $pdo -> prepare('
			INSERT INTO `access_link_member`(`fk_id_gimic_page`, `fk_id_member_home`, `limit_hall`, `kind_process`)
			VALUES(:gimic, :id, :hall, :process)
			');
			$stmt -> bindValue(':gimic', $P_itemid, PDO::PARAM_INT);
			$stmt -> bindValue(':id', $P_targetid, PDO::PARAM_INT);
			$stmt -> bindValue(':hall', (int)$P_pmsnHall, PDO::PARAM_INT);
			$stmt -> bindValue(':process', $P_pmsnLevel, PDO::PARAM_INT);
			$stmt -> execute();

			F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_pmsnType, $P_itemid, $P_targetid, $P_pmsnHall, $P_pmsnLevel);

		}
	}

	if($P_pmsnType == "staff"){
		//会員
		//対象権限削除
		$stmt = $pdo -> prepare('
		DELETE FROM `access_link_staff`
		WHERE `fk_id_gimic_page` = :gimic AND `fk_id_staff` = :id
		');
		$stmt -> bindValue(':gimic', $P_itemid, PDO::PARAM_INT);
		$stmt -> bindValue(':id', $P_targetid, PDO::PARAM_INT);
		$stmt -> execute();

		if($P_pmsnLevel >= 0){
			//指定権限登録
			$stmt = $pdo -> prepare('
			INSERT INTO `access_link_staff`(`fk_id_gimic_page`, `fk_id_staff`, `limit_hall`, `kind_process`)
			VALUES(:gimic, :id, :hall, :process)
			');
			$stmt -> bindValue(':gimic', $P_itemid, PDO::PARAM_INT);
			$stmt -> bindValue(':id', $P_targetid, PDO::PARAM_INT);
			$stmt -> bindValue(':hall', (int)$P_pmsnHall, PDO::PARAM_INT);
			$stmt -> bindValue(':process', $P_pmsnLevel, PDO::PARAM_INT);
			$stmt -> execute();

			F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_pmsnType, $P_itemid, $P_targetid, $P_pmsnHall, $P_pmsnLevel);

		}
	}

}

//情報格納
$G_section_i = array();	//役務項目
$G_sections = array();	//役務種別
$G_posts = array();			//職種
$G_p_members = array();	//権限あり会員
$G_p_staffs = array();	//権原あり職員

$G_contents = array();	//コンテンツ管理ページ情報格納
$G_accesses = array();	//アクセス権限情報格納
//グループ	arr['group'][1=会員グループ、2=職員グループ]=*
//役務			arr['section'][役務id]=*
//職種			arr['posts'][職種id]=*
//会員			arr['member'][会員基id]=*
//職員			arr['staff'][スタッフid]=*
//* = array('hall'=>他館アクセス制限 0=無、1=有, 'kind'=>処理権限 0=参照、1=参照、更新　2=管理者, 'name'=>名称)

//選択管理ページ名取得
$stmt = $pdo -> prepare('
SELECT `title_gimmick_page`, `note`
FROM `gimmick_page`
WHERE `id_gimmick_page` = :id
');
$stmt -> bindValue(':id', $P_itemid, PDO::PARAM_INT);
$stmt -> execute();
$G_contents = $stmt -> fetchAll()[0];

//アクセス権限情報取得
//グループ
$stmt = $pdo -> prepare('
SELECT t_link.`fk_id_gimic_page` AS id_gimic, t_link.`kind_group` AS id_target, t_link.`limit_hall` AS hall, t_link.`kind_process` AS kind,
(CASE
WHEN t_link.`kind_group` = 1 THEN "会員"
WHEN t_link.`kind_group` = 2 THEN "職員"
ELSE NULL END
	) as `name`
FROM `access_link_group` AS t_link
WHERE t_link.`fk_id_gimic_page` = :id
ORDER BY id_target ASC
');
$stmt -> bindValue(':id', $P_itemid, PDO::PARAM_INT);
$stmt -> execute();
$a_access = $stmt -> fetchAll();
F_setAccess($a_access, "group");
//役務
$stmt = $pdo -> prepare('
SELECT t_link.`fk_id_gimic_page` AS id_gimic, t_link.`fk_id_section` AS id_target, t_link.`limit_hall` AS hall, t_link.`kind_process` AS kind,
t_sec.`name_section` AS `name`
FROM `access_link_section` AS t_link
LEFT JOIN `section_kind` AS t_sec
ON t_link.`fk_id_section` = t_sec.`id_section`
WHERE t_link.`fk_id_gimic_page` = :id AND t_sec.`del_flg` = 0
ORDER BY id_target ASC
');
$stmt -> bindValue(':id', $P_itemid, PDO::PARAM_INT);
$stmt -> execute();
$a_access = $stmt -> fetchAll();
F_setAccess($a_access, "section");
//職種
$stmt = $pdo -> prepare('
SELECT t_link.`fk_id_gimic_page` AS id_gimic, t_link.`fk_id_staff_post` AS id_target, t_link.`limit_hall` AS hall, t_link.`kind_process` AS kind,
t_st.`post` AS `name`
FROM `access_link_posts` AS t_link
LEFT JOIN `staff_posts` AS t_st
ON t_link.`fk_id_staff_post` = t_st.`id_staff_post`
WHERE t_link.`fk_id_gimic_page` = :id AND t_st.`del_flg` = 0
ORDER BY id_target ASC
');
$stmt -> bindValue(':id', $P_itemid, PDO::PARAM_INT);
$stmt -> execute();
$a_access = $stmt -> fetchAll();
F_setAccess($a_access, "posts");
//会員
$stmt = $pdo -> prepare('
SELECT t_link.`fk_id_gimic_page` AS id_gimic, t_link.`fk_id_member_home` AS id_target, t_link.`limit_hall` AS hall, t_link.`kind_process` AS kind,
CONCAT(t_hall.`name_hall`, " ", t_pa.`surname`) AS `name`
FROM `access_link_member` AS t_link
LEFT JOIN `members_home` AS t_home
ON t_link.`fk_id_member_home` = t_home.`id_member_home`
LEFT JOIN `halls` AS t_hall
ON t_home.`fk_id_hall` = t_hall.`id_hall`
LEFT JOIN `members_parent` AS t_pa
ON t_home.`id_member_home` = t_pa.`fk_id_member_home`
WHERE t_link.`fk_id_gimic_page` = :id AND t_home.`activity_flg` = 1 AND t_pa.`activity_flg` = 1 AND t_pa.`delegate_flg` = 1
ORDER BY id_target ASC
');
$stmt -> bindValue(':id', $P_itemid, PDO::PARAM_INT);
$stmt -> execute();
$a_access = $stmt -> fetchAll();
F_setAccess($a_access, "member");
//職員
$stmt = $pdo -> prepare('
SELECT t_link.`fk_id_gimic_page` AS id_gimic, t_link.`fk_id_staff` AS id_target, t_link.`limit_hall` AS hall, t_link.`kind_process` AS kind,
CONCAT(t_hall.`name_hall`, " ", t_staff.`surname`) AS `name`
FROM `access_link_staff` AS t_link
LEFT JOIN `staff` as t_staff
ON t_link.`fk_id_staff` = t_staff.`id_staff`
LEFT JOIN `halls` AS t_hall
ON t_staff.`fk_id_hall` = t_hall.`id_hall`
WHERE t_link.`fk_id_gimic_page` = :id AND t_staff.`activity_flg` = 1
ORDER BY id_target ASC
');
$stmt -> bindValue(':id', $P_itemid, PDO::PARAM_INT);
$stmt -> execute();
$a_access = $stmt -> fetchAll();
F_setAccess($a_access, "staff");

//役務項目取得
$stmt = $pdo -> prepare('SELECT * FROM `section_items`
WHERE `del_flg` <> 1 ORDER BY `order_section_item` ASC');
$stmt -> execute();
$G_section_i = $stmt -> fetchall();

//役務取得
$stmt = $pdo -> prepare('SELECT * FROM `section_kind`
WHERE `del_flg` <> 1 ORDER BY `order_section` ASC');
$stmt -> execute();
$t_section = $stmt -> fetchall();
foreach($t_section as $val){
	if(!isset($G_sections[$val['fk_id_section_item']])){
		$G_sections[$val['fk_id_section_item']] = array();
	}
	$G_sections[$val['fk_id_section_item']][] = $val;
}

//職種情報取得
$stmt = $pdo -> prepare('SELECT `id_staff_post`, `post` FROM `staff_posts` WHERE `del_flg` <> 1 ORDER BY `id_staff_post` ASC');
$stmt -> execute();
$G_posts = $stmt -> fetchAll();

//権限付与会員情報取得
$stmt = $pdo -> prepare('
SELECT t_link.`fk_id_member_home` AS id_target, t_link.`limit_hall` AS hall, t_link.`kind_process`,
CONCAT("(", t_hall.`name_hall`, ")", t_pa.`surname`, " ", t_pa.`firstname`) AS `name`
FROM `access_link_member` AS t_link
LEFT JOIN `members_home` AS t_home
ON t_link.`fk_id_member_home` = t_home.`id_member_home`
LEFT JOIN `halls` AS t_hall
ON t_home.`fk_id_hall` = t_hall.`id_hall`
LEFT JOIN `members_parent` AS t_pa
ON t_home.`id_member_home` = t_pa.`fk_id_member_home`
WHERE t_link.fk_id_gimic_page = :id AND t_home.`activity_flg` = 1 AND t_pa.`activity_flg` = 1 AND t_pa.`delegate_flg` = 1
ORDER BY id_target ASC
');
$stmt -> bindValue(':id', $P_itemid, PDO::PARAM_INT);
$stmt -> execute();
$G_p_members = $stmt -> fetchAll();

//権限付与職員情報取得
$stmt = $pdo -> prepare('
SELECT t_link.`fk_id_staff` AS id_target, t_link.`limit_hall` AS hall, t_link.`kind_process`,
CONCAT("(", t_hall.`name_hall`, ")", t_staff.`surname`, " ", t_staff.`firstname`) AS `name`
FROM `access_link_staff` AS t_link
LEFT JOIN `staff` as t_staff
ON t_link.`fk_id_staff` = t_staff.`id_staff`
LEFT JOIN `halls` AS t_hall
ON t_staff.`fk_id_hall` = t_hall.`id_hall`
WHERE t_link.`fk_id_gimic_page` = :id AND t_staff.`activity_flg` = 1
ORDER BY id_target ASC
');
$stmt -> bindValue(':id', $P_itemid, PDO::PARAM_INT);
$stmt -> execute();
$G_p_staffs = $stmt -> fetchAll();

//追加選択用会員情報取得
$stmt = $pdo -> prepare('
SELECT t_home.`id_member_home`, t_hall.`name_hall`, t_prt.`surname`, t_prt.`firstname`
FROM `members_home` AS t_home
LEFT JOIN `halls` AS t_hall ON t_home.`fk_id_hall` = t_hall.`id_hall`
LEFT JOIN `members_parent` AS t_prt ON t_home.`id_member_home` = t_prt.`fk_id_member_home`
WHERE t_home.`activity_flg` = 1 AND t_prt.`activity_flg` = 1 AND t_prt.`delegate_flg` = 1
ORDER BY t_hall.id_hall ASC, t_prt.`surname_kana` ASC, t_prt.`firstname_kana` ASC
');
$stmt -> execute();
$G_members = $stmt -> fetchAll();

//追加選択用職員情報取得
$stmt = $pdo -> prepare('
SELECT t_staff.`id_staff`, t_staff.`surname`, t_staff.`firstname`, t_hall.`name_hall`, t_post.`post`
FROM `staff` AS t_staff
LEFT JOIN `staff_posts` AS t_post
ON t_staff.`fk_id_staff_post` = t_post.`id_staff_post`
LEFT JOIN `halls` AS t_hall
ON t_staff.`fk_id_hall` = t_hall.`id_hall`
WHERE t_staff.`activity_flg` = 1 AND t_post.`del_flg` <> 1
ORDER BY t_staff.`fk_id_hall` ASC, t_staff.`fk_id_staff_post` ASC
');
$stmt -> execute();
$G_staffs = $stmt -> fetchAll();


//会員選択ddl
$t_select_mem = '<select name="add_member_s">';
$t_select_mem .= '<option value="">-権限付与会員選択-</option>';
$t_hall="";
foreach($G_members as $val){
	if($t_hall != $val['name_hall']){
		$t_hall = $val['name_hall'];
		$t_select_mem .= '<option value="">【' . $t_hall . '】</option>';
	}
	$t_select_mem .= "<option value=\"{$val['id_member_home']}\">{$val['surname']}{$val['firstname']}</option>";
}
$t_select_mem .= "</select>";

//職員選択ddl
$t_select_staff = '<select name="add_staff_s">';
$t_select_staff .= '<option value="">-権限付与職員選択-</option>';
$t_hall = "";
foreach($G_staffs as $val){
	if($t_hall != $val['name_hall']){
		$t_hall = $val['name_hall'];
		$t_select_staff .= '<option value="">【' . $t_hall . '】</option>';
	}
	$t_select_staff .= "<option value=\"{$val['id_staff']}\">({$val['post']}){$val['surname']}{$val['firstname']}</option>";
}
$t_select_staff .= "</select>";

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
<script src="./js/conf_permissionedit.js?<?php echo date("Ymd-Hi"); ?>"></script>
<script src="/js/jquery-3.4.1.min.js"></script>
<script type="text/javascript">$(document).ready(function(){$(window).scrollTop(<?php echo $P_window_position; ?>);});</script>
<title><?php echo $G_npo_items['name_npo']; ?> コンテンツ管理権限設定</title>
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
		<h3>コンテンツ管理権限設定</h3>
		<p>
			<a href = "./configure_menu.php" target = "_self">・管理トップへ戻る</a><br>
			<a href = "./conf_permissionpage.php" target = "_self">・コンテンツ管理権限一覧へ戻る</a><br>
		</p>
		<hr>
		<p>
			<form action="" method="post" onSubmit="return false;">
				<h3><?php echo $G_contents['title_gimmick_page']; ?></h3>
				【グループ】<br>
				<?php
				echo F_makeSelectPermission("会員", $G_accesses, 'group', 1);
				?>
				<?php
				echo F_makeSelectPermission("職員", $G_accesses, 'group', 2);
				?>
				<br>
				<?php
				foreach($G_section_i as $i_val){	//役務項目ループ
					echo "【{$i_val['name_section_item']}】<br>";
					if(isset($G_sections[$i_val['id_section_item']])){
						foreach($G_sections[$i_val['id_section_item']] as $s_val){
							echo F_makeSelectPermission($s_val['name_section'], $G_accesses, 'section', $s_val['id_section']);
						}
					}
				}
				?>
				<br>
				<b>【職種】</b><br>
				<?php
				foreach($G_posts as $t_val){
					echo F_makeSelectPermission($t_val['post'], $G_accesses, 'posts', $t_val['id_staff_post']);
				}
				?>
				<br>
				<b>【会員】</b><br>
				<?php
				foreach($G_p_members as $t_val){
					echo F_makeSelectPermission($t_val['name'], $G_accesses, 'member', $t_val['id_target'], 1);
				}
				?>
				<?php echo $t_select_mem; ?>
				[権限]　<label><input type="radio" name="add_member_p" value=-1 ' . $t_cp['m1'] . ' checked>(なし)</label>
				　<label><input type="radio" name="add_member_p" value=0 ' . $t_cp[0] . '>(参照)</label>
				　<label><input type="radio" name="add_member_p" value=1 ' . $t_cp[1] . '>(参照、更新)</label>
				　<label><input type="radio" name="add_member_p" value=2 ' . $t_cp[2] . '>(参照、更新、承認)</label><br>
				[他館アクセス]　<label><input type="radio" name="add_member_h" value=0 ' . $t_ch[0] . '>(許可)</label>
				　<label><input type="radio" name="add_member_h" value=1 ' . $t_ch[1] . ' checked>(制限)</label>
				<?php
				if($G_pmsnLevel > 0){
					//編集権限あり
				?>
				　<input type="button" value="　権限追加　" onClick="f_add_pmsn(this.form, 'member', 'add_member_s', 'add_member_p', 'add_member_h');">
				<?php
				}
				?>
				<hr>
				<b>【職員】</b><br>
				<?php
				foreach($G_p_staffs as $t_val){
					echo F_makeSelectPermission($t_val['name'], $G_accesses, 'staff', $t_val['id_target'], 1);
				}
				?>
				<?php echo $t_select_staff; ?>
				[権限]　<label><input type="radio" name="add_staff_p" value=-1 ' . $t_cp['m1'] . ' checked>(なし)</label>
				　<label><input type="radio" name="add_staff_p" value=0 ' . $t_cp[0] . '>(参照)</label>
				　<label><input type="radio" name="add_staff_p" value=1 ' . $t_cp[1] . '>(参照、更新)</label>
				　<label><input type="radio" name="add_staff_p" value=2 ' . $t_cp[2] . '>(参照、更新、承認)</label><br>
				[他館アクセス]　<label><input type="radio" name="add_staff_h" value=0 ' . $t_ch[0] . '>(許可)</label>
				　<label><input type="radio" name="add_staff_h" value=1 ' . $t_ch[1] . ' checked>(制限)</label>
				<?php
				if($G_pmsnLevel > 0){
					//編集権限あり
				?>
				　<input type="button" value="　権限追加　" onClick="f_add_pmsn(this.form, 'staff', 'add_staff_s', 'add_staff_p', 'add_staff_h');">
				<?php
				}
				?>
				<hr>
				<input type="hidden" name="gimmick_type" value="">
				<input type="hidden" name="item_id" value=<?php echo $P_itemid; ?>>
				<input type="hidden" name="pmsn_type" value="">
				<input type="hidden" name="target_id" value=0>
				<input type="hidden" name="pmsn_level" value=0>
				<input type="hidden" name="pmsn_hall" value=0>
				<input type="hidden" name="p_window_position" value=0>
			</form>
		</p>
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
アクセス権限情報格納

引数
$f_arr = 権限arr
$f_key = 権限対象キー

戻り値(globalセット)
$G_accesses =
//グループ	arr['group'][1=会員グループ、2=職員グループ]=*
//役務			arr['section'][役務id]=*
//職種			arr['posts'][職種id]=*
//会員			arr['member'][会員基id]=*
//職員			arr['staff'][スタッフid]=*
* = array('hall'=>他館アクセス制限 0=無、1=有, 'kind'=>処理権限 0=参照、1=参照、更新　2=管理者, 'name'=>名称)
:::::::::::::::::::::::::::::::*/
function F_setAccess($f_arr, $f_key){
	global $G_accesses;
	foreach($f_arr as $arr){
		if(!isset($G_accesses[$f_key])){
			$G_accesses[$f_key] = array();
		}
		if(!isset($G_accesses[$f_key][$arr['id_target']])){
			$G_accesses[$f_key][$arr['id_target']] = array();
		}
		$G_accesses[$f_key][$arr['id_target']] = array('hall' => $arr['hall'], 'kind' => $arr['kind'], 'name' => $arr['name']);
	}
}

/*:::::::::::::::::::::::::::::::
アクセス権限選択表示作成

引数
$f_title = 権限対象
$f_arr = 権限arr
//グループ	arr['group'][1=会員グループ、2=職員グループ]=*
//役務			arr['section'][役務id]=*
//職種			arr['posts'][職種id]=*
//会員			arr['member'][会員基id]=*
//職員			arr['staff'][スタッフid]=*
* = array(
'hall'=>他館アクセス制限 0=無、1=有,
'kind'=>処理権限 0=参照、1=参照、更新　2=管理者, 'name'=>名称
)

$f_type				= 権限対象項目(group, section等)
$f_target_id	= 対象者id(役職、個人等id)
$f_del				= 個人権限削除表示フラグ 1 = 表示

戻り値
権限設定radioテキスト
:::::::::::::::::::::::::::::::*/
function F_makeSelectPermission($f_title, &$f_arr, $f_type, $f_target_id, $f_del = 0){
	global $G_pmsnTitle;
	global $G_acscTitle;
	global $G_pmsnLevel;

	$ret_tex = "";

	//権限レベルcheckedフラグ
	$t_cp = array("m1" => "checked", 0 => "", 1 => "", 2 => "");

	//他館参照checkedフラグ
	$t_ch = array(0 => "", 1 => "checked");

	//パーミッションradioキー
	$t_p_key = $f_type . "p" . $f_target_id;
	//他館権限radioキー
	$t_h_key = $f_type . "h" . $f_target_id;

	if(isset($f_arr[$f_type][$f_target_id])){
		$a_perm = $f_arr[$f_type][$f_target_id];
		$t_cp['m1'] = "";
		$t_cp[$a_perm['kind']] = "checked";
		$t_ch[1] = "";
		$t_ch[$a_perm['hall']] = "checked";
	}

	$t_pmsn = "";
	if(isset($a_perm['kind']) && $a_perm['kind'] != ""){
		$t_pmsn = '<span class="col_blue">[権限]=>' . $G_pmsnTitle[$a_perm['kind']] . '　[他館アクセス]=>' . $G_acscTitle[$a_perm['hall']] . "</span>";
	}

	$ret_tex = '
	<b>' . $f_title . '</b>　' . $t_pmsn . '<br>
	[権限]　<label><input type="radio" name="' . $t_p_key . '" value=-1 ' . $t_cp['m1'] . '>(なし)</label>
	　<label><input type="radio" name="' . $t_p_key . '" value=0 ' . $t_cp[0] . '>(参照)</label>
	　<label><input type="radio" name="' . $t_p_key . '" value=1 ' . $t_cp[1] . '>(参照、更新)</label>
	　<label><input type="radio" name="' . $t_p_key . '" value=2 ' . $t_cp[2] . '>(参照、更新、承認)</label><br>
	[他館アクセス]　<label><input type="radio" name="' . $t_h_key . '" value=0 ' . $t_ch[0] . '>(許可)</label>
	　<label><input type="radio" name="' . $t_h_key . '" value=1 ' . $t_ch[1] . '>(制限)</label>
	　
';

	if($G_pmsnLevel > 0){
		//編集権限あり
		$ret_tex .= '<input type="button" value="　更新　" onClick="f_set_pmsn(this.form, \'' . $f_type . '\', ' . $f_target_id . ', \'' . $t_p_key . '\', \'' . $t_h_key . '\');">';
	}

	$ret_tex .= '<hr>';

	return $ret_tex;
}

?>
