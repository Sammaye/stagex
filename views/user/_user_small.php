<?php 
if(!isset($model)||!$model){
	$model=new app\models\User;
	$model->username='[Unknown]';
}
?>
<div class='stream_item_user'>
	<img alt='thumbnail' class='user_img' src='<?php echo $model->getAvatar(30, 30) ?>'/>
	<h3 class='username'><?php echo html::a(array('href' => glue::http()->url('/user/view', array('id' => strval($model->_id))), 'text' => $model->getUsername())) ?></h3>
</div>