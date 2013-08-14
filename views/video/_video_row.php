<?php
?>
<div style='padding:10px 0;border-bottom:1px solid #eeeeee;'>
	<div class='checkbox_col' style='float:left;padding-top:16px;width:15px;margin:0 10px 0 10px;'><div class="checkbox_input" style=''><?php echo html::checkbox('video_id[]', strval(isset($custid) ? $custid : $item->_id), 0) ?></div></div>
	<div class='thumbnail' style='position:relative;float:left;width:88px;margin:2px 10px 0 0;'><a href="/video/watch?id=<?php echo strval($item->_id) ?>" >
		<img alt='<?php echo Html::encode($item->title) ?>' src="<?php echo $item->getImage(88, 49) ?>"/></a>
		<?php if($item->state == 'finished'): ?>
		<div class='duration'><span><?php echo $item->get_time_string() ?></span></div>
		<a class='add_to_playlist' href='#'><img alt='Add to Playlist' src='/images/add_tooltip.png'/></a>
		<?php endif ?>
	</div>
	<div style="width:400px;float:left;">
		<span style='font-size:16px;line-height:20px;color:#333333;'><a href="/video/watch?id=<?php echo strval($item->_id) ?>"><?php echo $item->title ?></a></span>
		<div class="expandable" style='color:#999999;font-size:12px;line-height:17px;'><?php echo $item->description ?></div>
	</div>
	<div style='width:70px;float:left;color:#666666;font-size:14px;margin:0 40px;padding:22px 0 0 0;'>
		00:01:04
		<?php echo $item->duration ?>
	</div>
	<div style="width:120px;float:left;font-size:14px;padding-top:22px;">
		<a href="/user/view?id=<?php echo $item->author->username ?>"><?php echo $item->author->username ?></a>
	</div>		
	<div class="clear"></div>			
</div>