//閉開所切替
function f_setOpen($form, $day, $hallid, $openflg){
	$form.gimmick_type.value = "set";
	$form.p_day.value = $day;
	$form.p_hall.value = $hallid;
	$form.p_kind.value = $openflg;
	$form.p_text.value = $form['note' + $day + '_' + $hallid].value;
	$form.p_window_position.value = $(window).scrollTop();
	$form.submit();
}

//全登録
function f_setAll($form, $openflg){
	if(window.confirm("全登録します")){
		$form.gimmick_type.value = "set_all";
		$form.p_kind.value = $openflg;
		$form.submit();
	}
}

//年月変更
function f_changeDate($form, $year, $month){
	$form.p_year.value = $year;
	$form.p_month.value = $month;
	$form.action = "#";
	$form.submit();
}
