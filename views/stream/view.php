<?php

glue::clientScript()->addJsFile('jquery-expander', "/js/jquery-expander.js");
glue::clientScript()->addJsFile('j-dropdown', '/js/jdropdown.js');

ob_start();
	?>
		<div class='filters_menu'>
			<div class='item' data-caption='Showing All Activity' data-filter='all'>All Activity</div>
			<div class='item' data-caption='Showing Actions Activity' data-filter='actions'>Actions Only</div>
			<div class='item' data-caption='Showing Comment Activity' data-filter='comments'>Comments Only</div>
		</div>
	<?php
	$menu_html = ob_get_contents();
ob_end_clean();

ob_start(); ?>
	<h2 class='diag_header'>Reply</h2>
		<div class='form reply_form'>
			<div class='row'><?php echo html::hiddenfield('user_id').html::textarea('message', null) ?></div>
			<a href='#' class='green_css_button add_reply float_left'>Reply</a> <a href='#' class='grey_css_button cancel'>Cancel</a>
		</div><?php
	$reply_diag_html = ob_get_contents();
ob_end_clean();

glue::clientScript()->addJsScript('streampage.base', "
	$(function(){

		var hash_action = getActionHash();
		if(hash_action[0] == 'wall_post_reply'){
			$.facebox(".GClientScript::encode($reply_diag_html).", 'add_wall_post_diag');
			$('.add_wall_post_diag input[name=user_id]').val(hash_action[1]);
			window.location.hash = '';
		}

		$('.expandable').expander({slicePoint: 200});

		$(document).on('click', '.stream_comment_reply a', function(event){
			event.preventDefault();
			$.facebox(".GClientScript::encode($reply_diag_html).", 'add_wall_post_diag');
			$('.add_wall_post_diag input[name=user_id]').val($(this).parents('.streamitem').data('target_user'));
		});

		$(document).on('click', '.add_wall_post_diag .cancel', function(e){
			e.preventDefault();
			$('.add_wall_post_diag textarea').val('');
			$.facebox.close();
		});

		$(document).on('click', '.add_wall_post_diag .add_reply', function(e){
			e.preventDefault();
			$.post('/stream/add_comment', {text: $('.add_wall_post_diag textarea').val(), user_id: $('.add_wall_post_diag input[name=user_id]').val()}, function(data){
				if(data.success){
					$('.add_wall_post_diag textarea').val('');
					$('.add_wall_post_diag input[name=user_id]').val('');
					$.facebox.close();
					$('.list').prepend($(data.html));
				}
			}, 'json');
		});

		$(document).on('click focus', '.profile_comment_textarea', function(event){
			if($('.profile_comment_textarea').hasClass('profile_comment_textarea_unchanged')){
				$('.profile_comment_textarea').removeClass('profile_comment_textarea_unchanged').val('');
			}
		});

		$(document).on('click', '.delete_item a', function(event){
			event.preventDefault();
			var el = $(this).parents('.streamitem');

			$.post('/stream/deleteitems', {items: [$(this).parents('.streamitem').data('id')]}, function(data){
				if(data.success){
					el.remove();
				}
			}, 'json');
		});

		$(document).on('click', '.clear_all', function(event){
			event.preventDefault();
			$.getJSON('/stream/clearall', function(data){
				if(data.success){
					$('.list').html(data.html);
				}
			});
		});

		$(document).on('click', '.load_more', function(event){
			event.preventDefault();
			var last_ts = $('.list .streamitem').last().data('ts'),
				filter = $('.list').data('sort');
			$.getJSON('/stream/get_stream', {ts: last_ts, filter: filter }, function(data){
				if(data.success){
					$('.list').append(data.html);
				}else{
					if(data.noneleft){
						$('.load_more').html(data.messages[0]);
					}
				}
			});
		});

		$('body').append($(".GClientScript::encode($menu_html)."));
		$('.selected_filter').jdropdown({
			'orientation': 'over',
			'menu_div': '.filters_menu',
			'item': '.filters_menu .item'
		});

	    $(document).on('jdropdown.selectItem', '.filters_menu .item', function(e, event){
	        //event.preventDefault();
			$('.selected_filter').html($(this).data('caption'));
			$('.list').data('sort', $(this).data('filter'));

			$.getJSON('/stream/get_stream', { filter: $(this).data('filter') }, function(data){
				if(data.success){
					$('.list').html(data.html);
					$('.load_more').html('Load more stream');
				}else{
					if(data.noneleft){
						$('.list').html('<div style=\'font-size:16px; font-weight:normal; padding:21px;\'>'+data.initMessage+'</div>');
						$('.load_more').html(data.messages[0]);
					}
				}
			});
	    });
	});
");

?>

<div class='grid_5 alpha omega boxed_page_layout_outer float_left' style='width:574px;'>
	<div class='head_outer' style=''><div class='page_head'>Stream</div>
		<!-- <div class='clear_all grey_css_button float_right button'><span>Clear All Stream</span></div> -->
		<div class='grey_css_button selected_filter float_right button_margined'>Showing All Activity</div>
	</div>
	<div class='list' style=''>
		<?php
		//var_dump($model->count());
		if($model->count() > 0){
			foreach($model as $k => $item){
				//var_dump($k);
				$this->partialRender('stream/streamitem', array('item' => $item));
			}
		}else{ ?>
			<div class='not_found_head'>No stream has yet been recorded for your user</div>
		<?php } ?>
	</div>
	<?php if($model->count() > 20){ ?>
		<a class='load_more' href='#'>Load more stream</a>
	<?php } ?>
</div>
<div style='float:left; width:160px; margin-left:25px;'>
	<?php $this->widget("application/widgets/Advertising/Ad_box.php", array( "configuration"=>'300_box' )); ?>
	<div style='margin-top:25px;'>
		<?php $this->widget("application/widgets/Advertising/Ad_box.php", array( "configuration"=>'300_box' )); ?>
	</div>
</div>