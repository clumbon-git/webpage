<?php
ini_set('display_errors', 1);	//エラー表示 0=非表示 1=表示

//ip取得
$G_ip = $_SERVER['REMOTE_ADDR'];

/*:::::::::::::::::::::::::::::::
エスケープ記略

引数
$val  エスケープ対象文字列
戻り値
エスケープ後文字列
:::::::::::::::::::::::::::::::*/
function F_h($val){
	return htmlspecialchars($val, ENT_QUOTES, "UTF-8");
}

/*:::::::::::::::::::::::::::::::
メアド構成チェック

引数
$email  入力メアド
戻り値
構成ok → true
:::::::::::::::::::::::::::::::*/
function F_isEmail($email){
	if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
		return true;
	}
	if(strpos($email, '@docomo.ne.jp') !== false || strpos($email, '@ezweb.ne.jp') !== false) {
		$pattern = '/^([a-zA-Z])+([a-zA-Z0-9\._-])*@(docomo\.ne\.jp|ezweb\.ne\.jp)$/';
		if(preg_match($pattern, $email, $matches) === 1) {
			return true;
		}
	}
	return false;
}
/*:::::::::::::::::::::::::::::::
日付構成チェック
判定可能引数構成
yyyymmdd yyyy[/-]m+[/-]d+

引数
$date  入力日付
戻り値
構成ok → true
:::::::::::::::::::::::::::::::*/
function F_isDate($date){
	$ret_check = false;
	$pattern_1 = "#^(\d{4})[/-](\d+)[/-](\d+)$#";
	$pattern_2 = "#^(\d{4})(\d{2})(\d{2})$#";

	if(preg_match($pattern_1, $date, $m) && checkdate($m[2], $m[3], $m[1])){
		$ret_check = true;
	}elseif(preg_match($pattern_2, $date, $m) && checkdate($m[2], $m[3], $m[1])){
		$ret_check = true;
	}
	return $ret_check;
}

/*:::::::::::::::::::::::::::::::
現在学年計算
小学校入学年から現在の学年を計算する

引数
$f_year  入学年yyyy

戻り値
学年d
:::::::::::::::::::::::::::::::*/
function F_calc_schoolgrade($f_year){
	$ret_d = date("Y") - $f_year;
	if(date("n") >= 4){
		++$ret_d;
	}
	if($ret_d > 6){$ret_d = "(卒業)";}

	return $ret_d;
}

/*:::::::::::::::::::::::::::::::
現任期就任年
現在任期の就任年を返す
1-3月は前年、それ以外は今年のyyyy値

引数
なし

戻り値
yyyy
:::::::::::::::::::::::::::::::*/
function F_YofO(){
	$ret_y = date("Y");
	if(date("n") <= 3){
		--$ret_y;
	}

	return $ret_y;
}

/*:::::::::::::::::::::::::::::::
textbox作成
textboxを作成する
表示指定がlist時はvalue値のみ返す

引数
$disp		表示指定 list→$valueのみ返す
$tag		list時前後タグ(<>の中身)　false以外ならタグ整形前後追加
$name		name
$value	value
$size		size
$class	class名
$ex			タグ内追加要素(disable等)

戻り値
textbox || value値
:::::::::::::::::::::::::::::::*/
function F_maketbox($disp, $tag, $name, $value, $size, $class = "", $ex = ""){
	$ret = "";
	if($disp == "list"){
		$ret = $value;
		if($tag){
			$ret = "<{$tag}>" . $ret . "</$tag>";
		}
	}else{
		$class != "" && $class = ' class="' . $class . '"';
		$ex && $ex = ' ' . $ex;
		$ret = '<input type="text" name="' . $name . '" value="' . $value . '" size="' . $size . '"' . $class . $ex . '>';
	}
	return $ret;
}

