<div class='playlist user_playlist_item clearfix' data-id="<?php echo isset($custid)?$custid:$item->_id ?>">
	<div class='checkbox_col'><div class="checkbox_input" style=''><?php echo html::checkbox('video_id[]', strval(isset($custid) ? $custid : $item->_id), 0) ?></div></div>
	<div class="row">
	<div class='thumbnail col-md-2'>
		<?php $pics = $item->get4Pics(); ?><?php for($i = 1; $i < count($pics); $i++){ ?>
			<img alt='thumbnail' src='<?php echo $pics[$i] ?>' class='smaller <?php if($i==3) echo 'last' ?>'/>
		<?php } ?>		
	</div>
	<div class='info col-md-10'>
		<h3 class='title'><a href='<?php echo glue::http()->url('/playlist/view', array('id' => strval($item->_id))) ?>'><?php echo $item->title ?></a></h3>
		<?php if($item->description){ ?>
			<div class='expandable description'><?php echo nl2br(htmlspecialchars($item->description)) ?></div>
		<?php } ?>
		<div class="created">
			<?php echo date('d F Y', $item->created->sec) ?> - <?php echo $item->totalVideos . ' videos' ?>
		</div>		
	</div>
	</div>
</div>