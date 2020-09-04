//削除
function f_del($id){
	if(window.confirm("指定お問合せを削除します")){
		$form = document.getElementById('fm_info');
		$form.info_id.value = $id;
		$form.gimmick_type.value = "info_del";
		$form.p_window_position.value = $(window).scrollTop();
		$form.submit();
	}
}

//対応追加入力
function f_res($id){
	$form = document.getElementById('fm_info');
	$form.info_id.value = $id;
	$form.gimmick_type.value = "info_res";
	$form.submit();
}

//対応追加実行
function f_res_add($id){
	$form = document.getElementById('fm_info');
	$res_val = document.getElementById('res_input').value;
	if($res_val == ""){
		alert("対応内容を入力してください");
	}else if(window.confirm("入力した対応内容を登録します")){
		$form.info_id.value = $id;
		$form.res_text.value = $res_val;
		$form.gimmick_type.value = "info_res_add";
		$form.submit();
	}
}

//返信入力
function f_resMailInput($id){
	$form = document.getElementById('fm_info');
	$form.info_id.value = $id;
	$form.gimmick_type.value = "info_mail";
	$form.submit();
}

//返信送信
function fresMailSend($id){
	$form = document.getElementById('fm_info');
	$res_title = document.getElementById('res_title').value;
	$res_val = document.getElementById('res_input').value;
	if($res_val == "" || $res_title == ""){
		alert("タイトル、本文を入力してください");
	}else if(window.confirm("送信します、内容は確認しましたか？")){
		$form.info_id.value = $id;
		$form.res_title.value = $res_title;
		$form.res_text.value = $res_val;
		$form.gimmick_type.value = "info_mail_send";
		$form.submit();
	}
}
