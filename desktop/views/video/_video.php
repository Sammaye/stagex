<div class='video' data-id='<?php echo strval($item->_id) ?>'>

	<div class="deleted">
		This video has been deleted <a href="#" class="undo">Undo</a>
	</div>

	<div class='checkbox_col'><div class="checkbox_input" style=''><?php echo html::checkbox('video_id[]', strval(isset($custid) ? $custid : $item->_id), 0) ?></div></div>
	<div class='thumbnail' style='position:relative;float:left;'><a href="/video/watch?id=<?php echo strval($item->_id) ?>" >
		<img alt='<?php echo Html::encode($item->title) ?>' src="<?php echo $item->getImage(138, 77) ?>"/></a>
	</div>
	<div class='info'>
		<h3 class='title'><a href="/video/watch?id=<?php echo strval($item->_id) ?>"><?php echo $item->title ?></a></h3>
		<?php if($item->description){ ?>
			<div class='expandable description'><?php echo nl2br(htmlspecialchars($item->description)) ?></div>
		<?php } ?>
		<div class="detail">
			<?php echo date('d F Y', $item->created->sec) ?>
		</div>
	</div>
	<?php if($item->isProcessing() || $item->state=='failed'): ?>
		<div class="encoding">
			<?php if($item->state == 'failed'): ?>
			<span class='encoding_failed'>Encoding FAILED <input type="button" class="btn" value="Delete"/></span>
			<?php elseif($item->isProcessing()): ?>
			<span class='currently_encoding light'>Encoding In Progress</span>
			<?php endif; ?>
		</div>		
	<?php else: ?>
		<!-- 
		<div class="statistics">
			<div class="stacked_info"><?php //echo $item->views ?> <span>Views</span></div>
			<div class="stacked_info"><?php //echo $item->likes ?> <span>Likes</span></div>
			<div class="stacked_info"><?php //echo $item->totalResponses ?> <span>Responses</span></div>
		</div>
		-->
		
		<div class='infocons'>
			<span class="length"><?php echo $item->getTimeString() ?></span>
			<?php if($item->isUnlisted()){ ?>
			<span class="listing unlisted-setting-icon"></span>
			<?php }elseif($item->isPrivate()){ ?>
			<span class="listing private-setting-icon"></span>
			<?php } ?>
			<?php if(!$item->allowTextComments && !$item->allowVideoComments){ ?>
			<span class="comments comments-disabled-setting-icon"></span>
			<?php }elseif($item->moderated){ ?>
			<span class="comments moderated-setting-icon"></span>
			<?php } ?>
		</div>		
	<?php endif; ?>
	<div class="clear"></div>
</div>
