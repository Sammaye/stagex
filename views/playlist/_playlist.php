<div class='playlist_item' style='padding:10px 0;border-bottom:1px solid #eeeeee;'>
	<div class='checkbox_col' style='float:left;padding:15px 10px 0 10px;'><div class="checkbox_input" style=''><?php echo html::checkbox('video_id[]', strval(isset($custid) ? $custid : $item->_id), 0) ?></div></div>
	<div class='thumb_block' style='float:left;width:150px;padding-top:10px;'>
		<?php
			$pics = $item->get4Pics();
		?><?php for($i = 1; $i < count($pics); $i++){ ?>
			<img alt='thumbnail' src='<?php echo $pics[$i] ?>' class='smaller <?php if($i==3) echo 'last' ?>'/>
		<?php } ?>		
	</div>
	<div class='playlist_item_right' style='float:left;width:400px;'>
		<h3 class='title' style='font-size:18px;line-height: 30px;margin: 0;'><a href='<?php echo glue::http()->url('/playlist/view', array('id' => strval($item->_id))) ?>'><?php echo $item->title ?></a></h3>
		<?php if($item->description){ ?>
			<div class='expandable description'><?php echo nl2br(htmlspecialchars($item->description)) ?></div>
		<?php } ?>
		<div class="detail" style='color:#999999;font-size:11px;'>
			<?php echo date('d F Y', $item->created->sec) ?>
		</div>		
	</div>
	<div class='video_count' style='float:left;padding-top:15px;color:#666666;'>
		<?php echo count($item->videos) ?> videos
	</div>

	<div class='status_floated' style='float:right;padding-top:10px;margin-right:20px;'>
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
	<div class='clear'></div>
</div>