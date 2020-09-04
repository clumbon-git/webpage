//指定会員情報表示ジャンプ
function f_jump_details($form, $id){
	$form.action = "./conf_memberdetails.php";
	$form.homeid.value = $id;
  $form.submit();
}

//画面表示切替
function f_disp_toggle($form, $flg){
	$form.gimmick_type.value = "disp_all";
	$form.disp.value = $flg;
	$form.submit();
}

//役務登録
function f_add_section($form, $id){
	$t_sec = $form['sec_' + $id].value;
	$t_year = $form['y_' + $id].value;
	if(!$t_sec){
		alert("登録する役務を選択して下さい");
	}else{
		$form.gimmick_type.value = "add_section";
		$form.homeid.value = $id;
		$form.section.value = $t_sec;
		$form.year.value = $t_year;
		$form.p_window_position.value = $(window).scrollTop();
		$form.submit();
	}
}

//除籍処理
function f_delbase($form, $id){
	if(window.confirm("指定会員を除籍します(表示されなくなりますがデータは消えません)")){
		$form.gimmick_type.value = "delbase";
		$form.homeid.value = $id;
		$form.p_window_position.value = $(window).scrollTop();
		$form.submit();
	}
}

//パスワード登録メール送信
function f_mailsend($form, $id){
	if(window.confirm("登録メールを送信します")){
		$form.gimmick_type.value = "mailsend";
		$form.homeid.value = $id;
		$form.p_window_position.value = $(window).scrollTop();
		$form.submit();
	}
}