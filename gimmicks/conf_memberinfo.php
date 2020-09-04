<?php
//学童内お知らせ投稿
session_start();
//管理ページno
$G_gimmickno = 18;

//基本設定関数
require_once __DIR__ . '/../basic_function.php';
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

//post値取得
$P_gimmick_type = ""; //操作種別
$P_title = "";				//投稿タイトル
$P_text = "";					//投稿本文

$P_member_chks = array();		//会員対象館 arr[対象館id]=true
$P_staff_chks = array();		//職員対象館 arr[対象館id]=true
$P_section_chks = array();	//役務対象館 arr[役務id][対象館id]=true
$P_post_chks = array();			//職種対象館 arr[職種id][対象館id]=true

isset($_POST["gimmick_type"]) && $P_gimmick_type = $_POST["gimmick_type"];

if($P_gimmick_type == "toConf"){
	//確認画面===========================================
	$_SESSION['post'] = $_POST;	//選択館session退避
}elseif($P_gimmick_type == "clear"){
	//選択値sessionクリア
	$_SESSION["post"] = array();
	$P_gimmick_type == "";
}

isset($_SESSION["post"]["title"]) && $P_title = $_SESSION["post"]["title"];
isset($_SESSION["post"]["text"]) && $P_text = $_SESSION["post"]["text"];

isset($_POST["title"]) && $P_title = $_POST["title"];
isset($_POST["text"]) && $P_text = $_POST["text"];

//会員対象館格納
if(isset($_SESSION["post"]['mem_hall'])){
	foreach($_SESSION["post"]['mem_hall'] as $hallId){
		$P_member_chks[$hallId] = true;
	}
}
if(isset($_POST['mem_hall'])){
	foreach($_POST['mem_hall'] as $hallId){
		$P_member_chks[$hallId] = true;
	}
}
//職員対象館格納
if(isset($_SESSION["post"]['stf_hall'])){
	foreach($_SESSION["post"]['stf_hall'] as $hallId){
		$P_staff_chks[$hallId] = true;
	}
}
if(isset($_POST['stf_hall'])){
	foreach($_POST['stf_hall'] as $hallId){
		$P_staff_chks[$hallId] = true;
	}
}
//役務、職種対象館格納
if(isset($_SESSION["post"])){
	foreach($_SESSION["post"] as $key => $val){
		$num_section = strstr($key, 'sec_hall', false);
		$num_post = strstr($key, 'post_hall', false);

		if($num_section !== false){
			$num = substr($num_section, strlen('sec_hall'));	//idno抜き出し
			$P_section_chks[$num] = array();
			foreach($_SESSION["post"][$key] as $hallId){
				$P_section_chks[$num][$hallId] = true;
			}
		}elseif($num_post !== false){
			$num = substr($num_post, strlen('post_hall'));	//idno抜き出し
			$P_post_chks[$num] = array();
			foreach($_SESSION["post"][$key] as $hallId){
				$P_post_chks[$num][$hallId] = true;
			}
		}
	}
}
foreach($_POST as $key => $val){
	$num_section = strstr($key, 'sec_hall', false);
	$num_post = strstr($key, 'post_hall', false);

	if($num_section !== false){
		$num = substr($num_section, strlen('sec_hall'));	//idno抜き出し
		$P_section_chks[$num] = array();
		foreach($_POST[$key] as $hallId){
			$P_section_chks[$num][$hallId] = true;
		}
	}elseif($num_post !== false){
		$num = substr($num_post, strlen('post_hall'));	//idno抜き出し
		$P_post_chks[$num] = array();
		foreach($_POST[$key] as $hallId){
			$P_post_chks[$num][$hallId] = true;
		}
	}
}

