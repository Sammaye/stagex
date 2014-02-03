<?php
if(!isset($model)||!$model){
	$model=new app\models\Video();
	$model->title='[Not Available]';
}
?>
<div class='video <?php echo isset($extra_classes)?$extra_classes:'' ?>' data-id='<?php echo isset($custid)?$custid:strval($model->_id) ?>' 
		data-ts='<?php echo isset($item)?$item->getTs($item->created):'' ?>' style='position:relative;'>
		
	<div class='thumbnail' style='position:relative;float:left;'><a href="/video/watch?id=<?php echo strval($model->_id) ?>" >
		<img alt='<?php echo Html::encode($model->title) ?>' src="<?php echo $model->getImage(88, 49) ?>"/></a>
	</div>		
	<div class='info'>
		<h3 class='title' style='font-size:14px;line-height:17px;margin-bottom:5px;'><a href="/video/watch?id=<?php echo strval($model->_id) ?>"><?php echo $model->title ?></a></h3>
		<?php if($model->author instanceof \app\models\User): ?>
			<div class='uploader' style='font-size:12px;'>
				<span style='color:#999999;'><?php echo $model->getTimeString() ?> - </span>
				<a href="<?php echo glue::http()->url('/user/view', array('id' => $model->author->_id)) ?>"><?php echo $model->author->getUsername() ?></a>
				<span style='color:#999999;'><?php echo date('j M Y',$model->getTs($model->created)) ?></span>
			</div>
		<?php endif; ?>			
	</div>
	<div class="clear"></div>
</div>