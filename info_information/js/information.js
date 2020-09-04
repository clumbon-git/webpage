//問合せ確認
function f_confirm(){
  $mail = document.fm_confirm.info_mail.value;
  $val = document.fm_confirm.info_val.value;
  $err = "";
  if($val == ""){
    $err += "お問合せ内容を入力してください。\n";
  }

  if($err){
    alert($err);
  }else{
    document.fm_confirm.gimmick_type.value = "info_disp";
    document.fm_confirm.submit();
  }
}

//入力画面リターン
function f_back(){
  document.fm_confirm.submit();
}

//送信
function f_send(){
  document.fm_confirm.gimmick_type.value = "info_send";
  document.fm_confirm.submit();
}











//表示切替
function f_dispchange($id){
  document.fm.menu_id.value = $id;
  document.fm.gimmick_type.value = "disp_change";
  document.fm.submit();
}

//タイトル編集
function f_titlechange($id, $t_title){
  $val = document.getElementById($t_title).value;
  if($val == ""){
    alert("タイトルを入力してください");
  } else {
    document.fm.menu_id.value = $id;
    document.fm.gimmick_type.value = "title_change";
    document.fm.menu_title.value = $val;
    document.fm.submit();
  }
}

//リンク編集
function f_linkchange($id, $t_link){
  $val = document.getElementById($t_link).value;
  if($val == ""){
    alert("リンクを入力してください");
  } else {
    document.fm.menu_id.value = $id;
    document.fm.gimmick_type.value = "link_change";
    document.fm.menu_link.value = $val;
    document.fm.submit();
  }
}

//表示順up
function f_moveup($id, $t_rank, $id_hr, $t_rank_hr){
  document.fm.menu_id.value = $id;
  document.fm.menu_id_hr.value = $id_hr;
  document.fm.menu_rank.value = $t_rank;
  document.fm.menu_rank_hr.value = $t_rank_hr;
  document.fm.gimmick_type.value = "rank_change";
  document.fm.submit();
}

//削除
function f_del($id, $t_count){
  if(window.confirm($t_count + "番のメニューを削除します")){
    document.fm.menu_id.value = $id;
    document.fm.gimmick_type.value = "menu_del";
    document.fm.submit();
  }
}

//追加
function f_add(){
  $title = document.fm_add.menu_title.value;
  $link = document.fm_add.menu_link.value;
  if($title == "" || $link == ""){
    alert("新規に登録するタイトルとリンクを入力してください");
  }else{
    document.fm_add.gimmick_type.value = "list_add";
    document.fm_add.submit();
  }
}
function ff(){
}
