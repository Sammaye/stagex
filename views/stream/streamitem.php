<?php
use app\models\Stream;
if(!isset($hideDelete)) $hideDelete = false;
?>

<div data-id='<?php echo $item->_id ?>' data-ts='<?php echo $item->getTs($item->created) ?>' class='streamitem' style='border-bottom:1px solid #e5e5e5;padding:20px 0;'
	<?php if($item->type == Stream::WALL_POST): ?>data-target_user='<?php echo strval($item->commenting_user->_id) ?>'<?php endif; ?>>

	<?php if(!$hideDelete && (glue::user()->_id == $item->status_sender->_id )): ?>
		<span class="close_button"><a href="#"><?php echo utf8_decode('&#215;') ?></a></span>
	<?php endif; ?>

	<?php if($item->type == Stream::WALL_POST): ?>
		<a href='<?php echo glue::http()->url('/user/view', array('id' => strval($item->commenting_user->_id))) ?>'><img alt='thumbnail' src='<?php echo $item->commenting_user->getAvatar(48, 48) ?>' class='' style='float:left;'/></a>
	<?php else: ?>
		<a href='<?php echo glue::http()->url('/user/view', array('id' => strval($item->status_sender->_id))) ?>'><img alt='thumbnail' src='<?php echo $item->status_sender->getAvatar(48, 48) ?>' class='' style='float:left;'/></a>
	<?php endif; ?>

	<div class='stream_item_inner' style='float:left; margin-left:10px;'>
		<?php if($item->type == Stream::WALL_POST): ?>
			<div class='stream_item_head'><?php
				if(strval($item->commenting_user->_id) == strval($item->status_sender->_id)){
					echo html::a(array('href' => glue::http()->url('/user/view', array('id' => strval($item->commenting_user->_id))),
						'text' => $item->commenting_user->getUsername()));
				}else{
				 	echo html::a(array('href' => glue::http()->url('/user/view', array('id' => strval($item->commenting_user->_id))),
						'text' => $item->commenting_user->getUsername()))." posted on ".html::a(array('href' => glue::http()->url('/user/view', array('id' => strval($item->status_sender->_id))),
						'text' => $item->status_sender->getUsername())).'\'s stream';
				} ?><span class='sent_date'><?php echo $item->getDateTime() ?></span>
				</div>
				<div class='stream_comment'><span class='expandable'><?php echo htmlspecialchars($item->message) ?></span>
					<?php if(glue::session()->user->_id == $item->status_sender->_id){ ?>
						<div class='stream_comment_reply'><?php echo html::a(array('href' => glue::http()->url('/user/view', array('id' => strval($item->commenting_user->_id))),
							'text' => 'Reply to this user')) ?></div>
					<?php } ?>
				</div>
		<?php elseif($item->type == Stream::COMMENTED_ON):
				?><div class='stream_item_head' style='margin-bottom:15px;'><?php
				echo html::a(array('href' => glue::http()->url('/user/view', array('id' => strval($item->status_sender->_id))), 'text' => $item->status_sender->getUsername()))." responded to ".html::a(array('href' => glue::http()->url('/video/watch', array('id' => strval($item->video->_id))),
					'text' => $item->video->title))?><span class='sent_date'><?php echo $item->getDateTime() ?></span>
				</div>
				<div class='stream_media_item' style='margin-bottom:15px;'><?php echo $this->renderPartial('video/_video_stream', array('model' => $item->video, 'hide_a2p_button' => true, 'hideDescription' => true)) ?></div>
				<div><a href="">View responses</a></div>
		<?php elseif($item->type == Stream::VIDEO_RATE):
				?><div class='stream_item_head'><?php
				if($item->like == 1){
					echo html::a(array('href' => glue::http()->url('/user/view', array('id' => $item->status_sender ? strval($item->status_sender->_id) : '')),
							'text' => $item->status_sender ? $item->status_sender->getUsername() : '[User Deleted]'))." liked ".html::a(array('href' =>
								glue::http()->url('/video/watch', array('id' => $item->video ? strval($item->video->_id) : '')),
								'text' => $item->video ? $item->video->title : '[Video Deleted]'));
				}else{
					echo html::a(array('href' => glue::http()->url('/user/view', array('id' => $item->status_sender ? strval($item->status_sender->_id) : '')),
							'text' => $item->status_sender ? $item->status_sender->getUsername() : '[User Deleted]'))." disliked ".html::a(array('href' =>
								glue::http()->url('/video/watch', array('id' => $item->video ? strval($item->video->_id) : '')),
								'text' => $item->video ? $item->video->title : '[Video Deleted]'));
				}?><span class='sent_date'> - <?php echo $item->getDateTime() ?></span>
				</div>
				<div class='stream_media_item'><?php $this->partialRender('videos/_video_ext', array('model' => $item->video, 'hide_a2p_button' => true, 'descLength' => 500)) ?></div><?php
		elseif($item->type == Stream::VIDEO_WATCHED):
				?><div class='stream_item_head'><?php
				echo html::a(array('href' => glue::http()->url('/user/view', array('id' => strval($item->status_sender->_id))), 'text' => $item->status_sender->getUsername()))." watched ".html::a(array('href' => glue::http()->url('/video/watch', array('id' => strval($item->video->_id))),
						'text' => $item->video->title)) ?><span class='sent_date'><?php echo $item->getDateTime() ?></span>
				</div>
				<div class='stream_media_item' style='margin-top:20px;'><?php echo $this->renderPartial('video/_video_stream', array('model' => $item->video, 'hide_a2p_button' => true, 'descLength' => 500)) ?></div><?php
		elseif($item->type == Stream::VIDEO_UPLOAD):
				?><div class='stream_item_head'><?php
				echo html::a(array('href' => glue::http()->url('/user/view', array('id' => strval($item->status_sender->_id))), 'text' => $item->status_sender->getUsername()))." uploaded ".html::a(array('href' => glue::http()->url('/video/watch', array('id' => strval($item->video->_id))),
						'text' => $item->video->title)) ?><span class='sent_date'> - <?php echo $item->getDateTime() ?></span>
				</div>
				<div class='stream_media_item'><?php $this->renderPartial('video/_video_ext', array('model' => $item->video, 'hide_a2p_button' => true, 'descLength' => 500)) ?></div><?php
		elseif($item->type == Stream::ADD_TO_PL):
				?><div class='stream_item_head'><?php

				$video = app\models\Video::model()->findOne(array('_id' => $item->items[0]));
				//var_dump($item);
				echo html::a(array('href' => glue::http()->url('/user/view', array('id' => strval($item->status_sender->_id))), 'text' => $item->status_sender->getUsername()))." added "
					.html::a(array('href' => glue::http()->url('/video/watch', array('id' => strval($video->_id))), 'text' => $video->title)).
					" to ".html::a(array('href' => glue::http()->url('/playlist/view', array('id' => $item->parent_playlist ? strval($item->parent_playlist->_id) : '')),
					'text' => $item->parent_playlist ? $item->parent_playlist->title : '[Playlist Deleted]'))." playlist" ?>
					<span class='sent_date'> - <?php echo $item->getDateTime() ?></span>
				</div>
					<div class='stream_media_item'><?php $this->renderPartial('Playlist/_playlist_ext', array('model' => $item->parent_playlist)) ?></div>
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
					$video = Video::model()->findOne(array('_id' => $item->item_id));
					$a = html::a(array('href' => glue::http()->url('/video/watch', array('id' => strval($video->_id))), 'text' => $video->title));
				}else{
					$playlist = Playlist::model()->findOne(array('_id' => $item->item_id));

					if($playlist){
						$a = html::a(array('href' => glue::http()->url('/playlist/view', array('id' => strval($playlist->_id))), 'text' => $playlist->title));
					}else{
						$a = html::a(array('href' => glue::http()->url('/playlist/view'), 'text' => '[Playlist Deleted]'));
					}
				}

				echo html::a(array('href' => glue::http()->url('/user/view', array('id' => strval($item->status_sender->_id))), 'text' => $item->status_sender->getUsername()))." shared ".$a ?><span class='sent_date'> - <?php echo $item->getDateTime() ?></span>
				<div class='stream_share_cus_text'><?php echo nl2br(html::encode($item->message)) ?></div>
				</div>
				<div class='stream_media_item'><?php
				if($item->item_type == 'video'){
					$this->partialRender('videos/_video_ext', array('model' => $video, 'hide_a2p_button' => true, 'descLength' => 500));
				}elseif($item->item_type == 'playlist'){
					$this->partialRender('Playlist/_playlist_ext', array('model' => $playlist));
				}?></div>
				<?php
		elseif($item->type == Stream::SUBSCRIBED_TO):
				?><div class='stream_item_head'><?php

				if($item->subscribed_user){
					$subscription = $item->subscribed_user;
				}else{
					$subscription = new User;
				}
