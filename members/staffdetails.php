<?php
//職員詳細情報管理
session_start();

//基本設定関数
require_once __DIR__.'/../basic_function.php';
//DB接続
require_once __DIR__ . '/../dbip.php';

//ヘッダDB情報ロード
require_once __DIR__ . '/../head_dbread.php';

//ログイン確認
//array('id' => 個人識別id, 'kind' => 会員種別(1 会員, 2 職員), 'hall' => 所属館id)
if(!$G_login || $G_login['kind'] != 2){
  header('Location: /', true, 301);
}

//個人識別id
$P_staffid = $G_login['id'];

//都道府県
$G_ken = array('北海道','青森県','岩手県','宮城県','秋田県','山形県','福島県','茨城県','栃木県','群馬県','埼玉県','千葉県','東京都','神奈川県','新潟県','富山県','石川県','福井県','山梨県','長野県','岐阜県','静岡県','愛知県','三重県','滋賀県','京都府','大阪府','兵庫県','奈良県','和歌山県','鳥取県','島根県','岡山県','広島県','山口県','徳島県','香川県','愛媛県','高知県','福岡県','佐賀県','長崎県','熊本県','大分県','宮崎県','鹿児島県','沖縄県');

//post値格納変数初期化
$P_gimmick_type = "details"; //操作種別
$P_mail = "";			 //ログインid併用メアド
$P_zip = "";			//郵便番号
$P_ken = "埼玉県";	//都道府県
$P_address = "";	//住所
$P_hall_no = 0;		//所属館
$P_name1 = "";		//職員名字
$P_name2 = "";		//職員名前
$P_kana1 = "";		//職員名字カナ
$P_kana2 = "";		//職員名前カナ
$P_phone = "";		//電話番号
$P_ent_hall = "";		//就職日
$P_lev_hall = "";		//退職日
$P_post_no = 0;		//職種id
$P_nickname = "";	//あだ名
$P_comment = ""; //自己紹介

//post値取得
isset($_POST["gimmick_type"]) && $P_gimmick_type = $_POST["gimmick_type"];
isset($_POST["zip"]) && $P_zip = F_h($_POST["zip"]);
isset($_POST["ken"]) && $P_ken = F_h($_POST["ken"]);
isset($_POST["address"]) && $P_address = F_h($_POST["address"]);
isset($_POST["mail"]) && $P_mail = F_h($_POST["mail"]);
isset($_POST["hall"]) && $G_select_hall = $P_hall_no = $_POST["hall"];
isset($_POST["name1"]) && $P_name1 = F_h($_POST["name1"]);
isset($_POST["name2"]) && $P_name2 = F_h($_POST["name2"]);
isset($_POST["kana1"]) && $P_kana1 = F_h($_POST["kana1"]);
isset($_POST["kana2"]) && $P_kana2 = F_h($_POST["kana2"]);
isset($_POST["tel"]) && $P_phone = F_h($_POST["tel"]);
isset($_POST["ent_hall"]) && $P_ent_hall = F_h($_POST["ent_hall"]);
isset($_POST["lev_hall"]) && $P_lev_hall = F_h($_POST["lev_hall"]);
isset($_POST["post_no"]) && $P_post_no = $_POST["post_no"];
isset($_POST["nickname"]) && $P_nickname = F_h($_POST["nickname"]);
isset($_POST["comment"]) && $P_comment = F_h($_POST["comment"]);

//操作実行
$G_workmes = "";
$G_err_flg = false;

