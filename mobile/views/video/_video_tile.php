<?php
$model=$item;
if(!$model || !$model instanceof \app\models\Video || !$model->_id instanceof MongoId || $model->deleted){
	$model = new app\models\Video;
	$model->title='[Video Unavailable]';
}
if(!isset($extra_classes)) $extra_classes = '';
?>
<div class="video_tile_item_outer">
<div class="thumbnail playlist_box_item video_tile_item <?php echo isset($i)&&$i%4==0?'first_item':'' ?>">
	<div class="clearfix">
	<img src="<?php echo $model->getImage(124,69) ?>" alt="<?php echo $model->title ?>" class="video_thumbnail">
	</div>
    <div class="caption">
    <h5 class="h6"><a href="<?php echo glue::http()->url('/video/watch',array('id'=>$model->_id)) ?>"><?php echo $model->title ?></a></h5>
    <p class="text-muted"><?php echo $model->views ?> views</p>
	</div>
</div>
</div>