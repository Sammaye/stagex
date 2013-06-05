<?php
glue::clientScript()->addJsFile('autoresize', "/js/autoresizetextarea.js");

glue::clientScript()->addJsScript('thread.page', "
	$(function(){
		$(document).on('click', '.thread_parent .actions_bar .like', function(event){
			event.preventDefault();
			var el = $(this);

			$.getJSON('/videoresponse/like', { id: el.parents('.thread_parent').data().id }, function(data){
				if(data.success){
					forms.reset(el.parents('.thread_parent').find('.block_summary'));
					el.html('Unlike').removeClass('like').addClass('unlike');
				}else{
					forms.summary(el.parents('.thread_parent').find('.block_summary'), false,
						'You could not like that response due to an unknown error. Please try agian later.', data.errors);
				}
			});
		});

		$(document).on('click', '.thread_parent .actions_bar .unlike', function(event){
			event.preventDefault();
			var el = $(this);

			$.getJSON('/videoresponse/unlike', { id: el.parents('.thread_parent').data().id }, function(data){
				if(data.success){
					forms.reset(el.parents('.thread_parent').find('.block_summary'));
					el.html('Like').removeClass('unlike').addClass('like');
				}else{
					forms.summary(el.parents('.thread_parent').find('.block_summary'), false,
						'You could not unlike that response due to an unknown error. Please try again later.', data.errors);
				}
			});
		});

		$(document).on('click', '.thread_parent .actions_bar .reply_button', function(event){
			event.preventDefault();
			$(this).parents('.thread_parent').find('.reply').css({ 'display': 'block' });
			$(this).parents('.thread_parent').find('.reply').find('textarea').focus();
		});

		$(document).on('click', '.thread_parent .reply .cancel', function(event){
			event.preventDefault();
			$(this).parents('.reply').css({ 'display':'none' });
		});

		$(document).on('click', '.thread_parent .reply .post_comment_reply', function(event){
			event.preventDefault();
			var el = $(this);

			$.post('/videoresponse/add_response', { 'parent_comment': el.parents('.thread_parent').data().id, 'content':  el.parents('.reply').find('textarea').val(),
				vid: '".strval($video->_id)."', type: 'text'}, function(data){

				forms.reset(el.parents('.thread_parent').find('.block_summary'));

				if(data.success){
					el.parents('.reply').find('textarea').val('');
					el.parents('.reply').css({ 'display':'none' });

					$('.comment_list .list').prepend($(data.html));
				}else{
					forms.summary(el.parents('.thread_parent').find('.block_summary'), false,
						'Your reply could not be posted because:<ul></ul>', data.messages);
				}
			}, 'json');
		});
	});
");

glue::clientScript()->addJsScript('watch.response_list', "
	var live_comments_timeout;

	$(document).on('click', '.video_response_item .like', function(event){
		event.preventDefault();
		var el = $(this);

		$.getJSON('/videoresponse/like', { id: el.parents('.video_response_item').data().id }, function(data){
			if(data.success){
				el.html('Unlike').removeClass('like').addClass('unlike');
			}
		});
	});

	$(document).on('click', '.video_response_item .unlike', function(event){
		event.preventDefault();
		var el = $(this);

		$.getJSON('/videoresponse/unlike', { id: el.parents('.video_response_item').data().id }, function(data){
			if(data.success){
				el.html('Like').removeClass('unlike').addClass('like');
			}
		});
	});

	$(document).on('click', '.video_response_item .delete_button', function(event){
		event.preventDefault();
		var el = $(this).parents('.video_response_item');

		$.getJSON('/videoresponse/delete', { id: el.data().id }, function(data){
			if(data.success){
				el.remove();
			}
		});
	});

	$(document).on('click', '.video_response_item .reply_button', function(event){
		event.preventDefault();
		var el = $(this).parents('.video_response_item');

		el.addClass('expanded');
		el.find('.reply').css({ 'display': 'block' }).find('textarea').focus();
	});

	$(document).on('click', '.video_response_item .reply .cancel', function(event){
		event.preventDefault();
		var item = $(this).parents('.video_response_item');

		if(item.find('.thread_parent_viewer').css('display') != 'block'){ // Then remove expanded class
			item.removeClass('expanded');
		}
		item.find('.reply').css({ 'display':'none' });
	});

	$(document).on('click', '.video_response_item .reply .post_comment_reply', function(event){
		event.preventDefault();
		var el = $(this), item = $(this).parents('.video_response_item'),
			sort = $('.video_response_list').data('sort') == null ? '' : $('.video_response_list').data('sort'),
			mode = $('.video_response_list').data('mode') == null ? '' : $('.video_response_list').data('mode');

		$.post('/videoresponse/add_response', { 'parent_comment': item.data().id, 'content':  item.find('.reply').find('textarea').val(),
			sort: sort, mode: mode, vid: '".strval($video->_id)."', type: 'text'}, function(data){

			forms.reset(item.find('.block_summary'));
			if(data.success){
				item.find('textarea').val('');
				item.find('.reply').css({ 'display':'none' });
				item.after($(data.html).addClass('thread_comment'));

				if(item.find('.thread_parent_viewer').css('display') != 'block'){ // Then remove expanded class
					item.removeClass('expanded');
				}
			}else{
				forms.summary(item.find('.block_summary'), false, 'Your reply could not be posted because:', data.messages);
			}
		}, 'json');
	});

	$(document).on('click', '.video_response_item .approve_button', function(event){
		event.preventDefault();
		var el = $(this), item = $(this).parents('.video_response_item'),
			mode = $('.video_response_list').data('mode') == null ? '' : $('.video_response_list').data('mode');

		$.get('/videoresponse/approve', { 'id': item.data().id, mode: mode }, function(data){
			if(data.success){
				item.replaceWith($(data.html));
			}
		}, 'json');
	});

	$(document).on('click', '.video_response_item .expand_comment_parent', function(event){
		event.preventDefault();
		var item = $(this).parents('.video_response_item');

		if(item.find('.thread_parent_viewer').css('display') == 'block'){
			item.find('.thread_parent_viewer').css({ 'display': 'none' });
			if(item.find('.reply').css('display') != 'block'){ // Then remove expanded class
				item.removeClass('expanded');
			}
		}else{
			item.addClass('expanded').find('.thread_parent_viewer').css({ 'display': 'block' });
		}
	});

	$(function(){
		$('.reply_comment_content').autoResize();
	});
"); // All Response list related stuff gets shoved into here
?>

<div class='responses_body video_response_thread_body'>
	<div class='thread_view_left float_left' id='responses'>
		<div class='block_summary'></div>

		<div class='thread_parent' data-id='<?php echo $thread_parent->_id ?>'>
			<img alt='thumbnail' class='float_left' src='<?php echo $thread_parent->author->getAvatar(48, 48) ?>'/>
			<div class='main_body'>
				<div class='user'><a href='<?php echo glue::url()->create('/user/view', array('id' => $thread_parent->author->_id)) ?>'><b><?php echo $thread_parent->author->getUsername() ?></b></a> <span class='ts'>- <?php echo ago($thread_parent->ts->sec) ?></span></div>
				<div class='thread_content'><?php echo html::encode($thread_parent->content) ?></div>
				<div class='actions_bar'><?php if($thread_parent->currentUserLikes()): ?><a href='#' class='unlike'>Unlike</a><?php else: ?><a href='#' class='like'>Like</a><?php endif; ?> <span style='color:#006600;'><?php if($thread_parent->likes > 0): echo "+".$thread_parent->likes; endif ?></span><span class='divider'>-</span><a href='#' class='reply_button'>Reply</a></div>

				<div class='reply'>
					<?php echo html::textarea('reply_comment_content', null, array('class' => 'reply_coment_content')) ?>
					<div class='reply_footer'>
						<div class='green_css_button post_comment_reply float_left'>Post reply</div>
						<div class='grey_css_button cancel'>Cancel</div>
					</div>
					<div class="clearer"></div>
				</div>

				<div class='thread_video'>
					<div class='in_repl_text'>In reply to:</div>
					<div class='video_item thread_video_item'>
						<div class='video_image'><img alt='thumbnail' src='<?php echo $video->getImage(124, 69) ?>'/></div>
						<div class='more_info_pane' style='margin-left:134px; width:350px;'>
							<div class='title'><a href='<?php echo glue::url()->create('/video/watch', array('id' => $video->_id)) ?>'><?php echo $video->title ?></a></div>
							<div class='details'>
								Uploaded by <a href="<?php echo glue::url()->create('/user/view', array('id' => $thread_parent->author->_id)) ?>"><?php echo $video->author->getUsername() ?></a> <span class='divider'>|</span> <?php echo $video->views ?> views
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php if(count($thread) > 0){ ?>
			<div class='thread_reply_list'>
				<div class='thread_reply_amnt'><?php echo count($thread) ?> replies to <?php echo $thread_parent->author->getUsername() ?></div>
				<div class='comment_list'>
					<div class='list'>
						<?php foreach($thread as $k => $v){
							$this->partialRender('responses/_response', array('item' => $v));
						} ?>
					</div>
				</div>
			</div>
		<?php }else{ ?>
			<div class='comment_list'><div class='list'></div></div>
		<?php } ?>
	</div>
		<div style='float:left; width:300px; margin-left:10px;'>
			<?php $this->widget("application/widgets/Advertising/Ad_box.php", array( "configuration"=>'300_box' )); ?>
			<div style='margin-top:25px;'>
				<?php $this->widget("application/widgets/Advertising/Ad_box.php", array( "configuration"=>'300_box' )); ?>
			</div>
		</div>
</div>