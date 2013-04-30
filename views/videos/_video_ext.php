<?php
	$last_ts = '';
	if(isset($item)) if($item->ts instanceof MongoDate) $last_ts = $item->ts->sec;
	if(!isset($custid)) $custid = null;
	if(!isset($show_checkbox)) $show_checkbox = false;
	if(!isset($show_watched_status)) $show_watched_status = false;
?>

<div class='video_item <?php echo isset($extra_classes) ? $extra_classes : '' ?>' data-id='<?php echo $model ? strval($model->_id) : '' ?>' data-ts='<?php echo $last_ts ?>' style='position:relative;'>
	<?php if(!$model){
		$model = new Video;
		if($show_checkbox): ?><div class='checkbox_pane'><?php echo html::checkbox(strval($custid ? $custid : $model->_id), 1, 0, array('style' => '')) ?></div><?php endif; ?>
		<div class='video_thumb_pane video_thumbnail_pane' style='position:relative;'><a href="/video/watch" ><img alt='thumbnail' class='video_img' src="<?php echo $model->getImage(138, 77) ?>"/></a></div>
		<div class='more_info_pane'><h3 class='title'><a href="/video/watch">[Video Deleted]</a></h3></div>
	<?php }else{
		if($show_checkbox): ?><div class='checkbox_pane'><?php echo html::checkbox(strval($custid ? $custid : $model->_id), 1, 0, array('style' => '')) ?></div><?php endif; ?>
		<div class='video_thumb_pane video_thumbnail_pane' style='position:relative;'><a href="/video/watch?id=<?php echo strval($model->_id) ?>" ><img alt='thumbnail' class='video_img' src="<?php echo $model->getImage(138, 77) ?>"/></a>
			<div class='duration_hover'><span><?php echo $model->get_time_string() ?></span></div>
			<?php if(!isset($hide_a2p_button)): ?><a class='playlist_button' href='#'><img alt='add_to' src='/images/add_tooltip.png'/></a><?php endif; ?></div>
		<div class='more_info_pane'>
			<h3 class='title'><a href="/video/watch?id=<?php echo strval($model->_id) ?>"><?php echo $model->title ?></a></h3>
			<?php if($model->author): ?>
				<div class='details'>
					Uploaded by <a href="<?php echo glue::url()->create('/user/view', array('id' => $model->author->_id)) ?>"><?php echo $model->author->getUsername() ?></a> <span class='divider'>|</span> <?php echo $model->views ?> views
				</div>
			<?php endif;

			if($model->description && !isset($hideDescription)){

				if(isset($descLength)){
					$desc = strlen($model->description) > $descLength ? substr_replace(substr($model->description, 0, $descLength), '...', -3) : $model->description;
				}else{
					$desc = $model->description;
				}

				?>
				<div class='expandable description'><?php echo html::encode($desc) ?></div>
			<?php } ?>
		</div>
	<?php } ?>
	<div class="clearer"></div>

	<?php if($model->userHasWatched() && $show_watched_status){ ?>
		<div class='xtra_info_icons tags_outer'>
			<span class='tag'>Watched</span>
		</div>
	<?php } ?>
</div>