//新規役務項目追加
function f_add_item($form){
	$t_title=$form['new_item'].value;
	if(!$t_title){
		alert("追加する役務項目を入力してください");
	}else{
		$form.i_title.value = $t_title;
		$form.gimmick_type.value = "add_item";
		$form.p_window_position.value = $(window).scrollTop();
		$form.submit();
	}
}

//新規役務追加
function f_add_section($form, $id){
	$t_title=$form['new_section_' + $id].value;
	if(!$t_title){
		alert("追加する役務を入力してください");
	}else{
		$form.i_id.value = $id;
		$form.i_title.value = $t_title;
		$form.gimmick_type.value = "add_section";
		$form.p_window_position.value = $(window).scrollTop();
		$form.submit();
	}
}

//役務名更新
function f_edit_kind($form, $id){
	$t_title=$form['section_' + $id].value;
	if(!$t_title){
		alert("更新する役務名を入力してください");
	}else{
		$form.i_id.value = $id;
		$form.i_title.value = $t_title;
		$form.gimmick_type.value = "edit_section";
		$form.p_window_position.value = $(window).scrollTop();
		$form.submit();
	}
}

//選択役務項目削除
function f_del_item($form, $id){
	if(window.confirm("選択役務項目を削除します")){
		$form.i_id.value = $id;
		$form.gimmick_type.value = "del_item";
		$form.p_window_position.value = $(window).scrollTop();
		$form.submit();
	}
}

//選択役務削除
function f_del_kind($form, $id){
	if(window.confirm("選択役務を削除します")){
		$form.i_id.value = $id;
		$form.gimmick_type.value = "del_kind";
		$form.p_window_position.value = $(window).scrollTop();
		$form.submit();
	}
}

//担当者登録
function f_join_member($form, $id){
	$t_year=$form['y_' + $id].value;
	$t_member=$form['mem_' + $id].value;
	$pattern = /^\d{4}$/g;
	if(!$t_year.match($pattern)){
		alert("就任年度を入力してください");
	}else if(!$t_member){
		alert("担当者を選択してください");
	}else{
		$form.i_id.value = $id;
		$form.year.value = $t_year;
		$form.mem_no.value = $t_member;
		$form.gimmick_type.value = "join_member";
		$form.p_window_position.value = $(window).scrollTop();
		$form.submit();
	}
}

//担当者削除
function f_del_member($form, $id){
	if(window.confirm("指定担当者を削除します")){
		$form.i_id.value = $id;
		$form.gimmick_type.value = "del_member";
		$form.p_window_position.value = $(window).scrollTop();
		$form.submit();
	}
}

//担当者権限解除
function f_term_member($form, $id){
	if(window.confirm("指定担当者の権限を解除します")){
		$form.i_id.value = $id;
		$form.gimmick_type.value = "term_member";
		$form.p_window_position.value = $(window).scrollTop();
		$form.submit();
	}
}

//画面表示切替
function f_disp_toggle($form, $flg){
	$form.disp.value = $flg;
	$form.p_window_position.value = $(window).scrollTop();
	$form.submit();
}
