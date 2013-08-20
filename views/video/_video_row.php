<?php
?>
<div class="video_row" data-id="<?php echo isset($custid)?$custid:$item->_id ?>" style='padding:10px 0;border-bottom:1px solid #eeeeee;'>
	<div class='checkbox_col' style='float:left;padding-top:16px;width:15px;margin:0 10px 0 10px;'><div class="checkbox_input" style=''><?php echo html::checkbox('video_id[]', strval(isset($custid) ? $custid : $item->_id), 0) ?></div></div>
	<?php if(isset($show_sorter)&&$show_sorter): ?><div style='float:left;padding-top:18px;width:15px;margin:0 5px 0 5px;'><img alt='sort' class='sortable_handle' src='/images/sortable_icon.png'/></div><?php endif; ?>
	<?php if(isset($show_delete)&&$show_delete): ?><div style='float:left;padding-top:14px;width:15px;margin:0 12px 0 0px;color:#333333;opacity:0.5;font-size:22px;font-weight:bold;'>&times;</div><?php endif; ?>
	<?php if(!glue::auth()->check(array('viewable'=>$item))): ?>
	<div class="deleted" style='float:left;margin:2px 10px 0 0;'>
		This video has been deleted
	</div>
	<?php else: ?>
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
	<div style='width:70px;float:left;color:#666666;font-size:14px;margin:0 20px;padding:22px 0 0 0;'>
		<?php echo $item->duration?:'&nbsp;'; ?>
	</div>
	<div style="width:120px;float:left;font-size:14px;padding-top:12px;">
		<img style='border-radius:50px;vertical-align:middle;' src="<?php echo $item->author->getAvatar(30,30) ?>"/>
		<a href="/user/view?id=<?php echo $item->author->username ?>"><?php echo $item->author->username ?></a>
	</div>		
	<?php endif; ?>
	<div class="clear"></div>			
</div>