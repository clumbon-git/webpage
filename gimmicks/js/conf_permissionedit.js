//権限登録
function f_set_pmsn($form, $type, $id, $p_key, $h_key){
	$form.gimmick_type.value = "set_pmsn";
	$form.pmsn_type.value = $type;
	$form.target_id.value = $id;
	$form.pmsn_level.value = $form[$p_key].value;
	$form.pmsn_hall.value = $form[$h_key].value;
	$form.p_window_position.value = $(window).scrollTop();
	$form.submit();
}

//権限付与個人追加
function f_add_pmsn($form, $type, $id_key, $p_key, $h_key){
	$t_id = $form[$id_key].value;
	if($t_id == ""){
		alert("権限追加する個人を選択してください");
	}else{
		$form.gimmick_type.value = "set_pmsn";
		$form.pmsn_type.value = $type;
		$form.target_id.value = $t_id;
		$form.pmsn_level.value = $form[$p_key].value;
		$form.pmsn_hall.value = $form[$h_key].value;
		$form.p_window_position.value = $(window).scrollTop();
		$form.submit();
	}
}