/*:::::::::::::::::::::::::::::::
メール送信

呼び元記述
require_once 'Mail.php';
require_once __DIR__ . '/../mailip.php';

引数
$title	メールタイトル
$body		メール本文
$mails	送信先メアド配列 arr[]=array(会員id, メアド)

戻り値
送信情報テキスト
送信 x件
未送信 x件
(ERROR メアド)
:::::::::::::::::::::::::::::::*/
function F_mailsend($title, $body, $mails){
global $MAIL_HOST;
global $MAIL_PORT;
global $MAIL_USERNAME;
global $MAIL_PASS;
global $MAIL_FROM;

$ret_tex = '送信先メールアドレス指定無し';
if(count($mails)){
		$t_params = array(
		"host" => $MAIL_HOST,
		"port" => $MAIL_PORT,
		"auth" => true,
		"username" => $MAIL_USERNAME,
		"password" => $MAIL_PASS,
		"persist" => true
		);

		$body = mb_convert_encoding($body, "ISO-2022-JP-MS", "UTF-8");
		$body = html_entity_decode($body, ENT_QUOTES);

		$mailObject = Mail::factory("smtp", $t_params);
		$ret_tex = '';
		$ok = array();
		$ng = array();
		$err = '';
		foreach($mails as $mail_arr){
			$mail = $mail_arr[1];
			$headers = array(
			"MIME-Version" => "1.0",
			"Content-Type" => "text/plain; charset=ISO-2022-JP",
			"Content-Transfer-Encoding" => "7bit",
			"To" => $mail,
			"From" => $MAIL_FROM,
			"Subject" => mb_encode_mimeheader(mb_convert_encoding($title, "ISO-2022-JP-MS", "UTF-8")),
			"Return-Path" => $MAIL_FROM
			);

			$status = $mailObject->send($mail, $headers, $body);
			if(PEAR::isError($status)){
				$ng[] = $mail;
				$err .= "ERROR " . $mail . " " . $status -> getMessage() . "<br>";
			}else{
				$ok[] = $mail;
			}
		}
		$ret_tex .= "送信 " . count($ok) . "件<br>未送信 " . count($ng) . "件<br>";
		$ret_tex .= $err;
	}
	return $ret_tex;
}

/*:::::::::::::::::::::::::::::::
ランダム文字列作成

引数
$length	作成文字数(デフォルト10文字)

戻り値
指定文字数ランダム文字列
:::::::::::::::::::::::::::::::*/
function F_mkRandStr($length = 10){
	$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJLKMNOPQRSTUVWXYZ0123456789';
	$str = '';
	for($i = 0; $i < $length; ++$i) {
		$str .= $chars[mt_rand(0, 61)];
	}
	return $str;
}

