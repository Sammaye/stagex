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

<div style='background:#eeeeee;'>
	<div class='edit_ribbon_menu grid-container'>
		<div class='edit_menu' style='padding:10px;'>
			<div class='alert'></div>
			<input type="button" class="btn btn-primary save_video" value="Save Changes"/>
			<div class="btn-group">
			<button type="button" id="settings_tab" class="btn btn-white btn-tab">Settings</button>
			</div>
			<button type="button" class='delete_video btn btn-error'>Delete</button>
		</div>
		<div class="edit_panes" style='display:none;'>
			<?php $form = html::activeForm(array('action' => '')) ?>
				<div class='edit_settings pane' id="settings_content">
				<div class="left">
					<div class="form-group"><?php echo html::label('Title', 'title') ?><?php echo html::activeTextField($playlist, 'title',array('class'=>'form-control')) ?></div>
					<div class="form-group"><?php echo html::label('Description', 'description')?><?php echo html::activeTextarea($playlist, 'description',array('class'=>'form-control')) ?></div>			
				</div>
				<div class='right'>
					<h4>Listing</h4>
					<?php $grp = html::activeRadio_group($playlist, 'listing') ?>
					<div class="label_options">
						<label class="radio"><?php echo $grp->add(0) ?>Listed</label>
						<p class='text-muted'>Your video is public to all users of StageX</p>
						<label class="radio"><?php echo $grp->add(1) ?>Unlisted</label>
						<p class='text-muted'>Your video is hidden from listings but can still be accessed directly using the video URL</p>
						<label class="radio"><?php echo $grp->add(2) ?>Private</label>
						<p class='text-muted'>No one but you can access this video</p>
					</div>
					<label class="checkbox"><?php echo html::activeCheckbox($playlist, 'allowFollowers',1)?>Allow people to follow this playlist</label>
				</div>
				<div class="clear"></div>						
				</div>
			<?php $form->end(); ?>
		</div>
	</div>
</div>

<div class="grid-container" style='margin:40px auto;'>
	<div class="left_menu">
		<?php if(glue::auth()->check(array('@'))) 
			app\widgets\UserMenu::widget(array('tab'=>'playlists')); 
		else
			echo '&nbsp;';
		?>
	</div>
	<div style='float:left;width:820px;'>
	
	<h1><?php echo $playlist->title ?></h1>
	
	<div style='margin:10px 0 20px 0;'>
		<a rel='new_window' href="http://www.facebook.com/sharer.php?u=<?php echo urlencode(glue::http()->url("/playlist/view", array("id"=>$playlist->_id))) ?>"><img alt='fb' src="/images/fb_large.png"/></a>
		<a rel='new_window' href="http://twitter.com/share?url=<?php echo urlencode(glue::http()->url("/playlist/view", array("id"=>$playlist->_id))) ?>"><img alt='twt' src="/images/twt_large.png"/></a>
		<g:plusone size="medium" annotation="inline" href="<?php echo glue::http()->url('/playlist/view', array('id' => $playlist->_id)) ?>"></g:plusone>
	</div>	
	
	<p><?php echo $playlist->descrption ?></p>
	
	<?php ob_start(); ?>
		<div class='stickytoolbar-placeholder grey_sticky_toolbar'>
			<div class='stickytoolbar-bar'>
				<div class='inner_bar'>
					<div class='checkbox_button checkbox_input'><?php echo Html::checkbox('selectAll', 1, 0, array('class' => 'selectAll_input')) ?></div>
					<button class='btn btn-error btn_delete_videos'>Delete</button>
				</div>
				<div class="alert block-alert"></div>
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
</div>	
</div>