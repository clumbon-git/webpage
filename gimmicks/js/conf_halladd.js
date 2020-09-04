//館公開切替
function f_dispChange($form, $id){
	$form.gimmick_type.value = "dispChange";
	$form.hall_id.value = $id;
	$form.submit();
}

//館削除
function f_closeHall($form, $id, $name){
	if(window.confirm($name + "を削除します")){
		$form.gimmick_type.value = "hallDelete";
		$form.hall_id.value = $id;
		$form.submit();
	}
}
