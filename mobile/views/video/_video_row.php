<?php
if(isset($playlistId)&&$playlistId)
	$videoUrl=glue::http()->url('/video/watch',array('id'=>$item->_id,'playlist_id'=>$playlistId));
else
	$videoUrl=glue::http()->url('/video/watch',array('id'=>$item->_id));

if(!isset($model)||!$model){
	$model=new app\models\Video();
	$model->title='This video is unavailable';
	$model->deleted=1; // Stands to reason this video was deleted
}

$extraClasses = isset($extraClasses) ? $extraClasses : '';
if(
	(isset($show_sorter) && $show_sorter === false || !isset($show_sorter)) &&  
	(isset($show_delete) && $show_delete === false || !isset($show_delete))
){
	$extraClasses .= ' without_controls';
}

?>
<<?php if(isset($useLiTag)&&$useLiTag): echo 'li'; else: echo 'div'; endif; ?> 
		class="video_row clearfix<?php echo isset($extraClasses) ? $extraClasses : '' ?>" data-id="<?php echo isset($custid)?$custid:$item->_id ?>">
	<div class="inner">
	<?php if(isset($admin)&&$admin): ?><div class='checkbox_col'><div class="checkbox_input" style=''><?php echo html::checkbox('video_id[]', strval(isset($custid) ? $custid : $item->_id), 0) ?></div></div><?php endif; ?>
	<?php if(isset($show_sorter)&&$show_sorter): ?><div class='sortable_handle'><img alt='sort' src='/images/sortable_icon.png'/></div><?php endif; ?>
	<?php if(isset($show_delete)&&$show_delete): ?><div class='delete_handle'>&times;</div><?php endif; ?>
	<?php if(!glue::auth()->check(array('viewable'=>$item))): ?>
	<div class="deleted">
		<?php if($item->deleted){ ?>
			This video has been deleted
		<?php }else{ ?>
			This video is unavailable
		<?php } ?>
	</div>
	<?php else: ?>
	<div class='thumbnail'><a href="<?php echo $videoUrl ?>" >
		<img alt='<?php echo html::encode($item->title) ?>' src="<?php echo $item->getImage(88, 49) ?>"/></a>
	</div>
	<div class="info">
		<span class='title'><a href="<?php echo $videoUrl ?>"><?php echo $item->title ?></a></span>
		<div class="expandable text-muted description"><?php echo $item->description ?></div>
		<span class="duration small text-muted"><?php echo $item->getTimeString() ?></span><span class="sep small text-muted"> - </span>
		<a class="small" href="/user/view?id=<?php echo $item->author->_id ?>"><?php echo $item->author->username ?></a>		
	</div>
	<?php endif; ?>
	</div>
</<?php if(isset($useLiTag)&&$useLiTag): echo 'li'; else: echo 'div'; endif; ?>>