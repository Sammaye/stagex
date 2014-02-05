<?php
	if(!$model) $model = new app\models\Playlist;
	$this->jsFile("/js/jquery.expander.js");
	$this->JsFile('/js/jdropdown.js');
	$this->jsFile('/js/playlist_dropdown.js');

	$this->js('watch_later', "
		$('.expandable').expander();	
		$('.grey_sticky_toolbar .block-alert').summarise();
			
		$('.dropdown-group').jdropdown();
		$('.playlist-dropdown').playlist_dropdown();				

		$('.selectAll_input').click(function(){
			if($(this).prop('checked')==true){
				$('.video_list input:checkbox').prop('checked', false).trigger('click');
			}else{
				$('.video_list input:checkbox').prop('checked', true).trigger('click');
			}
		});
			
		function reset_checkboxes(){
			$('.selectAll_input').prop('checked',true).trigger('click');
		}			
			
		$(document).on('click', '.grey_sticky_toolbar .btn_delete', function(){
			params={'ids[]':[]};
			$('.video_list .video .checkbox_col input:checked').each(function(i,item){
				params['ids[]'][params['ids[]'].length]=$(item).val();
			});
			params['playlist_id']='".$model->_id."';

			$.post('/playlist/deleteVideo', params, null, 'json').done(function(data){
				if(data.success){
					$('.grey_sticky_toolbar .block-alert').summarise('set', 'success','The videos you selected were deleted');
					$.each(params['ids[]'],function(i,item){
						$('.video_list .video[data-id='+item+']').remove();
					});
					reset_checkboxes();
				}else{
					$('.grey_sticky_toolbar .block-alert').summarise('set', 'error','The videos you selected could not be deleted');
				}
			}, 'json');			
		});			

		$(document).on('click', '.grey_sticky_toolbar .btn_delete_all', function(event){
			event.preventDefault();
			$.getJSON('/playlist/clear', {id: '".$model->_id."'}, function(data){
				if(data.success){
					$('.grey_sticky_toolbar .block-alert').summarise('set', 'success','Your watch later list was cleared');
					$('.video_list').html('');
				}
			});
		});
	");
?>
<div class="boxed_page_layout_outer watch_later_body">

	<?php ob_start(); ?>
	
		<div class='stickytoolbar-placeholder grey_sticky_toolbar'>
			<div class='stickytoolbar-bar'>
				<div class='inner_bar'>
					<div class='checkbox_button checkbox_input'><?php echo html::checkbox('selectAll', 1, 0, array('class' => 'selectAll_input')) ?></div>
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
					<button class='btn btn-danger selected_actions btn_delete'>Remove</button>
					<button class='btn btn-danger selected_actions btn_delete_all'>Clear Queue</button>					
				</div>
				<div class="alert block-alert"></div>
			</div>
		</div>	
	<?php $html = ob_get_contents();
	ob_end_clean();
	echo app\widgets\Stickytoolbar::run(array(
		"element" => '.grey_sticky_bar',
		"options" => array(
			'onFixedClass' => 'grey_sticky_bar-fixed'
		),
		'html' => $html
	));
	?>

	<div class='video_list'>
		<?php
		if(count($model->videos) > 0){
			$_id_array = array(); $videos = array();
			foreach($model->videos as $k => $item){
				$_id_array[] = $item['_id'];
			}
			$videos = app\models\Video::find(array('_id' => array('$in' => $_id_array)));

			foreach($videos as $k => $item){
				if($item instanceof app\models\Video){
					echo $this->renderPartial('video/_video_ext', array('model' => $item, 'show_checkbox' => true, 'show_watched_status' => true));
				}
			}
		}else{ ?>
			<div class='no_results_found'>No videos have been queued for later</div>
		<?php } ?>
	</div>
</div>