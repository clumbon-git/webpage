<?php
//学童内お知らせ情報取得、表示
$t_if_id = $_SESSION['member_id'];
$t_if_kind = $_SESSION['member_kind'];
$t_if_hall 		= 0;	//所属館id 0=未定
$t_if_section = array();	//会員役務 array[]=役務id
$t_if_post 		= array();	//職員職種 array[]=職種id

//表示対象お知らせ情報格納配列
//arr[お知らせid member_news_text/id_news]=array('title'=>タイトル, 'text'=>本文);
$G_if_info = array();

$q_group		= "";	//会員、職員対象クエリ
$q_section	= "";	//会員役務対象クエリ
$q_post			= ""; //職員職種対象クエリ
$q_psn			=	""; //会員、職員個人対象クエリ

$q_join	= "";	//対象クエリまとめ

//所属館取得
$stmt = "";
if($t_if_kind == 1){
	//会員
	$stmt = $pdo -> prepare('
SELECT `fk_id_hall` AS hall_id FROM `members_home` WHERE `id_member_home` = :id
	');
}elseif($t_if_kind == 2){
	//職員
	$stmt = $pdo -> prepare('
SELECT `fk_id_hall` AS hall_id FROM `staff` WHERE `id_staff` = :id
	');
}
if($stmt){
	$stmt -> bindValue(':id', $t_if_id, PDO::PARAM_INT);
	$stmt -> execute();
	$t_if_hall = $stmt -> fetchAll()[0]['hall_id'];
}

//会員役務取得
if($t_if_kind == 1){
	$stmt = $pdo -> prepare('
SELECT `fk_id_section` FROM `section_member`
WHERE `fk_id_member_home` = :id AND `special_flg` = 1 AND `del_flg` <> 1
	');
	$stmt -> bindValue(':id', $t_if_id, PDO::PARAM_INT);
	$stmt -> execute();
	$t_arr = $stmt -> fetchAll();
	foreach($t_arr as $t_val){
		$t_if_section[] = $t_val['fk_id_section'];
	}
}

//職員職種取得
if($t_if_kind == 2){
	$stmt = $pdo -> prepare('
SELECT `fk_id_staff_post` FROM `staff`
WHERE `id_staff` = :id AND `activity_flg` = 1
	');
	$stmt -> bindValue(':id', $t_if_id, PDO::PARAM_INT);
	$stmt -> execute();
	$t_arr = $stmt -> fetchAll();
	foreach($t_arr as $t_val){
		$t_if_post[] = $t_val['fk_id_staff_post'];
	}
}

//各条件表示対象クエリ作成
if($t_if_kind == 1){
	//会員対象クエリ作成
	//会員グループ--------------------------------
	$q_group = "(t_tgt.`target_member_flg` = 1)";
	//役務条件--------------------------------
	if(count($t_if_section)){
		$i = 0;
		foreach($t_if_section as $t_val){
			($i++ > 0) && $q_section .= "OR";
			$q_section .= "(t_tgt.`fk_id_section` = {$t_val})";
		}
	}
	//会員個人条件--------------------------------
	$q_psn = "(t_tgt.`fk_id_member_home` = {$t_if_id})";

}elseif($t_if_kind == 2){
	//職員対象クエリ作成
	//職員グループ--------------------------------
	$q_group = "(t_tgt.`target_staff_flg` = 1)";
	//職種条件--------------------------------
	if(count($t_if_post)){
		$i = 0;
		foreach($t_if_post as $t_val){
			($i++ > 0) && $q_post .= "OR";
			$q_post .= "(t_tgt.`fk_id_staff_post` = {$t_val})";
		}
	}
	//職員個人条件--------------------------------
	$q_psn = "(t_tgt.`fk_id_staff` = {$t_if_id})";

}

//取得条件連結
if($q_group){
	$q_join && $q_join .= " OR ";
	$q_join .= $q_group;
}

if($q_section){
	$q_join && $q_join .= " OR ";
	$q_join .= $q_section;
}

if($q_post){
	$q_join && $q_join .= " OR ";
	$q_join .= $q_post;
}

if($q_psn){
	$q_join && $q_join .= " OR ";
	$q_join .= $q_psn;
}

if($q_join){
	$q_join = " AND({$q_join})";
}

//表示対象お知らせ取得
$stmt = $pdo -> prepare('
SELECT t_txt.`id_news`, t_txt.`title_news` AS title, t_txt.`text_news` AS text, t_txt.`regist_time` AS time
FROM `member_news_target` AS t_tgt
LEFT JOIN `member_news_text` AS t_txt
ON t_tgt.`fk_id_news` = t_txt.`id_news`
WHERE t_txt.`del_flg` <> 1
AND t_txt.`disp_flg` = 1
AND (t_tgt.`fk_id_hall` = 0 OR t_tgt.`fk_id_hall` = :hall)'
. $q_join .
' ORDER BY t_txt.`top_flg` DESC, t_txt.`id_news` DESC
');
$stmt -> bindValue(':hall', $t_if_hall, PDO::PARAM_INT);
$stmt -> execute();
$t_arr = $stmt -> fetchAll();
$infoCount = $stmt -> rowCount();

if($infoCount){
	foreach($t_arr as $val){
		$G_if_info[$val['id_news']] = array('title' => $val['title'], 'text' => $val['text'], 'time' => $val['time']);
	}
}

if($infoCount){
	if($disp_pageNo < 1){
		$disp_pageNo = 1;
	}
	$max_page = ceil($infoCount / GV_numberOfEvents_innerInfo);
	if($disp_pageNo > $max_page){
		$disp_pageNo = $max_page;
	}

	$page = F_makePaging($infoCount, GV_numberOfEvents_innerInfo, $disp_pageNo, "", "infoplace", "pn");
	$i_start = ($disp_pageNo -1) * GV_numberOfEvents_innerInfo;
	$i_last = $i_start + GV_numberOfEvents_innerInfo -1;
	echo '<div style="text-align:center">' . $page . '</div>';

	$i_countNow = -1;
	foreach($G_if_info as $key => $val){
		++$i_countNow;
		if($i_countNow < $i_start){
			continue;
		}
		if($i_countNow > $i_last){
			break;
		}
?>
<div class="infobox2">
	<span class="box-title"><?php echo $val['time']; ?></span>
	<p>
<?php
		echo "<b>「" . $val['title'] . "」</b><br>";
		echo nl2br($val['text']);
?>
	</p>
</div>
<?php
	}
	echo '<div style="text-align:center">' . $page . '</div>';
}else{
?>
<div class="infobox2">
	<p>お知らせはありません</p>
</div>
<?php
}
?>
