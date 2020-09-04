<?php
//会員詳細情報管理
session_start();

//管理ページno
$G_gimmickno = 9;

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

//情報格納
$G_home_d = array();		//会員ベース詳細
$G_parents_d = array();	//保護者詳細
$G_children_d = array();//児童詳細
$G_phone_d = array();		//電話番号
$G_mail_d = array();		//メアド

//都道府県
$G_ken = array('北海道','青森県','岩手県','宮城県','秋田県','山形県','福島県','茨城県','栃木県','群馬県','埼玉県','千葉県','東京都','神奈川県','新潟県','富山県','石川県','福井県','山梨県','長野県','岐阜県','静岡県','愛知県','三重県','滋賀県','京都府','大阪府','兵庫県','奈良県','和歌山県','鳥取県','島根県','岡山県','広島県','山口県','徳島県','香川県','愛媛県','高知県','福岡県','佐賀県','長崎県','熊本県','大分県','宮崎県','鹿児島県','沖縄県');
//続柄
$G_relation = array('父', '母', '祖父', '祖母');
//続柄
$G_gender = array(0 => '男', 1 => '女', 2 => 'その他');

//post値格納変数初期化
$P_gimmick_type = "details";
//操作種別
/*
home_disp	=	基本情報表示
home_up		=	基本情報更新

phonenote_edit	=	電話番号メモ更新
phone_del	=	電話番号メモ更新
phone_add	=	電話番号追加
mailnote_edit	=	メアドメモ更新
mail_del	=	メアド削除
mail_add	=	メアド追加
home_disp	=	基本情報編集画面

details		=	指定会員詳細表示
delegate_change = 会員代表者変更
parent_del = 会員保護者削除
parent_edit = 保護者氏名更新
parent_add = 保護者追加
child_edit = 児童情報更新
child_delete = 児童情報削除
child_leave = 児童退所処理
*/

$P_mail = "";				//ログインid併用メアド
$P_zip = "";				//郵便番号
$P_ken = "埼玉県";		//都道府県
$P_address = "";		//住所
$P_hall_no = 0;			//所属館
$P_name1 = "";			//保護者名字
$P_name2 = "";			//保護者名前
$P_kana1 = "";			//保護者名字カナ
$P_kana2 = "";			//保護者名前カナ
$P_c_name1 = "";		//児童名字
$P_c_name2 = "";		//児童名前
$P_c_kana1 = "";		//児童名字カナ
$P_c_kana2 = "";		//児童名前カナ
$P_phone = "";			//電話番号
$P_relation_select = "";	//続柄選択
$P_relation_input = "";		//続柄入力
$P_ent_school = "";	//小学1年生入学年
$P_birthday = "";		//児童誕生日
$P_gender = -1;			//性別 0=男、1=女、2=その他
$P_ent_hall = "";		//学童入所日
$P_lev_hall = "";		//学童退所日
$P_note_child = "";	//児童メモ
$P_homeid = 0;			//会員ベースid
$P_personid = 0;		//選択個人id
$P_phoneid = 0;			//登録電話番号id
$P_phonenote = "";	//電話番号メモ
$P_mailid = 0;			//登録メアドid
$P_mailnote = "";		//メアドメモ


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
isset($_POST["lev_hall"]) && $P_lev_hall = F_h($_POST["lev_hall"]);
isset($_POST["note_child"]) && $P_note_child = F_h($_POST["note_child"]);

isset($_POST["homeid"]) && $P_homeid = $_POST["homeid"];
isset($_POST["personid"]) && $P_personid = $_POST["personid"];
isset($_POST["phoneid"]) && $P_phoneid = $_POST["phoneid"];
isset($_POST["phonenote"]) && $P_phonenote = F_h($_POST["phonenote"]);
isset($_POST["mailid"]) && $P_mailid = $_POST["mailid"];
isset($_POST["mailnote"]) && $P_mailnote = F_h($_POST["mailnote"]);

$P_window_position = 0;	//windowスクロール位置
isset($_POST["p_window_position"]) && $P_window_position = $_POST["p_window_position"];

//操作実行
$G_workmes = "";
$G_err_flg = false;

