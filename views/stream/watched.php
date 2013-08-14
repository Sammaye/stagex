<?php

use glue\Html;

$this->jsFile("/js/jquery.expander.js");

$this->js('watched_page', "
	//$.playlist_dropdown({ multi_seek_parent: true });
	$(function(){
		$('div.expandable').expander({slicePoint: 120});
	});		

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
		$.post('/history/remove_watched', {items: ret}, function(data){
			if(data.success){
				$('.video_list input:checked').parents('.video_item').remove();
			}
		}, 'json');
	});

	$(document).on('click', '.clear_all', function(event){
		event.preventDefault();
		$.getJSON('/history/remove_all_watched', function(data){
			if(data.success){
				$('.video_list').html(data.html);
			}
		});
	});

	$(document).on('click', '.load_more', function(event){
		event.preventDefault();
		var last_ts = $('.video_list .video_item').last().data('ts');
		$.getJSON('/history/get_watched_history', {ts: last_ts, filter: 'watched' }, function(data){
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
");
?>
<div class="user_history_body">
	<div class="tabs-nav">
		<ul>
			<li><a href="/user/videos">Uploads</a></li>
			<li><a href="/history/watched" class="selected">Watched</a></li>
			<li><a href="/history/ratedVideos">Liked</a></li>
			<li><a href="/history/ratedVideos?filter=dislikes">Disliked</a></li>
			<a style='float:right;' class="btn-success" href="<?php echo glue::http()->url('/video/upload', array(), glue::$params['uploadBase']) ?>">Add New Upload</a>
		</ul>
	</div>

	<?php ob_start(); ?>
		<div class='stickytoolbar-placeholder grey_sticky_toolbar'>
			<div class='stickytoolbar-bar'>
				<div class='inner_bar'>
					<div class='checkbox_button checkbox_input'><?php echo Html::checkbox('selectAll', 1, 0, array('class' => 'selectAll_input')) ?></div>
					<button class='btn-grey selected_actions btn_delete'>Delete</button>
					<div class="btn-group dropdown-group playlist-dropdown">
						<button class='btn-grey add_to_playlist dropdown-anchor'>Add To <span class="caret">&#9660;</span></button>
						<div class="dropdown-menu">
							<div class="head_ribbon">
								<a href="#" data-id="<?php echo glue::user()->watchLaterPlaylist()->_id ?>" class='watch_later playlist_link'>Watch Later</a>
								<input type="text" placeholder="Search for Playlists" class="search_input"/>
							</div>
							<div class="playlist_results">
							<div class='item'>
								Search for playlists above
							</div>
							</div>
						</div>
					</div>
				</div>
				<div class="alert block-alert" style='display:none;'></div>
			</div>
		</div>	
	<?php $html = ob_get_contents();
	ob_end_clean();
	app\widgets\stickytoolbar::widget(array(
		"element" => '.grey_sticky_toolbar',
		"options" => array(
			'onFixedClass' => 'grey_sticky_bar-fixed'
		),
		'html' => $html
	)); ?>

	<div class='video_list'>
		<?php
		if($items->count() > 0){
			foreach($items as $k => $item){
				$item = (Object)$item;
				$video = app\models\Video::model()->findOne(array('_id' => $item->item));
				if($video instanceof app\models\Video){
					echo $this->renderPartial('video/_video_row', array('item' => $video, 'custid' => $item->_id, 'model' => $item, 'show_checkbox' => true));
				}
			}
		}else{ ?>
			<div class='no_results_found'>No watched history has been recorded</div>
		<?php } ?>
	</div>
	<?php if($items->count() > 20){ ?>
		<a class='load_more' href='#'>Load more history</a>
	<?php } ?>
</div>