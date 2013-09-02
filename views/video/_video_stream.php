<?php
	$last_ts = '';
	if(isset($item)) if($item->ts instanceof MongoDate) $last_ts = $item->ts->sec;
	if(!isset($custid)) $custid = null;
	if(!isset($show_checkbox)) $show_checkbox = false;
	if(!isset($show_watched_status)) $show_watched_status = false;
	
	if(!isset($model)||!$model){
		$model=new app\models\Video();
		$model->title='[Deleted]';
	}
?>

<div class='video <?php echo isset($extra_classes) ? $extra_classes : '' ?>' data-id='<?php echo $model ? strval($model->_id) : '' ?>' data-ts='<?php echo $last_ts ?>' style='position:relative;'>
	<div class='thumbnail' style='position:relative;float:left;'><a href="/video/watch?id=<?php echo strval($model->_id) ?>" >
		<img alt='<?php echo Html::encode($model->title) ?>' src="<?php echo $model->getImage(88, 49) ?>"/></a>
		<?php if($model->state == 'finished'): ?>
		<div class='duration'><span><?php echo $model->get_time_string() ?></span></div>
		<a class='add_to_playlist' href='#'><img alt='Add to Playlist' src='/images/add_tooltip.png'/></a>
		<?php endif ?>
	</div>		
	<div class='info'>
		<h3 class='title' style='font-size:14px;line-height:17px;margin-bottom:5px;'><a href="/video/watch?id=<?php echo strval($model->_id) ?>"><?php echo $model->title ?></a></h3>
		<?php if($model->author): ?>
			<div class='uploader'>
				<img style='border-radius:50%;width:25px;height:25px;vertical-align:middle;margin-right:8px;' src="<?php echo $model->author->getAvatar(30,30) ?>"/><a href="<?php echo glue::http()->url('/user/view', array('id' => $model->author->_id)) ?>"><?php echo $model->author->getUsername() ?></a>
				<span style='color:#999999;'><?php echo date('j M Y',$model->getTs($model->created)) ?></span>
			</div>
		<?php endif; ?>			
	</div>
	<div class="clear"></div>
</div>