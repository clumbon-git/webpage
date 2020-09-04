<?php
//新規会員登録
session_start();

//管理ページno
$G_gimmickno = 7;

//基本設定関数
require_once __DIR__.'/../basic_function.php';
//DB接続
require_once __DIR__ . '/../dbip.php';

//ヘッダDB情報ロード
require_once __DIR__ . '/../head_dbread.php';

//ログイン確認
//array('id' => 個人識別id, 'kind' => 会員種別(1 会員, 2 職員), 'hall' => 所属館id)
if(!$G_login){
  header('Location: /', true, 301);
}

//権限確認 Array[ギミックページid]=array( [hall] =>他館許可値 0=許可、1=制限 [kind] =>権限値 0=参照、1=参照、更新　2=管理者)
$G_arrPmsn = F_pmsnCheck($G_login);
if(!isset($G_arrPmsn[$G_gimmickno])){
	header('Location: /', true, 301);
}
$G_pmsnLevel = 0;	//権限レベル
if(isset($G_arrPmsn[$G_gimmickno])){
	$G_pmsnLevel = $G_arrPmsn[$G_gimmickno]['kind'];
}
$G_hallLevel = 1;	//他館許可 0=許可 1=制限
if(isset($G_arrPmsn[$G_gimmickno])){
	$G_hallLevel = $G_arrPmsn[$G_gimmickno]['hall'];
}

//都道府県
$G_ken = array('北海道','青森県','岩手県','宮城県','秋田県','山形県','福島県','茨城県','栃木県','群馬県','埼玉県','千葉県','東京都','神奈川県','新潟県','富山県','石川県','福井県','山梨県','長野県','岐阜県','静岡県','愛知県','三重県','滋賀県','京都府','大阪府','兵庫県','奈良県','和歌山県','鳥取県','島根県','岡山県','広島県','山口県','徳島県','香川県','愛媛県','高知県','福岡県','佐賀県','長崎県','熊本県','大分県','宮崎県','鹿児島県','沖縄県');
//続柄
$G_relation = array('父', '母', '祖父', '祖母');
//続柄
$G_gender = array(0 => '男', 1 => '女', 2 => 'その他');

//post値格納変数初期化
$P_gimmick_type = ""; //操作種別
$P_mail = "";			 //ログインid併用メアド
$P_zip = "";			//郵便番号
$P_ken = "埼玉県";	//都道府県
$P_address = "";	//住所
$P_hall_no = 0;		//所属館
$P_name1 = "";		//保護者名字
$P_name2 = "";		//保護者名前
$P_kana1 = "";		//保護者名字カナ
$P_kana2 = "";		//保護者名前カナ
$P_c_name1 = "";	//児童名字
$P_c_name2 = "";	//児童名前
$P_c_kana1 = "";	//児童名字カナ
$P_c_kana2 = "";	//児童名前カナ
$P_phone = "";		//電話番号
$P_relation_select = "";	//続柄選択
$P_relation_input = "";		//続柄入力
$P_ent_school = "";	//小学1年生入学年
$P_birthday = "";		//児童誕生日
$P_gender = -1;			//性別 0=男、1=女、2=その他
$P_ent_hall = "";		//学童入所日
$P_note_child = "";	//児童メモ

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
isset($_POST["c_name1"]) && $P_c_name1 = F_h($_POST["c_name1"]);
isset($_POST["c_name2"]) && $P_c_name2 = F_h($_POST["c_name2"]);
isset($_POST["c_kana1"]) && $P_c_kana1 = F_h($_POST["c_kana1"]);
isset($_POST["c_kana2"]) && $P_c_kana2 = F_h($_POST["c_kana2"]);
isset($_POST["tel"]) && $P_phone = F_h($_POST["tel"]);
isset($_POST["relation_select"]) && $P_relation_select = $_POST["relation_select"];
isset($_POST["relation_input"]) && $P_relation_input = F_h($_POST["relation_input"]);
if(!$P_relation_input && $P_relation_select){
	$P_relation_input = $P_relation_select;
}
$P_relation_select = "";

