<?php
//学童スケジュール管理
session_start();
//管理ページno
$G_gimmickno = 20;

//基本設定関数
require_once __DIR__ . '/../basic_function.php';
require_once __DIR__ . '/../basic_setting.php';
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

//当日
$today_date = date('Y-m-d');

//対象年
$P_year = date('Y');
//対象月
$P_month = date('m');
//対象日
$P_day = 0;
//対象館
$P_hall = 0;

//注記
$P_text = "";

$P_gimmick_type = ""; //操作種別 dispChange=表示切替, delete=削除
$P_open_kind = 0;		//開閉所種別 0=閉所 1=開所(通常) 2=開所(条件付)

$P_window_position = 0;	//windowスクロール位置

//post値取得
isset($_POST['p_year']) && $P_year = $_POST['p_year'];
isset($_POST['p_month']) && $P_month = sprintf('%02d', $_POST['p_month']);
isset($_POST['p_day']) && $P_day = sprintf('%02d', $_POST['p_day']);
isset($_POST['p_hall']) && $P_hall = $_POST['p_hall'];
isset($_POST['p_text']) && $P_text = $_POST['p_text'];
isset($_POST['p_kind']) && $P_open_kind = $_POST['p_kind'];
isset($_POST["gimmick_type"]) && $P_gimmick_type = $_POST["gimmick_type"];
isset($_POST["p_window_position"]) && $P_window_position = $_POST["p_window_position"];

//情報格納
//開閉所マーク
$G_openMark = array(0 => '<span class="col_red">Ｘ</span>', 1 => '<span class="col_blue">〇</span>', 2 => '<span class="">△</span>');

//館情報 arr[館id]=館名
$G_halls = array();
//館情報取得
$stmt = $pdo -> prepare('SELECT `id_hall`, `name_hall` FROM `halls` WHERE `activity_flg` = 1 ORDER BY `id_hall` ASC');
$stmt -> execute();
$t_halls = $stmt -> fetchAll();
foreach($t_halls as $hall){
	$G_halls[$hall['id_hall']] = $hall['name_hall'];
}

$G_mess = "";	//作業メッセージ
if($P_gimmick_type == "set"){
	//スケジュール登録===========================================
	$date_key = $P_year . '-' . $P_month . '-' . sprintf('%02d', $P_day);

	$id_home = 0;
	$id_staff = 0;
	if($G_login['kind'] == 1){
		$id_home = $G_login['id'];
	}elseif($G_login['kind'] == 2){
		$id_staff = $G_login['id'];
	}

	$stmt = $pdo -> prepare('
INSERT INTO `scheduled_to_open`(`fk_id_hall`, `target_date`, `open_flg`, `note`, `fk_id_member_home`, `fk_id_staff`)
VALUES(:id_hall, :target_date, :open_kind, :note, :id_home, :id_staff)
ON DUPLICATE KEY UPDATE
`fk_id_hall` = VALUES(`fk_id_hall`),
`target_date` = VALUES(`target_date`),
`open_flg` = VALUES(`open_flg`),
`note` = VALUES(`note`),
`fk_id_member_home` = VALUES(`fk_id_member_home`),
`fk_id_staff` = VALUES(`fk_id_staff`)
	');
	$stmt -> bindValue(':id_hall', $P_hall, PDO::PARAM_INT);
	$stmt -> bindValue(':target_date', $date_key, PDO::PARAM_STR);
	$stmt -> bindValue(':open_kind', $P_open_kind, PDO::PARAM_INT);
	$stmt -> bindValue(':note', $P_text, PDO::PARAM_STR);
	$stmt -> bindValue(':id_home', $id_home, PDO::PARAM_INT);
	$stmt -> bindValue(':id_staff', $id_staff, PDO::PARAM_INT);
	$stmt ->execute();

	$G_mess = '';	//機能追加があれば使用

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_hall, $date_key, $P_open_kind, $P_text);

}elseif($P_gimmick_type == "set_all"){
	//全権同一開閉種登録===========================================
	$id_home = 0;
	$id_staff = 0;
	if($G_login['kind'] == 1){
		$id_home = $G_login['id'];
	}elseif($G_login['kind'] == 2){
		$id_staff = $G_login['id'];
	}
	$calendar = F_calcCalendar($P_year, $P_month);

	$stmt = $pdo -> prepare('
INSERT INTO `scheduled_to_open`(`fk_id_hall`, `target_date`, `open_flg`, `fk_id_member_home`, `fk_id_staff`)
VALUES(:id_hall, :target_date, :open_kind, :id_home, :id_staff)
ON DUPLICATE KEY UPDATE
`fk_id_hall` = VALUES(`fk_id_hall`),
`target_date` = VALUES(`target_date`),
`open_flg` = VALUES(`open_flg`),
`fk_id_member_home` = VALUES(`fk_id_member_home`),
`fk_id_staff` = VALUES(`fk_id_staff`)
	');
	foreach($calendar as $day => $val){
		$date_key = $P_year . '-' . $P_month . '-' . sprintf('%02d', $day);
		foreach($G_halls as $hall_id => $hall_name){
			$stmt -> bindValue(':id_hall', $hall_id, PDO::PARAM_INT);
			$stmt -> bindValue(':target_date', $date_key, PDO::PARAM_STR);
			$stmt -> bindValue(':open_kind', $P_open_kind, PDO::PARAM_INT);
			$stmt -> bindValue(':id_home', $id_home, PDO::PARAM_INT);
			$stmt -> bindValue(':id_staff', $id_staff, PDO::PARAM_INT);
			$stmt ->execute();
		}
	}
	$G_mess = '';	//機能追加があれば使用

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, 0, $date_key, $P_open_kind);

}

