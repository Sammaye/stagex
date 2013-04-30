<div class='container_16 stream_body user_notifications_body'>
	<div class='grid_5 alpha omega user_notifications_left'>
		<div class='header_outer'><h1 class='page_head'>Notifications</h1></div>

		<?php
		$stream = Notification::model()->find(array('user_id' => glue::session()->user->_id))->sort(array('ts' => -1))->limit(20);
		if(sizeof($stream) <= 0){
			?><div class='no_notifications'>You have no new notifications</div><?php
		}

		foreach($stream as $stream_item){
			if(!$stream_item->parent_video && $stream_item->type != Notification::WALL_POST){
				continue;
			}

			if($stream_item->type == Notification::VIDEO_COMMENT){ ?>
				<div class='notification_item'>
					<div class='notification_header'>
						<?php
						echo sprintf('%1$s'.(sizeof($stream_item->from_users) > 1 ? ' have ' : ' has ').
							($stream_item->response_count > sizeof($stream_item->from_users) ? 'made a total of %2$s responses to' : 'responded to').
							' %3$s '.($stream_item->approved == false ? 'which require moderation' : ''),

							$stream_item->get_usernames_caption(), $stream_item->response_count,
							html::a(array('href' => glue::url()->create('/video/watch', array('id' => $stream_item->parent_video->_id)), 'text' => $stream_item->parent_video->title)));
						?><span class='sent_date'> - <?php echo $stream_item->getDateTime() ?></span>
					</div>
					<div class='response_manage'><a href='<?php echo glue::url()->create('/videoresponse/view_all', array('id' => strval($stream_item->parent_video->_id))) ?>'>Manage all responses for this video</a></div>
					<div class='video_item'>
						<?php if($stream_item->parent_video): ?>
							<div class='video_thumb_pane'><a href="/video/watch?id=<?php echo strval($stream_item->parent_video->_id) ?>" ><img alt='thumbnail' class='video_img' src="<?php echo $stream_item->parent_video->getImage(138, 77) ?>"/></a></div>
							<div class='more_info_pane'>
								<h3 class='title'><a href="/video/watch?id=<?php echo strval($stream_item->parent_video->_id) ?>"><?php echo $stream_item->parent_video->title ?></a></h3>
								<div class='details'><a href='#'><?php echo $stream_item->parent_video->views ?> Views</a>
									<?php echo utf8_decode('&#183;') ?> <a href='<?php echo glue::url()->create('/videoresponse/view_all', array('id' => strval($stream_item->parent_video->_id))) ?>'><?php echo $stream_item->parent_video->total_responses ?> Responses</a> <?php echo utf8_decode('&#183;') ?>
								</div>
							</div>
						<?php else: $video = new Video; ?>
							<div class='video_thumb_pane'><a href="/video/watch" ><img alt='thumbnail' class='video_img' src="<?php echo $video->getImage(138, 77) ?>"/></a></div>
							<div class='more_info_pane'>
								<h3 class='title'><a href="/video/watch">[Video Deleted]</a></h3>
							</div>
						<?php endif; ?>
						<div class='clearer'></div>
					</div>
				</div>
			<?php }elseif($stream_item->type == Notification::VIDEO_COMMENT_REPLY){ ?>
				<div class='notification_item'>
					<div class='notification_header'>
						<?php echo sprintf('%1$s '.($stream_item->response_count > sizeof($stream_item->from_users) ? 'made %2$s responses to a comment you made on' :
								'responded to a comment you made on').' %3$s',

							$stream_item->get_usernames_caption(), $stream_item->response_count,
							html::a(array('href' => glue::url()->create('/video/watch', array('id' => $stream_item->parent_video->_id)), 'text' => $stream_item->parent_video->title)));
						?><span class='sent_date'> - <?php echo $stream_item->getDateTime() ?></span>
					</div>
					<?php
					$matches = array();
					if($stream_item->original_comment){
						preg_match('/^.[^,]*/', $stream_item->original_comment->path, $matches);
						$parent_id = strval($matches[0]);
					}else{
						$parent_id = '';
					} ?>
					<div class='response_manage'><a href='<?php echo glue::url()->create('/videoresponse/thread', array('id' => $parent_id)) ?>'>View this thread</a></div>
					<div class='video_item'>
						<?php if($stream_item->parent_video): ?>
							<div class='video_thumb_pane'><a href="/video/watch?id=<?php echo strval($stream_item->parent_video->_id) ?>" ><img alt='thumbnail' class='video_img' src="<?php echo $stream_item->parent_video->getImage(138, 77) ?>"/></a></div>
							<div class='more_info_pane'>
								<h3 class='title'><a href="/video/watch?id=<?php echo strval($stream_item->parent_video->_id) ?>"><?php echo $stream_item->parent_video->title ?></a></h3>
								<div class='details'><a href='#'><?php echo $stream_item->parent_video->views ?> Views</a>
									<?php echo utf8_decode('&#183;') ?> <a href='<?php echo glue::url()->create('/videoresponse/view_all', array('id' => strval($stream_item->parent_video->_id))) ?>'><?php echo $stream_item->parent_video->total_responses ?> Responses</a> <?php echo utf8_decode('&#183;') ?>
								</div>
							</div>
						<?php else: $video = new Video; ?>
							<div class='video_thumb_pane'><a href="/video/watch" ><img alt='thumbnail' class='video_img' src="<?php echo $video->getImage(138, 77) ?>"/></a></div>
							<div class='more_info_pane'>
								<h3 class='title'><a href="/video/watch">[Video Deleted]</a></h3>
							</div>
						<?php endif; ?>
						<div class='clearer'></div>
					</div>
					<div class='comment_item'>You wrote: <?php echo nl2br(html::encode($stream_item->original_comment ? $stream_item->original_comment->content : '[Comment Deleted]')) ?></div>
				</div>
			<?php }elseif($stream_item->type == Notification::VIDEO_RESPONSE_APPROVE){ ?>
				<div class='notification_item'>
					<div class='notification_header'>
						<?php echo sprintf('%1$s has approved '.($stream_item->response_count > 1 ? 'the %2$s responses you made on' : 'the response you made on').' %3$s',

							$stream_item->get_usernames_caption(), $stream_item->response_count,
							html::a(array('href' => glue::url()->create('/video/watch', array('id' => $stream_item->parent_video->_id)), 'text' => $stream_item->parent_video->title)));
						?><span class='sent_date'> - <?php echo $stream_item->getDateTime() ?></span>
					</div>
					<div class='response_manage'><a href='<?php echo glue::url()->create('/videoresponse/view_all', array('id' => strval($stream_item->parent_video->_id))) ?>'>View Comments</a></div>
					<div class='video_item'>
						<?php if($stream_item->parent_video): ?>
							<div class='video_thumb_pane'><a href="/video/watch?id=<?php echo strval($stream_item->parent_video->_id) ?>" ><img alt='thumbnail' class='video_img' src="<?php echo $stream_item->parent_video->getImage(138, 77) ?>"/></a></div>
							<div class='more_info_pane'>
								<h3 class='title'><a href="/video/watch?id=<?php echo strval($stream_item->parent_video->_id) ?>"><?php echo $stream_item->parent_video->title ?></a></h3>
								<div class='details'><a href='#'><?php echo $stream_item->parent_video->views ?> Views</a>
									<?php echo utf8_decode('&#183;') ?> <a href='<?php echo glue::url()->create('/videoresponse/view_all', array('id' => strval($stream_item->parent_video->_id))) ?>'><?php echo $stream_item->parent_video->total_responses ?> Responses</a> <?php echo utf8_decode('&#183;') ?>
								</div>
							</div>
						<?php else: $video = new Video; ?>
							<div class='video_thumb_pane'><a href="/video/watch" ><img alt='thumbnail' class='video_img' src="<?php echo $video->getImage(138, 77) ?>"/></a></div>
							<div class='more_info_pane'>
								<h3 class='title'><a href="/video/watch">[Video Deleted]</a></h3>
							</div>
						<?php endif; ?>
						<div class='clearer'></div>
					</div>
				</div>
			<?php }elseif($stream_item->type == Notification::WALL_POST){ ?>
				<div class='notification_item'>
					<div class='notification_header'>
						<?php echo sprintf('%1$s has posted '.($stream_item->response_count > sizeof($stream_item->from_users) ? '%2$s comments' :
								'a comment').' on your stream',
							$stream_item->get_usernames_caption(), $stream_item->response_count);
						?><span class='sent_date'> - <?php echo $stream_item->getDateTime() ?></span>
					</div>
					<div class='response_manage'><a href='<?php echo glue::url()->create('/stream') ?>'>View your stream</a></div>
				</div>
			<?php }
		} ?>
	</div>
</div>