if($P_gimmick_type == "home_up"){
	//基本情報更新-----------------------------------------------------------------
	//メアド確認
	if(!$P_mail || !F_isEmail($P_mail)){
		$G_err_flg = true;
		$G_workmes .= '<span class="col_red">※メールアドレスを入力、確認してください ' . $P_mail . '</span><br>';
	}

	if($G_err_flg){
		//入力値エラーあり
		$P_gimmick_type = "home_disp";
		$G_workmes .= '<span class="col_red">基本情報は更新されていません</span>';
	}else{
		//メアド重複確認
		$stmt = $pdo -> prepare('SELECT COUNT(*) FROM `members_home` WHERE `id_member_home` <> :id_home AND `login_id` = :id');
		$stmt -> bindValue(':id_home', $P_homeid, PDO::PARAM_INT);
		$stmt -> bindValue(':id', $P_mail, PDO::PARAM_STR);
		$stmt -> execute();
		IF($stmt -> fetchColumn()){
			$G_err_flg = true;
			$G_workmes .= '<span class="col_red">※このメールアドレス[' . $P_mail . ']は既に使用されています(削除済含)</span><br>';
			$G_workmes .= '<span class="col_red">基本情報は更新されていません</span>';
			$P_gimmick_type = "home_disp";
		}else{
			//基本情報更新実行
			$stmt = $pdo -> prepare('UPDATE `members_home`
			SET `fk_id_hall` = :hall, `login_id` = :id, `zip_home` = :zip, `prefecture_home` = :prefecture, `address_home` = :address
			WHERE `id_member_home` = :id_home');
			$stmt -> bindValue(':hall', $P_hall_no, PDO::PARAM_INT);
			$stmt -> bindValue(':id', $P_mail, PDO::PARAM_STR);
			$stmt -> bindValue(':zip', $P_zip, PDO::PARAM_STR);
			$stmt -> bindValue(':prefecture', $P_ken, PDO::PARAM_STR);
			$stmt -> bindValue(':address', $P_address, PDO::PARAM_STR);
			$stmt -> bindValue(':id_home', $P_homeid, PDO::PARAM_INT);
			$stmt -> execute();
			//会員ベースに紐付く児童所属館更新
			$stmt = $pdo -> prepare('UPDATE `members_child`
			SET `fk_id_hall` = :hall
			WHERE `fk_id_member_home` = :id_home');
			$stmt -> bindValue(':hall', $P_hall_no, PDO::PARAM_INT);
			$stmt -> bindValue(':id_home', $P_homeid, PDO::PARAM_INT);
			$stmt -> execute();

			$G_workmes .= '<span class="col_blue">基本情報を更新しました</span>';

				F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_homeid, $P_hall_no, $P_mail, $P_zip, $P_address);

			$P_gimmick_type = "details";
		}
	}
}elseif($P_gimmick_type == "delegate_change"){
	//会員代表者変更-----------------------------------------------------------------
	$stmt = $pdo -> prepare('UPDATE `members_parent`
	SET `delegate_flg` = 0
	WHERE `fk_id_member_home` = :id_home');
	$stmt -> bindValue(':id_home', $P_homeid, PDO::PARAM_INT);
	$stmt -> execute();

	$stmt = $pdo -> prepare('UPDATE `members_parent`
	SET `delegate_flg` = 1
	WHERE `id_parent` = :id');
	$stmt -> bindValue(':id', $P_personid, PDO::PARAM_INT);
	$stmt -> execute();

	$G_workmes .= '<span class="col_blue">代表を変更しました</span>';

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_homeid, $P_personid);

	$P_gimmick_type = "details";
}elseif($P_gimmick_type == "parent_del"){
	//会員保護者削除-----------------------------------------------------------------
	$stmt = $pdo -> prepare('UPDATE `members_parent`
	SET `activity_flg` = 0
	WHERE `id_parent` = :id');
	$stmt -> bindValue(':id', $P_personid, PDO::PARAM_INT);
	$stmt -> execute();

	$G_workmes .= '<span class="col_blue">指定保護者を削除しました</span>';

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_personid);

	$P_gimmick_type = "details";
}elseif($P_gimmick_type == "parent_edit"){
	//保護者氏名更新-----------------------------------------------------------------
	$stmt = $pdo -> prepare('UPDATE `members_parent`
	SET `surname` = :name1, `firstname` = :name2, `surname_kana` = :kana1, `firstname_kana` = :kana2, `relation` = :rela
	WHERE `id_parent` = :id');
	$stmt -> bindValue(':name1', $P_name1, PDO::PARAM_STR);
	$stmt -> bindValue(':name2', $P_name2, PDO::PARAM_STR);
	$stmt -> bindValue(':kana1', $P_kana1, PDO::PARAM_STR);
	$stmt -> bindValue(':kana2', $P_kana2, PDO::PARAM_STR);
	$stmt -> bindValue(':rela', $P_relation_input, PDO::PARAM_STR);
	$stmt -> bindValue(':id', $P_personid, PDO::PARAM_INT);
	$stmt -> execute();

	$G_workmes .= '<span class="col_blue">保護者氏名を更新しました</span>';

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_homeid, $P_name1 . $P_name2, $P_kana1 . $P_kana2, $P_relation_input, $P_personid);

	$P_gimmick_type = "details";

	$P_name1 = $P_name2 = $P_kana1 = $P_kana2 = $P_relation_input = "";
}elseif($P_gimmick_type == "parent_add"){
	//保護者追加-----------------------------------------------------------------
	$stmt = $pdo -> prepare('INSERT INTO `members_parent`
	(`fk_id_member_home`, `surname`, `firstname`, `surname_kana`, `firstname_kana`, `relation`)
	VALUES(:id, :name1, :name2, :kana1, :kana2, :rela)
	');
	$stmt -> bindValue(':id', $P_homeid, PDO::PARAM_INT);
	$stmt -> bindValue(':name1', $P_name1, PDO::PARAM_STR);
	$stmt -> bindValue(':name2', $P_name2, PDO::PARAM_STR);
	$stmt -> bindValue(':kana1', $P_kana1, PDO::PARAM_STR);
	$stmt -> bindValue(':kana2', $P_kana2, PDO::PARAM_STR);
	$stmt -> bindValue(':rela', $P_relation_input, PDO::PARAM_STR);
	$stmt -> execute();

	$G_workmes .= '<span class="col_blue">保護者を追加しました</span>';

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_homeid, $P_name1 . $P_name2, $P_kana1 . $P_kana2, $P_relation_input);

	$P_gimmick_type = "details";

	$P_name1 = $P_name2 = $P_kana1 = $P_kana2 = $P_relation_input = "";
}elseif($P_gimmick_type == "child_edit"){
	//児童情報更新--------------------------------------------------------------
	//入力値チェック
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
		$G_workmes .= '<span class="col_red">※児童誕生日を入力、確認してください</span><br>';
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
		$G_workmes .= '<span class="col_red">児童情報は更新されていません</span>';
		//児童情報post値初期化
		$P_c_name1 = $P_c_name2 = $P_c_kana1 = $P_c_kana2 = $P_ent_school = $P_ent_hall = $P_birthday = $P_note_child = "";
		$P_gender = -1;
	}else{
		//更新実行
		$stmt = $pdo -> prepare('UPDATE `members_child`
			SET `surname` = :sname, `firstname` = :fname, `surname_kana` = :sname_k, `firstname_kana` = :fname_k, `enterschool_year` = :ent_school, `enterhall_date` = :ent_hall, `birthday` = :birthday, `gender` = :gender, `note` = :note
			WHERE `id_child` = :id_child');
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
		$stmt -> bindValue(':id_child', $P_personid, PDO::PARAM_INT);

		$stmt -> execute();

			F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_personid, $P_c_name1 . $P_c_name2, $P_c_kana1 . $P_c_kana2, $P_gender, $P_note_child);

		//児童情報post値初期化
		$P_c_name1 = $P_c_name2 = $P_c_kana1 = $P_c_kana2 = $P_ent_school = $P_ent_hall = $P_birthday = $P_note_child = "";
		$P_gender = -1;
		$G_workmes .= '<span class="col_blue">児童情報を更新しました</span>';
	}
	$P_gimmick_type = "details";
}elseif($P_gimmick_type == "child_del"){
		//児童情報削除--------------------------------------------------------------
		$stmt = $pdo -> prepare('UPDATE `members_child`
		SET `activity_flg` = 0
		WHERE `id_child` = :id_child');
		$stmt -> bindValue(':id_child', $P_personid, PDO::PARAM_INT);

		$stmt -> execute();
		$G_workmes .= '<span class="col_blue">指定児童情報を削除しました</span>';

			F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_personid);

		$P_gimmick_type = "details";
}elseif($P_gimmick_type == "child_leave"){
	//児童退所処理-------------------------------------------------------------
	$t_datecheck = F_isDate($P_lev_hall);
	if($t_datecheck === false){
		$G_err_flg = true;
		$G_workmes .= '<span class="col_red">※児童退所日を入力、確認してください</span><br>';
	}

	if($G_err_flg){
		//入力値エラーあり
		$G_workmes .= '<span class="col_red">指定児童は退所処理されていません</span>';
	}else{
		//退所実行
		$stmt = $pdo -> prepare('UPDATE `members_child`
			SET `activity_flg` = 0, `leaving_date` = :ldate
			WHERE `id_child` = :id_child');
		$stmt -> bindValue(':ldate', $P_lev_hall, PDO::PARAM_STR);
		$stmt -> bindValue(':id_child', $P_personid, PDO::PARAM_INT);

		$stmt -> execute();

		$G_workmes .= '<span class="col_blue">指定児童を退所処理しました</span>';

			F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_personid, $P_lev_hall);

	}
	$P_gimmick_type = "details";

}elseif($P_gimmick_type == "child_add"){
	//児童追加処理-------------------------------------------------------------
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
	$t_datecheck = F_isDate($P_birthday);
	if($t_datecheck === false){
		$G_err_flg = true;
		$G_workmes .= '<span class="col_red">※児童誕生日を入力、確認してください</span><br>';
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
		$G_workmes .= '<span class="col_red">児童は追加されていません</span>';
	}else{
		//会員ベース情報取得(所属館情報使用)
		$stmt = $pdo -> prepare('SELECT t_home.*, t_halls.`name_hall`
			FROM `members_home` as t_home
			LEFT JOIN `halls` as t_halls ON t_home.`fk_id_hall` = t_halls.`id_hall`
			WHERE t_home.`id_member_home` = :id AND t_home.`activity_flg` = 1');
		$stmt -> bindvalue(':id', $P_homeid, PDO::PARAM_INT);
		$stmt -> execute();
		$G_home_d = $stmt -> fetchAll()[0];

		//児童追加
		$stmt = $pdo -> prepare('INSERT INTO `members_child`
		(`fk_id_member_home`, `fk_id_hall`, `surname`, `firstname`, `surname_kana`, `firstname_kana`, `enterschool_year`, `enterhall_date`, `birthday`, `gender`, `note`)
		VALUES(:fk_id, :id_hall, :sname, :fname, :sname_k, :fname_k, :ent_school, :ent_hall, :birthday, :gender, :note)');
		$stmt -> bindValue(':fk_id', $P_homeid, PDO::PARAM_INT);
		$stmt -> bindValue(':id_hall', $G_home_d['fk_id_hall'], PDO::PARAM_INT);
		$stmt -> bindValue(':sname', $P_c_name1, PDO::PARAM_STR);
		$stmt -> bindValue(':fname', $P_c_name2, PDO::PARAM_STR);
		$stmt -> bindValue(':sname_k', $P_c_kana1, PDO::PARAM_STR);
		$stmt -> bindValue(':fname_k', $P_c_kana2, PDO::PARAM_STR);
		$stmt -> bindValue(':ent_school', $P_ent_school, PDO::PARAM_INT);
		$stmt -> bindValue(':ent_hall', $P_ent_hall, PDO::PARAM_STR);
		$stmt -> bindValue(':birthday', $P_birthday, PDO::PARAM_STR);
		$stmt -> bindValue(':gender', $P_gender, PDO::PARAM_INT);
		$stmt -> bindValue(':note', $P_note_child, PDO::PARAM_STR);
		$stmt -> execute();

			F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_homeid, $P_c_name1 . $P_c_name2, $P_c_kana1 . $P_c_kana2, $P_gender, $P_note_child);

		//児童情報post値初期化
		$P_c_name1 = $P_c_name2 = $P_c_kana1 = $P_c_kana2 = $P_ent_school = $P_ent_hall = $P_birthday = $P_note_child = "";
		$P_gender = -1;
		$G_workmes = '<span class="col_blue">児童を追加しました</span>';
	}
	$P_gimmick_type = "details";
}

//=====================================================================

if($P_gimmick_type == "phonenote_edit"){
	//電話番号メモ更新-----------------------------------------------------------------
	$stmt = $pdo -> prepare('UPDATE `members_phone` SET `note` = :note WHERE `id_member_phone` = :id');
	$stmt -> bindvalue(':note', $P_phonenote, PDO::PARAM_STR);
	$stmt -> bindvalue(':id', $P_phoneid, PDO::PARAM_INT);
	$stmt -> execute();

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_phoneid, $P_phonenote);

	$P_gimmick_type = "details";
	$G_workmes = '<span class="col_blue">電話番号メモを更新しました</span>';
}elseif($P_gimmick_type == "phone_del"){
	//電話番号削除-----------------------------------------------------------------
	$stmt = $pdo -> prepare('UPDATE `members_phone` SET `activity_flg` = 0 WHERE `id_member_phone` = :id');
	$stmt -> bindvalue(':id', $P_phoneid, PDO::PARAM_INT);
	$stmt -> execute();

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_phoneid);

	$P_gimmick_type = "details";
	$G_workmes = '<span class="col_blue">電話番号を削除しました</span>';
}elseif($P_gimmick_type == "phone_add"){
	//電話番号追加-----------------------------------------------------------------
	$stmt = $pdo -> prepare('
	INSERT INTO `members_phone`(`fk_id_member_home`, `phone_member`, `note`)
	VALUES(:id, :phone, :note)
		');
	$stmt -> bindvalue(':id', $P_homeid, PDO::PARAM_INT);
	$stmt -> bindvalue(':phone', $P_phone, PDO::PARAM_STR);
	$stmt -> bindvalue(':note', $P_phonenote, PDO::PARAM_STR);
	$stmt -> execute();

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_homeid, $P_phone, $P_phonenote);

	$P_gimmick_type = "details";
	$G_workmes = '<span class="col_blue">電話番号を追加しました</span>';
}elseif($P_gimmick_type == "mailnote_edit"){
		//メアドメモ更新-----------------------------------------------------------------
		$stmt = $pdo -> prepare('UPDATE `members_mail_add` SET `note` = :note WHERE `id_member_mail` = :id');
		$stmt -> bindvalue(':note', $P_mailnote, PDO::PARAM_STR);
		$stmt -> bindvalue(':id', $P_mailid, PDO::PARAM_INT);
		$stmt -> execute();

			F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_mailid, $P_mailnote);

		$P_gimmick_type = "details";
		$P_mailnote = "";
		$G_workmes = '<span class="col_blue">メールアドレスメモを更新しました</span>';
}elseif($P_gimmick_type == "mail_del"){
		//メアド削除-----------------------------------------------------------------
		$stmt = $pdo -> prepare('UPDATE `members_mail_add` SET `activity_flg` = 0 WHERE `id_member_mail` = :id');
		$stmt -> bindvalue(':id', $P_mailid, PDO::PARAM_INT);
		$stmt -> execute();

			F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_mailid);

		$P_gimmick_type = "details";
		$G_workmes = '<span class="col_blue">メールアドレスを削除しました</span>';
}elseif($P_gimmick_type == "mail_add"){
	//メアド追加-----------------------------------------------------------------
	if(!F_isEmail($P_mail)){
		$P_gimmick_type = "details";
		$G_workmes = '<span class="col_red">メールアドレスは追加されていません、構成を確認してください</span>';
	}else{
		$stmt = $pdo -> prepare('
		INSERT INTO `members_mail_add`(`fk_id_member_home`, `mail_member`, `note`)
		VALUES(:id, :mail, :note)
			');
		$stmt -> bindvalue(':id', $P_homeid, PDO::PARAM_INT);
		$stmt -> bindvalue(':mail', $P_mail, PDO::PARAM_STR);
		$stmt -> bindvalue(':note', $P_mailnote, PDO::PARAM_STR);
		$stmt -> execute();

			F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_homeid, $P_mail, $P_mailnote);

		$P_gimmick_type = "details";
		$P_mail = "";
		$P_mailnote = "";
		$G_workmes = '<span class="col_blue">メールアドレスを追加しました</span>';
	}
}elseif($P_gimmick_type == "home_disp"){
	//基本情報編集画面-----------------------------------------------------------------
	//会員ベース情報取得
	$stmt = $pdo -> prepare('SELECT t_home.*, t_halls.`name_hall`
		FROM `members_home` as t_home
		LEFT JOIN `halls` as t_halls ON t_home.`fk_id_hall` = t_halls.`id_hall`
		WHERE t_home.`id_member_home` = :id AND t_home.`activity_flg` = 1');
	$stmt -> bindvalue(':id', $P_homeid, PDO::PARAM_INT);
	$stmt -> execute();
	$G_home_d = $stmt -> fetchAll()[0];

	//会員保護者情報取得
	$stmt = $pdo -> prepare('SELECT * FROM `members_parent`
		WHERE `fk_id_member_home` = :id AND `activity_flg` = 1 AND `delegate_flg` = 1
		ORDER BY `delegate_flg` DESC, `surname_kana`, `firstname_kana`');
	$stmt -> bindvalue(':id', $P_homeid, PDO::PARAM_INT);
	$stmt -> execute();
	$G_parents_d = $stmt -> fetchAll()[0];
}

//=====================================================================

if($P_gimmick_type == "details"){
	//指定会員詳細表示---------------------------------------------------------
	if(!$P_homeid){
		$G_workmes = '<span class="col_red">会員idエラーです</span>';
		$G_err_flg = true;
	}else{
		//会員ベース情報取得
		$stmt = $pdo -> prepare('SELECT t_home.*, t_halls.`name_hall`
			FROM `members_home` as t_home
			LEFT JOIN `halls` as t_halls ON t_home.`fk_id_hall` = t_halls.`id_hall`
			WHERE t_home.`id_member_home` = :id AND t_home.`activity_flg` = 1');
		$stmt -> bindvalue(':id', $P_homeid, PDO::PARAM_INT);
		$stmt -> execute();
		$G_home_d = $stmt -> fetchAll()[0];

		//会員保護者情報取得
		$stmt = $pdo -> prepare('SELECT * FROM `members_parent`
			WHERE `fk_id_member_home` = :id AND `activity_flg` = 1
			ORDER BY `delegate_flg` DESC, `surname_kana`, `firstname_kana`');
		$stmt -> bindvalue(':id', $P_homeid, PDO::PARAM_INT);
		$stmt -> execute();
		$G_parents_d = $stmt -> fetchAll();

		//会員児童情報取得
		$stmt = $pdo -> prepare('SELECT * FROM `members_child`
			WHERE `fk_id_member_home` = :id AND `activity_flg` = 1
			ORDER BY `birthday` DESC');
		$stmt -> bindvalue(':id', $P_homeid, PDO::PARAM_INT);
		$stmt -> execute();
		$G_children_d = $stmt -> fetchAll();

		//会員電話情報取得
		$stmt = $pdo -> prepare('SELECT * FROM `members_phone`
			WHERE `fk_id_member_home` = :id AND `activity_flg` = 1
			ORDER BY `regist_time` ASC');
		$stmt -> bindvalue(':id', $P_homeid, PDO::PARAM_INT);
		$stmt -> execute();
		$G_phone_d = $stmt -> fetchAll();

		//会員メアド情報取得
		$stmt = $pdo -> prepare('SELECT * FROM `members_mail_add`
			WHERE `fk_id_member_home` = :id AND `activity_flg` = 1
			ORDER BY `regist_time` ASC');
		$stmt -> bindvalue(':id', $P_homeid, PDO::PARAM_INT);
		$stmt -> execute();
		$G_mail_d = $stmt -> fetchAll();
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
<script src="./js/conf_memberdetails.js?<?php echo date("Ymd-Hi"); ?>"></script>
<script src="/js/jquery-3.4.1.min.js"></script>
<script type="text/javascript">$(document).ready(function(){$(window).scrollTop(<?php echo $P_window_position; ?>);});</script>
<title><?php echo $G_npo_items['name_npo']; ?>会員詳細、編集</title>
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
		<h3>会員詳細、編集</h3>
		<p>
			<a href = "./configure_menu.php" target = "_self">・管理トップへ戻る</a><br>
			<a href = "./conf_memberregist.php" target = "_self">・新規会員登録へ移動</a><br>
			<a href = "./conf_membermanagement.php" target = "_self">・会員一覧、編集トップへ移動</a><br>
		</p>
		<hr>
<?php
echo $G_workmes;
?>
<?php
if($P_gimmick_type == "details"){
	//会員詳細表示
?>
		<p>
			<h3>会員詳細</h3>
<br>
			<form name="fm_parents" action="" method="post">
				<table class="config">
					<tr><th colspan="2">保護者</th></tr>
					<?php
						foreach($G_parents_d as $val){
							$t_delegate = "";	//代表者表示
							if($val['delegate_flg']){
								$t_delegate = "代表";
							}

							$t_change_d = "";	//代表変更ボタン
							if(!$val['delegate_flg'] && $G_pmsnLevel > 0){
								$t_change_d = '<input type="button" value="代表変更" onClick="f_change_d(this.form, ' . $val['id_parent'] . ');">';
							}

							$t_del_p = ""; //削除ボタン
							if(!$val['delegate_flg'] && $G_pmsnLevel > 0){
								$t_del_p = '<input type="button" value="削除" 	onClick="f_del_p(this.form, ' . $val['id_parent'] . ');">';
							}
				echo '<tr>
								<td>' . $t_delegate . $t_change_d . "　" . $t_del_p . '</td>
								<td>' .
								F_makedisp_parents($val)
						. '</td>
							</tr>';
						}
					?>
					<tr><td colspan="2"><span class="t80per">※代表は削除できません</span></td></tr>
					<?php
					if($G_pmsnLevel > 0){
					?>
					<tr>
						<td>保護者追加</td>
						<td>
氏<input type="text" value="<?php echo $P_name1; ?>" size="20" name="name1_new">
名<input type="text" value="<?php echo $P_name2; ?>" size="20" name="name2_new"><br>
　<input type="text" value="<?php echo $P_kana1; ?>" size="20" name="kana1_new">
　<input type="text" value="<?php echo $P_kana2; ?>" size="20" name="kana2_new"><br>
　　　　　　　　　　　　　　児童との続柄:<input type="text" value="<?php echo $P_relation_select;?>" size="5" name="relation_input_new">
 <input type="button" value="　追加　" onClick="f_add_p(this.form);">						</td>
					</tr>
					<?php
					}
					?>
				</table>
				<input type="hidden" name="gimmick_type" value="parent_disp">
				<input type="hidden" name="name1" value="">
				<input type="hidden" name="name2" value="">
				<input type="hidden" name="kana1" value="">
				<input type="hidden" name="kana2" value="">
				<input type="hidden" name="relation_input" value="">
				<input type="hidden" name="homeid" value=<?php echo $P_homeid; ?>>
				<input type="hidden" name="personid" value=0>
				<input type="hidden" name="p_window_position" value=0>
			</form>
<br>
			<form name="fm_child" action="" method="post">
				<table class="config">
					<tr><th colspan="2">児童</th></tr>
					<?php
						foreach($G_children_d as $val){
							echo '
							<tr>
								<td>';
								if($G_pmsnLevel > 0){
								echo '<input type="button" value="削除" onClick="f_delete_c(' . $val['id_child'] . ');"><br>
								<br><br>
								退所日:<input type="text" name="lev_' . $val['id_child'] . '" size="10"><br>
								　　　 <input type="button" value="退所処理"  onClick="f_leave_c(' . $val['id_child'] . ');"><br>
								<span class="t80per">入力例<br>(2019-7-1)<br>(2019/7/1)<br>(20190701)</span>';
								}
								echo '</td>
								<td>'
								. F_makedisp_child($val) .
								'</td>
							</tr>';
						}
					?>
					<?php
					if($G_pmsnLevel > 0){
					?>
					<tr><td colspan="2"><span class="t80per">　</span></td></tr>
					<tr><td>児童追加</td>
						<td>
							<?php
							//性別選択セレクト
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

							//小学1年生入学年セレクト
							$t_select_ent_school = '<select name="ent_school">';
								$t_select_ent_school .= '<option value="">-入学年-</option>';
								$t_select_year = date('Y');
								$P_ent_school && $t_select_year = $P_ent_school;
							for($i = date('Y') - 10; $i <= date('Y') + 1; $i++){
								$t_selected = "";
								if($i == $t_select_year){ $t_selected = " selected"; };
								$t_select_ent_school .= "<option value=\"{$i}\"{$t_selected}>{$i}</option>";
							}
							$t_select_ent_school .= "</select>";
							?>
							氏<input type="text" name="c_name1" value="<?php echo $P_c_name1; ?>">
							名<input type="text" name="c_name2" value="<?php echo $P_c_name2; ?>"><br>
							　<input type="text" name="c_kana1" value="<?php echo $P_c_kana1; ?>">
							　<input type="text" name="c_kana2" value="<?php echo $P_c_kana2; ?>"><br>
							性別:<?php echo $t_select_gender; ?>
							　　　小学校入学年:<?php echo $t_select_ent_school; ?>年<br>
							誕生日:<input type= "text" name="birthday" value="<?php echo $P_birthday; ?>" size="10"><span class="t80per">(例 2019-7-1 2019/7/1 20190701)</span><br>
							入所日:<input type= "text" name="ent_hall" value="<?php echo $P_ent_hall; ?>" size="10"><span class="t80per">(例 2019-7-1 2019/7/1 20190701)</span><br>
							<textarea rows="5" cols="70" name="note_child" placeholder="食物アレルギー等メモ"><?php echo $P_note_child; ?></textarea><br>
							　　　　　　　　　　　　　　　　　　　　　<input type="button" value="　追加　" onClick="f_add_c(this.form, 11);">
						</td>
					</tr>
					<?php
					}
					?>
				</table>
				<input type="hidden" name="gimmick_type" value="">
				<input type="hidden" name="homeid" value=<?php echo $P_homeid; ?>>
				<input type="hidden" name="p_window_position" value=0>
			</form>

			<form name="fm_child_edit" action="" method="post">
				<input type="hidden" name="gimmick_type" value="">
				<input type="hidden" name="homeid" value=<?php echo $P_homeid; ?>>
				<input type="hidden" name="personid" value=0>
				<input type="hidden" name="c_name1" value="">
				<input type="hidden" name="c_name2" value="">
				<input type="hidden" name="c_kana1" value="">
				<input type="hidden" name="c_kana2" value="">
				<input type="hidden" name="gender" value="">
				<input type="hidden" name="ent_school" value="">
				<input type="hidden" name="ent_hall" value="">
				<input type="hidden" name="lev_hall" value="">
				<input type="hidden" name="birthday" value="">
				<input type="hidden" name="note_child" value="">
				<input type="hidden" name="p_window_position" value=0>
			</form>
<br>
			<form name="fm_home" action="" method="post">
				<table class="config">
					<tr><th colspan="3">基本情報</th></tr>
						<?php
							echo F_makedisp_home($G_home_d);
						?>

				</table>
				<input type="hidden" name="gimmick_type" value="home_disp">
				<input type="hidden" name="homeid" value=<?php echo $P_homeid; ?>>
			</form>

<br>
			<form name="fm_tel" action="" method="post">
				<table class="config">
					<tr><th colspan="2">電話番号</th></tr>
						<?php
							foreach($G_phone_d as $val){
								echo F_makedisp_tel($val);
							}
						?>
						<?php
						if($G_pmsnLevel > 0){
						?>
					<tr><td colspan="2"> </td></tr>
					<tr>
						<td>
							<input type="button" value="追加" onClick="f_addphone();">
						</td>
						<td>
							電話番号:<input type="text" name="tel" size="30" maxlength="30" value=""><br>
							　　メモ:<input type="text" name="new_note" size="30" maxlength="30" value="">
						</td>
					</tr>
					<?php
					}
					?>
				</table>
				<input type="hidden" name="gimmick_type" value="">
				<input type="hidden" name="phoneid" value=0>
				<input type="hidden" name="phonenote" value="">
				<input type="hidden" name="homeid" value=<?php echo $P_homeid; ?>>
				<input type="hidden" name="p_window_position" value=0>
			</form>

<br>
			<form name="fm_mail" action="" method="post">
				<table class="config">
					<tr><th colspan="2">メールアドレス</th></tr>
						<?php
							foreach($G_mail_d as $val){
								echo F_makedisp_mail($val);
							}
						?>
						<?php
						if($G_pmsnLevel > 0){
						?>
					<tr><td colspan="2"> </td></tr>
					<tr>
						<td>
							<input type="button" value="追加" onClick="f_addmail();">
						</td>
						<td>
							メールアドレス:<input type="text" name="mail" size="30" maxlength="60" value="<?php echo $P_mail; ?>">
							<br>
							　　　　　メモ:<input type="text" name="new_note" size="30" maxlength="30" value="<?php echo $P_mailnote; ?>">
						</td>
					</tr>
					<?php
					}
					?>
				</table>
				<input type="hidden" name="gimmick_type" value="">
				<input type="hidden" name="mailid" value=0>
				<input type="hidden" name="mailnote" value="">
				<input type="hidden" name="homeid" value=<?php echo $P_homeid; ?>>
				<input type="hidden" name="p_window_position" value=0>
			</form>
		</p>
		<hr>
<?php
}elseif($P_gimmick_type == "home_disp"){
	//基本情報編集画面
	//都道府県セレクト
	$t_select_ken = '<select name="ken">';
		$t_select_ken .= '<option value="">都道府県</option>';
	foreach($G_ken as $val){
		$t_selected = "";
		if($val == $G_home_d['prefecture_home']){ $t_selected = " selected"; };
		$t_select_ken .= "<option value=\"{$val}\"{$t_selected}>{$val}</option>";
	}
	$t_select_ken .= "</select>";

	//所属館セレクト
	$t_select_hall = '<select name="hall">';
	$t_select_hall .= '<option value=0>-未定-</option>';
	foreach($hall_names as $val){
		$t_selected = "";
		if($val['id_hall'] == $G_home_d['fk_id_hall']){ $t_selected = " selected"; };
		$t_select_hall .= "<option value=\"{$val['id_hall']}\"{$t_selected}>{$val['name_hall']}</option>";
	}
	$t_select_hall .= "</select>";

?>
<p>
	<form name="fm_data" action="" method="post" onsubmit="return false;">
		<h3>会員基本情報編集</h3>
		<table class = "config">
			<tr>
				<td>保護者</td>
				<td><?php echo $G_parents_d['surname'] . ' ' . $G_parents_d['firstname']; ?></td>
			</tr>
			<tr>
				<td>所属館</td>
				<td>
					<?php echo $t_select_hall; ?>
				</td>
			</tr>
			<tr>
				<td><span class="col_red">※</span>メールアドレス<br>(ログインid併用)</td>
				<td>
					<input type="text" name="mail" size="50" value="<?php echo $G_home_d['login_id']; ?>" placeholder="メールアドレス">
				</td>
			</tr>
			<tr>
				<td>住所</td>
				<td>
					〒<input type="text" name="zip" size="7" value="<?php echo $G_home_d['zip_home']; ?>" placeholder="xxxxxxx">(ハイフン無し数字のみ入力)<br>
					<?php echo $t_select_ken; ?><br>
					<input type="text" name="address" size="70" value="<?php echo $G_home_d['address_home']; ?>" placeholder="住所">
				</td>
			</tr>
		</table>
		<?php
		if($G_pmsnLevel > 0){
		?>
		<input type="button" value="更新" onClick="submit();">　
		<input type="button" value="戻る" onClick="f_bak_homedisp(this.form);">
		<?php
		}
		?>
		<input type="hidden" name="homeid" value=<?php echo $P_homeid; ?>>
		<input type="hidden" name="gimmick_type" value="home_up">
	</form>
</p>
<hr>

<?php
}
?>

	</div>

<!-- footer -->
<?php
include "./../foot.php";
?>

</div>
</body>
</html>

<?php
/*:::::::::::::::::::::::::::::::
詳細用児童情報表示作成

引数
$f_c[]  児童情報arr
	[members_childテーブルカラム名] => 値

戻り値
児童情報テキスト
:::::::::::::::::::::::::::::::*/
function F_makedisp_child($f_c){
	$ret_tex = "";
	global $G_gender;
	global $G_pmsnLevel;

	$t_sg = F_calc_schoolgrade($f_c['enterschool_year']);
	$t_bc = "";	//誕生月文字色
	$t_bm = "";	//誕生月マーク
	if(date("n") == date("n", strtotime($f_c['birthday']))){
		$t_bc = "col_blue";
		$t_bm = "★";
	}
	//性別選択セレクト
	$t_select_gender = '<select name="gnd_' . $f_c['id_child'] . '">';
		$t_selected = "";
		!$f_c['gender'] && $t_selected = " selected";
		$t_select_gender .= '<option value="-1"' . $t_selected . '>-性別-</option>';
	foreach($G_gender as $key => $val){
		$t_selected = "";
		if($key == $f_c['gender']){ $t_selected = " selected"; };
		$t_select_gender .= "<option value=\"{$key}\"{$t_selected}>{$val}</option>";
	}
	$t_select_gender .= "</select>";
	//小学1年生入学年セレクト
	$t_select_ent_school = '<select name="ents_' . $f_c['id_child'] . '">';
		$t_select_ent_school .= '<option value="">-入学年-</option>';
		$t_select_year = date('Y');
		$f_c['enterschool_year'] && $t_select_year = $f_c['enterschool_year'];
	for($i = date('Y') - 10; $i <= date('Y') + 1; $i++){
		$t_selected = "";
		if($i == $t_select_year){ $t_selected = " selected"; };
		$t_select_ent_school .= "<option value=\"{$i}\"{$t_selected}>{$i}</option>";
	}
	$t_select_ent_school .= "</select>";

	$ret_tex .= '
	氏<input type="text" name="cn1_' . $f_c['id_child'] . '" value="' . $f_c['surname'] . '">
	名<input type="text" name="cn2_' . $f_c['id_child'] . '" value="' . $f_c['firstname'] . '"><br>';
	$ret_tex .= '
	　<input type="text" name="ck1_' . $f_c['id_child'] . '" value="' . $f_c['surname_kana'] . '">
	　<input type="text" name="ck2_' . $f_c['id_child'] . '" value="' . $f_c['firstname_kana'] . '"><br>';
	$ret_tex .= "性別:{$t_select_gender}";
	$ret_tex .= '　　　' . $t_sg . '年生 小学校入学年:' . $t_select_ent_school . '年<br>';
	$ret_tex .= '誕生日:<input type= "text" name="btd_' . $f_c['id_child'] . '" value="' . $f_c['birthday'] . '" size="10"><span class="t80per">(例 2019-7-1 2019/7/1 20190701)</span><br>';
	$ret_tex .= '入所日:<input type= "text" name="enth_' . $f_c['id_child'] . '" value="' . $f_c['enterhall_date'] . '" size="10"><span class="t80per">(例 2019-7-1 2019/7/1 20190701)</span><br>';
	$ret_tex .= '<textarea rows="5" cols="70" name="nc_' . $f_c['id_child'] . '" placeholder="食物アレルギー等メモ">' . $f_c['note'] . '</textarea><br>';
	if($G_pmsnLevel > 0){
		$ret_tex .= '　<input type="button" value="　更新　" onClick="f_edit_c(this.form, ' . $f_c['id_child'] . ');">';
	}
	return $ret_tex;
}

/*:::::::::::::::::::::::::::::::
詳細用保護者情報表示作成

引数
$f_p[]  保護者情報arr
	[members_parentテーブルカラム名] => 値

戻り値
保護者情報テキスト
:::::::::::::::::::::::::::::::*/
function F_makedisp_parents($f_p){
	global $G_pmsnLevel;

	$ret_tex = "";
	$t_edit_b = '';
	if($G_pmsnLevel > 0){
		$t_edit_b = '<input type="button" value="　更新　" onClick="f_edit_p(this.form, ' . $f_p['id_parent'] . ');">';
	}
	$ret_tex .= '
氏<input type="text" value="' . $f_p['surname'] . '" size="20" name="sn_' . $f_p['id_parent'] . '">
名<input type="text" value="' . $f_p['firstname'] . '" size="20" name="fn_' . $f_p['id_parent'] . '"><br>';

	$ret_tex .= '
　<input type="text" value="' . $f_p['surname_kana'] . '" size="20" name="snk_' . $f_p['id_parent'] . '">
　<input type="text" value="' . $f_p['firstname_kana'] . '" size="20" name="fnk_' . $f_p['id_parent'] . '"><br>';

$ret_tex .= '
　　　　　　　　　　　　　　児童との続柄:<input type="text" value="' . $f_p['relation'] . '" size="5" name="rel_' . $f_p['id_parent'] . '">';

	$ret_tex .= " {$t_edit_b}";

	return $ret_tex;
}

/*:::::::::::::::::::::::::::::::
詳細用会員基本情報表示作成

引数
$f_h[]  基本情報arr
	[members_homeテーブルカラム名] => 値

戻り値
基本情報テキスト
:::::::::::::::::::::::::::::::*/
function F_makedisp_home($f_h){
	global $G_pmsnLevel;

	$t_hall = $f_h['name_hall']?$f_h['name_hall']:"未定";
	$ret_tex = '<tr>
<td rowspan="4">';
	if($G_pmsnLevel > 0){
		$ret_tex .= '<input type="submit" value="編集">';
	}
	$ret_tex .= '</td>
<td colspan="2">会員基本No.[' . $f_h['id_member_home'] . ']</td>
</tr>';
	$ret_tex .= "<tr><td>所属館</td><td><b>" . $t_hall . "</b></td></tr>";
	$ret_tex .= "<tr><td>メールアドレス<br>(ログインid併用)</td><td>" . $f_h['login_id'] . "</td></tr>";
	$ret_tex .= "<tr><td>住所</td><td>
〒{$f_h['zip_home']}<br>
{$f_h['prefecture_home']}{$f_h['address_home']}
	</td></tr>";

	return $ret_tex;
}

/*:::::::::::::::::::::::::::::::
詳細用電話番号表示作成

引数
$f_t[]  電話番号情報arr
	[members_phoneテーブルカラム名] => 値

戻り値
電話番号情報テキスト
:::::::::::::::::::::::::::::::*/
function F_makedisp_tel($f_t){
	global $G_pmsnLevel;

	$ret_tex = '
<tr>
	<td>';
	if($G_pmsnLevel > 0){
		$ret_tex .= '<input type="button" value="削除" onClick="f_deltel(' . $f_t['id_member_phone'] . ');">';
	}
	$ret_tex .= '</td>
	<td>'
		. $f_t['phone_member'] .
		'　メモ<input type="text" name="pnone_' . $f_t['id_member_phone'] . '" size="30" maxlength="30" value="' . $f_t['note'] . '">';
		if($G_pmsnLevel > 0){
			$ret_tex .= '<input type="button" value="メモ更新" onClick="f_phone_n(' . $f_t['id_member_phone'] . ', \'pnone_' . $f_t['id_member_phone'] . '\');">';
		}
	$ret_tex .= '</td>
</tr>';
	return $ret_tex;
}

/*:::::::::::::::::::::::::::::::
詳細用メールアドレス表示作成

引数
$f_m[]  メールアドレス情報arr
	[members_mail_addテーブルカラム名] => 値

戻り値
メールアドレス情報テキスト
:::::::::::::::::::::::::::::::*/
function F_makedisp_mail($f_m){
	global $G_pmsnLevel;

	$ret_tex = '
<tr>
	<td>';
	if($G_pmsnLevel > 0){
		$ret_tex .= '<input type="button" value="削除" onClick="f_delmail(' . $f_m['id_member_mail'] . ');">';
	}
	$ret_tex .= '</td>
	<td>'
		. $f_m['mail_member'] .
		'　メモ<input type="text" name="mail_' . $f_m['id_member_mail'] . '" size="30" maxlength="30" value="' . $f_m['note'] . '">';
	if($G_pmsnLevel > 0){
		$ret_tex .= '　<input type="button" value="メモ更新" onClick="f_mail_n(' . $f_m['id_member_mail'] . ', \'mail_' . $f_m['id_member_mail'] . '\');">';
	}
	$ret_tex .= '</td>
</tr>';
	return $ret_tex;
}

?>
