<?php if($item instanceof User){
	$user = $item;
}else{
	$user = $item->user_subscribed;
} ?>

<div data-id='<?php echo $user->_id ?>' class='subscription <?php if($i == 0): echo 'first'; endif; ?>'>
	<img alt='thumbnail' class='float_left' src='<?php echo $user->getAvatar(55, 55) ?>'/>
	<div class='username'><a href='<?php echo glue::url()->create('/user/view', array('id' => $user->_id)) ?>'><?php echo $user->getUsername() ?></a>
		<div class='info'><?php echo $user->total_uploads ?> videos - <?php echo $user->total_playlists ?> playlists</div>
	</div>

	<div class='unsubscribe grey_css_button'>
		Unsubscribe
    </div>
    <div class='clearer'></div>
</div>