//画像追加
function f_linkPicUp($form){
	$file = $form["link_pic"].value;

	if(!$file){
		alert("追加する画像ファイルを選択してください");
	}else{
		$form.submit();
	}
}
