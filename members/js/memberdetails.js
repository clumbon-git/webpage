//選択保護者情報表示
function f_select_p($id){
  document.fm_parents.personid.value = $id;
  document.fm_parents.gimmick_type.value = "parent_disp";
  document.fm_parents.submit();
}

//選択児童情報削除
function f_delete_c($id){
	if(window.confirm("指定児童情報を削除します")){
  	document.fm_child.personid.value = $id;
  	document.fm_child.gimmick_type.value = "child_del";
  	document.fm_child.submit();
	}
}

//選択電話番号削除
function f_deltel($id){
	if(window.confirm("指定電話番号を削除します")){
  	document.fm_tel.phoneid.value = $id;
		document.fm_tel.gimmick_type.value = "phone_del";
  	document.fm_tel.submit();
	}
}

//選択電話番号メモ更新
function f_phone_n($id, $note_i){
  document.fm_tel.phoneid.value = $id;
  document.fm_tel.phonenote.value = document.fm_tel[$note_i].value;
	document.fm_tel.gimmick_type.value = "phonenote_edit";
  document.fm_tel.submit();
}

//電話番号追加
function f_addphone(){
	$t_no = document.fm_tel.tel.value;
	if($t_no == ""){
		alert("追加する電話番号を入力してください");
	}else{
  	document.fm_tel.phonenote.value = document.fm_tel.new_note.value;
		document.fm_tel.gimmick_type.value = "phone_add";
  	document.fm_tel.submit();
	}
}

//選択メアド削除
function f_delmail($id){
	if(window.confirm("指定メールアドレスを削除します")){
  	document.fm_mail.mailid.value = $id;
		document.fm_mail.gimmick_type.value = "mail_del";
  	document.fm_mail.submit();
	}
}

//メアドメモ更新
function f_mail_n($id, $note_i){
  document.fm_mail.mailid.value = $id;
  document.fm_mail.mailnote.value = document.fm_mail[$note_i].value;
	document.fm_mail.gimmick_type.value = "mailnote_edit";
  document.fm_mail.submit();
}

//メアド追加
function f_addmail(){
	$t_mail = document.fm_mail.mail.value;
	if($t_mail == ""){
		alert("追加するメールアドレスを入力してください");
	}else{
  	document.fm_mail.mailnote.value = document.fm_mail.new_note.value;
		document.fm_mail.gimmick_type.value = "mail_add";
  	document.fm_mail.submit();
	}
}

//会員詳細へ戻る
function f_bak_homedisp($form){
  $form.gimmick_type.value = "details";
  $form.submit();
}

//会員代表変更
function f_change_d($form, $id){
	$form.gimmick_type.value = "delegate_change";
	$form.personid.value = $id;
	$form.submit();
}

//会員保護者削除
function f_del_p($form, $id){
	if(window.confirm("指定保護者を削除します")){
		$form.gimmick_type.value = "parent_del";
		$form.personid.value = $id;
		$form.submit();
	}
}

//選択保護者氏名更新
function f_edit_p($form, $id){
	$name1 = $form['sn_' + $id].value;
	$name2 = $form['fn_' + $id].value;
	$kana1 = $form['snk_' + $id].value;
	$kana2 = $form['fnk_' + $id].value;
	$rela = $form['rel_' + $id].value;
	if($name1 && $name2 && $kana1 && $kana2){
		$form.gimmick_type.value = "parent_edit";
		$form.name1.value = $name1;
		$form.name2.value = $name2;
		$form.kana1.value = $kana1;
		$form.kana2.value = $kana2;
		$form.relation_input.value = $rela;
		$form.personid.value = $id;
		$form.submit();
	}else{
		alert("氏名、カナをすべて入力してください");
	}
}

//保護者追加
function f_add_p($form){
	$name1 = $form.name1_new.value;
	$name2 = $form.name2_new.value;
	$kana1 = $form.kana1_new.value;
	$kana2 = $form.kana2_new.value;
	$rela = $form.relation_input_new.value;

	if($name1 && $name2 && $kana1 && $kana2){
		$form.gimmick_type.value = "parent_add";
		$form.name1.value = $name1;
		$form.name2.value = $name2;
		$form.kana1.value = $kana1;
		$form.kana2.value = $kana2;
		$form.relation_input.value = $rela;
		$form.submit();
	}else{
		alert("氏名、カナをすべて入力してください");
	}
}

//選択児童情報更新
function f_edit_c($form, $id){
	$name1 = $form['cn1_' + $id].value;
	$name2 = $form['cn2_' + $id].value;
	$kana1 = $form['ck1_' + $id].value;
	$kana2 = $form['ck2_' + $id].value;
	$gender = $form['gnd_' + $id].value;
	$e_school = $form['ents_' + $id].value;
	$e_hall = $form['enth_' + $id].value;
	$birthday = $form['btd_' + $id].value;
	$note = $form['nc_' + $id].value;

	document.fm_child_edit.gimmick_type.value = "child_edit";
	document.fm_child_edit.personid.value = $id;
	document.fm_child_edit.c_name1.value = $name1;
	document.fm_child_edit.c_name2.value = $name2;
	document.fm_child_edit.c_kana1.value = $kana1;
	document.fm_child_edit.c_kana2.value = $kana2;
	document.fm_child_edit.gender.value = $gender;
	document.fm_child_edit.ent_school.value = $e_school;
	document.fm_child_edit.ent_hall.value = $e_hall;
	document.fm_child_edit.birthday.value = $birthday;
	document.fm_child_edit.note_child.value = $note;
	document.fm_child_edit.submit();
}

//選択児童情報削除
function f_delete_c($id){
	if(window.confirm("指定児童情報を削除します")){
  	document.fm_child_edit.personid.value = $id;
  	document.fm_child_edit.gimmick_type.value = "child_del";
  	document.fm_child_edit.submit();
	}
}

//選択児童退所処理
function f_leave_c($id){
	$lev_d = document.fm_child['lev_' + $id].value;
	if(!$lev_d){
		alert("退所日を入力してください");
	}else if(window.confirm("指定児童を退所処理します")){
  	document.fm_child_edit.personid.value = $id;
		document.fm_child_edit.lev_hall.value = $lev_d;
  	document.fm_child_edit.gimmick_type.value = "child_leave";
  	document.fm_child_edit.submit();
	}
}

//児童追加
function f_add_c($form){
	$form.gimmick_type.value = "child_add";
	$form.submit();
}

//児童キャラ画像更新
function f_childPicUp($form, $id){
	$file = $form["cpic_" + $id].value;

	if(!$file){
		alert("登録する画像ファイルを選択してください");
	}else{
		$form.gimmick_type.value = "childPicUp";
		$form.childid.value = $id;
		$form.submit();
	}
}
