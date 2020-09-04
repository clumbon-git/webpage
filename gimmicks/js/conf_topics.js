//conf_topics.php conf_topics_npo.phpで使用
//表示切替
function f_disp($form, $id){
	$form.topic_id.value = $id;
	$form.gimmick_type.value = "disp_change";
	$form.p_window_position.value = $(window).scrollTop();
	$form.submit();
}

//削除
function f_del($form, $id, $t_count){
	if(window.confirm($t_count + "番のトピックを削除します")){
		$form.topic_id.value = $id;
		$form.gimmick_type.value = "topic_del";
		$form.p_window_position.value = $(window).scrollTop();
		$form.submit();
	}
}

//変更
function f_edit($form, $id){
	$title = document.getElementById('topic_title' + $id).value;
	$text = document.getElementById('topic_text' + $id).value;
	if($title == "" || $text == ""){
		alert("タイトルと本文を入力してください");
	}else{
		$form.topic_id.value = $id;
		$form.gimmick_type.value = "update";
		$form.topic_title.value = $title;
		$form.topic_text.value = $text;
		$form.p_window_position.value = $(window).scrollTop();
		$form.submit();
	}
}

//表示順down
function f_movedown($form, $id){
	$form.topic_id.value = $id;
	$form.gimmick_type.value = "rank_change";
	$form.p_window_position.value = $(window).scrollTop();
	$form.submit();
}

//追加
function f_add($form){
	$title = $form.topic_title.value;
	$link = $form.topic_text.value;
	if($title == "" || $link == ""){
		alert("新規に登録するタイトルと本文を入力してください");
	}else{
		$form.gimmick_type.value = "topic_add";
		$form.submit();
	}
}

//画像追加
function f_up($form, $id){
	$file = $form["newpic" + $id].value;

	if(!$file){
		alert("追加する画像ファイルを選択してください");
	}else{
		$form.topic_id.value = $id;
		$form.gimmick_type.value = "pic_add";
		$form.p_window_position.value = $(window).scrollTop();
		$form.submit();
	}
}

//画像削除
function f_picDelete($form, $id){
	if(window.confirm("No." + $id + "の画像を削除します")){
		$form.pic_id.value = $id;
		$form.gimmick_type.value = "pic_delete";
		$form.p_window_position.value = $(window).scrollTop();
		$form.submit();
	}
}
