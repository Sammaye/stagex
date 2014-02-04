<?php
glue::controller()->jsFile("/js/autosize.js");

glue::controller()->js('thread', "
		
	$('.video_thread_alert.alert').summarise();
		
	$(document).on('click', '.thread_parent .actions_bar .btn_like', function(event){
		event.preventDefault();
		var el = $(this);

		$.getJSON('/videoresponse/like', { id: el.parents('.thread_parent').data().id }, function(data){
			$('.video_thread_alert.alert').summarise('reset');
			if(data.success){
				el.html('Unlike').removeClass('btn_like').addClass('btn_unlike');
			}else{
				$('.video_thread_alert.alert').summarise('set','error',
				'<p>You could not like that response due to an unknown error. Please try agian later.</p>');
			}
		});
	});

	$(document).on('click', '.thread_parent .actions_bar .btn_unlike', function(event){
		event.preventDefault();
		var el = $(this);

		$.getJSON('/videoresponse/unlike', { id: el.parents('.thread_parent').data().id }, function(data){
			$('.video_thread_alert.alert').summarise('reset');
			if(data.success){
				el.html('Like').removeClass('btn_unlike').addClass('btn_like');
			}else{
				$('.video_thread_alert.alert').summarise('set','error',
				'<p>You could not unlike that response due to an unknown error. Please try again later.</p>');		
			}
		});
	});

	$(document).on('click', '.thread_parent .actions_bar .reply_button', function(event){
		event.preventDefault();
		$(this).parents('.thread_parent').find('.reply').css({ 'display': 'block' });
		$(this).parents('.thread_parent').find('.reply').find('textarea').focus();
	});

	$(document).on('click', '.thread_parent .reply .btn_cancel', function(event){
		event.preventDefault();
		$(this).parents('.reply').css({ 'display':'none' });
	});

	$(document).on('click', '.thread_parent .reply .btn_post_reply', function(event){
		event.preventDefault();
		var el = $(this);

		$.post('/videoresponse/add', { 'parent_comment': el.parents('.thread_parent').data().id, 'content':  el.parents('.reply').find('textarea').val(),
			video_id: '".strval($video->_id)."', type: 'text'}, function(data){

			$('.video_thread_alert.alert').summarise('reset');

			if(data.success){
				el.parents('.reply').find('textarea').val('');
				el.parents('.reply').css({ 'display':'none' });
				$('.thread_reply_list .list').prepend($(data.html));
			}else{
				$('.video_thread_alert.alert').summarise('set','error',
				{message:'<p>Your reply could not be posted because:</p>', list: data.messages});							
			}
		}, 'json');
	});
");
?>

<div class='responses_body video_response_thread_body'>
	<div class='alert video_thread_alert'></div>

	<div class='thread_parent clearfix' data-id='<?php echo $thread_parent->_id ?>'>
		<img alt='thumbnail' class='thumbnail' src='<?php echo $thread_parent->author->getAvatar(48, 48) ?>'/>
		<div class='main_body'>
			<div class='user'><a href='<?php echo glue::http()->url('/user/view', array('id' => $thread_parent->author->_id)) ?>'><b><?php echo $thread_parent->author->getUsername() ?></b></a>
			<span class='thread_created'><?php echo $thread_parent->ago($thread_parent->created) ?></span></div>
			<div class='thread_content'><?php echo html::encode($thread_parent->content) ?></div>
			<div class='actions_bar'>
			<span class="bar_btn">
			<?php if($video->voteableComments):
				if($thread_parent->currentUserLikes()): ?><a href='#' class='btn_unlike'>Unlike</a><?php else: ?><a href='#' class='btn_like'>Like</a><?php endif;
			endif; ?> 
			<span style='color:#006600;'><?php if($thread_parent->likes > 0): echo "+".$thread_parent->likes; endif ?></span>
			</span>
			<?php if($video->allowTextComments): ?>
			<a href='#' class='reply_button bar_btn'>Reply</a>
			<?php endif; ?>
			</div>
			<div class='reply'>
				<div class="form-group">
				<?php echo app\widgets\AutosizeTextarea::run(array(
				'attribute' => 'reply_comment_content', 'class' => 'reply_coment_content form-control grid-col-35'
				)) ?>					
				</div>
				<div class='reply_footer clearfix'>
					<button type="button" class="btn btn-success btn_post_reply">Post</button>
					<button type="button" class="btn btn-white btn_cancel">Cancel</button>
				</div>
			</div>
			<div class='thread_video'>
				<div class='in_repl_text'><b>In reply to:</b></div>
				<?php echo $this->renderPartial('video/_video_small', array('model' => $video)) ?>
			</div>
		</div>
	</div>
	<?php if(count($thread) > 0){ ?>
		<div class='thread_reply_list'>
			<div class='thread_reply_amnt text-muted'><?php echo count($thread) ?> replies to <?php echo $thread_parent->author->getUsername() ?></div>
			<?php echo $this->renderPartial('response/list', array('model' => $video, 'comments' => $thread, 'pageSize' => 1000, 'hideSelector' => true)) ?>
		</div>
	<?php }else{ ?>
		<div class='comment_list'><div class='list'></div></div>
	<?php } ?>
</div>