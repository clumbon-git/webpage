//年月変更
function f_changeDate($form, $year, $month){
	$form.p_year.value = $year;
	$form.p_month.value = $month;
	$form.action = "#";
	$form.submit();
}

//会員予定登録
function f_setSchedule($form, $f_ymd, $f_day){
	$form.gimmick_type.value = "set_note";
	$form.p_ymd.value = $f_ymd;
	$form.p_note.value = $form['note' + $f_day].value;
	$form.p_window_position.value = $(window).scrollTop();
	$form.submit();
}
