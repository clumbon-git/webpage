<?php

//格納
$G_npo_items = array();   //npo情報
//name_npo 団体名,zip_npo 郵便番号,prefecture_npo 都道府県,address_npo 住所,tel_npo 電話番号,fax_npo fax番号,open_npo 設立日,title_npo_topics_top npoトピックス上部固定タイトル,text_npo_topics_top npoトピックス上部固定本文(indexのdescriptionとしても使用),title_npo_topicks_foot npoトピックス下部固定タイトル,text_npo_topicks_foot npoトピックス下部固定本文,pic_title タイトルロゴ画像ファイル名,gmap_code GoogleMapコード
$hall_links = array();  //各館リンク情報
$hall_links_gimmicks = array();  //各館リンク情報 configure_menu.phpページ用

//学童コンテンツ情報取得
$stmt = $pdo -> query('SELECT * FROM npo');
$G_npo_items = $stmt -> fetchAll()[0];

//各館リンク情報取得
$stmt = $pdo -> query('SELECT id_hall, name_hall, folder_hall, pic_link FROM halls WHERE disp_flg = 1 AND activity_flg = 1 ORDER BY regist_time ASC');
$hall_links = $stmt -> fetchAll();

//各館リンク情報取得 機能ページ用
$stmt = $pdo -> query('SELECT * FROM halls WHERE activity_flg = 1 ORDER BY regist_time ASC');
$hall_links_gimmicks = $stmt -> fetchAll();

//ログイン関連処理
//post値取得
$P_gimmick_type = "";   //動作種別 login=ログイン処理,logout=ログアウト処理
$P_id = "";							//id
$P_pass = "";						//パスワード
$P_kind = 0;						//利用者種別 1=会員,2=職員
$P_save = 'off';						//id,passクッキー記録 on=記録 off=削除

//left.php logout
isset($_GET['wf']) && $P_gimmick_type = F_h($_GET['wf']);

isset($_POST['work_flg']) && $P_gimmick_type = $_POST['work_flg'];
isset($_POST['id_left']) && $P_id = $_POST['id_left'];
isset($_POST['pass_left']) && $P_pass = $_POST['pass_left'];
isset($_POST['kind_left']) && $P_kind = $_POST['kind_left'];
isset($_POST['passsave_left']) && $P_save = $_POST['passsave_left'];

//ログイン状態フラグ
$result_login = false;	//true ログイン中

if($P_gimmick_type == "logout"){
	//ログアウト処理

	$t_member_kind = $t_member_id = '';
	isset($_SESSION['member_kind']) && $t_member_kind = $_SESSION['member_kind'];
	isset($_SESSION['member_id']) && $t_member_id = $_SESSION['member_id'];

	F_loginLog(debug_backtrace()[0]['file'], 3, '', '', $t_member_kind, $t_member_id);

	$_SESSION = array();
	if(isset($_COOKIE[session_name()])){
		setcookie(session_name(), '', time()-4200, '/');
	}
	session_destroy();
	//クッキー削除
	//id,passクッキー削除
	setcookie('moto_id', "", time()-1, '/');
	setcookie('moto_p', "", time()-1, '/');
	setcookie('moto_k', "", time()-1, '/');

	header('Location: /', true, 301);

}elseif($P_gimmick_type == "login"){
	//ログイン処理

	$result_login = F_loginCheck($P_id, $P_pass, $P_kind);
	if($P_save == "on"){
		//id,passクッキー記録
		setcookie('moto_id', $P_id, time()+(60*60*24*30), '/');
		setcookie('moto_p', $P_pass, time()+(60*60*24*30), '/');
		setcookie('moto_k', $P_kind, time()+(60*60*24*30), '/');
	}else{
		//id,passクッキー削除
		setcookie('moto_id', "", time()-1, '/');
		setcookie('moto_p', "", time()-1, '/');
		setcookie('moto_k', "", time()-1, '/');
	}

	if(!$result_login){
		//ログイン失敗でsession破棄
		$_SESSION = array();
		if(isset($_COOKIE[session_name()])){
			setcookie(session_name(), '', time()-4200, '/');
		}
		session_destroy();

		F_loginLog(debug_backtrace()[0]['file'], 2, $P_id, $P_pass, $P_kind, 0);
	}else{
		F_loginLog(debug_backtrace()[0]['file'], 1, $P_id, '', $P_kind, $_SESSION['member_id']);
	}

}else{
	//ログイン情報がsessionになく、cookie保存idがあればログイン処理
	//post渡しid、cookie保存id情報取得
	$tp_id = "";
	$tp_pass = "";
	$tp_kind = "";
	isset($P_id) && $tp_id = $P_id;
	isset($_COOKIE['moto_id']) && $tp_id = $_COOKIE['moto_id'];
	isset($_COOKIE['moto_p']) && $tp_pass = $_COOKIE['moto_p'];
	isset($_COOKIE['moto_k']) && $tp_kind = $_COOKIE['moto_k'];

	//cookie保存idがあればログイン処理
	if(
	(!isset($_SESSION['member_id']) || !isset($_SESSION['member_kind']))
	 && ($tp_id != "" && $tp_pass != "" && $tp_kind != "")
	){
		$result_login = F_loginCheck($tp_id, $tp_pass, $tp_kind);
		if($result_login){
			//ログイン成功
			F_loginLog(debug_backtrace()[0]['file'], 1, $tp_id, '', $tp_kind, $_SESSION['member_id']);
		}else{
			//ログイン失敗
			F_loginLog(debug_backtrace()[0]['file'], 2, $tp_id, $tp_pass, $tp_kind, 0);
		}
	}
}

//ログイン状態確認
$G_login = F_loginGet();
if(!$G_login){
	//ログイン失敗でsession破棄
	$_SESSION = array();
	if(isset($_COOKIE[session_name()])){
		setcookie(session_name(), '', time()-4200, '/');
	}
	@session_destroy();
}

?>
