<?php

$this->jsFile('jquery-expander', "/js/jquery-expander.js");
$this->jsFile('j-dropdown', '/js/jdropdown.js');

$this->js('streampage.base', "
	$(function(){

		$('.dropdown-group').jdropdown();
		
		$('.expandable').expander({slicePoint: 200});

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

<div>

	<div class="tabs-nav">
		<ul>
			<li><a href="/stream/news" id="news_tab">News</a></li>
			<li><a href="/stream" class="selected">Activity</a></li>
		</ul>
	</div>

	<div class='' style='padding:20px;'>
		<!-- <div class='clear_all grey_css_button float_right button'><span>Clear All Stream</span></div> -->
		
		<div class="btn-group dropdown-group">
			<button class='btn-grey dropdown-anchor'>All Activity <span class="caret">&#9660;</span></button>
			<div class="dropdown-menu">
				<a href="">All Activity</a>
				<a href="">Actions Only</a>
				<a href="">Comments Only</a>
			</div>		
		</div>
	</div>
	<div class='list' style=''>
		<?php
		//var_dump($model->count());
		if($model->count() > 0){
			foreach($model as $k => $item){
				//var_dump($k);
				echo $this->renderPartial('stream/streamitem', array('item' => $item));
			}
		}else{ ?>
			<div class='no_results_found'>No stream has yet been recorded for your user</div>
		<?php } ?>
	</div>
	<?php if($model->count() > 20){ ?>
		<a class='load_more' href='#'>Load more stream</a>
	<?php } ?>
</div>
<div style='float:left; width:160px; margin-left:25px;'>
	<?php //$this->widget("application/widgets/Advertising/Ad_box.php", array( "configuration"=>'300_box' )); ?>
	<div style='margin-top:25px;'>
		<?php //$this->widget("application/widgets/Advertising/Ad_box.php", array( "configuration"=>'300_box' )); ?>
	</div>
</div>