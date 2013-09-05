<?php

$this->jsFile("/js/jquery.expander.js");
$this->jsFile('/js/jdropdown.js');

$this->js('streampage.base', "
	$(function(){
		
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

		$(document).on('click', '.streamitem .close_button', function(event){
			event.preventDefault();
			var el = $(this).parents('.streamitem');

			$.post('/stream/delete', {ids: [$(this).parents('.streamitem').data('id')]}, function(data){
				if(data.success&&data.updated>0)
					el.remove();
			}, 'json');
		});
	});
		
	$(document).on('click', '.load_more', function(event){
		event.preventDefault();
		var last_ts = $('.list .streamitem').last().data('ts'),
			filter = $('.list').data('sort');
		$.getJSON('/stream/getStream', {ts: last_ts, filter: filter }, function(data){
			if(data.success)
				$('.list').append(data.html);
			else if(data.noneleft)
				$('.load_more').html(data.message);
		});
	});

	$(document).on('click', '.simple-nav a', function(event){
	    event.preventDefault();
		$('.simple-nav a').not($(this)).removeClass('selected');
		$(this).addClass('selected');

		$.getJSON('/stream/getStream', { filter: $(this).data('filter') }, function(data){
			if(data.success){
				$('.list').html(data.html);
				$('.load_more').html('Load more stream');
			}else if(data.remaining<=0){
				$('.list').html($('<div/>').addClass('no_results_found').text(data.initMessage));
				$('.load_more').html(data.message);
			}
		});
	});		
");
?>

<div>
	<div class="simple-nav" style='background:#f5f5f5;'>
		<a href="#" class="selected" data-filter="all">All</a>
		<a href="#" data-filter="posts">Posts</a>
		<a href="#" data-filter="comments">Comments</a>
		<a href="#" data-filter="liked">Likes</a>
		<a href="#" data-filter="watched">Watched</a>
	</div>
	<div class='list' style=''>
		<?php
		if($cursor->count() > 0){
			foreach($cursor as $k => $item){
				echo $this->renderPartial('stream/streamitem', array('item' => $item));
			}
		}else{ ?>
			<div class='no_results_found'>No stream has yet been recorded for your user</div>
		<?php } ?>
	</div>
	<?php if($cursor->count() > 20){ ?>
		<a class='load_more' href='#'>Load more stream</a>
	<?php } ?>
</div>