$G_mess = "";	//作業メッセージ
if($P_gimmick_type == "send"){
	//投稿実行===========================================
	if(count($_SESSION['post'])){
		//投稿情報session有で実行(リロード時はスルー)
		//投稿内容登録
		$stmt = $pdo -> prepare('
			INSERT INTO `member_news_text`
			(`title_news`, `text_news`, `fk_id_member_home`, `fk_id_staff`)
			VALUES(:title, :text, :id_member, :id_staff)
		');
		$id_member = 0;
		$id_staff = 0;
		if($G_login['kind'] == 1){
			$id_member = $G_login['id'];
		}
		if($G_login['kind'] == 2){
			$id_staff = $G_login['id'];
		}
		$stmt -> bindValue(':title', $P_title, PDO::PARAM_STR);
		$stmt -> bindValue(':text', $P_text, PDO::PARAM_STR);
		$stmt -> bindValue(':id_member', $id_member, PDO::PARAM_INT);
		$stmt -> bindValue(':id_staff', $id_staff, PDO::PARAM_INT);
		$stmt -> execute();
		$set_id = $pdo -> lastInsertId();

			F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $P_gimmick_type, $P_title, $P_text);

		//投稿表示対象登録
		if(count($P_member_chks)){
			//会員
			$stmt = $pdo -> prepare('
			INSERT INTO `member_news_target`
			(`fk_id_news`, `fk_id_hall`, `target_member_flg`)
			VALUES(:id_news, :id_hall, 1)
			');
			foreach($P_member_chks as $t_hallid => $temp){
				$stmt -> bindValue(':id_news', $set_id, PDO::PARAM_INT);
				$stmt -> bindValue(':id_hall', $t_hallid, PDO::PARAM_INT);
				$stmt -> execute();
			}
		}
		if(count($P_staff_chks)){
			//職員
			$stmt = $pdo -> prepare('
			INSERT INTO `member_news_target`
			(`fk_id_news`, `fk_id_hall`, `target_staff_flg`)
			VALUES(:id_news, :id_hall, 1)
			');
			foreach($P_staff_chks as $t_hallid => $temp){
				$stmt -> bindValue(':id_news', $set_id, PDO::PARAM_INT);
				$stmt -> bindValue(':id_hall', $t_hallid, PDO::PARAM_INT);
				$stmt -> execute();
			}
		}
		if(count($P_section_chks)){
			//役務
			$stmt = $pdo -> prepare('
			INSERT INTO `member_news_target`
			(`fk_id_news`, `fk_id_hall`, `fk_id_section`)
			VALUES(:id_news, :id_hall, :id_section)
			');
			foreach($P_section_chks as $t_sec_id => $halls_id){
				foreach($halls_id as $t_hall_id => $temp){
					$stmt -> bindValue(':id_news', $set_id, PDO::PARAM_INT);
					$stmt -> bindValue(':id_hall', $t_hall_id, PDO::PARAM_INT);
					$stmt -> bindValue(':id_section', $t_sec_id, PDO::PARAM_INT);
					$stmt -> execute();
				}
			}
		}
		if(count($P_post_chks)){
			//職種
			$stmt = $pdo -> prepare('
			INSERT INTO `member_news_target`
			(`fk_id_news`, `fk_id_hall`, `fk_id_staff_post`)
			VALUES(:id_news, :id_hall, :id_post)
			');
			foreach($P_post_chks as $t_post_id => $halls_id){
				foreach($halls_id as $t_hall_id => $temp){
					$stmt -> bindValue(':id_news', $set_id, PDO::PARAM_INT);
					$stmt -> bindValue(':id_hall', $t_hall_id, PDO::PARAM_INT);
					$stmt -> bindValue(':id_post', $t_post_id, PDO::PARAM_INT);
					$stmt -> execute();
				}
			}
		}
	}

	$_SESSION['post'] = array();	//投稿情報session削除
}

//情報格納
//役務項目 arr[]=arr('id_section_item'=>項目id,'name_section_item'=>項目名)
$G_section_i = array();

//役務種別 arr[項目id][]=arr('id_section'=>役務id,'fk_id_section_item'=>項目id,'name_section'=>役務名)
$G_sections = array();

$G_posts = array();			//職種
$G_members = array();		//追加選択用会員情報
$G_staffs = array();		//追加選択用職員情報
$G_halls = array();			//館情報array[]=array([id_hall] => hall_id,[name_hall]=>館名)

//館情報取得
$stmt = $pdo -> prepare('SELECT `id_hall`, `name_hall` FROM `halls` WHERE `activity_flg` = 1 ORDER BY `id_hall` ASC');
$stmt -> execute();
$G_halls = $stmt -> fetchAll();


//役務項目取得
$stmt = $pdo -> prepare('SELECT `id_section_item`, `name_section_item` FROM `section_items`
WHERE `del_flg` <> 1 ORDER BY `order_section_item` ASC');
$stmt -> execute();
$G_section_i = $stmt -> fetchall();

//役務取得
$stmt = $pdo -> prepare('SELECT `id_section`, `fk_id_section_item`, `name_section` FROM `section_kind`
WHERE `del_flg` <> 1 ORDER BY `order_section` ASC');
$stmt -> execute();
$t_section = $stmt -> fetchall();
foreach($t_section as $val){
	if(!isset($G_sections[$val['fk_id_section_item']])){
		$G_sections[$val['fk_id_section_item']] = array();
	}
	$G_sections[$val['fk_id_section_item']][] = $val;
}

//職種情報取得
$stmt = $pdo -> prepare('SELECT `id_staff_post`, `post` FROM `staff_posts` WHERE `del_flg` <> 1 ORDER BY `id_staff_post` ASC');
$stmt -> execute();
$G_posts = $stmt -> fetchAll();


//追加選択用会員情報取得
$stmt = $pdo -> prepare('
SELECT t_home.`id_member_home`, t_hall.`name_hall`, t_prt.`surname`, t_prt.`firstname`
FROM `members_home` AS t_home
LEFT JOIN `halls` AS t_hall ON t_home.`fk_id_hall` = t_hall.`id_hall`
LEFT JOIN `members_parent` AS t_prt ON t_home.`id_member_home` = t_prt.`fk_id_member_home`
WHERE t_home.`activity_flg` = 1 AND t_prt.`activity_flg` = 1 AND t_prt.`delegate_flg` = 1
ORDER BY t_hall.id_hall ASC, t_prt.`surname_kana` ASC, t_prt.`firstname_kana` ASC
');
$stmt -> execute();
$G_members = $stmt -> fetchAll();

//追加選択用職員情報取得
$stmt = $pdo -> prepare('
SELECT t_staff.`id_staff`, t_staff.`surname`, t_staff.`firstname`, t_hall.`name_hall`, t_post.`post`
FROM `staff` AS t_staff
LEFT JOIN `staff_posts` AS t_post
ON t_staff.`fk_id_staff_post` = t_post.`id_staff_post`
LEFT JOIN `halls` AS t_hall
ON t_staff.`fk_id_hall` = t_hall.`id_hall`
WHERE t_staff.`activity_flg` = 1 AND t_post.`del_flg` <> 1
ORDER BY t_staff.`fk_id_hall` ASC, t_staff.`fk_id_staff_post` ASC
');
$stmt -> execute();
$G_staffs = $stmt -> fetchAll();

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
<script src="./js/conf_memberinfo.js?<?php echo date("Ymd-Hi"); ?>"></script>
<title><?php echo $G_npo_items['name_npo']; ?> 学童内お知らせ投稿</title>
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
		<h3>学童内お知らせ投稿</h3>
		<p>
			<a href = "./configure_menu.php" target = "_self">・管理トップへ戻る</a><br>
			<a href = "" target = "_self">・学童内お知らせ投稿トップへ戻る</a><br>
		</p>
		<hr>
<?php
if($P_gimmick_type == "send"){
	//投稿実行--------------------------------------------------
?>
<p>
	<h3>お知らせ投稿</h3>
	投稿を実行しました
</p>
<hr>
<?php
}elseif($P_gimmick_type == "toConf"){
	//確認画面--------------------------------------------------
?>
<p>
	<form action="" method="post" onSubmit="return false;">
		<h3>お知らせ確認</h3>
		タイトル:<br>
		<?php
			echo $P_title . "<br>";
		?>

		本文:<br>
		<?php
			echo nl2br($P_text) . "<br>";
		?>
		<h3>表示対象確認</h3>
<?php
$html_member = "";	//会員館選択テキスト
$html_staff = "";	//職員館選択テキスト

//会員館選択テキスト作成 checkboxname=mem_hall
$html_member = '[会員]<br>';
//職員選択テキスト作成 checkboxname=stf_hall
$html_staff = '[職員]<br>';

//職員職種選択テキスト作成 checkboxname=post_hall.id
$a_html_post = array();	//arr[職種id]=選択テキスト
foreach($G_posts as $t_post){
	$a_html_post[$t_post['id_staff_post']] = $t_post['post'] . '<br>';
}

//役員、係選択テキスト作成 checkboxname=sec_hall.id
$a_html_sections = array();	//arr[役務項目id][役務id]=選択テキスト
foreach($G_sections as $t_sec_iditem => $t_arrSections){
	if(!isset($a_html_sections[$t_sec_iditem])){
		$a_html_sections[$t_sec_iditem] = array();
	}
	foreach($t_arrSections as $t_section){
		$a_html_sections[$t_sec_iditem][$t_section['id_section']] = $t_section['name_section'] . '<br>　';
	}
}

foreach($G_halls as $t_hall){
	//館ループ
	if($G_hallLevel == 1 && $t_hall['id_hall'] != $G_login['hall']){
	continue;
	}
	//館毎にまとめて作成
	$t_check_mem = "";
	$t_check_stf = "";

	//会員
	if(isset($P_member_chks[$t_hall['id_hall']])){
		$color = "col_blue";
	}else{
		$color = "col_gray";
	}
	$html_member .= "<span class=\"{$color}\">{$t_hall['name_hall']}_会員</span> ";
//職員
	if(isset($P_staff_chks[$t_hall['id_hall']])){
		$color = "col_blue";
	}else{
		$color = "col_gray";
	}
	$html_staff .= "<span class=\"{$color}\">{$t_hall['name_hall']}_職員</span> ";
	//役務
	foreach($G_sections as $t_sec_iditem => $t_arrSections){
		foreach($t_arrSections as $t_section){
			if(isset($P_section_chks[$t_section['id_section']][$t_hall['id_hall']])){
				$color = "col_blue";
			}else{
				$color = "col_gray";
			}
			$a_html_sections[$t_sec_iditem][$t_section['id_section']] .= "<span class=\"{$color}\">{$t_hall['name_hall']}</span> ";
		}
	}
	//職種
	foreach($G_posts as $val_p){
		if(isset($P_post_chks[$val_p['id_staff_post']][$t_hall['id_hall']])){
			$color = "col_blue";
		}else{
			$color = "col_gray";
		}
		$a_html_post[$val_p['id_staff_post']] .= "<span class=\"{$color}\">{$t_hall['name_hall']}</span> ";
	}
}

$html_member .= "<br><span class='t80per'>※選択館の全会員に表示されます</span><br>";
$html_staff .= "<br><span class='t80per'>※選択館の全職員に表示されます</span><br>";

?>


<?php echo $html_member; ?>
<?php echo $html_staff; ?>
<hr>
<?php
foreach($G_section_i as $val_i){
//役務分類ループ
if(isset($a_html_sections[$val_i['id_section_item']])){
echo "<br>[{$val_i['name_section_item']}]<br>";
foreach($a_html_sections[$val_i['id_section_item']] as $v_secid => $v_secchk){
	echo $v_secchk . "<br>";
}
}
}

echo "<br>[職員職種]<br>";
foreach($G_posts as $val_p){
//職種ループ
echo $a_html_post[$val_p['id_staff_post']] . "<br>";
}
?>
		<br><br><input type="button" value="　投稿実行　" onClick="f_send(this.form);">
		　<input type="button" value="　戻る　" onClick="location.href='';">
		<input type="hidden" name="gimmick_type" value="">
	</form>
</p>
<hr>

<?php
}else{
	//入力画面--------------------------------------------------
?>
		<p>
			<form action="" method="post" onSubmit="return false;">
				<h3>お知らせ入力</h3>
				タイトル:<br>
				<input type="text" name="title" size="70" value="<?php echo $P_title; ?>" placeholder="タイトル"><br>

				本文:<br>
<textarea rows="15" cols="70" name="text" placeholder="本文">
<?php echo $P_text; ?>
</textarea><br>
				<h3>表示対象選択</h3>
<?php
$html_member = "";	//会員館選択テキスト
$html_staff = "";	//職員館選択テキスト

//会員館選択テキスト作成 checkboxname=mem_hall
$html_member = '[会員]<br><input type="button" value="全館選択" onClick="f_allCheck(this, this.form, \'mem_hall[]\');">　';
//職員選択テキスト作成 checkboxname=stf_hall
$html_staff = '[職員]<br><input type="button" value="全館選択" onClick="f_allCheck(this, this.form, \'stf_hall[]\');">　';

//職員職種選択テキスト作成 checkboxname=post_hall.id
$a_html_post = array();	//arr[職種id]=選択テキスト
foreach($G_posts as $t_post){
	$a_html_post[$t_post['id_staff_post']] = $t_post['post'] . '<br><input type="button" value="全館選択" onClick="f_allCheck(this, this.form, \'post_hall' . $t_post['id_staff_post'] . '[]\');">　';
}

//役員、係選択テキスト作成 checkboxname=sec_hall.id
$a_html_sections = array();	//arr[役務項目id][役務id]=選択テキスト
foreach($G_sections as $t_sec_iditem => $t_arrSections){
	if(!isset($a_html_sections[$t_sec_iditem])){
		$a_html_sections[$t_sec_iditem] = array();
	}
	foreach($t_arrSections as $t_section){
		$a_html_sections[$t_sec_iditem][$t_section['id_section']] = $t_section['name_section'] . '<br><input type="button" value="全館選択" onClick="f_allCheck(this, this.form, \'sec_hall' . $t_section['id_section'] . '[]\');">　';
	}
}

foreach($G_halls as $t_hall){
	//館ループ
	if($G_hallLevel == 1 && $t_hall['id_hall'] != $G_login['hall']){
		continue;
	}
	//館毎にまとめて作成
	$t_check_mem = "";
	$t_check_stf = "";

	//会員
	$checked = '';
	isset($P_member_chks[$t_hall['id_hall']]) && $checked = ' checked="checked"';
	$html_member .= "<label><input type=\"checkbox\" name=\"mem_hall[]\" value={$t_hall['id_hall']} {$t_check_mem}{$checked}>{$t_hall['name_hall']}_会員</label> ";
	//職員
	$checked = '';
	isset($P_staff_chks[$t_hall['id_hall']]) && $checked = ' checked="checked"';
$html_staff .= "<label><input type=\"checkbox\" name=\"stf_hall[]\" value={$t_hall['id_hall']} {$t_check_stf}{$checked}>{$t_hall['name_hall']}_職員</label> ";
	//役務
	foreach($G_sections as $t_sec_iditem => $t_arrSections){
		foreach($t_arrSections as $t_section){
			$checked = '';
			isset($P_section_chks[$t_section['id_section']][$t_hall['id_hall']]) && $checked = ' checked="checked"';
			$a_html_sections[$t_sec_iditem][$t_section['id_section']] .= "<label><input type=\"checkbox\" name=\"sec_hall{$t_section['id_section']}[]\" value={$t_hall['id_hall']} {$t_check_mem}{$checked}>{$t_hall['name_hall']}　</label> ";
		}
	}
	//職種
	foreach($G_posts as $val_p){
		$checked = '';
		isset($P_post_chks[$val_p['id_staff_post']][$t_hall['id_hall']]) && $checked = ' checked="checked"';
		$a_html_post[$val_p['id_staff_post']] .= "<label><input type=\"checkbox\" name=\"post_hall{$val_p['id_staff_post']}[]\" value={$t_hall['id_hall']} {$t_check_mem}{$checked}>{$t_hall['name_hall']}　</label> ";
	}
}

$html_member .= "<br><span class='t80per'>※選択館の全会員に表示されます</span><br>";
$html_staff .= "<br><span class='t80per'>※選択館の全職員に表示されます</span><br>";

?>


<?php echo $html_member; ?>
<?php echo $html_staff; ?>
<input type="button" value="　投稿確認　" onClick="f_toConf(this.form);">
　<input type="button" value="　入力消去　" onClick="f_crear(this.form);">
<hr>
<?php
foreach($G_section_i as $val_i){
	//役務分類ループ
	if(isset($a_html_sections[$val_i['id_section_item']])){
		echo "<br>[{$val_i['name_section_item']}]<br>";
		foreach($a_html_sections[$val_i['id_section_item']] as $v_secid => $v_secchk){
			echo $v_secchk . "<br>";
		}
	}
}

echo "<br>[職員職種]<br>";
foreach($G_posts as $val_p){
	//職種ループ
	echo $a_html_post[$val_p['id_staff_post']] . "<br>";
}
?>
				<br><br><input type="button" value="　投稿確認　" onClick="f_toConf(this.form);">
				　<input type="button" value="　入力消去　" onClick="f_crear(this.form);">
				<input type="hidden" name="gimmick_type" value="">
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
