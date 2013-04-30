<?php
	glue::clientScript()->addJsFile('jquery-expander', "/js/jquery-expander.js");
	glue::clientScript()->addJsFile('playlist_dropdown', '/js/playlist_dropdown.js');
	glue::clientScript()->addJsFile('j-dropdown', '/js/jdropdown.js');

ob_start();
	?>
	<div class='filters_menu'>
		<div class='item' data-url='<?php echo glue::url()->create('/history/rated_videos') ?>'>Lied Videos</div>
		<div class='item' data-url='<?php echo glue::url()->create('/history/rated_videos', array('liked' => 0)) ?>'>Disliked Videos</div>
	</div>
	<?php
	$menu_html = ob_get_contents();
ob_end_clean();

	glue::clientScript()->addJsScript('liked_page', "
		$(function(){
			$.playlist_dropdown({ multi_seek_parent: true });

			$(document).on('click', '.selectAll_input', function(event){
				if($(this).attr('checked')){
					$('.video_list input:checkbox').attr('checked', true);
				}else{
					$('.video_list input:checkbox').attr('checked', false);
				}
			});

			$(document).on('click', '.delete', function(event){
				event.preventDefault();
				//console.log('d', {videos: $('.video_list input:checked').serializeArray()});

				var ar = $('.video_list input:checked').serializeArray(),
					ret = [];

				for(var i =0; i < ar.length; i++){
					ret[ret.length] = ar[i].name;
				}

				$.post('/history/remove_ratings', {items: ret, type: 'video'}, function(data){
					if(data.success){
						$('.video_list input:checked').parents('.object_item').remove();
					}
				}, 'json');
			});

//			$(document).on('click', '.clear_all', function(event){
//				event.preventDefault();
//
//				$.getJSON('/history/remove_all', {type: 'video'}, function(data){
//					if(data.success){
//						$('.item_list').html(data.html);
//					}
//				});
//			});

			$(document).on('click', '.load_more', function(event){
				event.preventDefault();
				var last_ts = $('.video_list .video_item').last().data('ts');

				$.getJSON('/history/get_rated_history', {ts: last_ts, type: 'video', filter: 'liked' }, function(data){
					if(data.success){
						$('.video_list').append(data.html);
						$('div.expandable').expander({slicePoint: 60});
					}else{
						if(data.noneleft){
							$('.load_more').html(data.messages[0]);
						}
					}
				});
			});
		});
	");

	glue::clientScript()->addJsScript('liked_page.base', "
		$(function(){
			$('div.expandable').expander({slicePoint: 60});
		});

		$('body').append($(".GClientScript::encode($menu_html)."));
		$('.selected_filter').jdropdown({
			'orientation': 'over',
			'menu_div': '.filters_menu',
			'item': '.filters_menu .item'
		});

	    $(document).on('jdropdown.selectItem', '.filters_menu .item', function(e, event){
	        //event.preventDefault();
			window.location = $(this).data('url');
	    });
	");
?>
<div class="user_history_body">
	<div class='head_outer'>
		<div class='head'>Videos You've Liked</div>
	</div>

	<?php ob_start(); ?>
		<div class='stickytoolbar-placeholder grey_sticky_bar'>
			<div class='stickytoolbar-bar'>
				<div class='inner_bar'>
					<div class='checkbox_input'><?php echo html::checkbox('selectAll', 1, 0, array('class' => 'selectAll_input')) ?></div>
					<div class='grey_css_button add_to_playlist left_button'>Add To</div>
					<div class='grey_css_button delete float_left'>Remove</div>
					<!-- <div class='grey_css_button clear_all' style='float:right;'></div> -->
				</div>
			</div>
		</div>
	<?php $html = ob_get_contents();
		ob_end_clean();
		$this->widget('application/widgets/stickytoolbar.php', array(
			"element" => '.grey_sticky_bar',
			"options" => array(
				'onFixedClass' => 'grey_sticky_bar-fixed'
			),
			'html' => $html
		));
	?>

	<div class='video_list'>
		<?php
		//var_dump($items->count());
		if($items->count() > 0){
			foreach($items as $k => $item){
				$item = (Object)$item;
				$related_o = Video::model()->findOne(array('_id' => $item->item));

				if(!$related_o instanceof Video)
					$related_o = new Video;

				$this->partialRender('videos/_video_ext', array('model' => $related_o, 'custid' => $item->_id, 'item' => $item, 'show_checkbox' => true, 'extra_classes' => 'object_item'));
			}
		}else{ ?>
			<div class='no_history'>
				No history has been recorded
			</div>
		<?php } ?>
	</div>
	<?php if($items->count() > 20){ ?>
		<a class='load_more' href='#'>Load more history</a>
	<?php } ?>
</div>