isset($_POST["ent_school"]) && $P_ent_school = $_POST["ent_school"];
isset($_POST["birthday"]) && $P_birthday = F_h($_POST["birthday"]);
isset($_POST["gender"]) && $P_gender = $_POST["gender"];
isset($_POST["ent_hall"]) && $P_ent_hall = F_h($_POST["ent_hall"]);
isset($_POST["note_child"]) && $P_note_child = F_h($_POST["note_child"]);

//操作実行
$G_workmes = "";
$G_err_flg = false;

if($P_gimmick_type == "sign_up"){
	//新規登録
	//入力値チェック
	//メアド確認
	if(!$P_mail || !F_isEmail($P_mail)){
		$G_err_flg = true;
		$G_workmes .= '<span class="col_red">※メールアドレスを入力、確認してください</span><br>';
	}

	//代表保護者氏名確認
	if(!$P_name1 || !$P_name2 || !$P_kana1 || !$P_kana2){
		$G_err_flg = true;
		$G_workmes .= '<span class="col_red">※代表保護者氏名、カナをすべて入力してください</span><br>';
	}
	//児童との続柄確認
	if(!$P_relation_input){
		$G_err_flg = true;
		$G_workmes .= '<span class="col_red">※児童との続柄を選択、又は入力してください</span><br>';
	}
	//児童氏名確認
	if(!$P_c_name1 || !$P_c_name2 || !$P_c_kana1 || !$P_c_kana2){
		$G_err_flg = true;
		$G_workmes .= '<span class="col_red">※児童氏名、カナをすべて入力してください</span><br>';
	}
	//児童性別確認
	if($P_gender == -1){
		$G_err_flg = true;
		$G_workmes .= '<span class="col_red">※児童性別を選択してください</span><br>';
	}
	//児童誕生日確認
	if($P_birthday == ""){
		$t_datecheck = true;
	}else{
		$t_datecheck = F_isDate($P_birthday);
	}
	if($t_datecheck === false){
		$G_err_flg = true;
		$G_workmes .= '<span class="col_red">※児童誕生日を確認してください</span><br>';
	}
	//児童入所日確認
	$t_datecheck = F_isDate($P_ent_hall);
	if($t_datecheck === false){
		$G_err_flg = true;
		$G_workmes .= '<span class="col_red">※入所日を入力、確認してください</span><br>';
	}
	//小学1年生入学年
	if(!$P_ent_school){
		$G_err_flg = true;
		$G_workmes .= '<span class="col_red">※小学1年生入学年を選択してください</span><br>';
	}

	if($G_err_flg){
		//入力値エラーあり
		$G_gimmick_type = "";
		$G_workmes .= '<span class="col_red">会員登録はされていません</span>';
	}else{
		//メアド重複確認
		$stmt = $pdo -> prepare('SELECT COUNT(*) FROM `members_home` WHERE `login_id` = :id');
		$stmt -> bindValue(':id', $P_mail, PDO::PARAM_STR);
		$stmt -> execute();
		IF($stmt -> fetchColumn()){
			$G_err_flg = true;
			$G_workmes .= '<span class="col_red">※このメールアドレスは既に使用されています</span><br>';
			$G_workmes .= '<span class="col_red">会員登録はされていません</span>';
		}else{
			//新会員ベース登録実行
			$stmt = $pdo -> prepare('INSERT INTO `members_home`
			(`fk_id_hall`, `login_id`, `login_pass`, `zip_home`, `prefecture_home`, `address_home`)
			VALUES(:hall, :id, :pass, :zip, :prefecture, :address)');
			$stmt -> bindValue(':hall', $P_hall_no, PDO::PARAM_INT);
			$stmt -> bindValue(':id', $P_mail, PDO::PARAM_STR);
			$stmt -> bindValue(':pass', "temp", PDO::PARAM_STR);
			$stmt -> bindValue(':zip', $P_zip, PDO::PARAM_STR);
			$stmt -> bindValue(':prefecture', $P_ken, PDO::PARAM_STR);
			$stmt -> bindValue(':address', $P_address, PDO::PARAM_STR);
			$stmt -> execute();
			$set_id = $pdo -> lastInsertId();

			//会員家族単位代表者登録
			$stmt = $pdo -> prepare('INSERT INTO `members_parent`
			(`fk_id_member_home`, `surname`, `firstname`, `surname_kana`, `firstname_kana`, `delegate_flg`, `relation`)
			VALUES(:fk_id, :sname, :fname, :sname_k, :fname_k, 1, :relation)');
			$stmt -> bindValue(':fk_id', $set_id, PDO::PARAM_INT);
			$stmt -> bindValue(':sname', $P_name1, PDO::PARAM_STR);
			$stmt -> bindValue(':fname', $P_name2, PDO::PARAM_STR);
			$stmt -> bindValue(':sname_k', $P_kana1, PDO::PARAM_STR);
			$stmt -> bindValue(':fname_k', $P_kana2, PDO::PARAM_STR);
			$stmt -> bindValue(':relation', $P_relation_input, PDO::PARAM_STR);
			$stmt -> execute();

			//会員家族単位児童登録
			$stmt = $pdo -> prepare('INSERT INTO `members_child`
			(`fk_id_member_home`, `fk_id_hall`, `surname`, `firstname`, `surname_kana`, `firstname_kana`, `enterschool_year`, `enterhall_date`, `birthday`, `gender`, `note`)
			VALUES(:fk_id, :id_hall, :sname, :fname, :sname_k, :fname_k, :ent_school, :ent_hall, :birthday, :gender, :note)');
			$stmt -> bindValue(':fk_id', $set_id, PDO::PARAM_INT);
			$stmt -> bindValue(':id_hall', $P_hall_no, PDO::PARAM_INT);
			$stmt -> bindValue(':sname', $P_c_name1, PDO::PARAM_STR);
			$stmt -> bindValue(':fname', $P_c_name2, PDO::PARAM_STR);
			$stmt -> bindValue(':sname_k', $P_c_kana1, PDO::PARAM_STR);
			$stmt -> bindValue(':fname_k', $P_c_kana2, PDO::PARAM_STR);
			$stmt -> bindValue(':ent_school', $P_ent_school, PDO::PARAM_INT);
			$stmt -> bindValue(':ent_hall', $P_ent_hall, PDO::PARAM_STR);
			if($P_birthday == ""){
				$stmt -> bindValue(':birthday', NULL, PDO::PARAM_NULL);
			}else{
				$stmt -> bindValue(':birthday', $P_birthday, PDO::PARAM_STR);
			}
			$stmt -> bindValue(':gender', $P_gender, PDO::PARAM_INT);
			$stmt -> bindValue(':note', $P_note_child, PDO::PARAM_STR);
			$stmt -> execute();

			if($P_phone){
				//電話番号登録
				$stmt = $pdo -> prepare('INSERT INTO `members_phone`
				(`fk_id_member_home`, `phone_member`)
				VALUES(:fk_id, :phone)');
				$stmt -> bindValue(':fk_id', $set_id, PDO::PARAM_INT);
				$stmt -> bindValue(':phone', $P_phone, PDO::PARAM_STR);
				$stmt -> execute();
			}

			$G_workmes = '<span class="col_blue">新会員[' . $P_name1 . $P_name2 . ']さんを登録しました</span>';
			$G_workmes .= '<br>[' . $P_name1 . $P_name2 . ']さんの会員情報を修正、又は保護者、児童、メアドを追加する';
			$G_workmes .= '<form name="f_details" action="conf_memberdetails.php" method="post" onsubmit="return false;">
			<input type="button" value="修正追加" onClick="submit();">
			<input type="hidden" name="gimmick_type" value="details">
			<input type="hidden" name="homeid" value=' . $set_id . '>
			</form>';

				F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_hall_no, $P_mail, $P_name1 . $P_name2, $P_phone, $P_c_name1 . $P_c_name2);

			//post値格納変数初期化
			$P_gimmick_type = "";	//操作種別
			$P_mail = "";			//ログインid併用メアド
			$P_zip = "";			//郵便番号
			$P_ken = "埼玉県";	//都道府県
			$P_address = "";	//住所
			$P_hall_no = 0;		//所属館
			$P_name1 = "";		//保護者名字
			$P_name2 = "";		//保護者名前
			$P_kana1 = "";		//保護者名字カナ
			$P_kana2 = "";		//保護者名前カナ
			$P_c_name1 = "";	//児童名字
			$P_c_name2 = "";	//児童名前
			$P_c_kana1 = "";	//児童名字カナ
			$P_c_kana2 = "";	//児童名前カナ
			$P_phone = "";		//電話番号
			$P_relation_select = "";	//続柄選択
			$P_relation_input = "";		//続柄入力
			$P_birthday = "";	//児童誕生日
			$P_gender = -1;		//性別 0=男、1=女、2=その他
			$P_note_child = "";	//児童メモ
		}
	}
}

//館情報取得
$stmt = $pdo -> prepare('SELECT `id_hall`, `name_hall` FROM `halls` WHERE `activity_flg` = 1 ORDER BY `id_hall` ASC');
$stmt -> execute();
$hall_names = $stmt -> fetchAll();
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
<link rel="stylesheet" href="./css/gimmicks.css?<?php echo date("Ymd-Hi"); ?>">
<title><?php echo $G_npo_items['name_npo']; ?>新規会員登録</title>
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
		<h3>新規会員登録</h3>
		<p>
			<a href = "./configure_menu.php" target = "_self">・管理トップへ戻る</a><br>
			<a href = "./<?php echo basename(__FILE__); ?>" target = "_self">・新規会員登録トップへ戻る</a><br>
			<a href = "./conf_membermanagement.php" target = "_self">・会員一覧、編集トップへ移動</a><br>
		</p>
		<hr>
<?php
echo $G_workmes;
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
	$t_selected = "";
	!$P_hall_no && $t_selected = " selected";
	$t_select_hall .= '<option value=0' . $t_selected . '>-未定-</option>';
foreach($hall_names as $val){
	$t_selected = "";
	if($val['id_hall'] == $P_hall_no){ $t_selected = " selected"; };
	$t_select_hall .= "<option value=\"{$val['id_hall']}\"{$t_selected}>{$val['name_hall']}</option>";
}
$t_select_hall .= "</select>";

//続柄セレクト
$t_select_relation = '<select name="relation_select">';
	$t_selected = "";
	!$P_relation_select && $t_selected = " selected";
	$t_select_relation .= '<option value=""' . $t_selected . '>-児童との続柄-</option>';
foreach($G_relation as $val){
	$t_selected = "";
	if($val == $P_relation_select){ $t_selected = " selected"; };
	$t_select_relation .= "<option value=\"{$val}\"{$t_selected}>{$val}</option>";
}
$t_select_relation .= "</select>";

//小学1年生入学年セレクト
$t_select_ent_school = '<select name="ent_school">';
	$t_select_ent_school .= '<option value="">-小学1年生入学年-</option>';
	$t_select_year = date('Y');
	$P_ent_school && $t_select_year = $P_ent_school;
for($i = date('Y') - 10; $i <= date('Y') + 1; $i++){
	$t_selected = "";
	if($i == $t_select_year){ $t_selected = " selected"; };
	$t_select_ent_school .= "<option value=\"{$i}\"{$t_selected}>{$i}</option>";
}
$t_select_ent_school .= "</select>";

//性別セレクト
$t_select_gender = '<select name="gender">';
	$t_selected = "";
	!$P_gender && $t_selected = " selected";
	$t_select_gender .= '<option value="-1"' . $t_selected . '>-性別-</option>';
foreach($G_gender as $key => $val){
	$t_selected = "";
	if($key == $P_gender){ $t_selected = " selected"; };
	$t_select_gender .= "<option value=\"{$key}\"{$t_selected}>{$val}</option>";
}
$t_select_gender .= "</select>";


?>
		<p>
			<form name="fm_data" action="" method="post" onsubmit="return false;">
				<h3>新規会員基本情報</h3>
				<table class = "config">
					<tr>
						<td>所属館</td>
						<td>
							<?php echo $t_select_hall; ?>
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
						<td><span class="col_red">※</span>代表保護者氏名</td>
						<td>
							名字:<input type="text" name="name1" size="20" value="<?php echo $P_name1; ?>" placeholder="保護者名字">
							名前:<input type="text" name="name2" size="20" value="<?php echo $P_name2; ?>" placeholder="保護者名前"><br>
							カナ:<input type="text" name="kana1" size="20" value="<?php echo $P_kana1; ?>" placeholder="ホゴシャミョウジ">
							カナ:<input type="text" name="kana2" size="20" value="<?php echo $P_kana2; ?>" placeholder="ホゴシャナマエ">
						</td>
					</tr>
					<tr>
						<td>電話番号</td>
						<td>
							<input type="text" name="tel" size="20" value="<?php echo $P_phone; ?>">
						</td>
					</tr>
					<tr>
						<td><span class="col_red">※</span>児童との続柄</td>
						<td>
							<?php echo $t_select_relation; ?>
							その他:<input type="text" name="relation_input" size="20" value="<?php echo $P_relation_input; ?>" placeholder="選択肢に無ければこちらへ">
						</td>
					</tr>
					<tr>
						<td><span class="col_red">※</span>児童氏名</td>
						<td>
							名字:<input type="text" name="c_name1" size="20" value="<?php echo $P_c_name1; ?>" placeholder="児童名字">
							名前:<input type="text" name="c_name2" size="20" value="<?php echo $P_c_name2; ?>" placeholder="児童名前"><br>
							カナ:<input type="text" name="c_kana1" size="20" value="<?php echo $P_c_kana1; ?>" placeholder="ジドウミョウジ">
							カナ:<input type="text" name="c_kana2" size="20" value="<?php echo $P_c_kana2; ?>" placeholder="ジドウナマエ">
						</td>
					</tr>
					<tr>
						<td><span class="col_red">※</span>性別、誕生日</td>
						<td>
							性別:<?php echo $t_select_gender; ?>
							　<input type="text" name="birthday" size="10" value="<?php echo $P_birthday; ?>" placeholder="誕生日"><span class="t80per">(例 2019-7-1 2019/7/1 20190701)</span>
						</td>
					</tr>
					<tr>
						<td><span class="col_red">※</span>小学1年生入学年</td>
						<td>
							<?php echo $t_select_ent_school; ?>年<span class="t80per">(小学校に入学した年を選択)</span>
						</td>
					</tr>
					<tr>
						<td><span class="col_red">※</span>学童入所日</td>
						<td>
							<input type="text" name="ent_hall" size="10" value="<?php echo $P_ent_hall; ?>" placeholder="入所日"><span class="t80per">(例 2019-4-1 2019/4/1 20190401)</span>
						</td>
					</tr>
					<tr>
						<td>児童メモ</td>
						<td>
<textarea rows="5" cols="70" name="note_child" placeholder="食物アレルギー等"><?php echo $P_note_child; ?></textarea>
						</td>
					</tr>
				</table>
<?php
if($G_pmsnLevel > 0){
?>
				<input type="button" value="新規登録" onClick="submit();">
<?php
}
?>
				<input type="hidden" name="gimmick_type" value="sign_up">
			</form>
		</p>
		<hr>
	</div>

<!-- footer -->
<?php
include "./../foot.php";
?>

</div>
</body>
</html>
