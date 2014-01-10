<div class='video clearfix' data-id='<?php echo strval($item->_id) ?>'>

	<div class="deleted">
		This video has been deleted <a href="#" class="undo">Undo</a>
	</div>

	<div class='checkbox_col'><div class="checkbox_input" style=''><?php echo html::checkbox('video_id[]', strval(isset($custid) ? $custid : $item->_id), 0) ?></div></div>
	<div class='thumbnail' style='position:relative;float:left;'><a href="/video/watch?id=<?php echo strval($item->_id) ?>" >
		<img alt='<?php echo Html::encode($item->title) ?>' src="<?php echo $item->getImage(88, 49) ?>"/></a>
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
</div>
