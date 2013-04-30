<div class='playlist_item'>
	<div class='checkbox_pane'><?php echo html::checkbox(strval(isset($custid) ? $custid : $item->_id), 1, 0) ?></div>
	<div class='thumb_block'>
		<?php
			$pics = $item->get4Pics();
			$large_pic = $pics[0];
		?>
		<img alt='thumbnail' src='<?php echo $large_pic ?>' class='large_pic'/>
	</div>
	<div class='playlist_item_right'>
		<div class='title'><a href='<?php echo glue::url()->create('/playlist/view', array('id' => strval($item->_id))) ?>'><?php echo $item->title ?></a></div>
		<div class='video_preview'>
			<div class='video_count'>
				<?php echo sizeof($item->videos) ?>
				<div>videos</div>
			</div><div class='image_row'><?php for($i = 1; $i < sizeof($pics); $i++){ ?>
			<img alt='thumbnail' src='<?php echo $pics[$i] ?>' class='smaller <?php if($i==3) echo 'last' ?>'/>
		<?php } ?></div>
		</div>
	</div>

	<div class='status_floated'>
		<span class='playlist_listing'>
			<?php if($item->listing == 2){ ?>
				<img alt='unlisted' src='/images/unlisted_icon.png' class='margined'/>
			<?php }elseif($item->listing == 3){ ?>
				<img alt='private' src='/images/private_icon.png' class='margined'/>
			<?php } ?>
		</span>

		<?php if(!(bool)$item->allow_like){ ?>
			<img alt='like_d' src='/images/like_disabled.png'/>
		<?php } ?>
	</div>
	<div class='clearer'></div>
</div>