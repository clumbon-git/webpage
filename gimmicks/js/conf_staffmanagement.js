//指定職員情報表示ジャンプ
function f_jump_details($form, $id){
	$form.action = "./conf_staffdetails.php";
	$form.staffid.value = $id;
  $form.submit();
}

//画面表示切替
function f_disp_toggle($form, $flg){
	$form.gimmick_type.value = "disp_all";
	$form.disp.value = $flg;
	$form.submit();
}

//パスワード登録メール送信
function f_mailsend($form, $id){
	$form.gimmick_type.value = "mailsend";
	$form.staffid.value = $id;
	$form.submit();
}