/*:::::::::::::::::::::::::::::::
ログイン実行

ログイン成功時に$_SESSION[]値をセット、失敗時は削除
member_kind		利用者種別 1=会員, 2=職員
member_id			利用者id members_home/id_member_home or staff/id_staff
member_name		利用者名

引数
$login_id		ログインid
$login_pass	ログインpass
$login_kind	ログイン対象 1=会員(DB member_kind),2=職員(DB staff)

戻り値
true=成功, false=失敗
:::::::::::::::::::::::::::::::*/
function F_loginCheck($login_id, $login_pass, $login_kind){
	global $pdo;

	if($login_kind == 1){
		//会員
		$stmt = $pdo -> prepare('
		SELECT t_home.`id_member_home` AS `id`, t_home.`login_pass` as `pass`, t_home.`fk_id_hall` AS `hall`, t_parent.`surname` AS `name`
		FROM `members_home` AS t_home
		LEFT JOIN `members_parent` as t_parent
		ON t_home.`id_member_home` = t_parent.`fk_id_member_home`
		WHERE t_home.`login_id` = :login_id AND t_parent.`delegate_flg` = 1 AND t_home.`activity_flg` = 1
		');
	}elseif($login_kind == 2){
		//職員
		$stmt = $pdo -> prepare('
		SELECT `id_staff` AS `id`, `login_pass` AS `pass`, `surname` AS `name`, `fk_id_hall` AS `hall`
		FROM `staff`
		WHERE `login_id` = :login_id AND `activity_flg` = 1
		');
	}else{
		//対応種別なし
		return false;
	}

	$stmt -> bindValue(':login_id', $login_id, PDO::PARAM_STR);
	$stmt -> execute();
	$t_val = $stmt -> fetchAll();

	if(!count($t_val)){
		//id合致なし
		return false;
	}

	if(!password_verify($login_pass, $t_val[0]['pass'])){
		//pass非合致
		return false;
	}

	//ログイン
	$_SESSION['member_kind'] = $login_kind;
	$_SESSION['member_id'] = $t_val[0]['id'];
	$_SESSION['member_name'] = $t_val[0]['name'];
	$_SESSION['member_hall'] = $t_val[0]['hall'];
	return true;
}

/*:::::::::::::::::::::::::::::::
ログイン状態確認

ログイン$_SESSION[]情報を確認
在籍フラグチェック
member_kind		利用者種別 1=会員, 2=職員
member_id			利用者id members_home/id_member_home or staff/id_staff

引数
なし

戻り値
ログイン中
array('id' => 個人識別id, 'kind' => 会員種別(1 会員, 2 職員));
非ログイン
false
:::::::::::::::::::::::::::::::*/
function F_loginGet(){
	global $pdo;

	$a_ret = array();
	if(
		isset($_SESSION['member_kind']) && $_SESSION['member_kind']
		&& isset($_SESSION['member_id']) && $_SESSION['member_id']
		&& isset($_SESSION['member_hall'])
	){

		if($_SESSION['member_kind'] == 1){
			//会員
			$stmt = $pdo -> prepare('
			SELECT `id_member_home` AS `id`
			FROM `members_home`
			WHERE `id_member_home` = :login_id AND `activity_flg` = 1
			');
		}elseif($_SESSION['member_kind'] == 2){
			//職員
			$stmt = $pdo -> prepare('
			SELECT `id_staff` AS `id`
			FROM `staff`
			WHERE `id_staff` = :login_id AND `activity_flg` = 1
			');
		}else{
			//対応種別なし
			return false;
		}
		$stmt -> bindValue(':login_id', $_SESSION['member_id'], PDO::PARAM_STR);
		$stmt -> execute();
		$t_val = $stmt -> fetchAll();

		if(!count($t_val)){
			//アクティブid合致なし
			return false;
		}else{
			$a_ret = array('id' => $_SESSION['member_id'], 'kind' => $_SESSION['member_kind'], 'hall' => $_SESSION['member_hall']);
		}
	}else{
		return false;
	}

	return $a_ret;
}

/*:::::::::::::::::::::::::::::::
権限付与情報取得

ログイン情報から紐付き権限を取得

引数
$f_arr = array('id' => 個人識別id, 'kind' => 会員種別(1 会員, 2 職員));

戻り値
紐付き権限情報
$ret_arr = array[ギミックページid] = array('hall'=>他館アクセス制限 0=無、1=有, 'kind'=>処理権限 0=参照、1=参照、更新　2=管理者)
:::::::::::::::::::::::::::::::*/
function F_pmsnCheck($f_arr){
	global $pdo;
	$ret_arr = array();

	//会員、職員グループ権限
	$stmt = $pdo -> prepare('
	SELECT t_acsc.`limit_hall` AS hall, t_acsc.`kind_process` AS kind, t_acsc.`fk_id_gimic_page` AS page
	FROM `access_link_group` AS t_acsc
	WHERE t_acsc.`kind_group` = :id AND t_acsc.`fk_id_gimic_page` IS NOT NULL
	');
	$stmt -> bindValue(':id', $f_arr['kind'], PDO::PARAM_INT);
	$stmt -> execute();
	$a_temp = $stmt -> fetchAll();
	F_pmsnCheckSort($ret_arr, $a_temp);

	if($f_arr['kind'] == 1){
		//会員ログイン時
		//役務権限
		$stmt = $pdo -> prepare('
		SELECT t_acsc.`limit_hall` AS hall, t_acsc.`kind_process` AS kind, t_acsc.`fk_id_gimic_page` AS page
		FROM `members_home` AS t_home
		LEFT JOIN `section_member` AS t_sec
		ON t_home.`id_member_home` = t_sec.`fk_id_member_home`
		LEFT JOIN `access_link_section` AS t_acsc
		ON t_sec.`fk_id_section` = t_acsc.`fk_id_section`
		WHERE t_home.`id_member_home` = :id AND t_home.`activity_flg` = 1 AND t_sec.`del_flg` <> 1 AND t_acsc.`fk_id_gimic_page` IS NOT NULL
		');
		$stmt -> bindValue(':id', $f_arr['id'], PDO::PARAM_INT);
		$stmt -> execute();
		$a_temp = $stmt -> fetchAll();
		F_pmsnCheckSort($ret_arr, $a_temp);

		//会員個人権限
		$stmt = $pdo -> prepare('
		SELECT t_acsc.`limit_hall` AS hall, t_acsc.`kind_process` AS kind, t_acsc.`fk_id_gimic_page` AS page
		FROM `members_home` AS t_home
		LEFT JOIN `access_link_member` AS t_acsc
		ON t_home.`id_member_home` = t_acsc.`fk_id_member_home`
		WHERE t_home.`id_member_home` = :id AND t_home.`activity_flg` = 1 AND t_acsc.`fk_id_gimic_page` IS NOT NULL
		');
		$stmt -> bindValue(':id', $f_arr['id'], PDO::PARAM_INT);
		$stmt -> execute();
		$a_temp = $stmt -> fetchAll();
		F_pmsnCheckSort($ret_arr, $a_temp);

	}elseif($f_arr['kind'] == 2){
		//職員ログイン時
		//職種権限
		$stmt = $pdo -> prepare('
		SELECT t_acsc.`limit_hall` AS hall, t_acsc.`kind_process` AS kind, t_acsc.`fk_id_gimic_page` AS page
		FROM `staff` as t_staff
		LEFT JOIN `access_link_posts` as t_acsc
		ON t_staff.`fk_id_staff_post` = t_acsc.`fk_id_staff_post`
		WHERE t_staff.`id_staff` = :id AND t_staff.`activity_flg` = 1 AND t_acsc.`fk_id_gimic_page` IS NOT NULL
		');
		$stmt -> bindValue(':id', $f_arr['id'], PDO::PARAM_INT);
		$stmt -> execute();
		$a_temp = $stmt -> fetchAll();
		F_pmsnCheckSort($ret_arr, $a_temp);

		//職員個人権限
		$stmt = $pdo -> prepare('
		SELECT t_acsc.`limit_hall` AS hall, t_acsc.`kind_process` AS kind, t_acsc.`fk_id_gimic_page` AS page
		FROM `staff` AS t_staff
		LEFT JOIN `access_link_staff` AS t_acsc
		ON t_staff.`id_staff` = t_acsc.`fk_id_staff`
		WHERE t_staff.`id_staff` = :id AND t_staff.`activity_flg` = 1 AND t_acsc.`fk_id_gimic_page` IS NOT NULL
		');
		$stmt -> bindValue(':id', $f_arr['id'], PDO::PARAM_INT);
		$stmt -> execute();
		$a_temp = $stmt -> fetchAll();
		F_pmsnCheckSort($ret_arr, $a_temp);

	}
	return $ret_arr;
}

/*:::::::::::::::::::::::::::::::
権限付与情報取得→配列にまとめ

権限、他館アクセス値を配列にまとめる
各値は大なりで上書き

引数
&$f_ret = array[ギミックページid] = array('hall'=>他館アクセス制限 0=無、1=有, 'kind'=>処理権限 0=参照、1=参照、更新　2=管理者)
$f_pmsn = array('hall'=>他館アクセス制限 0=無、1=有, 'kind'=>処理権限 0=参照、1=参照、更新　2=管理者, 'page'=>ギミックページid);

戻り値
紐付き権限情報、参照登録
$ret_arr = array[ギミックページid] = array('hall'=>他館アクセス制限 0=無、1=有, 'kind'=>処理権限 0=参照、1=参照、更新　2=管理者)
:::::::::::::::::::::::::::::::*/
function F_pmsnCheckSort(&$f_ret, $f_pmsn){
	foreach($f_pmsn as $val){
		if(!isset($f_ret[$val['page']])){
			$f_ret[$val['page']]['hall'] = 1;
			$f_ret[$val['page']]['kind'] = 0;
		}
		if($f_ret[$val['page']]['hall'] > (int)$val['hall']){
			$f_ret[$val['page']]['hall'] = (int)$val['hall'];
		}
		if($f_ret[$val['page']]['kind'] < (int)$val['kind']){
			$f_ret[$val['page']]['kind'] = (int)$val['kind'];
		}
	}
}

/*:::::::::::::::::::::::::::::::
ページングリンク作成

引数
$f_count 全項目数
$f_dispCount 1ページ表示項目数
$f_pageNo 指定表示ページ
$f_link リンク
$f_id 画面内jump_id
$f_getName get名

戻り値
ページングリンクテキスト
:::::::::::::::::::::::::::::::*/
function F_makePaging($f_count, $f_dispCount, $f_pageNo, $f_link, $f_id, $f_getName){
	$ret_tex = "";

	$backFirst = "<span class=\"col_gray\">|<</span>";
	$backOne = "<span class=\"col_gray\"><</span>";
	$ffLast = "<span class=\"col_gray\">>|</span>";
	$ffOne = "<span class=\"col_gray\">></span>";
	$f_jump = "";	//画面内ジャンプ
	$f_numbers = "";

	if($f_id){
		$f_jump = "#" . $f_id;
	}

	$allPage = ceil($f_count / $f_dispCount);	//全ページ数

	if($f_pageNo > $allPage){
		//指定ページオーバー指定
		$f_pageNo = $allPage;
	}

	if($f_pageNo > 1){	//トップページ
		$backFirst = "<a href=\"{$f_link}?{$f_getName}=1{$f_jump}\">|<</a>";
	}
	if($f_pageNo < $allPage){	//ラストページ
		$ffLast = "<a href=\"{$f_link}?{$f_getName}={$allPage}{$f_jump}\">>|</a>";
	}
	if($f_pageNo > 1){	//前ページ
		$backOne = "<a href=\"{$f_link}?{$f_getName}=" . ($f_pageNo - 1) . "{$f_jump}\"><</a>";
	}
	if($f_pageNo < $allPage){	//次ページ
		$ffOne = "<a href=\"{$f_link}?{$f_getName}=" . ($f_pageNo + 1) . "{$f_jump}\">></a>";
	}

	//初ページ
	if($f_pageNo == 1){
		$f_numbers .= "<b>1</b>";
	}else{
		$f_numbers .= "<a href=\"{$f_link}?{$f_getName}=1{$f_jump}\">1</a>";
	}
	$f_numbers .= " ";

	if($f_pageNo - GV_numberOfDispPage - 1 > 1){
		$f_numbers .= "… ";
	}

	//中ページ
	if($allPage > 2){
		for($i = 2; $i < $allPage; $i++){
			if($i < ($f_pageNo - GV_numberOfDispPage) || $i > ($f_pageNo + GV_numberOfDispPage)){
				continue;
			}

			if($f_pageNo == $i){
				$f_numbers .= "<b>{$i}</b>";
			}else{
				$f_numbers .= "<a href=\"{$f_link}?{$f_getName}={$i}{$f_jump}\">{$i}</a>";
			}
			$f_numbers .= " ";
		}
	}

	if($f_pageNo + GV_numberOfDispPage + 1 < $allPage){
		$f_numbers .= "… ";
	}

	//末ページ
	if($allPage > 1){
		if($f_pageNo == $allPage){
			$f_numbers .= "<b>{$allPage}</b>";
		}else{
			$f_numbers .= "<a href=\"{$f_link}?{$f_getName}={$allPage}{$f_jump}\">{$allPage}</a>";
		}
	}

	$ret_tex = $backFirst . "　" . $backOne . "　" . $f_numbers . "　" . $ffOne . "　" . $ffLast;
	return $ret_tex;
}

/*:::::::::::::::::::::::::::::::
カレンダー計算

指定年月の1日から最終日、曜日を配列で返す

引数
$f_year 年
$f_month 月

戻り値
arr[日(d)]=array('id'=>曜日id, 'week'=>曜日);
:::::::::::::::::::::::::::::::*/
function F_calcCalendar($f_year, $f_month){
	$ret_arr = array();
	$week = array("日", "月", "火", "水", "木", "金", "土");

	$date = $f_year . '-' . $f_month . '-01';
	$last_day = date('t', strtotime($date));
	$datetime = new DateTime($date);
	$week_id = (int)$datetime -> format('w');

	for($i = 1; $i <= $last_day; $i++){
		$ret_arr[$i] = array('id' => $week_id, 'week' => $week[$week_id]);
		++$week_id > 6 && $week_id = 0;
	}
	return $ret_arr;
}

/*:::::::::::::::::::::::::::::::
カレンダー計算(直近)

指定範囲年月日の開始日から最終日、曜日を配列で返す

引数
$f_date 開始日(yyyy-mm-dd)
$f_count 範囲(日数)

戻り値
arr[日(yyyy-mm-dd)]=array('id'=>曜日id, 'week'=>曜日);
:::::::::::::::::::::::::::::::*/
function F_calcCalendar_short($f_date, $f_count){
	$ret_arr = array();
	$week = array("日", "月", "火", "水", "木", "金", "土");

	$datetime = new DateTime($f_date);
	$plus = 0;
	for($i = 0; $i <= $f_count; $i++){
		$date = $datetime -> modify('+' . $plus . ' day') -> format('Y-m-d');
		$week_id = (int)$datetime -> format('w');
		$ret_arr[$date] = array('id' => $week_id, 'week' => $week[$week_id]);
		$plus = 1;
	}
	return $ret_arr;
}

/*:::::::::::::::::::::::::::::::
画像リサイズ値計算(最大サイズ収め、拡大あり)

元画像のリサイズ値を縦横比を維持し作成最大サイズに収まるよう計算する
隙間が空くことあり

引数
$max_x 作成最大値幅
$max_y 作成最大値高さ
$arc_x 元画像幅
$arc_y 元画像高さ

戻り値
arr(リサイズx値, リサイズy値);
:::::::::::::::::::::::::::::::*/
function F_calcPicResizeFit($max_x, $max_y, $arc_x, $arc_y){
	$ret_arr = array();

	$rate_x = $max_x / $arc_x;
	$rate_y = $max_y / $arc_y;

	$calc = $rate_x;
	($rate_y < $rate_x) && $calc = $rate_y;

	$new_x = $arc_x * $calc;
	$new_y = $arc_y * $calc;

	$ret_arr = array($new_x, $new_y);
	return $ret_arr;
}

/*:::::::::::::::::::::::::::::::
画像リサイズ値計算(最大サイズ収め、拡大なし)

元画像のリサイズ値を縦横比を維持し作成最大サイズに収まるよう計算する
元画像を拡大はしない

引数
$max_x 作成最大値幅
$max_y 作成最大値高さ
$arc_x 元画像幅
$arc_y 元画像高さ

戻り値
arr(リサイズx値, リサイズy値);
:::::::::::::::::::::::::::::::*/
function F_calcPicResizeFitNoEx($max_x, $max_y, $arc_x, $arc_y){
	$ret_arr = array($arc_x, $arc_y);

	$rate_x = $arc_x / $max_x;
	$rate_y = $arc_y / $max_y;

	$calc = $rate_x;
	($rate_y > $rate_x) && $calc = $rate_y;

	if($calc > 1){
		$new_x = $arc_x / $calc;
		$new_y = $arc_y / $calc;
		$ret_arr = array($new_x, $new_y);
	}
	return $ret_arr;
}

/*:::::::::::::::::::::::::::::::
画像リサイズ値計算(最大サイズ埋め)

元画像のリサイズ値を縦横比を維持し作成最大サイズに隙間なく表示するよう計算する
はみ出す場合あり

引数
$max_x 作成最大値幅
$max_y 作成最大値高さ
$arc_x 元画像幅
$arc_y 元画像高さ

戻り値
arr(リサイズx値, リサイズy値);
:::::::::::::::::::::::::::::::*/
function F_calcPicResizeFill($max_x, $max_y, $arc_x, $arc_y){
	$ret_arr = array();

	$rate_x = $max_x / $arc_x;
	$rate_y = $max_y / $arc_y;

	$calc = $rate_x;
	($rate_y > $rate_x) && $calc = $rate_y;

	$new_x = $arc_x * $calc;
	$new_y = $arc_y * $calc;

	$ret_arr = array($new_x, $new_y);
	return $ret_arr;
}

/*:::::::::::::::::::::::::::::::
画像アップロード

アップロード画像を指定サイズにリサイズし格納する

引数
$f_upFile 画像ファイルpost名
$f_path 格納パス
$f_file 格納画像名(ドット以下拡張子なし)
$max_x 格納画像最大値幅
$max_y 格納画像最大値高さ
$expansion 拡大種別
	0=最大サイズ収め、拡大なし			F_calcPicResizeFitNoEx
	1=最大サイズ収め、拡大あり			F_calcPicResizeFit
	2=最大サイズ埋め、はみ出しあり	F_calcPicResizeFill
	3=オリジナルサイズ
$base_size 画像格納サイズ	0=拡縮後画像サイズ 1=指定最大サイズ

戻り値
正常終了 arr(true, 格納ファイル名);
エラー時 arr(false, エラーメッセージ);
:::::::::::::::::::::::::::::::*/
function F_picUp($f_upFile, $f_path, $f_file, $max_x, $max_y, $expansion = 0, $base_size = 0){
	$ret = array(true, 'filename');
	$tmp_fileName = '';

	//ファイルアップ確認
	if(!isset($_FILES[$f_upFile]) || !is_uploaded_file($_FILES[$f_upFile]['tmp_name'])){
		$ret = array(false, 'ファイルアップロードエラー');
		return $ret;
	}
	$tmp_fileName = $_FILES[$f_upFile]['tmp_name'];

	//アップ画像obj作成
	list($pic_arc_x, $pic_arc_y, $pic_type) = getimagesize($tmp_fileName);
	$flg_pic_type = false;
	switch($pic_type){
		case 1:	//IMAGETYPE_GIF:
			$f_file .= '.gif';
			$image_arc = imagecreatefromgif($tmp_fileName);
			$flg_pic_type = true;
			break;
		case 2:	//IMAGETYPE_JPEG:
			$f_file .= '.jpg';
			$image_arc = imagecreatefromjpeg($tmp_fileName);
			$flg_pic_type = true;
			break;
		case 3:	//IMAGETYPE_PNG:
			$f_file .= '.png';
			$image_arc = imagecreatefrompng($tmp_fileName);
			$flg_pic_type = true;
			break;
		default:
			if(!$flg_pic_type){
				$ret = array(false, '画像ファイルを選択してください(jpeg, gif, pngが使用可)');
				return $ret;
			}
	}

	//画像リサイズ、格納
	$flg_pic_up = false;
	//リサイズ値計算
	if($expansion == 0){
		list($new_x, $new_y) = F_calcPicResizeFitNoEx($max_x, $max_y, $pic_arc_x, $pic_arc_y);
	}elseif($expansion == 1){
		list($new_x, $new_y) = F_calcPicResizeFit($max_x, $max_y, $pic_arc_x, $pic_arc_y);
	}elseif($expansion == 2){
		list($new_x, $new_y) = F_calcPicResizeFill($max_x, $max_y, $pic_arc_x, $pic_arc_y);
	}elseif($expansion == 3){
		$new_x = $pic_arc_x;
		$new_y = $pic_arc_y;
	}
	if($base_size == 0){
		$image_put = ImageCreateTrueColor($new_x, $new_y);
	}elseif($base_size == 1){
		$image_put = ImageCreateTrueColor($max_x, $max_y);
	}
	$col_white = imagecolorallocatealpha($image_put, 255, 255, 255, 127);
	imagefill($image_put, 0, 0, $col_white);

	imagecopyresampled(
		$image_put, $image_arc,
		0, 0, 0, 0,
		$new_x, $new_y,
		$pic_arc_x, $pic_arc_y
	);
	switch($pic_type){
		case 1:	//IMAGETYPE_GIF:
			if(imagegif($image_put, $f_path . '/' . $f_file)){
				$flg_pic_up = true;
			}
			break;
		case 2:	//IMAGETYPE_JPEG:
			if(imagejpeg($image_put, $f_path . '/' . $f_file, 85)){
				$flg_pic_up = true;
			}
			break;
		case 3:	//IMAGETYPE_PNG:
			if(imagepng($image_put, $f_path . '/' . $f_file)){
				$flg_pic_up = true;
			}
			break;
		default:
			imagedestroy($image_put);
			if(!$flg_pic_up){
				$ret = array(false, 'アップロードに失敗しました、画像のフォーマットを確認するか、他の画像ファイルでお試しください');
				return $ret;
			}
	}

	$ret = array(true, $f_file);
	return $ret;
}

/*:::::::::::::::::::::::::::::::
フォルダコピー

指定コピー先フォルダが無ければ設置する
コピー元ディレクトリ以下を再帰コピーする
再帰中エラーで以降動作停止false返し

引数
$orig_dir	元ディレクトリパス
$new_dir	先ディレクトリパス
$flg	動作結果引継ぎ 正常=true, 異常=false

戻り値
true 正常終了
false エラー
:::::::::::::::::::::::::::::::*/
function F_dir_copy($orig_dir, $new_dir, $flg = true){
	if($flg === true){

		if(!is_dir($orig_dir)){
			return false;
		}

		if(!is_dir($new_dir)){
			if(!mkdir($new_dir, 0755)){
				return false;
			}
		}

		if(!($hand = opendir($orig_dir))){
			return false;
		}

		while(($file_handle = readdir($hand)) !== false){
			if($file_handle == "." || $file_handle == ".."){
				continue;
			}
			$orig_file = $orig_dir . "/" . $file_handle;
			$copy_file = $new_dir . "/" . $file_handle;
			if(is_dir($orig_file)){
				$flg = F_dir_copy($orig_file, $copy_file, $flg);
			}else{
				$flg = copy($orig_file, $copy_file);
			}

			if(!$flg){
				closedir($hand);
				return $flg;
			}
		}
		closedir($hand);
	}
	return $flg;
}

/*:::::::::::::::::::::::::::::::
ファイルテキスト置換

ファイル内の検索テキストを置換する
合致するテキスト全てを置換する

引数
$file	対象ファイル
$replace_orig	検索テキスト
$replace_value	置換テキスト

戻り値
true 正常終了
false エラー
:::::::::::::::::::::::::::::::*/
function F_textReplacement($file, $replace_orig, $replace_value){
	if($str = file_get_contents($file)){
		$str = str_replace($replace_orig, $replace_value, $str);
		if(file_put_contents($file, $str)){
			return true;
		}
	}
	return false;
}

/*:::::::::::::::::::::::::::::::
バーコード変換

数値列をバーコードに変換する

引数
$code_class	バーコードクラスインスタンス
$code	変換値(数値のみ)

戻り値
arr(バーコード配列, ディジット付加コード)
:::::::::::::::::::::::::::::::*/
function F_confBarcode($code_class, $code){
	$ret_arr = array('', '');
	$bar = $code_class -> convert($code);
	for($i = 0; $i < strlen($bar); ++$i){
		if($bar{$i} == '0'){
			$ret_arr[0] .= '<div class="barcode0"></div>';
		}elseif($bar{$i} == '1'){
			$ret_arr[0] .= '<div class="barcode1"></div>';
		}
	}
	$ret_arr[1] = $code_class -> code;
	return $ret_arr;
}

/*:::::::::::::::::::::::::::::::
機能操作ログ記録

機能操作者、内容を記録する

引数
$workerId		作業者id
$workerKind	作業者種別(1=会員、2=職員)
$file				作業ページ
$gimmicId		機能id
$kind				操作種
$val_1			操作値1
$val_2			操作値2
$val_3			操作値3
$val_4			操作値4
$val_5			操作値5

戻り値
なし
:::::::::::::::::::::::::::::::*/
function F_workLog($workerId, $workerKind, $file, $gimmicId = 0, $kind = '', $val_1 = '', $val_2 = '', $val_3 = '', $val_4 = '', $val_5 = ''){
	global $G_ip;
	global $pdo;

	$stmt = $pdo -> prepare('
INSERT INTO `work_log`(
`worker_id`, `worker_kind`, `file_name`, `gimmick_name`, `ip_worker`, `gimmick_id`, `value_1`, `value_2`, `value_3`, `value_4`, `value_5`
)
VALUES(
:id, :w_kind, :file, :kind, IF(IS_IPV6(:ip), INET6_ATON(:ip), INET_ATON(:ip)), :g_id, :val_1, :val_2, :val_3, :val_4, :val_5
)
	');

	$stmt -> bindValue(':id', $workerId, PDO::PARAM_INT);
	$stmt -> bindValue(':w_kind', $workerKind, PDO::PARAM_INT);
	$stmt -> bindValue(':file', $file, PDO::PARAM_STR);
	$stmt -> bindValue(':kind', $kind, PDO::PARAM_STR);
	$stmt -> bindValue(':ip', $G_ip, PDO::PARAM_INT);
	$stmt -> bindValue(':g_id', $gimmicId, PDO::PARAM_INT);
	$stmt -> bindValue(':val_1', $val_1, PDO::PARAM_STR);
	$stmt -> bindValue(':val_2', $val_2, PDO::PARAM_STR);
	$stmt -> bindValue(':val_3', $val_3, PDO::PARAM_STR);
	$stmt -> bindValue(':val_4', $val_4, PDO::PARAM_STR);
	$stmt -> bindValue(':val_5', $val_5, PDO::PARAM_STR);
	$stmt -> execute();

}

/*:::::::::::::::::::::::::::::::
ログインログ記録

ログインログアウト情報を記録する

引数
$file	アクセス元ページ
$login_kind	1=ログイン成功 2=ログイン失敗 3=ログアウト
$id	入力id
$pass	入力pass
$member_kind	利用者種別 1=会員 2=職員
$member_id	利用者id

戻り値
なし
:::::::::::::::::::::::::::::::*/
function F_loginLog($file, $login_kind, $id, $pass, $member_kind, $member_id){
	global $G_ip;
	global $pdo;

	$stmt = $pdo -> prepare('
INSERT INTO `login_log`(
`file_name`, `ip_worker`, `login_kind`, `value_id`, `value_pass`, `member_kind`, `member_id`
)
VALUES(
:file, IF(IS_IPV6(:ip), INET6_ATON(:ip), INET_ATON(:ip)), :login_kind, :id, :pass, :member_kind, :member_id
)
	');

	$stmt -> bindValue(':file', $file, PDO::PARAM_STR);
	$stmt -> bindValue(':ip', $G_ip, PDO::PARAM_INT);
	$stmt -> bindValue(':login_kind', $login_kind, PDO::PARAM_INT);
	$stmt -> bindValue(':id', $id, PDO::PARAM_STR);
	$stmt -> bindValue(':pass', $pass, PDO::PARAM_STR);
	$stmt -> bindValue(':member_kind', $member_kind, PDO::PARAM_INT);
	$stmt -> bindValue(':member_id', $member_id, PDO::PARAM_INT);
	$stmt -> execute();

}

/*:::::::::::::::::::::::::::::::
ファイルアップロード

アップロードファイルを格納する

引数
$f_upFile ファイルpost名
$f_path 格納パス
$f_file 格納画像名(ドット以下拡張子なし)

戻り値
正常終了 arr(true, 格納ファイル名);
エラー時 arr(false, エラーメッセージ);
:::::::::::::::::::::::::::::::*/
function F_fileUp($f_upFile, $f_path, $f_file){
	$ret = array(true, 'filename');
	$tmp_fileName = '';
	//ファイルアップ確認
	if(!isset($_FILES[$f_upFile]) || !is_uploaded_file($_FILES[$f_upFile]['tmp_name'])){
		$ret = array(false, 'ファイルアップロードエラー');
		return $ret;
	}
	$tmp_fileName = $_FILES[$f_upFile]['tmp_name'];
	$file_type = pathinfo($_FILES[$f_upFile]["name"], PATHINFO_EXTENSION);

	//ファイルタイプチェック
	if(!($file_type == "pdf" || $file_type == "PDF")){
		$ret = array(false, 'PDFファイルを選択してください');
		return $ret;
	}

	$f_file_name = $f_file . '.' . $file_type;
	$flg_file_up = move_uploaded_file($_FILES[$f_upFile]['tmp_name'], $f_path . '/' . $f_file_name);

	if(!$flg_file_up){
		$ret = array(false, 'アップロードに失敗しました、ファイルのフォーマット、サイズを確認するか、他のファイルでお試しください');
		return $ret;
	}

	$ret = array(true, $f_file_name);
	return $ret;
}

?>