if($P_gimmick_type == "up"){
	//情報更新
	//入力値チェック
	//メアド確認
	if(!$P_mail || !F_isEmail($P_mail)){
		$G_err_flg = true;
		$G_workmes .= '<span class="col_red">※メールアドレスを入力、確認してください</span><br>';
	}

	//職員氏名確認
	if(!$P_name1 || !$P_name2 || !$P_kana1 || !$P_kana2){
		$G_err_flg = true;
		$G_workmes .= '<span class="col_red">※職員氏名、カナをすべて入力してください</span><br>';
	}
	//就職日確認
	$t_datecheck = F_isDate($P_ent_hall);
	if($t_datecheck === false){
		$G_err_flg = true;
		$G_workmes .= '<span class="col_red">※就職日を入力、確認してください</span><br>';
	}

	if($G_err_flg){
		//入力値エラーあり
		$P_gimmick_type = "error";
		$G_workmes .= '<span class="col_red">職員情報は更新されていません</span>';
	}else{
		//メアド重複確認
		$stmt = $pdo -> prepare('SELECT COUNT(*) FROM `staff` WHERE `id_staff` <> :staff_id AND `login_id` = :login_id');
		$stmt -> bindValue(':staff_id', $P_staffid, PDO::PARAM_INT);
		$stmt -> bindValue(':login_id', $P_mail, PDO::PARAM_STR);
		$stmt -> execute();
		IF($stmt -> fetchColumn()){
			$G_err_flg = true;
			$G_workmes .= '<span class="col_red">※このメールアドレスは既に使用されています</span><br>';
			$G_workmes .= '<span class="col_red">職員情報は更新されていません</span>';
			$P_gimmick_type = "error";
		}else{
			//職員情報更新実行
			$stmt = $pdo -> prepare('UPDATE `staff`
			SET `fk_id_staff_post` = :post, `fk_id_hall` = :hall, `login_id` = :id, `zip` = :zip, `prefecture` = :prefecture, `address` = :address, `surname` = :sname, `firstname` = :fname, `surname_kana` = :sname_k, `firstname_kana` = :fname_k, `enterhall_date` = :ent_hall, `phone` = :phone, `nickname` = :nickname, `comment` = :comment
			WHERE `id_staff` = :staff_id');
			$stmt -> bindValue(':post', $P_post_no, PDO::PARAM_INT);
			$stmt -> bindValue(':hall', $P_hall_no, PDO::PARAM_INT);
			$stmt -> bindValue(':id', $P_mail, PDO::PARAM_STR);
			$stmt -> bindValue(':zip', $P_zip, PDO::PARAM_STR);
			$stmt -> bindValue(':prefecture', $P_ken, PDO::PARAM_STR);
			$stmt -> bindValue(':address', $P_address, PDO::PARAM_STR);
			$stmt -> bindValue(':sname', $P_name1, PDO::PARAM_STR);
			$stmt -> bindValue(':fname', $P_name2, PDO::PARAM_STR);
			$stmt -> bindValue(':sname_k', $P_kana1, PDO::PARAM_STR);
			$stmt -> bindValue(':fname_k', $P_kana2, PDO::PARAM_STR);
			$stmt -> bindValue(':ent_hall', $P_ent_hall, PDO::PARAM_STR);
			$stmt -> bindValue(':phone', $P_phone, PDO::PARAM_STR);
			$stmt -> bindValue(':staff_id', $P_staffid, PDO::PARAM_INT);
			$stmt -> bindValue(':nickname', $P_nickname, PDO::PARAM_STR);
			$stmt -> bindValue(':comment', $P_comment, PDO::PARAM_STR);
			$stmt -> execute();

			$G_workmes = '<span class="col_blue">職員[' . $P_name1 . $P_name2 . ']さんの情報を更新しました</span>';

			//post値格納変数初期化
			$P_gimmick_type = "details";
		}
	}
}elseif($P_gimmick_type == "lev"){
	//退職処理
	//就職日確認
	$t_datecheck = F_isDate($P_lev_hall);
	if($t_datecheck === false){
		$G_err_flg = true;
		$G_workmes .= '<span class="col_red">※退職日を入力、確認してください<br>退職処理はされていません</span><br>';
		$P_gimmick_type = "details";
	}else{
		//退職処理実行
		$stmt = $pdo -> prepare('UPDATE `staff` SET `activity_flg` = 0, `leave_date` = :lev_date WHERE `id_staff` = :staff_id');
		$stmt -> bindValue(':lev_date', $P_lev_hall, PDO::PARAM_STR);
		$stmt -> bindValue(':staff_id', $P_staffid, PDO::PARAM_INT);
		$stmt -> execute();

		$G_workmes .= '<span class="col_blue">※指定職員を退職処理しました</span><br>';
	}

}

if($P_gimmick_type == "details"){
	//DB登録情報取得
	$stmt = $pdo -> prepare('SELECT * FROM `staff` WHERE `id_staff` = :staff_id');
	$stmt -> bindValue(':staff_id', $P_staffid, PDO::PARAM_INT);
	$stmt -> execute();
	$t_staff = $stmt -> fetchAll()[0];

	$P_mail = $t_staff['login_id'];
	$P_zip = $t_staff['zip'];
	$P_ken = $t_staff['prefecture'];
	$P_address = $t_staff['address'];
	$P_hall_no = $t_staff['fk_id_hall'];
	$P_name1 = $t_staff['surname'];
	$P_name2 = $t_staff['firstname'];
	$P_kana1 = $t_staff['surname_kana'];
	$P_kana2 = $t_staff['firstname_kana'];
	$P_phone = $t_staff['phone'];
	$P_ent_hall = $t_staff['enterhall_date'];
	$P_post_no = $t_staff['fk_id_staff_post'];
	$P_nickname = $t_staff['nickname'];
	$P_comment = $t_staff['comment'];
}

//館情報取得
$stmt = $pdo -> prepare('SELECT `id_hall`, `name_hall` FROM `halls` WHERE `activity_flg` = 1 ORDER BY `id_hall` ASC');
$stmt -> execute();
$hall_names = $stmt -> fetchAll();

//職種情報取得
$stmt = $pdo -> prepare('SELECT `id_staff_post`, `post` FROM `staff_posts` WHERE `del_flg` <> 1 ORDER BY `id_staff_post` ASC');
$stmt -> execute();
$posts = $stmt -> fetchAll();

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="googlebot" content="noindex">
<meta NAME=”robots” CONTENT=”noindex,NOFOLLOW,NOARCHIVE”>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="description" content="<?php echo $G_npo_items['name_npo']; ?>">
<meta name="keywords" content="">
<link rel="stylesheet" href="/css/base.css?<?php echo date("Ymd-Hi"); ?>">
<link rel="stylesheet" media="screen and (max-width:800px)" href="/css/base_sp.css?<?php echo date("Ymd-Hi"); ?>">
<link rel="stylesheet" href="./css/members.css?<?php echo date("Ymd-Hi"); ?>">
<title><?php echo $G_npo_items['name_npo']; ?>登録情報確認</title>
</head>
<body>
<div id="pagebody">

<!-- header -->
<?php
include "./../head.php";
?>

	<!-- info left -->
<?php
include "./../left.php"
?>

	<!-- info main -->
	<div id="info">

<!-- 入力 -->
		<h3>登録情報確認</h3>
		<p>
			<a href = "./mypage.php" target = "_self">・マイページへ戻る</a><br>
		</p>
		<hr>
<?php
echo $G_workmes;

if($P_gimmick_type == "details" || $P_gimmick_type == "error"){
//指定職員詳細表示
//都道府県セレクト
$t_select_ken = '<select name="ken">';
	$t_selected = "";
	!$P_ken && $t_selected = " selected";
	$t_select_ken .= '<option value=""' . $t_selected . '>都道府県</option>';
foreach($G_ken as $val){
	$t_selected = "";
	if($val == $P_ken){ $t_selected = " selected"; };
	$t_select_ken .= "<option value=\"{$val}\"{$t_selected}>{$val}</option>";
}
$t_select_ken .= "</select>";

//所属館セレクト
$t_select_hall = '<select name="hall">';
foreach($hall_names as $val){
	if($val['id_hall'] != $P_hall_no){
		continue;
	}
	$t_select_hall .= "<option value=\"{$val['id_hall']}\">{$val['name_hall']}</option>";
}
$t_select_hall .= "</select>";

//職種セレクト
$t_select_post = '<select name="post_no">';
foreach($posts as $val){
	if($val['id_staff_post'] != $P_post_no){
		continue;
	};
	$t_select_post .= "<option value=\"{$val['id_staff_post']}\">{$val['post']}</option>";
}
$t_select_post .= "</select>";


?>
		<p>
			<form name="fm_data" action="" method="post" onsubmit="return false;">
				<h3>指定職員情報</h3>
				<table class = "config">
					<tr>
						<td>所属館</td>
						<td>
							<?php echo $t_select_hall; ?>
						</td>
					</tr>
					<tr>
						<td>職種</td>
						<td>
							<?php echo $t_select_post; ?>
						</td>
					</tr>
					<tr>
						<td><span class="col_red">※</span>メールアドレス<br>(ログインid併用)</td>
						<td>
							<input type="text" name="mail" size="50" value="<?php echo $P_mail; ?>" placeholder="メールアドレス">
						</td>
					</tr>
					<tr>
						<td>住所</td>
						<td>
							〒<input type="text" name="zip" size="7" value="<?php echo $P_zip; ?>" placeholder="xxxxxxx"><span class="t80per">(ハイフン無し数字のみ入力)</span><br>
							<?php echo $t_select_ken; ?><br>
							<input type="text" name="address" size="70" value="<?php echo $P_address; ?>" placeholder="住所">
						</td>
					</tr>
					<tr>
						<td><span class="col_red">※</span>職員氏名</td>
						<td>
							名字:<input type="text" name="name1" size="20" value="<?php echo $P_name1; ?>" placeholder="名字">
							名前:<input type="text" name="name2" size="20" value="<?php echo $P_name2; ?>" placeholder="名前"><br>
							カナ:<input type="text" name="kana1" size="20" value="<?php echo $P_kana1; ?>" placeholder="ミョウジ">
							カナ:<input type="text" name="kana2" size="20" value="<?php echo $P_kana2; ?>" placeholder="ナマエ">
						</td>
					</tr>
					<tr>
						<td>あだ名、自己紹介</td>
						<td>
							あだ名:<input type="text" name="nickname" size="20" value="<?php echo $P_nickname; ?>" placeholder="あだ名"><br>
自己紹介:<textarea rows="5" cols="70" name= "comment" placeholder="ホームページ表示指導員からテキスト"><?php echo $P_comment; ?></textarea>
						</td>
					</tr>
					<tr>
						<td>電話番号</td>
						<td>
							<input type="text" name="tel" size="20" value="<?php echo $P_phone; ?>">
						</td>
					</tr>
					<tr>
						<td><span class="col_red">※</span>入所日</td>
						<td>
							<?php echo $P_ent_hall; ?>
						</td>
					</tr>
				</table>
				<input type="button" value="更新" onClick="submit();">
				<input type="hidden" name="gimmick_type" value="up">
				<input type="hidden" name="staffid" value=<?php echo $P_staffid; ?>>
				<input type="hidden" name="ent_hall" value="<?php echo $P_ent_hall; ?>">
			</form>
		</p>
<?php
}
?>
		<hr>
	</div>

<!-- footer -->
<?php
include "./../foot.php";
?>

</div>
</body>
</html>
