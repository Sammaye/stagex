<div class='stream_body user_notifications_body'>
	<?php
	$stream = app\models\Notification::model()->find(array('userId' => glue::user()->_id))->sort(array('created' => -1))->limit(20);
	if($stream->count() <= 0){
		?><div class='no_results_found'>You have no new notifications</div><?php
	}

	foreach($stream as $item){

		if(!$item->video instanceof app\models\Video || !glue::auth()->check(array('viewable'=>$item->video))){
			$item->video=new app\models\Video;
			$item->video->title='[Not Available]';
		}
		
		?><div class='notification_item'><?php 

		if($item->type == app\models\Notification::VIDEO_COMMENT){ ?>
			<span class='sent_date'><?php echo $item->getDateTime() ?></span><?php
			echo sprintf('%1$s'.(count($item->from_users) > 1 ? ' have ' : ' has ').
				($item->totalResponses > count($item->from_users) ? 'made a total of %2$s responses to' : 'responded to').
				' %3$s '.($item->approved == false ? 'which require moderation' : ''),
				$item->get_usernames_caption(), $item->totalResponses,
				html::a(array('href' => glue::http()->url('/video/watch', array('id' => $item->video->_id)), 'text' => $item->video->title)));
			?><span class="sep">-</span><a href='<?php echo glue::http()->url('/videoresponse/list', array('id' => strval($item->video->_id))) ?>'>Manage responses</a>
		<?php }elseif($item->type == app\models\Notification::VIDEO_COMMENT_REPLY){ ?>
			<span class='sent_date'><?php echo $item->getDateTime() ?></span>
			<?php echo sprintf('%1$s '.($item->totalResponses > count($item->from_users) ? 'made %2$s responses to a comment you made on' :
				'responded to a comment you made on').' %3$s', $item->get_usernames_caption(), $item->totalResponses,
				html::a(array('href' => array('/video/watch', 'id' => $item->video->_id), 'text' => $item->video->title)));
				?>
			<?php
			$matches = array();
			if($item->response){
				preg_match('/^.[^,]*/', $item->response->path, $matches);
				$parent_id = strval($matches[0]);
			} ?><span class="sep">-</span><a href='<?php echo glue::http()->url('/videoresponse/thread', array('id' => isset($parent_id)?$parent_id:'')) ?>'>View thread</a>
		<?php }elseif($item->type == app\models\Notification::VIDEO_RESPONSE_APPROVE){ ?>
				<span class='sent_date'><?php echo $item->getDateTime() ?></span><?php echo sprintf('%1$s has approved '.($item->totalResponses > 1 ? 'the %2$s responses you made on' : 'the response you made on').' %3$s',
					$item->get_usernames_caption(), $item->totalResponses,
					html::a(array('href' => glue::http()->url('/video/watch', array('id' => $item->video->_id)), 'text' => $item->video->title)));
				?><span class="sep">-</span><a href='<?php echo glue::http()->url('/videoresponse/list', array('id' => strval($item->video->_id))) ?>'>View Comments</a>
		<?php }elseif($item->type == app\models\Notification::WALL_POST){ ?>
			<span class='sent_date'><?php echo $item->getDateTime() ?></span><?php echo sprintf('%1$s has posted '.($item->totalResponses > count($item->from_users) ? '%2$s comments' :
				'a comment').' on your stream', $item->get_usernames_caption(), $item->totalResponses); ?>
				 <span class="sep">-</span><a href='<?php echo glue::http()->url('/stream') ?>'>View your stream</a>
		<?php } ?>
		</div><?php 
	} ?>
</div>