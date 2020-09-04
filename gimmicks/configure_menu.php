<?php
//コンテンツ管理選択メニュー
session_start();

//基本設定関数
require_once __DIR__ . '/../basic_function.php';
require_once __DIR__ . '/../basic_setting.php';
//DB接続
require_once __DIR__ . '/../dbip.php';
//ヘッダDB情報ロード
require_once __DIR__ . '/../head_dbread.php';

//ログイン確認
if(!$G_login){
  header('Location: /', true, 301);
}

//権限確認 Array[ギミックページid]=array( [hall] =>他館許可値 0=許可、1=制限 [kind] =>権限値 0=参照、1=参照、更新　2=管理者)
$G_arrPmsn = F_pmsnCheck($G_login);

$G_contents = array();	//コンテンツ管理ページ情報格納
$stmt = $pdo -> prepare('
SELECT t_i.`id_gimmick_item`, t_i.`name_gimmick_item`, t_p.`id_gimmick_page`, t_p.`title_gimmick_page`, t_p.`link`, t_p.`note`, t_p.`hall_flg`, t_p.`hall_get`
FROM `gimmick_page` as t_p
LEFT JOIN `gimmick_items` as t_i
ON t_p.`fk_id_gimmick_item` = t_i.`id_gimmick_item`
WHERE t_p.`disp_flg` = 1
ORDER BY t_i.`order_gimmick_item` ASC, t_p.`order_gimmick_page` ASC
');
$stmt -> execute();
$G_contents = $stmt -> fetchAll();
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
<title><?php echo $G_npo_items['name_npo']; ?> コンテンツ管理</title>
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
		<h3>コンテンツ管理</h3>
		<p>
			<?php
				$item_id = 0;
				$change_c = 0;
				$links = "";
				foreach($G_contents as $val){
					if($val['id_gimmick_item'] != $item_id){
						//機能グループ名表示
						$item_id = $val['id_gimmick_item'];
						if(++$change_c > 1){
							//グループ名切変わりでリンク表示
							echo $links;
							echo "</div><hr>";
							$links = "";
						}
						echo "<div>";
						echo $val['name_gimmick_item'] . "<br>";
					}
					if($val['hall_flg']){
						//館毎振り分け有り
						if(count($hall_links_gimmicks)){
							//館登録有り
							if(!isset($G_arrPmsn[$val['id_gimmick_page']])){
								//権限なし
								continue;
							}
							foreach($hall_links_gimmicks as $v_hall){
								if($G_arrPmsn[$val['id_gimmick_page']]['hall'] == 1 && $v_hall['id_hall'] != $G_login['hall']){
									//他館許可なし
									continue;
								}
								$links .= "<a href = \"{$val['link']}?{$val['hall_get']}={$v_hall['id_hall']}\" target = \"_self\">・{$v_hall['name_hall']}{$val['title_gimmick_page']}</a><br>";
							}
							$links .= "<br>";
						}
					}else{
						if(!isset($G_arrPmsn[$val['id_gimmick_page']])){
							//権限なし
							continue;
						}
						$links .= "<a href = \"{$val['link']}\" target = \"_self\">・{$val['title_gimmick_page']}</a><br>";
					}
				}
				echo $links;
				echo "</div><hr>";

			?>
		</p>
	</div>

<!-- footer -->
<?php
include "./../foot.php";
?>

</div>
</body>
</html>
