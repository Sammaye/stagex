<?php 
if(!($model=app\models\Playlist::model()->findOne(array('_id' => $item['playlist_id'])))||!glue::auth()->check(array('viewable' => $model))){
	$model=new app\models\Playlist();
	$model->title='Playlist Unavailable';
	$model->deleted=1;
}
?>

<div class='playlist user_playlist_item user_playlist_subscription_item' data-id="<?php echo isset($custid)?$custid:$model->_id ?>">
	<div class='checkbox_col'><div class="checkbox_input" style=''><?php echo html::checkbox('sub_id[]', strval(isset($custid) ? $custid : $model->_id), 0) ?></div></div>
	<div class='thumbnail'>
		<?php $pics = $model->get4Pics(); ?><?php for($i = 1; $i < count($pics); $i++){ ?>
			<img alt='thumbnail' src='<?php echo $pics[$i] ?>' class='smaller <?php if($i==3) echo 'last' ?>'/>
		<?php } ?>		
	</div>
	<div class='info'>
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
			<span class='listing'>
				<?php if($model->listing == 1){ ?>
					<img alt='unlisted' src='/images/unlisted_icon.png'/>
				<?php }elseif($model->listing == 2){ ?>
					<img alt='private' src='/images/private_icon.png'/>
				<?php } ?>
			</span>			
		</div>	
		<?php } ?>
	</div>
	<?php if(!$model->deleted){ ?>
	<div class='video_count'>
		<?php echo count($model->videos) ?> videos
	</div>
	<div class="infocons">
		<button type="button" class="btn btn-success btn_subscribe">Subscribe</button>
	</div>
	<?php } ?>
	<div class='clear'></div>
</div>