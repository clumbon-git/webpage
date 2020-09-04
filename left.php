<?php
if(session_status() == PHP_SESSION_NONE){
	session_start();
}

//格納
$menu_items = array();   //menu情報
//id_left_menu お知らせリンクid title_menu お知らせタイトル,link_menu リンクアドレス,link_blank 別画面フラグ,start_date 表示開始日時,end_date 表示最終日時,disp_flg 表示許可フラグ,regist_time テーブル登録日時,del_flg 削除フラグ

//左リンクメニュー情報取得
$stmt = $pdo -> query('SELECT id_left_menu, title_menu, link_menu, link_blank, regist_time, disp_flg, order_menu FROM left_menu WHERE del_flg = 0 ORDER BY `order_menu` ASC');
$menu_items = $stmt -> fetchAll();
?>
<div id="submenu" class="clear">
	<h2>お知らせ</h2>
	<ul>
<?php
//お知らせメニュー作成
foreach($menu_items as $val){
	if(!$val['disp_flg']){
		continue;
	}
	$blank = "";
	$val['link_blank'] && $blank = ' target="_blank"';
	echo "<li><a href=\"" . $val['link_menu'] . "\"" . $blank . ">" . $val['title_menu'] . "</a></li>";
}
?>
	</ul>
<?php

//ログイン状態確認
//post渡しid、cookie保存id情報取得
$tp_id = "";
$tp_pass = "";
$tp_kind = "";
isset($P_id) && $tp_id = $P_id;
isset($_COOKIE['moto_id']) && $tp_id = $_COOKIE['moto_id'];
isset($_COOKIE['moto_p']) && $tp_pass = $_COOKIE['moto_p'];
isset($_COOKIE['moto_k']) && $tp_kind = $_COOKIE['moto_k'];

$t_menu_disp = "on";
isset($G_left_off) && $t_menu_disp = $G_left_off;
if($G_login && $t_menu_disp != "off"){
	//ログイン中 $G_left_offはパスワード登録ページで設定
?>
		<span><?php echo $_SESSION['member_name']; ?>さん</span>
		<a href="/members/mypage.php" target="_self" style="white-space: nowrap;" class="login">・マイページ</a>
		<a href="/members/mypage.php?wf=logout" target="_self" style="white-space: nowrap;" class="login">・ログアウト</a>
<?php
}elseif($t_menu_disp != "off"){
	//非ログイン
	$tp_check = "";
	isset($_COOKIE['moto_id']) && $tp_check = 'checked="checked"';
?>
	<form name="fm_login" action="/members/mypage.php" method="post" onsubmit="return false;">
		<input type="text" name="id_left" size="17" value="<?php echo $tp_id; ?>" placeholder="id(メールアドレス)">
		<input type="password" name="pass_left" id="pass_left" size="17" value="<?php echo $tp_pass; ?>" placeholder="パスワード">
		<span style="white-space: nowrap;"><label for="passdisp_left"><input type="checkbox" id="passdisp_left"><span class="t80per">パスワード表示</span></label></span>
		<span style="white-space: nowrap;"><label for="passsave_left"><input type="checkbox" name="passsave_left" id="passsave_left" <?php echo $tp_check; ?>><span class="t80per">ログイン状態記憶</span></label></span><br>
		<input type="button" id="b_id" value="会員login" onClick="f_leftLogin(this.form, 1);">　
		<input type="button" id="b_pass" value="職員login" onClick="f_leftLogin(this.form, 2);">
		<input type="hidden" name="work_flg" value="">
		<input type="hidden" name="kind_left" value=0>
	</form>

<?php
}

?>

</div>
<script src="/js/left.js?<?php echo date("Ymd-Hi"); ?>"></script>
