<?php
glue::$controller->js('dfgfdgfdgv', "
	$(function(){
		$('.expandable').expander({slicePoint: 200});
	});

	$(document).on('click', '.streamitem .close_button', function(event){
		event.preventDefault();
		var el = $(this).parents('.streamitem');

		$.post('/stream/delete', {ids: [$(this).parents('.streamitem').data('id')]}, function(data){
			if(data.success&&data.updated>0)
				el.remove();
		}, 'json');
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
	<div class="simple-nav user_profile_main_nav">
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
			<div class='no_results_found'>No stream has yet been recorded</div>
		<?php } ?>
	</div>
	<?php if($cursor->count() > 20){ ?>
		<a class='load_more' href='#'>Load more stream</a>
	<?php } ?>
</div>