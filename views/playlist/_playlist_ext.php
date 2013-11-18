<?php
	$last_ts = isset($model) ? $model->ts : '';
	if(isset($item)){
		if(isset($item->ts)) $last_ts =  $item->ts;
	}
	if(!isset($model))
		$model=$item;

	if(!isset($extra_classes)) $extra_classes = '';
	if(!isset($show_checkbox)) $show_checkbox = false;
//var_dump($model); exit();
	if(!$model || !$model->_id instanceof MongoId){
		$model = new app\models\Playlist; ?>
	<div class='playlist_item <?php echo $extra_classes ?>' data-id='<?php echo isset($item) ? strval($item->_id) :'' ?>'
			data-ts='<?php echo $last_ts instanceof MongoDate ? $last_ts->sec : '' ?>'>

		<div class='thumb_block'><?php $pics = $model->get4Pics(); $large_pic = $pics[0]; ?><img alt='thumbnail' src='<?php echo $large_pic ?>' class='large_pic'/></div>
		<div class='playlist_item_right no_margin'>
			<div class='title'><a href='<?php echo glue::http()->url('/playlist/view') ?>'>[Playlist Deleted]</a></div>
			<div class='video_preview'>
				<div class='video_count'>
					<?php echo count($model->videos) ?>
					<div>videos</div>
				</div><div class='image_row'><?php for($i = 1; $i < count($pics); $i++){ ?>
				<img alt='thumbnail' src='<?php echo $pics[$i] ?>' class='smaller <?php if($i==3) echo 'last' ?>'/>
			<?php } ?></div>
			</div>
		</div>
		<div class='clearer'></div>
	</div>
<?php }else{ ?>
	<div class='playlist_item <?php echo $extra_classes ?>' data-id='<?php echo isset($item) ? strval($item->_id) : strval($model->_id) ?>'
		data-ts='<?php echo $last_ts instanceof MongoDate ? $last_ts->sec : '' ?>'>

		<?php if($show_checkbox){ ?>
			<div class='checkbox_pane'><?php echo html::checkbox($item ? strval($item->_id) : strval($model->_id), 1, 0) ?></div>
		<?php } ?>

		<div class='thumb_block'>
			<?php $pics = $model->get4Pics(); $large_pic = $pics[0]; ?>
			<img alt='thumbnail' src='<?php echo $large_pic ?>' class='large_pic'/>
		</div>
		<div class='playlist_item_right no_margin'>
			<div class='title'><a href='<?php echo glue::http()->url('/playlist/view', array('id' => strval($model->_id))) ?>'><?php echo strlen($model->title) > 0 ? $model->title : '[Playlist Deleted]' ?></a></div>
			<div class='video_preview'>
				<div class='video_count'>
					<?php echo count($model->videos) ?>
					<div>videos</div>
				</div><div class='image_row'><?php for($i = 1; $i < count($pics); $i++){ ?>
				<img alt='thumbnail' src='<?php echo $pics[$i] ?>' class='smaller <?php if($i==3) echo 'last' ?>'/>
			<?php } ?></div>
			</div>
			<?php if($model->author): ?>
				<div class='compiled_user'>Compiled by <a href="<?php echo glue::http()->url('/user/view', array('id' => strval($model->author->_id))) ?>"><?php echo $model->author->getUsername() ?></a></div>
			<?php else: ?>
				<div class='compiled_user'>Compiled by <a href="<?php echo glue::http()->url('/user/view') ?>">[User Deleted]</a></div>
			<?php endif; ?>
		</div>
		<div class='clearer'></div>
	</div>
<?php } ?>