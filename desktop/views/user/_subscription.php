<?php if($item instanceof app\models\User){
	$user = $item;
}else{
	$user = $item->following;
} ?>
<div data-id='<?php echo $user->_id ?>' class='subscription'>
	<img alt='<?php echo $user->getUsername() ?>' class='thumbnail' src='<?php echo $user->getAvatar(125, 125) ?>'/>
	<div class='username'>
		<a href='<?php echo glue::http()->url('/user/view', array('id' => $user->_id)) ?>'><?php echo $user->getUsername() ?></a>
		<div class='info'><?php echo $user->totalUploads ?> videos - <?php echo $user->totalPlaylists ?> playlists</div>
	</div>
	<div class="subscribe_widget" data-user_id="<?php echo $user->_id ?>">
		<input type="button" class='unsubscribe btn button btn-danger btn-danger' value="Unsubscribe"/>
	</div>	
</div>