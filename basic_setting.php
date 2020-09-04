<?php
//共通設定値

//インフォメーションメアド
$GV_mail_information = "info@example.com";
//システム管理者メアド
$GV_mail_admin = "admin@example.com";

//学童内お知らせ
//1ページ表示項目数
const GV_numberOfEvents_innerInfo = 6;
//現ページの前後指定可能ページ数
const GV_numberOfDispPage = 3;

//所在選択テキスト
$GV_status_select = array(1 => '<span class="col_blue">　登所　</span>', 2 => '<span class="col_red">出(帰宅)</span>', 3 => '<span class="col_red">出(塾遊他)</span>');

//NPOトップ画像サイズ
$GV_npo_toppic_x = 920;
$GV_npo_toppic_y = 300;

//トピック画像格納サイズ
$GV_topic_x = 1000;
$GV_topic_y = 1000;

//トピック画像サムネール格納サイズ
$GV_topic_x_s = 200;
$GV_topic_y_s = 200;

//NPOトピック画像表示数
$GV_topic_npo_sheets = 4;

//館リンク画像サイズ
$GV_hall_link_x = 230;
$GV_hall_link_y = 50;

//児童キャラクタ画像サイズ
$GV_child_pic_x = 150;
$GV_child_pic_y = 150;

//児童バーコード
$GV_cb_head = '999';	//バーコードヘッダ
$GV_cb_in = '001';	//入所フラグ
$GV_cb_go = '003';	//途中出所フラグ
$GV_cb_out = '002';	//帰宅出所フラグ

?>
