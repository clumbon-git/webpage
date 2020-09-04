<?php
//NPOページトップ画像管理
session_start();

//管理ページno
$G_gimmickno = 3;

//基本設定関数
require_once __DIR__.'/../basic_function.php';
//基本設定値
require __DIR__.'/../basic_setting.php';
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

//格納
$top_pics = array();	//トップ画像情報取得

//画像格納パス
$G_path = "./../pic/";

//メッセージ
$G_mess = "";
//アップ処理フラグ
$G_upflg = false;

//post値取得
$G_id_pic = 0; //トップ画像id
$G_gimmick_type = ""; //操作種別 disp_change 表示切替
isset($_POST["id_pic"]) && $G_id_pic = $_POST["id_pic"];
isset($_POST["gimmick_type"]) && $G_gimmick_type = $_POST["gimmick_type"];

//操作実行
if($G_gimmick_type == "disp_change"){
	//表示切替
	$stmt = $pdo -> prepare('update npo_top_pics set disp_flg = 1 - disp_flg where id_pic = :id_pic');
	$stmt -> bindValue(':id_pic', $G_id_pic, PDO::PARAM_INT);
	$stmt -> execute();

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $G_gimmick_type, $G_id_pic);

}elseif($G_gimmick_type == "pic_del"){
	//削除
	$stmt = $pdo -> prepare('update npo_top_pics set `del_flg` = 1 where id_pic = :id_pic');
	$stmt -> bindValue(':id_pic', $G_id_pic, PDO::PARAM_INT);
	$stmt -> execute();

		F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $G_gimmick_type, $G_id_pic);

}elseif($G_gimmick_type == "pic_add"){
	//画像追加
	if(isset($_FILES['new_pic']) && is_uploaded_file($_FILES['new_pic']['tmp_name'])){
		$mk_fname = 'top_head_' . date("YmdHis") . mt_rand();
		list($pic_arc_x, $pic_arc_y, $pic_type) = getimagesize($_FILES['new_pic']['tmp_name']);
		$flg_pic_type = false;
		switch($pic_type){
			case 1:	//IMAGETYPE_GIF:
				$mk_fname .= '.gif';
				$image_arc = imagecreatefromgif($_FILES['new_pic']['tmp_name']);
				$flg_pic_type = true;
				break;
			case 2:	//IMAGETYPE_JPEG:
				$mk_fname .= '.jpg';
				$image_arc = imagecreatefromjpeg($_FILES['new_pic']['tmp_name']);
				$flg_pic_type = true;
				break;
			case 3:	//IMAGETYPE_PNG:
				$mk_fname .= '.png';
				$image_arc = imagecreatefrompng($_FILES['new_pic']['tmp_name']);
				$flg_pic_type = true;
				break;
			default:
				if($flg_pic_type == false){
					$G_mess = "画像ファイルを選択してください(jpeg, gif, pngが使用可)<br>";
				}
		}
		//アップロード確認
		if($flg_pic_type){
			$image_put = ImageCreateTrueColor($GV_npo_toppic_x, $GV_npo_toppic_y);
			$col_white = imagecolorallocatealpha($image_put, 255, 255, 255, 127);
			imagefill($image_put, 0, 0, $col_white);
			//リサイズ値計算
			list($new_x, $new_y) = F_calcPicResizeFill($GV_npo_toppic_x, $GV_npo_toppic_y, $pic_arc_x, $pic_arc_y);
			imagecopyresampled(
				$image_put, $image_arc,
				0, 0, 0, 0,
				$new_x, $new_y,
				$pic_arc_x, $pic_arc_y
			);
			switch($pic_type){
				case 1:	//IMAGETYPE_GIF:
					if(imagegif($image_put, $G_path . $mk_fname)){
						$G_upflg = true;
					}
					break;
				case 2:	//IMAGETYPE_JPEG:
					if(imagejpeg($image_put, $G_path . $mk_fname, 85)){
						$G_upflg = true;
					}
					break;
				case 3:	//IMAGETYPE_PNG:
					if(imagepng($image_put, $G_path . $mk_fname)){
						$G_upflg = true;
					}
					break;
				default:
					if($G_upflg == false){
						$G_mess = "アップロードに失敗しました<br>画像のフォーマットを確認するか、他の画像ファイルでお試しください<br>";
					}
					imagedestroy($image_put);
			}

			if($G_upflg){
				$G_mess = basename($_FILES['new_pic']['name']) . 'をアップロードしました<br>表示切替でトップページに表示されます';

				//DB登録
				$stmt = $pdo -> prepare('insert into npo_top_pics(pic_top, disp_flg) values(:fname, 0)');
				$stmt -> bindValue(':fname', $mk_fname, PDO::PARAM_STR);
				$stmt -> execute();
			}elseif(!$G_mess){
				$G_mess = "画像ファイル取得に失敗しました<br>ファイル選択からもう一度お試しください";
			}

					F_workLog($G_login['id'], $G_login['kind'], __FILE__, $G_gimmickno, $G_gimmick_type, $mk_fname);

		}
	}
}

