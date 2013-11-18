<?php
$model=$item;
if(!$model || !$model instanceof \app\models\Playlist || !$model->_id instanceof MongoId){
	$model = new app\models\Playlist;
	$model->title='Playlist Unavailable';
}elseif($model->deleted){
	$model->title='Playlist Deleted';
}
if(!isset($extra_classes)) $extra_classes = '';
?>
<div class="thumbnail playlist_box_item">
	<div class="clearfix">
	<?php $pics = $model->get4Pics(138,77); $large_pic = $pics[0]; ?>
	<img src="<?php echo $large_pic ?>" alt="<?php echo $model->title ?>" class="large_image">
	<div class='small_images'>
		<?php for($i = 1; $i < count($pics); $i++){ ?><img alt="<?php echo $model->title ?>" src='<?php echo $pics[$i] ?>'/><?php } ?>
	</div>
	</div>
    <div class="caption">
    <h5><a href="<?php echo glue::http()->url('/playlist/view',array('id'=>$model->_id)) ?>"><?php echo $model->title ?></a></h5>
    <p class="text-muted"><?php echo $model->totalVideos ?> videos</p>
	</div>
</div>