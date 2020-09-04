<!-- head -->
<div id="header">
<h1>
	<a href="/"><img src="<?php echo "/pic/" . $G_npo_items['pic_title']; ?>" alt="<?php echo $G_npo_items['name_npo']; ?>"></a>
<?php if(isset($hall_items)){echo $hall_items['name_hall'];} ?>
</h1>
</div>

<!-- link halls -->
<?php
if($hall_links){
	echo '<ul id="menu">';
	foreach($hall_links as $val){
		echo '<li><a href="/' . $val['folder_hall'] . '/" style="background-image:url(\'/' . $val['folder_hall'] . '/pic/' . $val['pic_link'] . '\');">' . $val['name_hall'] . 'へ</a></li>';
	}
	echo '</ul>';
}
?>
