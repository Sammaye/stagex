<?php if($item instanceof app\models\User){
	$user = $item;
}else{
	$user = $item->following;
} ?>

<div data-id='<?php echo $user->_id ?>' class='subscription <?php if($i == 0): echo 'first'; endif; ?>' style='margin-bottom:10px;float:left;height:55px;width:400px;border:1px solid #d8d8d8;border-bottom-width:2px;'>
	<img alt='thumbnail' class='float_left' style='float:left;margin-right:10px;' src='<?php echo $user->getAvatar(55, 55) ?>'/>
	<div class='username' style='float:left;'><a style='line-height:17px;' href='<?php echo glue::http()->url('/user/view', array('id' => $user->_id)) ?>'><?php echo $user->getUsername() ?></a>
		<div style='font-size:12px;color:#666666;line-height:17px;'><?php echo $user->about ?></div>
		<div class='info' style='font-size:11px; color:#999999; line-height:17px;'><?php echo $user->totalUploads ?> videos - <?php echo $user->totalPlaylists ?> playlists</div>
	</div>
	<div class="subscribe_widget" data-user_id="<?php echo $user->_id ?>" style='float:right; margin:12px 10px 0 0;'>
		<input type="button" class='unsubscribe btn button' value="Unsubscribe"/>
	</div>	
    <div class='clearer'></div>
</div>