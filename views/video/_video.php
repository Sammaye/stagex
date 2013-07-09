<div class='video_item' data-id='<?php echo strval($item->_id) ?>' style='position:relative;'>
	<div class='checkbox_pane'><?php echo html::checkbox(strval(isset($custid) ? $custid : $item->_id), 1, 0) ?></div>
	<div class='video_thumb_pane video_thumbnail_pane' style='position:relative;'><a href="/video/watch?id=<?php echo strval($item->_id) ?>" >
		<img alt='thumbnail' class='video_img' src="<?php echo $item->getImage(138, 77) ?>"/></a><?php if($item->state == 'finished'): ?>
		<div class='duration_hover'><span><?php echo $item->get_time_string() ?></span></div>
		<a class='playlist_button' href='#'><img alt='add to' src='/images/add_tooltip.png'/></a><?php endif ?></div>
	<div class='more_info_pane'>
		<h3 class='title' style='margin-top:9px;'><a href="/video/watch?id=<?php echo strval($item->_id) ?>"><?php echo $item->title ?></a></h3>
		<?php if($item->description){ ?>
			<div class='expandable description'><?php echo $item->description ?></div>
		<?php } ?>
		<span class='option_info'><a href='<?php echo glue::http()->createUrl('/video/statistics', array('id' => $item->_id)) ?>'><img alt='stats' src='/images/stats_icon.png'/> <?php echo $item->views ?></a>
			&nbsp;-&nbsp; <a href='<?php echo glue::http()->createUrl('/videoresponse/view_all', array('id' => $item->_id)) ?>'><img alt='comments' src='/images/responses_icon.png'/> <?php echo $item->total_responses ?></a> &nbsp;-&nbsp;
			<?php echo date('d F Y', $item->created->sec) ?></span> <?php if($item->state == 'failed'): ?><span class='encoding_failed'>Encoding FAILED</span><?php elseif($item->is_processing()): ?><span class='encoding'>Encoding In Progress</span><?php endif; ?>
	</div>
	<div class="clear"></div>

	<div class='xtra_info_icons'>
		<span class='video_listing'>
			<?php if($item->if_is_unlisted()){ ?>
				<img alt='unlisted' src='/images/unlisted_icon.png'/>
			<?php }elseif($item->if_is_private()){ ?>
				<img alt='private' src='/images/private_icon.png'/>
			<?php } ?>
		</span>

		<span class='video_comments'>
			<?php if(!$item->txt_coms_allowed && !$item->vid_coms_allowed){ ?>
				<img alt='comments' src='/images/comments_disabled_icon.png'/>
			<?php }elseif($item->mod_comments == 1){ ?>
				<img alt='modded' src='/images/moderated_icon.png'/>
			<?php } ?>
		</span>
	</div>
</div>
