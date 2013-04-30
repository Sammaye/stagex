<?php

	if(!$model) $model = new Playlist;

	glue::clientScript()->addJsFile('jquery-expander', "/js/jquery-expander.js");
	glue::clientScript()->addJsFile('playlist_dropdown', '/js/playlist_dropdown.js');

	glue::clientScript()->addJsScript('watch_later', "
		$(function(){
			$.playlist_dropdown();
			$('div.expandable').expander({slicePoint: 60});

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

				$.post('/playlist/delete_many_videos', {items: ret, id: '".$model->_id."'}, function(data){
					if(data.success){
						$('.video_list input:checked').parents('.video_item').remove();
					}else{
						forms.summary($('.grey_sticky_bar .block_summary'), false, data.messages[0]);
					}
				}, 'json');
			});

			$(document).on('click', '.clear_all', function(event){
				event.preventDefault();
				$.getJSON('/playlist/clear_all_videos', {id: '".$model->_id."'}, function(data){
					if(data.success){
						$('.video_list').html(data.html);
					}
				});
			});
		});
	");
?>
<div class="boxed_page_layout_outer">

	<div class='borderless_head'><div class='head'>Videos Queued for Later</div></div>
	<?php ob_start(); ?>
		<div class='stickytoolbar-placeholder grey_sticky_bar'>
			<div class='stickytoolbar-bar'>
				<div class='block_summary'></div>

				<div class='inner_bar'>
					<div class='checkbox_input'><?php echo html::checkbox('selectAll', 1, 0, array('class' => 'selectAll_input')) ?></div>
					<div class='grey_css_button add_to_playlist left_button'>Add To</div>
					<div class='grey_css_button delete float_left'>Remove</div>
					<div class='grey_css_button clear_all float_right'>Clear Queue</div>
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
		if(sizeof($model->videos) > 0){
			$_id_array = array(); $videos = array();
			foreach($model->videos as $k => $item){
				$_id_array[] = $item['_id'];
			}
			$videos = Video::model()->find(array('_id' => array('$in' => $_id_array)));

			foreach($videos as $k => $item){
				if($item instanceof Video){
					$this->partialRender('videos/_video_ext', array('model' => $item, 'show_checkbox' => true, 'show_watched_status' => true));
				}
			}
		}else{ ?>
			<div class='padded_list_not_found'>No videos have been queued for later</div>
		<?php } ?>
	</div>
</div>