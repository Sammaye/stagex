<?php
if(!isset($model)||!$model){
	$model=new app\models\Playlist();
	$model->title='[Not Available]';
}
?>
<div class='playlist_item <?php echo isset($cssClass)?$cssClass:'' ?>' data-id='<?php echo isset($item)&&$item->_id instanceof MongoId?$item->_id:$model->_id ?>'
	data-ts='<?php echo isset($model)?$model->getTs($model->create):'' ?>'>
	
	<div class='thumbnail' style='float:left; margin:0 10px 0 0;'>
		<?php $pics = $model->get4Pics(); ?><?php for($i = 1,$c=count($pics)>2?2:count($pics); $i <= $c; $i++){ ?>
			<img alt='thumbnail' src='<?php echo $pics[$i] ?>' class='smaller <?php if($i==3) echo 'last' ?>'/>
		<?php } ?>	
	</div>
	<div class='info' style='float:left;'>
		<h3 class='title' style='margin:0 0 5px 0;'><a href='<?php echo glue::http()->url('/playlist/view', array('id' => strval($model->_id))) ?>'><?php echo $model->title ?></a></h3>
		<div style='color:#999999;margin:0 0 5px 0;'><?php echo count($model->videos) ?> videos</div>
		<?php if($model->author instanceof \app\models\User): ?>
			<div class='uploader'>
				<img style='border-radius:50%;width:25px;height:25px;vertical-align:middle;margin-right:8px;' src="<?php echo $model->author->getAvatar(30,30) ?>"/>
				<a href="<?php echo glue::http()->url('/user/view', array('id' => $model->author->_id)) ?>"><?php echo $model->author->getUsername() ?></a>
				<span style='color:#999999;'><?php echo date('j M Y',$model->getTs($model->created)) ?></span>
			</div>
		<?php endif; ?>	
	</div>
	<div class='clear'></div>
</div>