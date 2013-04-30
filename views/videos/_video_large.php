<div class='video_item_large' style='<?php if(!$last): echo "margin-left:19px;"; endif; ?>'>
	<a class='video_image' href='<?php echo glue::url()->create('/video/watch', array('id' => strval($item->_id))) ?>'><img alt='thumbnail' src='<?php echo $item->getImage(234, 130) ?>'/></a>
	<div><h3 class='title'><a href='<?php echo glue::url()->create('/video/watch', array('id' => strval($item->_id))) ?>'><?php echo $item->title ?></a></h3></div>
</div>