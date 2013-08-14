<?php
glue::$controller->jsFile("/js/autosize.js");

glue::$controller->js('response_selector', "
		
	$('.video_response_selector .alert').summarise()
		
	$(document).on('click', '.video_response_selector .response_tab', function(event){
		event.preventDefault();
		var stub = $(this).children('.stub'),
			selector = $(this).attr('href').substr(1, $(this).attr('href').length);

		$('.video_response_selector .response_tabs').find('i').not(stub).css({ 'display': 'none' });
		stub.css({ 'display': 'block' });

		$('.video_response_selector .response_pane').not($('.response_panes '+$(this).attr('href'))).css({ 'display': 'none' });
		$('.video_response_selector .'+selector).css({ 'display': 'block' });
	});

	$(document).on('click', '.video_response_selector .post_response', function(event){
		event.preventDefault();
		var textarea = $(this).parents('.video_response_selector').find('.text_comment_content'),
			mode = $('.video_response_list').data('mode') == null ? '' : $('.video_response_list').data('mode');

		$.post('/videoresponse/add', { 'content': textarea.val(), 'vid': '".strval($model->_id)."', type: 'text', mode: mode}, function(data){
			//forms.reset($('.video_response_selector .block_summary'));
			if(!data.success){
				//console.log('errors', data);
				$('.video_response_selector .alert').summarise('set','error',{
					message: 'Your reply could not be posted because:',
					list: data.messages
				})
			}else{
				textarea.val('');
				forms.reset($('.video_response_selector .block_summary'));
				$('.video_response_list .list').prepend(data.html);
			}
		}, 'json');
	});

	$(document).on('click', '.video_response_selector .new_video_response', function(event){
			event.preventDefault();

		var mode = $('.video_response_list').data('mode') == null ? '' : $('.video_response_list').data('mode');

		$.post('/videoresponse/add_response', {id: $('.video_response_selector #video_response_id').val(), vid: '".strval($model->_id)."', type: 'video', mode: mode}, function(data){
			forms.reset($('.video_response_selector .block_summary'));
			if(data.success){
				$('.video_response_selector #video_response_id').val('');
				$('.video_response_selector .videoResponseSearch').val('');
				$('.video_response_list .list').prepend(data.html);
			}else{
				console.log(data.messages);
				forms.summary($('.video_response_selector .block_summary'), false, 'Your reply could not be posted because:', data.messages);
			}
		}, 'json');
	});
");

glue::$controller->js('watch.response_list', "
	var live_comments_timeout;

	$(function(){
		get_comments_by_epoch();
		$('.reply_comment_content').autosize();
	});

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
			sort: sort, mode: mode, vid: '".strval($model->_id)."', type: 'text'}, function(data){

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

	// Paging
	$(document).on('click', '.video_response_list .list .GListView_Pager a', function(event){
		event.preventDefault();
		refresh_video_response_list($(this).attr('href').replace(/#page_/, ''));
	});

	$(document).on('click', '.new_comments_notifier a', function(event){
		event.preventDefault();
		refresh_video_response_list(1, 1);
		$(this).parents('.new_comments_notifier').css({ 'display': 'none' });
	});

	function refresh_video_response_list(page, refresh){
		if(page == null){
			page = 1;
		}else if(page == 'current'){
			if($('.video_response_list .list .GListView_Pager div.active').length > 0){
				page = $('.video_response_list .list .GListView_Pager div.active').data().page
			}
		}
		if(refresh == null) refresh = 0;

		var sort = $('.video_response_list').data('sort') == null ? '' : $('.video_response_list').data('sort'),
			mode = $('.video_response_list').data('mode') == null ? '' : $('.video_response_list').data('mode'),
			responses_per_page = $('.video_response_list').data('responses_per_page') == null ? '' : $('.video_response_list').data('responses_per_page');

		$('.video_response_list .list').load('/videoresponse/get_comments', { id: '".strval($model->_id)."', page: page, sort: sort, mode: mode,
			responses_per_page: responses_per_page, refresh: refresh }, function(data){

			$('.reply_comment_content').autosize();
		});
	}

	function get_comments_by_epoch(){
		$.getJSON('/videoresponse/live_comments', {'id': '".strval($model->_id)."'}, function(data){
			if(data.success){
				if(data.number_comments == 1){
					$('.video_response_list .new_comments_notifier a').html(data.number_comments+' new comment since you start viewing');
					$('.video_response_list .new_comments_notifier').css({ 'display': 'block' });
				}
				if(data.number_comments > 0){
					$('.video_response_list .new_comments_notifier a').html(data.number_comments+' new comments since you started viewing');
					$('.video_response_list .new_comments_notifier').css({ 'display': 'block' });
				}
			}
			live_comments_timeout = setTimeout('get_comments_by_epoch()', 60000);
		});
	}
"); // All Response list related stuff gets shoved into here
?>

<div class='video_response_selector'>
	<div class='block_summary main_block_summary' style='display:none;'></div>
	<?php if(($model->allowTextComments || $model->allowVideoComments) && glue::auth()->check(array('@'))){ ?>
		<ul class="response_tabs">
			<li>Respond with:</li>
			<?php if($model->allowTextComments): ?><li><a href="#text_response_pane" class="response_tab text_response_tab">Comment<i class="stub" style='left:130px;'></i></a></li><?php endif ?>
			<?php if($model->allowVideoComments): ?><li><a href="#video_response_pane" class="response_tab video_response_tab">Video<i class="stub" style='<?php if($model->allowTextComments){ echo "display:none;"; } ?> left:205px;'></i></a></li><?php endif ?>
		</ul>
		<div class="response_panes">
			<?php if($model->allowTextComments){ ?>
				<div class='response_pane text_response_pane'>
					<?php app\widgets\autoresizetextarea::widget(array(
						'attribute' => 'text_comment_content',
						'class' => 'text_comment_content'
					)) ?>
					<input type="button" value="Post Response" class="btn-success post_response"/>
				</div>
			<?php } ?>

			<?php if($model->allowVideoComments){ ?>
				<div class='response_pane video_response_pane' style='display:none;'>
					<div class='inner'>
						<h2 class='select_head'>Search for and select one of your videos to add it as a response:</h2>
						<?php app\widgets\Jqautocomplete::widget(array(
								'attribute' => 'videoResponseSearch',
								'value' => '',
								'options' => array(
									'appendTo' => '#videoResponse_results',
									'source' => '/videoresponse/response_suggestions',
									'minLength' => 2,
									'select' => "js:function(event, ui){
										$( '.videoResponseSearch' ).val( ui.item.label );
										$( '#video_response_id' ).val( ui.item._id );
										return false;
									}"
								),
								'renderItem' => "
									return $( '<li></li>' )
										.data( 'item.autocomplete', item )
										.append( '<a class=\'content\'><img alt=\'thumbnail\' src=\''+ item.image_src +'\'/><span>' + item.label + '</span><div class=\'clearer\'></div></a>' )
										.appendTo( ul );"
						)) ?>
						<input type='hidden' id='video_response_id' name='_id'/>
						<a href='#' class='green_css_button post_video_response new_video_response' id=''>Post Response</a>
						<div class="clear"></div>
					</div>
					<div class='upload_video'>
						<p class='dark_grey_text'>Don't see your video there? <?php echo html::a(array('href' => 'http://upload.stagex.co.uk/video/upload', 'text' => 'Upload more videos'))?></p>
					</div>
				</div>
			<?php } ?>
		</div>
		<div class="clear"></div>
	<?php } ?>
</div>
<div class="video_response_list" data-sort='<?php echo isset($sort) ? $sort : '' ?>' data-mode='<?php echo isset($mode) ? $mode : '' ?>'
	data-responses_per_page='<?php echo isset($pageSize) ? $pageSize : '' ?>' data-video_id='<?php echo $model->_id ?>'>
	<div class="new_comments_notifier"><a href="#"></a></div>
	<?php
		ob_start();
			?> <div class='list'>{items}<div style='margin-top:7px;'>{pager}<div class="clear"></div></div></div> <?php
		$template = ob_get_contents();
		ob_end_clean();
		
		glue\widgets\ListView::widget(array(
				'pageSize'	 => $pageSize,
				"cursor"	 => $comments,
				'template' 	 => $template,
				'data' 		 => array('mode' => isset($mode) ? $mode : ''),
				'enableAjaxPagination' => true,				
				'itemView' => 'response/_response.php',
				'pagerCssClass' => 'grid_list_pager'
		));	 ?>
</div>