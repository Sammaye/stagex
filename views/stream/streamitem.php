<?php
use app\models\Stream;
if(!$item->video instanceof app\models\Video || !glue::auth()->check(array('viewable'=>$item->video))){
	$item->video=new app\models\Video;
	$item->video->title='[Not Available]';
}
if(!$item->playlist || !glue::auth()->check(array('viewable'=>$item->playlist))){
	$item->playlist=new app\models\Playlist;
	$item->playlist->title='[Not Available]';
}
if(!$item->status_sender /*|| !glue::auth()->check(array('viewable'=>$item->status_sender))*/){
	$item->status_sender=new app\models\User;
	$item->status_sender->username='[Deleted]';
}
if(!$item->subscribed_user /*|| !glue::auth()->check(array('viewable'=>$item->subscribed_user))*/){
	$item->subscribed_user=new app\models\User;
	$item->subscribed_user->username='[Deleted]';
}
?>
<div data-id='<?php echo $item->_id ?>' data-ts='<?php echo $item->getTs($item->created) ?>' class='streamitem' style='border-bottom:1px solid #e5e5e5;padding:20px 0;'
	<?php if($item->type == Stream::WALL_POST): ?>data-target_user='<?php echo strval($item->commenting_user->_id) ?>'<?php endif; ?>>

	<?php if((isset($hideDelete)&&!$hideDelete) && (glue::user()->_id == $item->status_sender->_id )): ?>
		<span class="close_button"><a href="#"><?php echo utf8_decode('&#215;') ?></a></span>
	<?php endif; ?>

	<a href='<?php echo glue::http()->url('/user/view', array('id' => strval($item->status_sender->_id))) ?>'><img alt='thumbnail' src='<?php echo $item->status_sender->getAvatar(48, 48) ?>' class='' style='float:left;border-radius:5px;'/></a>

	<div class='stream_item_inner' style='float:left; margin-left:10px;'>
	<?php if($item->type == Stream::WALL_POST): ?>
		<div class='stream_comment'><span class='expandable'><?php echo htmlspecialchars($item->message) ?></span>
		<?php if(glue::user()->_id != $item->status_sender->_id){ ?>
			<div class='stream_comment_reply'><a href="#" class="btn_reply">Reply to <?php echo $item->status_sender->getUsername() ?></a>
			<span class='sent_date'><?php echo $item->getDateTime() ?></span></div>
		<?php } ?>
	</div>
	<?php elseif($item->type == Stream::COMMENTED_ON):
		?><div class='stream_item_head' style='margin-bottom:15px;'><?php
		echo html::a(array('href' => glue::http()->url('/user/view', array('id' => $item->status_sender->_id)), 'text' => $item->status_sender->getUsername()))." responded to ".
			html::a(array('href' => glue::http()->url('/video/watch', array('id' => $item->video->_id)), 'text' => $item->video->title)) 
			?><span class='sent_date'><?php echo $item->getDateTime() ?></span>
		</div>
		<div class='stream_media_item' style='margin-bottom:15px;'><?php echo $this->renderPartial('video/_video_small', array('model' => $item->video)) ?></div>
		<div><a href="<?php echo glue::http()->url('/videoresponse/list', array('id' => $item->video->_id, 'sorton' => 'created', 'orderby' => -1, 
				'filter-username' => $item->status_sender->_id)) ?>">View responses</a></div>
	<?php elseif($item->type == Stream::VIDEO_RATE):
		?><div class='stream_item_head'><?php
		if($item->like == 1){
			echo html::a(array('href' => glue::http()->url('/user/view', array('id' => $item->status_sender->_id)), 'text' => $item->status_sender->getUsername()))." liked ".
				html::a(array('href' => glue::http()->url('/video/watch', array('id' => $item->video->_id)), 'text' => $item->video->title));
		}else{
			echo html::a(array('href' => glue::http()->url('/user/view', array('id' => $item->status_sender->_id)), 'text' => $item->status_sender->getUsername()))." disliked ".
				html::a(array('href' => glue::http()->url('/video/watch', array('id' => $item->video->_id)), 'text' => $item->video->title));
		} ?><span class='sent_date'><?php echo $item->getDateTime() ?></span>
		</div>
		<div class='stream_media_item' style='margin-top:20px;'><?php echo $this->renderPartial('video/_video_small', array('model' => $item->video)) ?></div><?php
	elseif($item->type == Stream::VIDEO_WATCHED):
		?><div class='stream_item_head'><?php
		echo html::a(array('href' => glue::http()->url('/user/view', array('id' => strval($item->status_sender->_id))), 'text' => $item->status_sender->getUsername()))." watched ".
			html::a(array('href' => glue::http()->url('/video/watch', array('id' => strval($item->video->_id))),
			'text' => $item->video->title)) ?><span class='sent_date'><?php echo $item->getDateTime() ?></span>
		</div>
		<div class='stream_media_item' style='margin-top:20px;'><?php echo $this->renderPartial('video/_video_small', array('model' => $item->video)) ?></div><?php
	elseif($item->type == Stream::VIDEO_UPLOAD):
		?><div class='stream_item_head'><?php
		echo html::a(array('href' => array('/user/view','id' => $item->status_sender->_id), 'text' => $item->status_sender->getUsername()))." uploaded ".
			html::a(array('href' => array('/video/watch', 'id' => $item->video->_id), 'text' => $item->video->title)) 
			?><span class='sent_date'><?php echo $item->getDateTime() ?></span>
		</div>
		<div class='stream_media_item'><?php $this->renderPartial('video/_video_small', array('model' => $item->video)) ?></div><?php
	elseif($item->type == Stream::ADD_TO_PL):
		?><div class='stream_item_head'><?php
		$firstVideo = app\models\Video::findOne(array('_id' => $item->items[0]));
		echo html::a(array('href' => array('/user/view', 'id' => $item->status_sender->_id), 'text' => $item->status_sender->getUsername()))." added "
			.html::a(array('href' => array('/video/watch', 'id' => $firstVideo->_id), 'text' => $firstVideo->title)).
			(count($item->items)>1?' and '.count($item->items).' others':'').
			" to ".html::a(array('href' => array('/playlist/view', 'id' => $item->playlist->_id), 'text' => $item->playlist->title))." playlist" ?>
		<span class='sent_date'><?php echo $item->getDateTime() ?></span>
		</div>
		<div class='stream_media_item' style='margin-top:20px;'><?php echo $this->renderPartial('playlist/_playlist_small', array('model' => $item->playlist)) ?></div>
		<?php
	elseif($item->type == Stream::LIKE_PL):
				?><div class='stream_item_head'><?php
				echo html::a(array('href' => glue::http()->url('/user/view', array('id' => strval($item->status_sender->_id))), 'text' => $item->status_sender->getUsername()))." liked "
					.html::a(array('href' => glue::http()->url('/playlist/view', array('id' => $item->parent_playlist ? strval($item->parent_playlist->_id) : '')),
								'text' => $item->parent_playlist ? $item->parent_playlist->title : '[Playlist Deleted]'))." playlist" ?>
					<span class='sent_date'> - <?php echo $item->getDateTime() ?></span>
				</div>
					<div class='stream_media_item'><?php $this->renderPartial('Playlist/_playlist_ext', array('model' => $item->parent_playlist)) ?></div>
				<?php
	elseif($item->type == Stream::ITEM_SHARED):
		?><div class='stream_item_head'><?php
		if($item->item_type == 'video'){
			$a = html::a(array('href' => array('/video/watch', 'id' => $item->video->_id), 'text' => $item->video->title));
			$media=$item->video;
		}else{
			$a = html::a(array('href' => array('/playlist/view', 'id' => $item->playlist->_id), 'text' => $item->playlist->title));
			$media=$item->playlist;
		}
		echo html::a(array('href' => glue::http()->url('/user/view', array('id' => strval($item->status_sender->_id))), 'text' => $item->status_sender->getUsername()))." shared ".
		$a ?><span class='sent_date'><?php echo $item->getDateTime() ?></span>
		<div class='stream_share_cus_text'><?php echo nl2br(html::encode($item->message)) ?></div>
		</div>
		<div class='stream_media_item'><?php $this->renderPartial($item->item_type=='video'?'video/_video_small':'playlist/_playlist_small', array('model'=>$media)); ?></div><?php
	elseif($item->type == Stream::SUBSCRIBED_TO):
		?><div class='stream_item_head' style='margin-bottom:10px;'><?php
			echo html::a(array('href' => glue::http()->url('/user/view', array('id' => strval($item->status_sender->_id))), 'text' => $item->status_sender->getUsername()))." subscribed to ".
				html::a(array('href' => glue::http()->url('/user/view', array('id' => strval($item->subscribed_user->_id))),
				'text' => $item->subscribed_user->getUsername())) ?><span class='sent_date'><?php echo $item->getDateTime() ?></span>
		</div>
		<?php echo $this->renderPartial('user/_user_small',array('model'=>$item->subscribed_user)) ?>
	<?php endif; ?>
	</div>
	<div class="clear"></div>
</div>