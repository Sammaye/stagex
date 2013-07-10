<div class='video' data-id='<?php echo strval($item->_id) ?>'>
	<div class='checkbox_custom' style='float:left;'><div class="checkbox_input" style='margin:25px 15px 0 0;'><?php echo html::checkbox(strval(isset($custid) ? $custid : $item->_id), 1, 0) ?></div></div>
	<div class='thumbnail' style='position:relative;float:left;'><a href="/video/watch?id=<?php echo strval($item->_id) ?>" >
		<img alt='<?php echo Html::encode($item->title) ?>' src="<?php echo $item->getImage(138, 77) ?>"/></a>
		<?php if($item->state == 'finished'): ?>
		<div class='duration'><span><?php echo $item->get_time_string() ?></span></div>
		<a class='add_to_playlist' href='#'><img alt='Add to Playlist' src='/images/add_tooltip.png'/></a>
		<?php endif ?>
	</div>
	<div class='info' style='float:left;'>
		<h3 class='title'><a href="/video/watch?id=<?php echo strval($item->_id) ?>"><?php echo $item->title ?></a></h3>
		<?php if($item->description){ ?>
			<div class='expandable description'><?php echo $item->description ?></div>
		<?php } ?>
		<div class="detail">
			<?php echo date('d F Y', $item->created->sec) ?>
		</div>
	</div>
	<div class="" style="float:left;">
		<?php if($item->isProcessing() || $item->state=='failed'): ?>
			<?php if($item->state == 'failed'): ?>
			<span class='encoding_failed'>Encoding FAILED</span>
			<?php elseif($item->isProcessing()): ?>
			<span class='currently_encoding'>Encoding In Progress</span>
			<?php endif; ?>		
		<?php else: ?>
			<span><?php echo $item->views ?> Views</span>
			<span><?php echo $item->totalResponses ?> Responses</span>
		<?php endif; ?>
	</div>
	<div class='infocons'>

		<span class='comments'>
			<?php if(!$item->allowTextComments && !$item->allowVideoComments){ ?>
				<img alt='Comments Allowed' src='/images/comments_disabled_icon.png'/>
			<?php }elseif($item->moderated){ ?>
				<img alt='Moderated' src='/images/moderated_icon.png'/>
			<?php } ?>
		</span>	
	
		<span class='listing'>
			<?php if($item->isUnlisted()){ ?>
				<img alt='Unlisted' src='/images/unlisted_icon.png'/>
			<?php }elseif($item->isPrivate()){ ?>
				<img alt='Private' src='/images/private_icon.png'/>
			<?php } ?>
		</span>
	</div>
	<div class="clear"></div>
</div>
