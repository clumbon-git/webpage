<?php
//役務管理
session_start();

//管理ページno
$G_gimmickno = 10;

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
$G_section_i = array();	//役務項目
$G_sections = array();	//役務種別
$G_members = array();		//会員情報(ddl用)
$G_staff = array();			//担当者情報


//post値格納変数初期化
$P_gimmick_type = ""; //操作種別
//add_item			役務項目追加
//add_section		新規役務追加
//edit_section	役務名更新
//dell_item			役務項目削除
//del_kind			選択役務削除
//join_member		担当者登録
//del_member		担当者削除
//term_member		担当者権限解除

$P_id = 0;						//操作対象id 役務種別、役務項目等共用
$P_title = "";				//項目タイトル 役務種別、役務項目共用
$P_year = date('Y');	//就任年
$P_member = 0;				//登録メンバーNo.

$P_disp = "conf";	//リスト画面、機能画面切替 conf=機能、list=リスト

//post値取得
isset($_POST["gimmick_type"]) && $P_gimmick_type = $_POST["gimmick_type"];
isset($_POST["i_id"]) && $P_id = $_POST["i_id"];
isset($_POST["i_title"]) && $P_title = F_h($_POST["i_title"]);
isset($_POST["year"]) && $P_year = F_h($_POST["year"]);
isset($_POST["mem_no"]) && $P_member = $_POST["mem_no"];
isset($_POST["disp"]) && $P_disp = $_POST["disp"];

$P_window_position = 0;	//windowスクロール位置
isset($_POST["p_window_position"]) && $P_window_position = $_POST["p_window_position"];

//編集権限チェック
if($G_pmsnLevel == 0){
	$P_disp = "list";
}

//操作実行
$G_workmes = "";
$G_err_flg = false;

if($P_gimmick_type == "add_item"){
	//役務項目追加
	$stmt = $pdo -> prepare('
	INSERT INTO `section_items`(`name_section_item`, `order_section_item`)
	SELECT :title,
	CASE
		WHEN MAX(`order_section_item`) is null then 1
		ELSE MAX(`order_section_item`) + 1
	END
	FROM `section_items`
	');
	$stmt -> bindvalue(':title', $P_title, PDO::PARAM_STR);
	$stmt -> execute();

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_title);

	$G_workmes = '<span class="col_blue">新役務項目[' . $P_title . ']を追加しました</span>';
}elseif($P_gimmick_type == "add_section"){
	//役務追加
	$stmt = $pdo -> prepare('
	INSERT INTO `section_kind`(`fk_id_section_item`, `name_section`, `order_section`)
	SELECT :id, :title,
	CASE
		WHEN MAX(`order_section`) is null then 1
		ELSE MAX(`order_section`) + 1
	END
	FROM `section_kind`
	');
	$stmt -> bindvalue(':id', $P_id, PDO::PARAM_INT);
	$stmt -> bindvalue(':title', $P_title, PDO::PARAM_STR);
	$stmt -> execute();

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_id, $P_title);

	$G_workmes = '<span class="col_blue">新役務[' . $P_title . ']を追加しました</span>';
}elseif($P_gimmick_type == "del_item"){
	//役務項目削除
	$stmt = $pdo -> prepare('
	UPDATE `section_items`
	SET `del_flg` = 1
	WHERE `id_section_item` = :id
	');
	$stmt -> bindvalue(':id', $P_id, PDO::PARAM_INT);
	$stmt -> execute();

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_id);

	$G_workmes = '<span class="col_blue">指定役務項目を削除しました</span>';
}elseif($P_gimmick_type == "del_kind"){
	//役務削除
	$stmt = $pdo -> prepare('
	UPDATE `section_kind`
	SET `del_flg` = 1
	WHERE `id_section` = :id
	');
	$stmt -> bindvalue(':id', $P_id, PDO::PARAM_INT);
	$stmt -> execute();

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_id);

	$G_workmes = '<span class="col_blue">指定役務を削除しました</span>';
}elseif($P_gimmick_type == "edit_section"){
	//役務名更新
	$stmt = $pdo -> prepare('
	UPDATE `section_kind`
	SET `name_section` = :title
	WHERE `id_section` = :id
	');
	$stmt -> bindvalue(':id', $P_id, PDO::PARAM_INT);
	$stmt -> bindvalue(':title', $P_title, PDO::PARAM_STR);
	$stmt -> execute();

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_id, $P_title);

	$G_workmes = '<span class="col_blue">指定役務名を更新しました</span>';
}elseif($P_gimmick_type == "join_member"){
	//担当者登録
	$stmt = $pdo -> prepare('
	INSERT INTO `section_member`(`fk_id_section`, `fk_id_member_home`, `year_section_member`)
	VALUES(:id_sec, :id_mem, :year)
	');
	$stmt -> bindvalue(':id_sec', $P_id, PDO::PARAM_INT);
	$stmt -> bindvalue(':id_mem', $P_member, PDO::PARAM_INT);
	$stmt -> bindvalue(':year', $P_year, PDO::PARAM_INT);
	$stmt -> execute();

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_id, $P_member, $P_year);

	$G_workmes = '<span class="col_blue">担当者を登録しました</span>';
}elseif($P_gimmick_type == "del_member"){
	//担当者削除
	$stmt = $pdo -> prepare('
	UPDATE `section_member`
	SET `del_flg` = 1
	WHERE `id_section_member` = :id
	');
	$stmt -> bindvalue(':id', $P_id, PDO::PARAM_INT);
	$stmt -> execute();

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_id);

	$G_workmes = '<span class="col_blue">指定担当者を削除しました</span>';
}elseif($P_gimmick_type == "term_member"){
	//担当者権限解除
	$stmt = $pdo -> prepare('
	UPDATE `section_member`
	SET `special_flg` = 0
	WHERE `id_section_member` = :id
	');
	$stmt -> bindvalue(':id', $P_id, PDO::PARAM_INT);
	$stmt -> execute();

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_id);

	$G_workmes = '<span class="col_blue">指定担当者の権限を解除しました</span>';
}



