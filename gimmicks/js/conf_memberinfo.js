//入力確認
function f_toConf($form){
	$title = $form.title.value;
	$text = $form.text.value;

	$err_mes = "";
	$chk_flg = false;

	$chks = document.getElementsByTagName("input");
	for($i = 0; $i < $chks.length; $i++){
		if($chks[$i].type === "checkbox" && $chks[$i].checked){
			$chk_flg = true;
		}
	}

	if($title == "" || $text == ""){
		$err_mes += "タイトル、本文を入力してください\r\n";
	}
	if(!$chk_flg){
		$err_mes += "表示対象を1つ以上チェックしてください";
	}

	if($err_mes == ""){
		$form.gimmick_type.value = "toConf";
		$form.submit();
	}else{
		alert($err_mes);
	}
}

//全チェック
function f_allCheck($button, $form, $name){
	$chk = $form[$name];
	$checked = "";
	if($button.value == "全館選択"){
		$button.value = "全館解除";
		$checked = "checked";
	}else{
		$button.value = "全館選択";
	}

	if($chk.length){
		for($i = 0; $i < $chk.length; $i++){
			$chk[$i].checked = $checked;
		}
	}else{
		$chk.checked = $checked;
	}
}

//入力クリア
function f_crear($form){
	if(window.confirm("入力した文章、対象選択を消去します")){
		$title = $form.title.value = "";
		$text = $form.text.value = "";
		$chks = document.getElementsByTagName("input");
		for($i = 0; $i < $chks.length; $i++){
			if($chks[$i].type === "checkbox"){
				$chks[$i].checked = false;
			}
		}
		$form.gimmick_type.value = "clear";
		$form.submit();
	}
}

//投稿実行
function f_send($form){
	if(window.confirm("投稿します")){
		$form.gimmick_type.value = "send";
		$form.submit();
	}
}