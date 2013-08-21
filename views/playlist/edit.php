<?php
$this->JsFile("/js/jquery.expander.js");
$this->js('edit', "

	$('.alert').summarise();
	$('.expandable').expander();
		
	$(document).on('click', '.btn_save', function(event){
		event.preventDefault();
		
		var fields = [];
		fields = $('.form_left input,.form_left textarea,.form_right input').serializeArray();
		fields[fields.length] = {name: 'id', value: '".strval($playlist->_id)."'};
		$('.sortable_list').find('.video_row').each(function(i){
			deleted=$(this).data('deleted');
			if(deleted===false||deleted===undefined){
				fields[fields.length] = {name: 'videos['+i+'][video_id]', value: $(this).data('id')};
				fields[fields.length] = {name: 'videos['+i+'][position]', value: i};
			}
		});

		$.post('/playlist/save', fields, function(data){
			if(data.success){
				window.location = '/user/playlists';
			}else
				$('.alert').summarise('set','error',{message:data.message,list:data.messages});
		}, 'json');
	});

	$(document).on('click', '.btn_delete', function(event){
		event.preventDefault();

		$.post('/playlist/delete', {id: '".strval($playlist->_id)."'}, function(data){
			if(data.success){
				window.location = '/user/playlists';
			}else
				$('.alert').summarise('set','error',{message:'The playlist could not be deleted because:',list:data.messages});
		},'json');
	});
		
	$(document).on('click', '.btn_cancel', function(event){
		event.preventDefault();
		window.location = '/user/playlists';
	});		

	$(document).on('click', '.btn_delete_videos', function(event){
		event.preventDefault();
		$('.video_row .checkbox_col input:checked').each(function(i){
			$(this).parents('.video_row').data('deleted',true).find('.inner').css({display:'none'});
			$(this).parents('.video_row').append(
				$('<div/>').addClass('deleted').html('This video will be deleted').append($('<a/>').addClass('btn_undo').text('Undo'))
			);
		});
		reset_checkboxes();
	});
		
	$(document).on('click', '.btn_undo', function(event){
		$(this).parents('.video_row').data('deleted',false).find('.inner').css({display:'block'});
		$(this).parents('.video_row').find('.deleted').remove();
	});

	$('.selectAll_input').click(function(){
		if($(this).prop('checked')==true){
			$('.video_list input:checkbox').prop('checked', false).trigger('click');
		}else{
			$('.video_list input:checkbox').prop('checked', true).trigger('click');
		}
	});

	$( '.sortable_list' ).sortable({
		placeholder: 'playlist_sortable_highlight',
		handle: '.sortable_handle',
		update: function(event, ui){
			//$('.sortable_list').find('.sortable_item').each(function(i){
				//$(this).find('.position_val').text(i+1);
			//});
		},
	});
	$( '.sortable_list' ).disableSelection();
	function reset_checkboxes(){
		$('.selectAll_input').prop('checked',true).trigger('click');
	}		
");
?>
<div class='edit_playlist_body'>
	<div class='alert'></div>
	<div style='margin:0 0 20px 0;'>
		<input type="submit" class="btn-success btn_save" value="Save"/>
		<input type="button" class="btn btn_cancel" value="Cancel"/>
		<input type="button" class="btn btn_delete" value="Delete"/>
		
		<span style='float:right;display:block;margin:10px 0 0;color:#666666;'><?php echo $playlist->followers ?> Followers</span>
	</div>
	<div class="form-stacked form_left" style='float:left;width:400px;'>
		<div class="form_row"><?php echo html::label('Title', 'title') ?><?php echo html::activeTextField($playlist, 'title') ?></div>
		<div class="form_row"><?php echo html::label('Description', 'description')?><?php echo html::activeTextarea($playlist, 'description') ?></div>			
	</div>
	<div class='form_right' style='float:right;width:400px;'>
		<h4>Listing</h4>
		<?php $grp = html::activeRadio_group($playlist, 'listing') ?>
		<div class="label_options">
		<label class="radio"><?php echo $grp->add(0) ?>Listed</label>
		<p class='light'>Your video is public to all users of StageX</p>
		<label class="radio"><?php echo $grp->add(1) ?>Unlisted</label>
		<p class='light'>Your video is hidden from listings but can still be accessed directly using the video URL</p>
		<label class="radio"><?php echo $grp->add(2) ?>Private</label>
		<p class='light'>No one but you can access this video</p>
		</div>
		<label class="checkbox"><?php echo html::activeCheckbox($playlist, 'allowFollowers',1)?>Allow people to follow this playlist</label>
	</div>	
	<div class='clear'></div>
	<div class='' style='padding:10px;height:30px;'>
		<div class='checkbox_button checkbox_input' style='float:left;margin:8px 20px 0 0;'><?php echo Html::checkbox('selectAll', 1, 0, array('class' => 'selectAll_input')) ?></div>
		<input type="button" value="Delete" class="btn btn_delete_videos" style='float:left;'/>
	</div>
	<div class='video_list'>
		<?php
		$videos = $playlist->get_sorted_videos();
		if(count($videos) > 0){ ?>
			<ul class='sortable_list playlist_video_list'>
			<?php foreach($videos as $k => $video){
				echo $this->renderPartial('video/_video_row',array('item'=>$video,'show_sorter'=>true,'show_delete'=>false,'playlistId'=>$playlist->_id,'useLiTag'=>true)); 
			} ?>
			</ul>
		<?php }else{ ?>
			<div class='no_results_found'>No videos exist here</div>
		<?php } ?>
	</div>
</div>