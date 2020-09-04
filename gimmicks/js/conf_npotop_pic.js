//表示切替
function f_dispchange($id){
	document.fm.id_pic.value = $id;
	document.fm.gimmick_type.value = "disp_change";
	document.fm.submit();
}

//削除
function f_del($id, $t_count){
	if(window.confirm($t_count + "番の画像を削除します")){
		document.fm.id_pic.value = $id;
		document.fm.gimmick_type.value = "pic_del";
		document.fm.submit();
	}
}

//追加
function f_up(){
	$file = document.pic_add.new_pic.value;
	if(!$file){
		alert("追加する画像ファイルを選択してください");
	}else{
		document.pic_add.gimmick_type.value = "pic_add";
		document.pic_add.submit();
	}
}
