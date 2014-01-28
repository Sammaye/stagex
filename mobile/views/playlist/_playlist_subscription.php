<?php 
if(!($model=app\models\Playlist::findOne(array('_id' => $item['playlist_id'])))||!glue::auth()->check(array('viewable' => $model))){
	$model=new app\models\Playlist();
	$model->title='Playlist Unavailable';
	$model->deleted=1;
}
?>

<div class='playlist clearfix user_playlist_item user_playlist_subscription_item' data-id="<?php echo strval($item['_id']) ?>" data-playlist-id="<?php echo strval($model->_id) ?>">
	<div class='checkbox_col'><div class="checkbox_input" style=''><?php echo html::checkbox('sub_id[]', strval($item['_id']), 0) ?></div></div>
	<div class="row">
	<div class='thumbnail col-md-3'>
		<?php $pics = $model->get4Pics(); ?><?php for($i = 1; $i < count($pics)-1; $i++){ ?>
			<img alt='thumbnail' src='<?php echo $pics[$i] ?>' class='smaller <?php if($i==3) echo 'last' ?>'/>
		<?php } ?>		
	</div>
	<div class='info col-md-9'>
		<h3 class='title'><a href='<?php echo glue::http()->url('/playlist/view', array('id' => strval($model->_id))) ?>'><?php echo $model->title ?></a></h3>
		<?php if($model->description){ ?>
			<div class='expandable description'><?php echo nl2br(htmlspecialchars($model->description)) ?></div>
		<?php } ?>
		<?php if(!$model->deleted){ ?>
		<div class="created">
			<?php echo date('d F Y', $model->created->sec) ?>
			<?php if($model->author){
				echo html::a(array('text' => $model->author->getUsername(), 'href' => array('/user/view','id'=>$model->author->_id), 'class' => 'username'));
			} ?>
			<?php echo count($model->videos) ?> videos
			<div><button type="button" class="btn btn-success btn_subscribe btn-xs">Subscribe</button></div>
		</div>	
		<?php } ?>
	</div>
	</div>
</div>