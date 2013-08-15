<?php if($item instanceof app\models\User){
	$user = $item;
}else{
	$user = $item->following;
} ?>

<div data-id='<?php echo $user->_id ?>' class='subscription <?php if($i == 0): echo 'first'; endif; ?>' style='margin-bottom:10px;float:left;height:75px;width:400px;border:1px solid #d8d8d8;border-bottom-width:2px;'>
	<img alt='thumbnail' class='float_left' style='float:left;margin-right:10px;width:75px;height:75px;' src='<?php echo $user->getAvatar(125, 125) ?>'/>
	<div class='username' style='float:left;margin-top:20px;overflow:hidden;'><a style='line-height:20px;font-size:16px;font-weight:bold;' href='<?php echo glue::http()->url('/user/view', array('id' => $user->_id)) ?>'><?php echo $user->getUsername() ?></a>
		<div class='info' style='font-size:11px; color:#666666; line-height:20px; margin-top:2px;'><?php echo $user->totalUploads ?> videos - <?php echo $user->totalPlaylists ?> playlists</div>
	</div>
	<div class="subscribe_widget" data-user_id="<?php echo $user->_id ?>" style='float:right; margin:23px 10px 0 0;'>
		<input type="button" class='unsubscribe btn button' value="Unsubscribe"/>
	</div>	
</div>