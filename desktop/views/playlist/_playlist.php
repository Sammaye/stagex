<div class='playlist user_playlist_item' data-id="<?php echo isset($custid)?$custid:$item->_id ?>">
	<div class='checkbox_col'><div class="checkbox_input" style=''><?php echo html::checkbox('video_id[]', strval(isset($custid) ? $custid : $item->_id), 0) ?></div></div>
	<div class='thumbnail'>
		<?php $pics = $item->get4Pics(); ?><?php for($i = 1; $i < count($pics); $i++){ ?>
			<img alt='thumbnail' src='<?php echo $pics[$i] ?>' class='smaller <?php if($i==3) echo 'last' ?>'/>
		<?php } ?>		
	</div>
	<div class='info'>
		<h3 class='title'><a href='<?php echo glue::http()->url('/playlist/view', array('id' => strval($item->_id))) ?>'><?php echo $item->title ?></a></h3>
		<?php if($item->description){ ?>
			<div class='expandable description'><?php echo nl2br(htmlspecialchars($item->description)) ?></div>
		<?php } ?>
		<div class="created">
			<?php echo date('d F Y', $item->created->sec) ?>
		</div>		
	</div>
	<div class='video_count'>
		<?php echo count($item->videos) ?> videos
	</div>
	<div class='infocons'>
		<span class='listing'>
			<?php if($item->listing == 1){ ?>
				<img alt='unlisted' src='/images/unlisted_icon.png'/>
			<?php }elseif($item->listing == 2){ ?>
				<img alt='private' src='/images/private_icon.png'/>
			<?php } ?>
		</span>
	</div>
	<div class='clear'></div>
</div>