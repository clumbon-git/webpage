<?php
session_start();
//マイページ

//基本設定関数
require_once __DIR__ . '/../basic_function.php';
require_once __DIR__ . '/../basic_setting.php';
//DB接続
require_once __DIR__ . '/../dbip.php';

//ヘッダDB情報ロード、ログイン関連
require_once __DIR__ . '/../head_dbread.php';


//-----------------------------------------------

//ログイン確認
//array('id' => 個人識別id, 'kind' => 会員種別(1 会員, 2 職員), 'hall' => 所属館id)
if($G_login !== false){
	$result_login = true;
}

//当日
$today_date = date('Ymd');

//学童内お知らせ表示開始ページ
$disp_pageNo = 1;

isset($_GET['pn']) && $disp_pageNo = $_GET['pn'];
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
<link rel="stylesheet" href="./css/members.css?<?php echo date("Ymd-Hi"); ?>">
<link rel="stylesheet" media="screen and (max-width:800px)" href="/css/base_sp.css?<?php echo date("Ymd-Hi"); ?>">
<title><?php echo $G_npo_items['name_npo']; ?> マイページ</title>
</head>
<body>
<div id="pagebody">

<!-- header -->
<?php

include "./../head.php";

?>

	<!-- info left -->
<?php

include "./../left.php";

?>

	<!-- info main -->
	<div id="info">

<?php
		if(!$G_login){
			//ログインエラー
?>
		<h3>ログインエラー</h3>
		<p>
			ログインエラーです。<br>
			id,パスワードを確認してください。<br>
			会員の方は[会員login]、職員の方は[職員login]をクリックしてください。
		</p>
<?php
		}else{
			//ログインok
?>
		<!-- 入力 -->
		<?php
			$details_link = "";
			if($G_login['kind'] == 1){
				$details_link = "./memberdetails.php";
			}elseif($G_login['kind'] == 2){
				$details_link = "./staffdetails.php";
			}
		?>
		<h3>マイページ</h3>
		<a href="<?php echo $details_link; ?>" target="_self">・ご自身の登録情報確認</a>　　<a href="/gimmicks/configure_menu.php" target="_self">・システム管理</a><br>
		<?php echo Fl_locationDisp($G_login, $today_date); ?>
		<hr>
		<h3>開所予定</h3>
			<?php
				//直近開所予定
				include "./calendar_short_function.php";
			 ?>
		<hr>
		<h3 name="infoplace" id="infoplace">学童内お知らせ</h3>
			<?php
				//学童お知らせ
				include "./information_function.php";
			?>
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

<?php
/*:::::::::::::::::::::::::::::::
在所情報表示テキスト作成
会員ログイン時に会員児童登下所情報テキストを作成する

引数
$f_login	ログイン者情報
//array('id' => 個人識別id, 'kind' => 会員種別(1 会員, 2 職員), 'hall' => 所属館id)
$f_today	日付キー Ymd

戻り値
在所情報表示テキスト
:::::::::::::::::::::::::::::::*/
function Fl_locationDisp($f_login, $f_today){
	global $pdo;	//dbオブジェクト
	global $GV_status_select;	//所在選択テキスト
	$ret = '';

	if($f_login['kind'] == 1){
		//児童情報
		$a_children = array();	//arr[]=[カラム名=>値, ・・・](members_childテーブル)
		$stmt = $pdo -> prepare('
		SELECT * FROM `members_child`
		WHERE `activity_flg` = 1 AND `fk_id_member_home` = :home_id
		ORDER BY `enterhall_date` DESC, `firstname_kana` ASC
		');
		$stmt -> bindValue(':home_id', $f_login['id'], PDO::PARAM_INT);
		$stmt -> execute();
		$a_children = $stmt -> fetchAll();

		//在所情報
		//arr[児童id][]=array[カラム値=>値, ・・・];(child_locationテーブル)
		$a_location = array();
		$stmt = $pdo -> prepare('
		SELECT *, DATE_FORMAT(`regist_time`, "%H時%i分") as `time`
		FROM `child_location`
		WHERE `del_flg` = 0
		AND DATE_FORMAT(`regist_time`, "%Y%m%d") = :Ymd
		AND `fk_id_child` = :child_id
		ORDER BY `regist_time` ASC
		');
		foreach($a_children as $val){
			$stmt -> bindValue(':Ymd', $f_today, PDO::PARAM_STR);
			$stmt -> bindValue(':child_id', $val['id_child'], PDO::PARAM_INT);
			$stmt -> execute();
			$a_location[$val['id_child']] = $stmt -> fetchAll();
		}

		//表示テキスト作成
		if(count($a_children)){
			$ret .= '<table class="children_mypage">';
			foreach($a_children as $t_child){
				$t_now_text = "(学童不在)";
				$t_col = "col_red";
				$t_history = "";
				if(isset($a_location[$t_child['id_child']]) && count($a_location[$t_child['id_child']])){
					if($a_location[$t_child['id_child']][count($a_location[$t_child['id_child']]) - 1]['existance_status'] == 1	){
						$t_now_text ="(学童在所)";
						$t_now_num = 1;
						$t_col = "col_blue";
					}
					foreach($a_location[$t_child['id_child']] as $t_val){
						$t_history .= $t_val['time'] . $GV_status_select[$t_val['existance_status']] . "<br>";
					}
				}
				$ret .= '<tr><td>';
				//氏名----------------------------------------------
				$ret .= "<span class=\"{$t_col}\" id=\"cid{$t_child['id_child']}\">{$t_now_text}</span>{$t_child['surname']} {$t_child['firstname']}<br>";
				$ret .= '</td><td>';
				//履歴----------------------------------------------
				$ret .= $t_history;
				$ret .= '</td></tr>';
			}
			$ret .= '</table>';
		}
	}
	return $ret;
}
 ?>