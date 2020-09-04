<?php
//学童スケジュール表示
session_start();

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

//当日
$today_date = date('Y-m-d');

//対象年
$P_year = date('Y');
//対象月
$P_month = date('m');

//登録対象日キー
$P_ymd = "";
//登録予定
$P_note = "";

//post値取得
isset($_POST['p_year']) && $P_year = $_POST['p_year'];
isset($_POST['p_month']) && $P_month = sprintf('%02d', $_POST['p_month']);
isset($_POST["p_ymd"]) && $P_ymd = $_POST["p_ymd"];
isset($_POST["p_note"]) && $P_note = F_h($_POST["p_note"]);
isset($_POST["gimmick_type"]) && $P_gimmick_type = $_POST["gimmick_type"];

$P_window_position = 0;	//windowスクロール位置
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


//会員予定登録
if($P_gimmick_type == "set_note"){
	$stmt = $pdo -> prepare('
INSERT INTO `members_schedule`(
`fk_id_hall`,
`target_date`,
`note`,
`fk_id_member_home`,
`member_name`
)
VALUES(
:id_hall,
:date,
:note,
:id_mem,
:name_mem
)
ON DUPLICATE KEY UPDATE
`fk_id_hall` = VALUES(`fk_id_hall`),
`target_date` = VALUES(`target_date`),
`note` = VALUES(`note`),
`fk_id_member_home` = VALUES(`fk_id_member_home`),
`member_name` = VALUES(`member_name`),
`renew_time` = NOW()
	');
	$stmt -> bindValue(':id_hall', $G_login['hall'], PDO::PARAM_INT);
	$stmt -> bindValue(':date', $P_ymd, PDO::PARAM_INT);
	$stmt -> bindValue(':note', $P_note, PDO::PARAM_INT);
	$stmt -> bindValue(':id_mem', $G_login['id'], PDO::PARAM_INT);
	$stmt -> bindValue(':name_mem', $_SESSION['member_name'], PDO::PARAM_INT);
	$stmt ->execute();

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, 0, $P_gimmick_type, $G_login['hall'], $P_ymd, $P_note);

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

//会員登録予定取得
//会員ログイン時 arr[年月日(yyyy-mm-dd)]=arr(members_scheduleテーブル格納値);
//職員ログイン時 arr[年月日(yyyy-mm-dd)][館id][]=arr(members_scheduleテーブル格納値);
$G_membersSchedule = array();
if($G_login['kind'] == 1){
	//会員ログイン
	$stmt = $pdo -> prepare('
	SELECT * FROM `members_schedule`
	WHERE DATE_FORMAT(`target_date`, "%Y%m") = :Ym AND fk_id_member_home = :homeid AND `note` <> ""
	');
	$stmt -> bindValue(':Ym', $ym_key, PDO::PARAM_STR);
	$stmt -> bindValue(':homeid', $G_login['id'], PDO::PARAM_STR);
	$stmt -> execute();
	$t_schedule = $stmt -> fetchAll();
	foreach($t_schedule as $t_val){
		!isset($G_membersSchedule[$t_val['target_date']]) && $G_membersSchedule[$t_val['target_date']] = $t_val;
	}
}elseif($G_login['kind'] == 2){
	//職員ログイン
	$stmt = $pdo -> prepare('
	SELECT * FROM `members_schedule`
	WHERE DATE_FORMAT(`target_date`, "%Y%m") = :Ym AND `note` <> ""
	');
	$stmt -> bindValue(':Ym', $ym_key, PDO::PARAM_STR);
	$stmt -> execute();
	$t_schedule = $stmt -> fetchAll();
	foreach($t_schedule as $t_val){
		!isset($G_membersSchedule[$t_val['target_date']]) && $G_membersSchedule[$t_val['target_date']] = array();
		!isset($G_membersSchedule[$t_val['target_date']][$t_val['fk_id_hall']]) && $G_membersSchedule[$t_val['target_date']][$t_val['fk_id_hall']] = array();
		$G_membersSchedule[$t_val['target_date']][$t_val['fk_id_hall']][] = $t_val;
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
<link rel="stylesheet" href="./css/members.css?<?php echo date("Ymd-Hi"); ?>">
<script src="./js/calendar.js?<?php echo date("Ymd-Hi"); ?>"></script>
<script src="/js/jquery-3.4.1.min.js"></script>
<?php
if($P_window_position > 0){
 ?>
<script type="text/javascript">$(document).ready(function(){$(window).scrollTop(<?php echo $P_window_position; ?>);});</script>
<?php
}
 ?>
<title><?php echo $G_npo_items['name_npo']; ?> 開閉所スケジュール</title>
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
			<form name="calendar" action="" method="post" onSubmit="return false;">
				<h3 name="infoplace" id="infoplace">開閉所スケジュール</h3>
				<?php
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
※お休みさせる日に児童名と予定を登録すると、指導員に表示されます(例、「〇〇ハナ子 休み」)
					<table class="calendar">
						<tr>
							<th>児童休所予定</th>
							<th>学童開閉所予定<br>(〇通常 △変則 Ｘ閉所)</th>
						</tr>
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

	$color_today = "";
	if($day_key == $today_date){
		$color_today = "today";
	}
?>
<tr id="day<?php echo $day; ?>" class="<?php echo $color_today; ?>">
	<td>
		<span class="<?php echo $day_col; ?>"><?php echo $day; ?>(<?php echo $val['week']; ?>)</span><br>
		<?php
			$t_schmess = "";
			if($G_login['kind'] == 1){
				//会員ログイン
				$t_note = "";
				if(isset($G_membersSchedule[$day_key])){
					$t_note = $G_membersSchedule[$day_key]['note'] . "<br>";
				}
				$t_schmess .= $t_note;
				$t_schmess .= '<input type="text" size="10" name="note' . $day . '">';
				$t_schmess .= '<input type="button" value="更新" onClick="f_setSchedule(this.form, \'' . $day_key . '\', \'' . $day . '\');">';
			}elseif($G_login['kind'] == 2){
				//職員ログイン
				if($day_key == $today_date){
					$t_schmess .= '<input type="button" value="表示更新" onClick="this.form.p_window_position.value = $(window).scrollTop();this.form.submit();">(' . date('Y-m-d H:i:s') . ')<br>';
				}
				foreach($G_halls as $hall_id => $hall_name){
					if(isset($G_membersSchedule[$day_key][$hall_id])){
						$t_schmess .= "-- {$hall_name} --<br>";
						foreach($G_membersSchedule[$day_key][$hall_id] as $t_memsDat){
							$t_schmess .= "No.{$t_memsDat['fk_id_member_home']} {$t_memsDat['member_name']}:{$t_memsDat['note']}(登録{$t_memsDat['renew_time']})<br>";
						}
					}
				}
			}
			echo $t_schmess;
		 ?>
	</td>
	<td>
	<span>
		<?php
			foreach($G_halls as $hall_id => $hall_name){
				echo $hall_name . " ";
				$open_mark = "<span class=\"col_red\">Ｘ</span>";
				$note = "";

				if(isset($G_schedule[$day_key][$hall_id])){
					$open_mark = $G_openMark[$G_schedule[$day_key][$hall_id]['open_flg']];
					$note = $G_schedule[$day_key][$hall_id]['note'];
				}

				echo $open_mark;
				echo $note;
				echo "<br>";
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
			<input type="hidden" name="p_ymd" value="">
			<input type="hidden" name="p_note" value="">
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
