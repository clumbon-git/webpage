<?php
//コンテンツ管理権限一覧
session_start();
//管理ページno
$G_gimmickno = 16;

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

$G_contents = array();	//コンテンツ管理ページ情報格納
$G_accesses = array();	//アクセス権限情報格納
//グループ	arr[ギミックページid]['group'][1=会員グループ、2=職員グループ]=*
//役務			arr[ギミックページid]['section'][役務id]=*
//職種			arr[ギミックページid]['posts'][職種id]=*
//会員			arr[ギミックページid]['member'][会員基id]=*
//職員			arr[ギミックページid]['staff'][スタッフid]=*
//* = array('hall'=>他館アクセス制限 0=無、1=有, 'kind'=>処理権限 0=参照、1=参照、更新　2=管理者, 'name'=>名称)

//コンテンツ管理ページ情報取得
$stmt = $pdo -> prepare('
SELECT t_i.`id_gimmick_item`, t_i.`name_gimmick_item`, t_p.`id_gimmick_page`, t_p.`title_gimmick_page`, t_p.`link`, t_p.`note`, t_p.`hall_flg`, t_p.`hall_get`
FROM `gimmick_page` as t_p
LEFT JOIN `gimmick_items` as t_i
ON t_p.`fk_id_gimmick_item` = t_i.`id_gimmick_item`
ORDER BY t_i.`order_gimmick_item` ASC, t_p.`order_gimmick_page` ASC
');
$stmt -> execute();
$G_contents = $stmt -> fetchAll();

//アクセス権限情報取得
//グループ
$stmt = $pdo -> prepare('
SELECT t_link.`fk_id_gimic_page` AS id_gimic, t_link.`kind_group` AS id_target, t_link.`limit_hall` AS hall, t_link.`kind_process` AS kind,
(CASE
WHEN t_link.`kind_group` = 1 THEN "会員"
WHEN t_link.`kind_group` = 2 THEN "職員"
ELSE NULL END
	) as `name`
FROM `access_link_group` AS t_link
ORDER BY id_target ASC
');
$stmt -> execute();
$a_access = $stmt -> fetchAll();
F_setAccess($a_access, "group");
//役務
$stmt = $pdo -> prepare('
SELECT t_link.`fk_id_gimic_page` AS id_gimic, t_link.`fk_id_section` AS id_target, t_link.`limit_hall` AS hall, t_link.`kind_process` AS kind,
t_sec.`name_section` AS `name`
FROM `access_link_section` AS t_link
LEFT JOIN `section_kind` AS t_sec
ON t_link.`fk_id_section` = t_sec.`id_section`
WHERE t_sec.`del_flg` = 0
ORDER BY id_target ASC
');
$stmt -> execute();
$a_access = $stmt -> fetchAll();
F_setAccess($a_access, "section");
//職種
$stmt = $pdo -> prepare('
SELECT t_link.`fk_id_gimic_page` AS id_gimic, t_link.`fk_id_staff_post` AS id_target, t_link.`limit_hall` AS hall, t_link.`kind_process` AS kind,
t_st.`post` AS `name`
FROM `access_link_posts` AS t_link
LEFT JOIN `staff_posts` AS t_st
ON t_link.`fk_id_staff_post` = t_st.`id_staff_post`
WHERE t_st.`del_flg` = 0
ORDER BY id_target ASC
');
$stmt -> execute();
$a_access = $stmt -> fetchAll();
F_setAccess($a_access, "posts");
//会員
$stmt = $pdo -> prepare('
SELECT t_link.`fk_id_gimic_page` AS id_gimic, t_link.`fk_id_member_home` AS id_target, t_link.`limit_hall` AS hall, t_link.`kind_process` AS kind,
CONCAT("(", t_hall.`name_hall`, ")", t_pa.`surname`, " ", t_pa.`firstname`) AS `name`
FROM `access_link_member` AS t_link
LEFT JOIN `members_home` AS t_home
ON t_link.`fk_id_member_home` = t_home.`id_member_home`
LEFT JOIN `halls` AS t_hall
ON t_home.`fk_id_hall` = t_hall.`id_hall`
LEFT JOIN `members_parent` AS t_pa
ON t_home.`id_member_home` = t_pa.`fk_id_member_home`
WHERE t_home.`activity_flg` = 1 AND t_pa.`activity_flg` = 1 AND t_pa.`delegate_flg` = 1
ORDER BY id_target ASC
');
$stmt -> execute();
$a_access = $stmt -> fetchAll();
F_setAccess($a_access, "member");
//職員
$stmt = $pdo -> prepare('
SELECT t_link.`fk_id_gimic_page` AS id_gimic, t_link.`fk_id_staff` AS id_target, t_link.`limit_hall` AS hall, t_link.`kind_process` AS kind,
CONCAT("(", t_hall.`name_hall`, ")", t_staff.`surname`, " ", t_staff.`firstname`) AS `name`
FROM `access_link_staff` AS t_link
LEFT JOIN `staff` as t_staff
ON t_link.`fk_id_staff` = t_staff.`id_staff`
LEFT JOIN `halls` AS t_hall
ON t_staff.`fk_id_hall` = t_hall.`id_hall`
WHERE t_staff.`activity_flg` = 1
ORDER BY id_target ASC
');
$stmt -> execute();
$a_access = $stmt -> fetchAll();
F_setAccess($a_access, "staff");

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
<title><?php echo $G_npo_items['name_npo']; ?> コンテンツ管理権限一覧</title>
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
		<h3>コンテンツ管理権限一覧</h3>
		<p>
			<a href = "./configure_menu.php" target = "_self">・管理トップへ戻る</a><br>
		</p>
		<hr>
		<p>
			<form action="./conf_permissionedit.php" method="post">
			<?php
				$item_id = 0;
				$t_countitem = 0;

				//他館アクセス制限タイトル
				$arr_hall = array(0 => "全館許可", 1 => "他館制限");
				//処理権限
				$arr_kind = array(0 => "参照", 1 => "参照、更新", 2 => "参照、更新、承認");

				foreach($G_contents as $val){
					if($val['id_gimmick_item'] != $item_id){
						$item_id = $val['id_gimmick_item'];
						if($t_countitem++ > 0){
							echo "</table></div><hr>";
						}
						echo '<div><table class="config">';
						echo '<tr><th colspan="2">' . $val['name_gimmick_item'] . '</th></tr>';
					}
			?>
					<tr>
						<td>
							<?php
							if($G_pmsnLevel > 0){
								//編集権限あり
								echo '<input type="button" value="編集" onClick="this.form.item_id.value=' . $val['id_gimmick_page'] . ';submit();">';
							}
							?>

							<?php echo "<b>{$val['title_gimmick_page']}</b>"; ?>
							<br>
							<?php
							if($val['note']){
								echo "「{$val['note']}」";
							}
							?>
						</td>
						<td>
			<?php
				//紐付け権限対象表示
				//グループ
				if(isset($G_accesses[$val['id_gimmick_page']]['group'])){
					$ac_arr = $G_accesses[$val['id_gimmick_page']]['group'];
					foreach($ac_arr as $ac_val){
						echo "[" . $ac_val['name'] . "]<br>";
						echo "(<span class=\"col_blue\">" . $arr_kind[$ac_val['kind']] . "</span>)";
						echo "(" . $arr_hall[$ac_val['hall']] . ")";
						echo '<br>';
					}
				}
				//役務
				if(isset($G_accesses[$val['id_gimmick_page']]['section'])){
					$ac_arr = $G_accesses[$val['id_gimmick_page']]['section'];
					foreach($ac_arr as $ac_val){
						echo "[" . $ac_val['name'] . "]<br>";
						echo "(<span class=\"col_blue\">" . $arr_kind[$ac_val['kind']] . "</span>)";
						echo "(" . $arr_hall[$ac_val['hall']] . ")";
						echo '<br>';
					}
				}
				//職種
				if(isset($G_accesses[$val['id_gimmick_page']]['posts'])){
					$ac_arr = $G_accesses[$val['id_gimmick_page']]['posts'];
					foreach($ac_arr as $ac_val){
						echo "[" . $ac_val['name'] . "]<br>";
						echo "(<span class=\"col_blue\">" . $arr_kind[$ac_val['kind']] . "</span>)";
						echo "(" . $arr_hall[$ac_val['hall']] . ")";
						echo '<br>';
					}
				}
				//会員
				if(isset($G_accesses[$val['id_gimmick_page']]['member'])){
					$ac_arr = $G_accesses[$val['id_gimmick_page']]['member'];
					foreach($ac_arr as $ac_val){
						echo "[" . $ac_val['name'] . "]<br>";
						echo "(<span class=\"col_blue\">" . $arr_kind[$ac_val['kind']] . "</span>)";
						echo "(" . $arr_hall[$ac_val['hall']] . ")";
						echo '<br>';
					}
				}
				//会員
				if(isset($G_accesses[$val['id_gimmick_page']]['staff'])){
					$ac_arr = $G_accesses[$val['id_gimmick_page']]['staff'];
					foreach($ac_arr as $ac_val){
						echo "[" . $ac_val['name'] . "]<br>";
						echo "(<span class=\"col_blue\">" . $arr_kind[$ac_val['kind']] . "</span>)";
						echo "(" . $arr_hall[$ac_val['hall']] . ")";
						echo '<br>';
					}
				}
			?>
						</td>
					</tr>
			<?php
				}
				if($t_countitem > 0){
					echo "</table></div><hr>";
				}
			?>
			<input type="hidden" name="gimmick_type" value="">
			<input type="hidden" name="item_id" value=0>
		</form>
		</p>
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
アクセス権限情報格納

引数
$f_arr = 権限arr
$f_key = 権限対象キー

戻り値
$G_accesses =
グループ	arr[ギミックページid]['group'][1=会員グループ、2=職員グループ]=*
役務			arr[ギミックページid]['section'][役務id]=*
職種			arr[ギミックページid]['posts'][職種id]=*
会員			arr[ギミックページid]['member'][会員基id]=*
職員			arr[ギミックページid]['staff'][スタッフid]=*
* = array('hall'=>他館アクセス制限 0=無、1=有, 'kind'=>処理権限 0=参照、1=参照、更新　2=管理者, 'name'=>名称)

:::::::::::::::::::::::::::::::*/
function F_setAccess($f_arr, $f_key){
	global $G_accesses;

	foreach($f_arr as $arr){
		if(!isset($G_accesses[$arr['id_gimic']])){
			$G_accesses[$arr['id_gimic']] = array();
		}
		if(!isset($G_accesses[$arr['id_gimic']][$f_key])){
			$G_accesses[$arr['id_gimic']][$f_key] = array();
		}
		$G_accesses[$arr['id_gimic']][$f_key][] = array('hall' => $arr['hall'], 'kind' => $arr['kind'], 'name' => $arr['name']);
	}
}
?>
