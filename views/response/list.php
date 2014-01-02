<?php

$ajaxPagination=isset($ajaxPagination)?$ajaxPagination:false;

glue::controller()->jsFile("/js/autosize.js");

glue::controller()->js('response_selector', "
	$('.video_response_selector .alert').summarise();
		
	$(document).on('click', '.video_response_selector .response_tab', function(event){
		event.preventDefault();
		pane=$('.video_response_selector').find('.'+$(this).attr('id').replace(/_tab/,'_content'));
		$('.video_response_selector .tabs .response_tab').not($(this)).removeClass('selected');
		$(this).addClass('selected');		
		$('.video_response_selector .response_pane').not(pane).css({display:'none'});
		pane.css({display:'block'});
	});

	$(document).on('click', '.video_response_selector .text_response_content .post_response', function(event){
		event.preventDefault();
		var textarea = $(this).parents('.video_response_selector').find('.text_comment_content'),
			mode = $('.video_response_list').data('mode') == null ? '' : $('.video_response_list').data('mode');

		$.post('/videoresponse/add', { 'content': textarea.val(), 'video_id': '".strval($model->_id)."', type: 'text', mode: mode}, function(data){
			if(!data.success){
				$('.video_response_selector .alert').summarise('set','error',{
					message: '<p>Your reply could not be posted because:</p>',
					list: data.messages
				});
			}else{
				textarea.val('');
				$('.video_response_selector .alert').summarise('reset');
				$('.video_response_list .list').prepend(data.html);
			}
		}, 'json');
	});

	$(document).on('click', '.video_response_selector .video_response_content .post_response', function(event){
			event.preventDefault();

		var mode = $('.video_response_list').data('mode') == null ? '' : $('.video_response_list').data('mode');

		$.post('/videoresponse/add', {id: $('.video_response_selector #video_response_id').val(), vid: '".strval($model->_id)."', type: 'video', mode: mode}, function(data){
			if(data.success){
				$('.video_response_selector .alert').summarise('reset');
				$('.video_response_selector #video_response_id').val('');
				$('.video_response_selector .videoResponseSearch').val('');
				$('.video_response_list .list').prepend(data.html);
			}else
				$('.video_response_selector .alert').summarise('set','error',{
					message: '<p>Your reply could not be posted because:</p>',
					list: data.messages
				});
		}, 'json');
	});
");

glue::controller()->js('list', "
	var live_comments_timeout;

	$(function(){
		get_comments_by_epoch();
		$('.reply_comment_content').autosize();
	});

	$(document).on('click', '.response .btn_like', function(event){
		event.preventDefault();
		var el = $(this);

		$.getJSON('/videoresponse/like', { id: el.parents('.response').data().id }, function(data){
			if(data.success){
				el.html('Unlike').removeClass('btn_like').addClass('btn_unlike');
			}
		});
	});

	$(document).on('click', '.response .btn_unlike', function(event){
		event.preventDefault();
		var el = $(this);

		$.getJSON('/videoresponse/unlike', { id: el.parents('.response').data().id }, function(data){
			if(data.success){
				el.html('Like').removeClass('btn_unlike').addClass('btn_like');
			}
		});
	});
		
	$(document).on('click', '.response .btn_delete', function(event){
		event.preventDefault();
		var el = $(this).parents('.response');

		$.post('/videoresponse/delete', { ids: [el.data().id], video_id:'".strval($model->_id)."' }, function(data){
			if(data.success&&data.updated>0)
				el.remove();
		},'json');
	});
				
	$(document).on('click', '.response .btn_approve', function(event){
		event.preventDefault();
		var el = $(this).parents('.response');

		$.get('/videoresponse/approve', { ids: [el.data().id], video_id:'".strval($model->_id)."' }, function(data){
			if(data.success&&data.updated>0){
				el.find('.btn_pending').remove();
				el.find('.btn_approved').css({display:'inline-block'});
			}
		}, 'json');
	});

	$(document).on('click', '.response .btn_reply', function(event){
		event.preventDefault();
		var el = $(this).parents('.response');

		el.find('.reply .alert').summarise();
		el.find('.reply .alert').summarise('reset');	
		el.find('.reply').css({ 'display': 'block' }).find('textarea').focus();
	});

	$(document).on('click', '.response .reply .btn_cancel', function(event){
		event.preventDefault();
		$(this).parents('.response').find('.reply').css({ 'display':'none' });
	});

	$(document).on('click', '.response .btn_post_reply', function(event){
		event.preventDefault();
		var el = $(this), item = $(this).parents('.response'),
			mode = $('.video_response_list').data('mode') == null ? '' : $('.video_response_list').data('mode');

		$.post('/videoresponse/add', { 'parent_comment': item.data().id, 'content':  item.find('.reply').find('textarea').val(),
			mode: mode, video_id: '".strval($model->_id)."', type: 'text'}, function(data){

			item.find('.reply .alert').summarise();
			item.find('.reply .alert').summarise('close');
			if(data.success){
				item.find('textarea').val('');
				item.find('.reply').css({ 'display':'none' });
				item.after($(data.html).addClass('thread_comment'));
			}else{
				item.find('.reply .alert').summarise('set','error',{message:'<p>Your reply could not be posted because:</p>', list:data.messages});
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

	function get_comments_by_epoch(){
		$.getJSON('/videoresponse/getNew', {'id': '".strval($model->_id)."'}, function(data){
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

if(isset($ajaxPagination)&&$ajaxPagination){
	glue::controller()->js('paging', "
		// Paging
		$(document).on('click', '.video_response_list .list .ListView_Pager a', function(event){
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
				if($('.video_response_list .list .ListView_Pager li.active').length > 0){
					page = $('.video_response_list .list .ListView_Pager li.active').data().page
				}
			}
			if(refresh == null) refresh = 0;
	
			var sort = $('.video_response_list').data('sort') == null ? '' : $('.video_response_list').data('sort'),
				mode = $('.video_response_list').data('mode') == null ? '' : $('.video_response_list').data('mode'),
				responses_per_page = $('.video_response_list').data('responses_per_page') == null ? '' : $('.video_response_list').data('responses_per_page');
	
			$.post('/videoresponse/getmore', { id: '".strval($model->_id)."', page: page, sort: sort, mode: mode,
				responses_per_page: responses_per_page, refresh: refresh }, function(data){
	
				if(data.success){
					$('.video_response_list .list').html(data.html);
					$('.reply_comment_content').autosize();
				}
			}, 'json');
		}
	");
}

if(!isset($hideSelector)||!$hideSelector)
echo $this->renderPartial('response/_selector',array('model'=>$model));

?>


<div class="video_response_list" data-sort='<?php echo isset($sort) ? $sort : '' ?>' data-mode='<?php echo isset($mode) ? $mode : '' ?>'
	data-responses_per_page='<?php echo isset($pageSize) ? $pageSize : '' ?>' data-video_id='<?php echo $model->_id ?>'>
	<div class="new_comments_notifier"><a href="#"></a></div>
	<?php
		ob_start();
			?> <div class='list'>{items}<div style='margin-top:7px;'>{pager}<div class="clear"></div></div></div> <?php
		$template = ob_get_contents();
		ob_end_clean();
		
		echo glue\widgets\ListView::run(array(
				'pageSize'	 => 1,
				"cursor"	 => $comments,
				'template' 	 => $template,
				'sortableAttributes' => array('likes'=>null,'created'=>null),
				'data' 		 => array('mode' => isset($mode) ? $mode : ''),
				'enableAjaxPagination' => $ajaxPagination?:false,				
				'itemView' => 'response/_response.php',
				'pagerCssClass' => 'grid_list_pager'
		));	 ?>
</div>