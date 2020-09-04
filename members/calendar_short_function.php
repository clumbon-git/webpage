<?php
//直近開所予定情報取得、表示

//取得範囲
$date_count = 6;
$date = new DateTime();
$date_start = $date -> format('Y-m-d');
$date_anchor = $date -> format('j');
$date_last = $date -> modify('+' . $date_count . ' day') -> format('Y-m-d');

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

//スケジュール取得===========================================
//スケジュール情報格納配列
//arr[年月日(yyyy-mm-dd)][館id]=array('open_flg'=>開閉種, 'note'=>注記);
$G_schedule = array();
$stmt = $pdo -> prepare('
SELECT *
FROM `scheduled_to_open`
WHERE `target_date` >= :start AND `target_date` <= :last
');
$stmt -> bindValue(':start', $date_start, PDO::PARAM_STR);
$stmt -> bindValue(':last', $date_last, PDO::PARAM_STR);
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

$calendar = F_calcCalendar_short($date_start, $date_count);
$th = '';
$td = '';
foreach($calendar as $day => $val){
	$day_col = "col_black";
	if($val['id'] == 0){
		$day_col = "col_red";
	}elseif($val['id'] == 6){
		$day_col = "col_blue";
	}
	$th .= "<th><span class=\"{$day_col}\">{$day}({$val['week']})</span></th>";

	$td .= "<td>";
	foreach($G_halls as $hall_id => $hall_name){
		$td .= "<div class=\"calendar_short\">";
		$td .= $hall_name;
		$open_mark = "<span class=\"col_red\">Ｘ</span>";
		$note = "";

		if(isset($G_schedule[$day][$hall_id])){
			$open_mark = $G_openMark[$G_schedule[$day][$hall_id]['open_flg']];
			$note = $G_schedule[$day][$hall_id]['note'];
		}

		$td .= $open_mark;
		$td .= $note;
		$td .= "</div>";
	}
	$td .= "</td>";
}
?>
<span class="t60per">〇通常 △変則 Ｘ閉所　<a href="calendar.php#day<?php echo $date_anchor; ?>">カレンダー表示</a></span>
<table class="calendar_short">
	<tr><?php echo $th; ?></tr>
	<tr><?php echo $td; ?></tr>
</table>