//トップ画像情報取得
$stmt = $pdo -> query('SELECT id_pic, pic_top, disp_flg FROM npo_top_pics WHERE del_flg = 0');
$top_pics = $stmt -> fetchAll();

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta NAME=”ROBOTS” CONTENT=”NOINDEX,NOFOLLOW,NOARCHIVE”>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="description" content="<?php echo $G_npo_items['name_npo']; ?>">
<meta name="keywords" content="">
<link rel="stylesheet" href="/css/base.css?<?php echo date("Ymd-Hi"); ?>">
<link rel="stylesheet" media="screen and (max-width:800px)" href="/css/base_sp.css?<?php echo date("Ymd-Hi"); ?>">
<link rel="stylesheet" href="./css/gimmicks.css?<?php echo date("Ymd-Hi"); ?>">
<link rel="stylesheet" href="./css/conf_npotop_pic.css?<?php echo date("Ymd-Hi"); ?>">
<script src="./js/conf_npotop_pic.js"></script>
<title><?php echo $G_npo_items['name_npo']; ?> NPOページトップ画像管理</title>
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

<!-- 入力 -->
		<h3>NPOページトップ画像管理</h3>
		<p>
			<a href = "./configure_menu.php" target = "_self">・管理トップへ戻る</a><br>
		</p>
		<hr>
		<p>
			<div>
				「表示中」設定の画像からトップページアクセス時にランダムで表示されます。
			</div>
<?php
$G_upflg?$col = "col_blue": $col = "col_red";
echo '<span class="' . $col . '">' . $G_mess . '</span>';
?>
		<form name="fm" action="" method="post">
			<table class = "config">
<?php
$t_count = 0; //表示行数
foreach($top_pics as $val){
	$fr_id = $val['id_pic'];      //メニューid
	$fr_fname = $val['pic_top'];  //画像ファイル名
	$fr_disp = $val['disp_flg'];  //表示フラグ
	//表示状態
	$fr_disp_val = $fr_disp?"<span class=\"col_blue\">表示中</span>":"<span class=\"col_red\">非表示</span>";
?>
				<tr>
					<td><?php echo ++$t_count; ?></td>
					<td><?php echo $fr_disp_val; ?>
						<?php
						if($G_pmsnLevel > 0){
						?>
						<input type="button" value="切替" onclick="f_dispchange(<?php echo $fr_id; ?>);">
						<?php
						}
						?>
					</td>
					<td>
							<img src="/pic/<?php echo $fr_fname; ?>" alt="<?php echo $G_npo_items['name_npo']; ?>のイメージ画像">
					</td>
<?php
if($G_pmsnLevel > 0){
?>
					<td><input type="button" value="削除" onclick="f_del(<?php echo $fr_id; ?>, <?php echo $t_count; ?>);"></td>
<?php
}
?>
				</tr>
<?php
}
?>
			</table>
			<input type="hidden" name="id_pic" value=0>
			<input type="hidden" name="gimmick_type" value="">
		</form>
		</p>
		<hr>
		<p>
		<div>
			「表示サイズは<?php echo $GV_npo_toppic_x; ?>x<?php echo $GV_npo_toppic_y; ?>ピクセル」<br>
			アップ画像は比率を保ち、このサイズに隙間なく表示されるよう左上を基準に拡大縮小されます。<br>
			はみ出した部分はカットされます。<br>
			アップロード後に表示切替でトップページに反映します。
		</div>
		<?php
		if($G_pmsnLevel > 0){
		?>
		<form name="pic_add" action="" method="post" enctype="multipart/form-data">
			<input type="file" name="new_pic">
			<input type="button" value="画像追加" onclick="f_up();">
			<input type="hidden" name="gimmick_type" value="">
		</form>
		<?php
		}
		?>
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
