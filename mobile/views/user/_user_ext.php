<?php

if(!$model||!$model->_id instanceof \MongoId||$model->getIsNewRecord()){
	$model=new app\models\User;
	$model->username='[User Deleted]';
	$model->deleted=1;
}

?>
<div class="user_ext_item clearfix <?php echo isset($extra_classes)?$extra_classes:'' ?>">
<div class='thumbnail'><a href="<?php echo glue::http()->url('/user/view', array('id' => $model->_id)) ?>">
<img src="<?php echo $model->getAvatar(55,55) ?>" alt="<?php echo $model->getUsername() ?>"/></a></div>
<div class="user_ext_right">
<h3 class="username"><a href="<?php echo glue::http()->url('/user/view', array('id' => $model->_id)) ?>"><?php echo $model->getUsername() ?></a></h3>
<div class="detail">
<?php if(!$model->deleted){ ?>
	<span><?php echo $model->totalPlaylists ?> playlists</span>
	<span class="videos"><?php echo $model->totalUploads ?> videos</span>
	<span><?php echo $model->totalFollowers ?> subscribers</span>
<?php } ?>
</div>
<?php if($model->about){ ?>
	<div class='expandable about'><?php echo nl2br(htmlspecialchars($model->about)) ?></div>
<?php } ?>
</div>
</div>