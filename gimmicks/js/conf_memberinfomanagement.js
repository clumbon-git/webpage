//表示切替
function f_dispChange($form, $id, $status){
	$form.gimmick_type.value = "dispChange";
	$form.infoId.value = $id;
	if($status == 1){
		$status = 0;
	}else{
		$status = 1;
	}
	$form.dispType.value = $status;
	$form.p_window_position.value = $(window).scrollTop();
	$form.submit();
}

//投稿削除
function f_delete($form, $id){
	if(window.confirm("削除します")){
		$form.gimmick_type.value = "delete";
		$form.infoId.value = $id;
		$form.p_window_position.value = $(window).scrollTop();
		$form.submit();
	}
}