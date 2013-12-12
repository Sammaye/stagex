<?php

$last_ts = isset($model) ? $model->ts : '';
if(isset($item))
	if(isset($item->ts)) $last_ts =  $item->ts;
if(!isset($model))
	$model=$item;
if(!isset($extra_classes)) $extra_classes = '';
if(!isset($show_checkbox)) $show_checkbox = false;

if(!$model || !$model->_id instanceof MongoId){
	$model = new app\models\Playlist; 
	$model->title = "[Playlist Deleted]"; 
	$model->deleted = 1;		
}
		
?>

<div class='playlist_item playlist_ext_item clearfix <?php echo $extra_classes ?>' data-id='<?php echo isset($item) ? strval($item->_id) : strval($model->_id) ?>'
	data-ts='<?php echo $last_ts instanceof MongoDate ? $last_ts->sec : '' ?>'>

	<div class='thumbnails clearfix'>
		<a href='<?php echo glue::http()->url('/playlist/view', array('id' => strval($model->_id))) ?>'>
		<?php $pics = $model->get4Pics(88,49,88,49); ?>
		<img alt='thumbnail' src='<?php echo $pics[0] ?>' class='tr'/>
		<img alt='thumbnail' src='<?php echo $pics[1] ?>' class='tl'/>
		<img alt='thumbnail' src='<?php echo $pics[2] ?>' class='br'/>
		<img alt='thumbnail' src='<?php echo $pics[3] ?>' class='bl'/>
		</a>
	</div>
	<div class='playlist_item_right no_margin'>
		<h3 class='title'><a href='<?php echo glue::http()->url('/playlist/view', array('id' => strval($model->_id))) ?>'><?php echo $model->title ?></a></h3>
		<?php if($model->author){ ?>
		<div class="detail">
			<span><?php echo count($model->vieos) ?> videos</span>
			<span class="author"><?php echo html::a(array('text' => $model->author->getUsername(), 'href' => array('/user/view','id'=>$model->author->_id))); ?></span>
			<span class="created"><?php echo date('d M Y', $model->created->sec) ?></span>		
		</div>		
		<?php } ?>
		<?php if($model->description){ ?>
			<div class='expandable description'><?php echo nl2br(htmlspecialchars($model->description)) ?></div>
		<?php } ?>			
	</div>
</div>