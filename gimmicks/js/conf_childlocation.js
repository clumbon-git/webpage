//入出登録
function f_locationSet($form, $childId, $kind){
	$form.p_id_child.value = $childId;
	$form.p_status.value = $kind;
	$form.gimmick_type.value = "set";
	$form.action = "#cid" + $childId;
	$form.p_window_position.value = $(window).scrollTop();
	$form.submit();
}

//削除
function f_del($form, $locationId, $t_mess, $childId){
	if(window.confirm("指定した" + $t_mess + "データを削除します")){
		$form.p_id_location.value = $locationId;
		$form.gimmick_type.value = "del";
		$form.action = "#cid" + $childId;
		$form.p_window_position.value = $(window).scrollTop();
		$form.submit();
	}
}

//バーコード入力キャッチ
$incode = '';
document.addEventListener('keydown', (event) => {
	var $keyName = event.key;
	if($keyName == 'Enter'){
		f_barcode();
		$incode = '';
	}else{
		$incode += $keyName;
	}
})

//バーコード送信
function f_barcode(){
	if($incode.length != 13){
		return false;
	}
	$bar_head = $incode.substr(0, 3);
	$bar_status = +$incode.substr(3, 3);
	$bar_id = +$incode.substr(6, 6);
	if($bar_head != '999'){
		return false;
	}
	$form = document.barcode;
	$form.p_status.value = $bar_status;
	$form.p_id_child.value = $bar_id;
	$form.p_window_position.value = $(window).scrollTop();
	$form.submit();
}
