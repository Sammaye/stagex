<?php

use glue\Html;

$this->JsFile("/js/jquery.expander.js");
$this->JsFile('/js/jdropdown.js');
$this->JsFile('/js/playlist_dropdown.js');

$this->js('videos', "
	$(function(){
		$('.expandable').expander();
		
		$('.dropdown-group').jdropdown();
		$('.playlist-dropdown').playlist_dropdown();		
		
		$('.mass_edit_form .alert').summarise();
		$('.grey_sticky_toolbar .block-alert').summarise()

		$('.selectAll_input').click(function(){
			if($(this).prop('checked')==true){
				$('.video_list input:checkbox').prop('checked', false).trigger('click');
			}else{
				$('.video_list input:checkbox').prop('checked', true).trigger('click');
			}
		});

		$(document).on('click', '.grey_sticky_toolbar .btn_delete', function(){
			params={'ids[]':[]};
			$('.video_list .video .checkbox_col input:checked').each(function(i,item){
				params['ids[]'][params['ids[]'].length]=$(item).val();
			});

			$.post('/video/delete', params, null, 'json').done(function(data){
				if(data.success){
					$('.grey_sticky_toolbar .block-alert').summarise('set', 'success','The videos you selected were deleted');
					$.each(params['ids[]'],function(i,item){
						$('.video_list .video[data-id='+item+']').children().not('.deleted').css({display:'none'});
						$('.video_list .video[data-id='+item+'] .deleted').css({display:'block'});
					});
					reset_checkboxes();
				}else{
					$('.grey_sticky_toolbar .block-alert').summarise('set', 'error','The videos you selected could not be deleted');
				}
			}, 'json');			
		});
		
		$(document).on('click', '.video .encoding_failed .btn', function(e){
			videoEl=$(this).parents('.video');
			$.post('/video/delete', {'ids[]':[videoEl.data().id]}, null, 'json').done(function(data){
				if(data.success){
					videoEl.remove();
				}else{
					$('.grey_sticky_toolbar .block-alert').summarise('set', 'error','The video you selected could not be deleted');
				}
			}, 'json');	
		});
		
		$(document).on('click', '.video .deleted .undo', function(e){
			e.preventDefault();
		
			elData=$(this).parents('.video').data();
			$.post('/video/undoDelete',{id:elData.id},null,'json').done(function(data){
				if(data.success){
					$('.video_list .video[data-id='+elData.id+']').children().not('.deleted').css({display:'block'});
					$('.video_list .video[data-id='+elData.id+'] .deleted').css({display:'none'});					
				}else{
					$(this).parents('.deleted').html('This video could not be recovered. Most likely because it has been processed and is deleted.');
				}
			});
		});
	});
		
	function reset_checkboxes(){
		$('.selectAll_input').prop('checked',true).trigger('click');
	}
");
?>
<div class="user_videos_body">

	<div class="header">
    		<div class='search form-search'>
			<?php $form = Html::form(array('method' => 'get')); ?>
				<div class="form-group"><?php echo app\widgets\Jqautocomplete::run(array(
					'attribute' => 'query',
					'value' => urldecode(htmlspecialchars(isset($_GET['query']) ? $_GET['query'] : '')),
					'placeholder' => 'Search Uploads',
					'htmlOptions' => array(
						'class' => 'form-search-input form-control'
					),
					'options' => array(
						'appendTo' => '#user_video_results',
						'source' => '/user/video_search_suggestions',
						'minLength' => 2,
					),
					'renderItem' => "
						return $( '<li></li>' )
							.data( 'item.autocomplete', item )
							.append( '<a class=\'content\'><span>' + item.label + '</span></div></a>' )
							.appendTo( ul );
				"))  ?></div><button class="btn btn-default submit_search">Search</button>
				<span class='text-muted small amount_found'><?php echo $video_rows->count() ?> found</span>
			<?php $form->end() ?>
			</div>    	
    	<div class="clear"></div>
    </div>

	<?php ob_start(); ?>
		<div class='stickytoolbar-placeholder grey_sticky_toolbar'>
			<div class='stickytoolbar-bar'>
				<div class='inner_bar'>
					<div class='checkbox_button checkbox_input'><?php echo Html::checkbox('selectAll', 1, 0, array('class' => 'selectAll_input')) ?></div>
					<button class='btn btn-danger selected_actions btn_delete'>Delete</button>
					<div class="dropdown-group playlist-dropdown">
						<button class='btn btn-default add_to_playlist dropdown-anchor'>Add To <span class="caret"></span></button>
						<div class="dropdown-menu">
							<div class="playlists-panel">
								<div class="head_ribbon">
									<a href="#" data-id="<?php echo glue::user()->watchLaterPlaylist()->_id ?>" class='watch_later playlist_link'>Watch Later</a>
									<input type="text" placeholder="Search for Playlists" class="form-control"/>
								</div>
								<div class="playlist_results">
								<div class='item'>
									Search for playlists above
								</div>
								</div>
							</div>
							<div class="message-panel" style='display:none;padding:20px;'>
								<p style='font-size:16px;'></p>
								<a href="#" class="message-back">Back</a> <span class="text-silent">|</span> <a href="#" class="message-close">Close</a>
							</div>
						</div>
					</div>
				</div>
				<div class="alert block-alert"></div>
			</div>
		</div>
		<?php $html = ob_get_contents();
	ob_end_clean();

	echo app\widgets\stickytoolbar::run(array(
		"element" => '.grey_sticky_toolbar',
		"options" => array(
			'onFixedClass' => 'grey_sticky_bar-fixed'
		),
		'html' => $html
	)); ?>

	<div class="video_list">
	<?php if($video_rows->count() > 0){
		echo glue\widgets\ListView::run(array(
			'pageSize'	 => 20,
			'page' 		 => isset($_GET['page']) ? $_GET['page'] : 1,
			"cursor"	 => $video_rows,
			'itemView' 	 => 'video/_video.php',
		));
	}else{ ?>
		<div class='no_results_found'>No videos were found</div>
	<?php } ?>
	</div>
</div>