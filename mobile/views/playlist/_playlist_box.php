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
<div class="thumbnail playlist_box_item <?php echo $extra_classes ?> <?php echo $i%4===0?'first':'' ?>">
	<div class="clearfix">
	<?php $pics = $model->get4Pics(88,49,88,49); ?>
	<img alt='thumbnail' src='<?php echo $pics[0] ?>' class='tr'/>
	<img alt='thumbnail' src='<?php echo $pics[1] ?>' class='tl'/>
	</div>
    <div class="caption">
    <h5><a href="<?php echo glue::http()->url('/playlist/view',array('id'=>$model->_id)) ?>"><?php echo $model->title ?></a></h5>
    <p class="text-muted"><?php echo $model->totalVideos ?> videos</p>
	</div>
</div>