//======================================================================
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

//会員情報取得
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

//担当者情報取得
$stmt = $pdo -> prepare('
SELECT t_secmem.`id_section_member`, t_secmem.`fk_id_section`, t_secmem.`year_section_member`, t_prt.`surname`, t_prt.`firstname`, t_hall.`name_hall`
FROM `section_member` AS t_secmem
LEFT JOIN `members_home` AS t_home ON t_secmem.`fk_id_member_home` = t_home.`id_member_home`
LEFT JOIN `members_parent` AS t_prt ON t_home.`id_member_home` = t_prt.`fk_id_member_home`
LEFT JOIN `halls` AS t_hall ON t_home.`fk_id_hall` = t_hall.`id_hall`
WHERE t_secmem.`del_flg` <> 1 AND t_secmem.special_flg = 1 AND t_prt.`delegate_flg` = 1
ORDER BY t_secmem.`year_section_member`, t_hall.id_hall ASC, t_prt.`surname_kana` ASC, t_prt.`firstname_kana` ASC
');
$stmt -> execute();
$t_staff = $stmt -> fetchall();
foreach($t_staff as $val){
	!isset($G_staff[$val['fk_id_section']]) && $G_staff[$val['fk_id_section']]=array();
	$G_staff[$val['fk_id_section']][]=$val;
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
<script src="./js/conf_sectionedit.js?<?php echo date("Ymd-Hi"); ?>"></script>
<script src="/js/jquery-3.4.1.min.js"></script>
<script type="text/javascript">$(document).ready(function(){$(window).scrollTop(<?php echo $P_window_position; ?>);});</script>
<title><?php echo $G_npo_items['name_npo']; ?>役務管理</title>
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
		<h3>役務管理</h3>
		<p>
			<a href = "./configure_menu.php" target = "_self">・管理トップへ戻る</a><br>
		</p>
		<hr>
<?php
echo $G_workmes;
?>
		<p>
			<form name="fm_section" action="" method="post">
				<h3>役務項目追加、編集</h3>
				<span class="t70per">※役務登録のある役務項目は削除できません<br>※担当者登録のある役務は削除できません<br>※任期満了後も権限の残っている会員は表示されます</span><br>

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
				<table class="config">
				<?php
				foreach($G_section_i as $i_val){	//役務項目ループ
					$b_del_i = "";
					if(!isset($G_sections[$i_val['id_section_item']])){
						$b_del_i = '<input type="button" value="役務項目削除" onClick="f_del_item(this.form, ' . $i_val['id_section_item'] . ');" class="' . $t_bclass . '">';
					}
				?>
					<tr><th><?php echo $i_val['name_section_item']; ?>　<?php echo $b_del_i; ?></th></tr>
					<?php
						if(isset($G_sections[$i_val['id_section_item']])){
							foreach($G_sections[$i_val['id_section_item']] as $s_val){
					?>
							<tr>
								<td>
									<?php
										//会員選択ddl
										$t_select_mem = '<select name="mem_' . $s_val['id_section'] . '" class="' . $t_bclass . '">';
										$t_select_mem .= '<option value="">-担当者選択-</option>';
										$t_hall="";
										foreach($G_members as $val){
											if($t_hall != $val['name_hall']){
												$t_hall = $val['name_hall'];
												$t_select_mem .= '<option value="">【' . $t_hall . '】</option>';
											}
											$t_select_mem .= "<option value=\"{$val['id_member_home']}\">{$val['surname']}{$val['firstname']}</option>";
										}
										$t_select_mem .= "</select>";

										//担当削除抑制
										$t_dis = "";
										if(isset($G_staff[$s_val['id_section']])){
											$t_dis = ' disabled="disabled"';
										}
									?>
									<input type="button" value="削除" onClick="f_del_kind(this.form, <?php echo $s_val['id_section']; ?>);"<?php echo $t_dis; ?> class="<?php echo $t_bclass; ?>"
>
									<?php
									echo F_maketbox($P_disp, 'b', "section_" . $s_val['id_section'], $s_val['name_section'], 20);
									?>
									<input type="button" value="　名称更新　" onClick="f_edit_kind(this.form, <?php echo $s_val['id_section']; ?>);" class="<?php echo $t_bclass; ?>"
>　
									<span class="<?php echo $t_bclass; ?>"><input type="text" name="y_<?php echo $s_val['id_section']; ?>" value="<?php echo $P_year; ?>" size="4">年度</span>　
									<?php echo $t_select_mem; ?>　
									<input type="button" value="担当者登録" onClick="f_join_member(this.form, <?php echo $s_val['id_section']; ?>);" class="<?php echo $t_bclass; ?>"
>
								</td>
							</tr>
							<tr>
								<td>
									<?php
										if(isset($G_staff[$s_val['id_section']])){
											foreach($G_staff[$s_val['id_section']] as $st_val){
												$b_del = '<input type="button" value="訂正削除" onClick="f_del_member(this.form, ' . $st_val['id_section_member'] . ');" class="' . $t_bclass . '">';

												//権限解除抑制
												$t_dis = ' disabled="disabled"';
												if($st_val['year_section_member'] < F_YofO()){
													$t_dis = '';
												}
												$b_term = '<input type="button" value="権限解除" onClick="f_term_member(this.form, ' . $st_val['id_section_member'] . ');"' . $t_dis . ' class="' . $t_bclass . '">';

												echo "{$b_del} {$b_term} {$st_val['year_section_member']}年度 [{$st_val['name_hall']}]<b>{$st_val['surname']} {$st_val['firstname']}</b><br>";
											}
										}
									?>
								</td>
							</tr>
					<?php
							}
						}
					?>
					<tr class="<?php echo $t_bclass; ?>">
						<td><input type="text" name="new_section_<?php echo $i_val['id_section_item']; ?>" value="" size="20">　<input type="button" value="　役務追加　" onClick="f_add_section(this.form, <?php echo $i_val['id_section_item']; ?>);" class="<?php echo $t_bclass; ?>"></td>
					</tr>
					<tr><td> </td></tr>
				<?php
				}
				?>
					<tr class="<?php echo $t_bclass; ?>">
						<th><input type="text" name="new_item" value="" size="15">　<input type="button" value="　新規役務項目追加　" onClick="f_add_item(this.form);" class="<?php echo $t_bclass; ?>"></th>
					</tr>
				</table>
				<input type="hidden" name="gimmick_type" value="">
				<input type="hidden" name="i_id" value=0>
				<input type="hidden" name="i_title" value="">
				<input type="hidden" name="year" value="<?php echo date('Y'); ?>">
				<input type="hidden" name="mem_no" value=0>
				<input type="hidden" name="disp" value="<?php echo $P_disp; ?>">
				<input type="hidden" name="p_window_position" value=0>
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