//スケジュール取得===========================================
//スケジュール情報格納配列
//arr[年月日(yyyy-mm-dd)][館id]=array('open_flg'=>開閉種, 'note'=>注記);
$G_schedule = array();
$ym_key = $P_year . $P_month;
$stmt = $pdo -> prepare('
SELECT *
FROM `scheduled_to_open`
WHERE DATE_FORMAT(`target_date`, "%Y%m") = :Ym
');
$stmt -> bindValue(':Ym', $ym_key, PDO::PARAM_STR);
$stmt ->execute();
$t_schedule = $stmt -> fetchAll();
$t_hallArr = array();
foreach($G_halls as $hall_id => $val){
	$t_hallArr[$hall_id] = array('open_flg' => 0, 'note' => '');
}
foreach($t_schedule as $t_val){
	!isset($G_schedule[$t_val['target_date']]) && $G_schedule[$t_val['target_date']] = $t_hallArr;
	if(isset($G_schedule[$t_val['target_date']][$t_val['fk_id_hall']])){
		//稼働中の館情報なら
		$G_schedule[$t_val['target_date']][$t_val['fk_id_hall']]['open_flg'] = $t_val['open_flg'];
		$G_schedule[$t_val['target_date']][$t_val['fk_id_hall']]['note'] = $t_val['note'];
	}
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
<link rel="stylesheet" href="./../members/css/members.css?<?php echo date("Ymd-Hi"); ?>">
<script src="./js/conf_scheduledtoopen.js?<?php echo date("Ymd-Hi"); ?>"></script>
<script src="/js/jquery-3.4.1.min.js"></script>
<script type="text/javascript">$(document).ready(function(){$(window).scrollTop(<?php echo $P_window_position; ?>);});</script>
<title><?php echo $G_npo_items['name_npo']; ?> 開閉所スケジュール管理</title>
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
		<h3>開閉所スケジュール管理</h3>
		<p>
			<a href = "./configure_menu.php" target = "_self">・管理トップへ戻る</a><br>
		</p>
		<hr>
		<p>
			<form name="calendar" action="" method="post" onSubmit="return false;">
				<h3 name="infoplace" id="infoplace">スケジュール登録</h3>
				<?php
				echo $G_mess;
				$r_year = $P_year;
				$r_month = $P_month - 1;
				if($r_month < 1){
					$r_month = 12;
					--$r_year;
				}
				$f_year = $P_year;
				$f_month = $P_month + 1;
				if($f_month > 12){
					$f_month = 1;
					++$f_year;
				}
				echo "<h2 class=\"nowrap\"><input type=\"button\" value=\"＜\" onClick=\"f_changeDate(this.form, {$r_year}, {$r_month});\"> {$P_year}年{$P_month}月 <input type=\"button\" value=\"＞\" onClick=\"f_changeDate(this.form, {$f_year}, {$f_month});\"></h2>";
				?>
				<?php
				if($G_pmsnLevel > 0){
				 ?>
				<input type="button" value="すべて〇にする" onClick="f_setAll(this.form, 1);">
				<?php
				}
				 ?>
　〇通常 △変則 Ｘ閉所　※一行ずつ更新
					<table class="calendar">
<?php
$calendar = F_calcCalendar($P_year, $P_month);
foreach($calendar as $day => $val){
	$day_key = $P_year . '-' . $P_month . '-' . sprintf('%02d', $day);
	$day_col = "col_black";
	if($val['id'] == 0){
		$day_col = "col_red";
	}elseif($val['id'] == 6){
		$day_col = "col_blue";
	}
?>
<tr id="day<?php echo $day; ?>">
	<td>
		<span class="<?php echo $day_col; ?>"><?php echo $day; ?>(<?php echo $val['week']; ?>)</span>
	</td>
	<td>
	<span>
		<?php
			foreach($G_halls as $hall_id => $hall_name){
				if($G_hallLevel == 1 && $hall_id <> $G_login['hall']){
					continue;
				}
				echo $hall_name . " ";
				$open_mark = "<span class=\"col_red\">Ｘ</span>";
				$note = "";

				if(isset($G_schedule[$day_key][$hall_id])){
					$open_mark = $G_openMark[$G_schedule[$day_key][$hall_id]['open_flg']];
					$note = $G_schedule[$day_key][$hall_id]['note'];
				}

				echo $open_mark;
				echo Fl_makeOpenButton($day, $hall_id, $note);
				echo "<br><br>";
			}
		 ?>
	</span>
	</td>
</tr>
<?php
}
?>
					</table>
			<input type="hidden" name="gimmick_type" value="">
			<input type="hidden" name="p_year" value="<?php echo $P_year; ?>">
			<input type="hidden" name="p_month" value="<?php echo $P_month; ?>">
			<input type="hidden" name="p_day" value=0>
			<input type="hidden" name="p_hall" value=0>
			<input type="hidden" name="p_text" value="">
			<input type="hidden" name="p_kind" value=0>
			<input type="hidden" name="p_window_position" value=0>
			</form>
			<hr>
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
開閉所状態変更ボタン作成

引数
$f_day 対象日
$f_hallid 館id
$f_note 注記

戻り値
状態変更ボタン
:::::::::::::::::::::::::::::::*/
function Fl_makeOpenButton($f_day, $f_hallid, $f_note){
	global $G_pmsnLevel;
	$ret = "";
	if($G_pmsnLevel > 0){
		$ret = '
<input type="button" value=" 〇 " onClick="f_setOpen(this.form, ' . $f_day . ', ' . $f_hallid . ', 1);">
<input type="button" value=" △ " onClick="f_setOpen(this.form, ' . $f_day . ', ' . $f_hallid . ', 2);">
<input type="button" value=" Ｘ " onClick="f_setOpen(this.form, ' . $f_day . ', ' . $f_hallid . ', 0);">
<input type="text" name="note' . $f_day . '_' . $f_hallid . '" size="15" value="' . $f_note . '" placeholder="注記あれば入力">';
	}else{
		$ret = $f_note;
	}

	return $ret;
}
?>