//var_dump($subscription);
				echo html::a(array('href' => glue::http()->url('/user/view', array('id' => strval($item->status_sender->_id))), 'text' => $item->status_sender->getUsername()))." subscribed to ".html::a(array('href' => glue::http()->url('/user/view', array('id' => strval($subscription->_id))),
					'text' => $subscription->getUsername())) ?><span class='sent_date'> - <?php echo $item->getDateTime() ?></span>
				</div>
				<div class='stream_item_user'>
					<?php if($item->subscribed_user){ ?>
						<img alt='thumbnail' class='user_img' src='<?php echo $item->subscribed_user->getAvatar(48, 48) ?>'/>
						<h3 class='username'><?php echo html::a(array('href' => glue::http()->url('/user/view', array('id' => strval($item->subscribed_user->_id))),
							'text' => $item->subscribed_user->getUsername())) ?></h3>
					<?php }else{ $user = new User; ?>
						<img alt='thumbnail' class='user_img' src='<?php echo $user->getAvatar(48, 48) ?>'/>
						<h3 class='username'><?php echo html::a(array('href' => glue::http()->url('/user/view'), 'text' => '[User Deleted]')) ?></h3>
					<?php } ?>
				</div>
				<div class='clearer'></div>
		<?php endif; ?>
	</div>
	<div class="clear"></div>
</div>