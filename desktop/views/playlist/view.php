<?php
$this->JsFile("/js/jquery.expander.js");
$this->jsFile('/js/subscribeButton.js');
$this->js('edit', "

	$('.alert').summarise();
	$('.expandable').expander({slicePoint:40});
		
	$('.subscribe_widget').subscribeButton();
		
	$(document).on('click', '.edit_menu .btn-tab', function(event){
		
		tabClass=$(this).attr('id').replace(/_tab/,'_content');
		pane=$('.edit_panes #'+tabClass);
		
		if(pane.length>0){
			if(pane.css('display') == 'none'){
				$('.edit_panes .pane').not(pane).css({ 'display': 'none' });
				$('.edit_menu .btn-tab').not($(this)).removeClass('active');
				pane.css({ 'display': 'block' });
				$(this).addClass('active');
			}else{
				pane.css({ 'display': 'none' });
				$('.edit_menu .btn-tab').removeClass('active');
			}
		}
	});	
		
	$(document).on('click', '.btn_save', function(event){
		event.preventDefault();
		
		var fields = [];
		fields = $('.edit_settings input,.edit_settings textarea,.edit_settings input').serializeArray();
		fields[fields.length] = {name: 'id', value: '".strval($model->_id)."'};
		$('.sortable_list').find('.video_row').each(function(i){
			deleted=$(this).data('deleted');
			if(deleted===false||deleted===undefined){
				fields[fields.length] = {name: 'videos['+i+'][video_id]', value: $(this).data('id')};
				fields[fields.length] = {name: 'videos['+i+'][position]', value: i};
			}
		});

		$.post('/playlist/save', fields, function(data){
			if(data.success){
				$('.edit_menu .alert').summarise('set','success', 'The changes you made were successfully saved');		
			}else
				$('.edit_menu .alert').summarise('set','error',{message:data.message,list:data.messages});
		}, 'json');
	});

	$(document).on('click', '.btn_delete', function(event){
		event.preventDefault();

		$.post('/playlist/delete', {id: '".strval($model->_id)."'}, function(data){
			if(data.success){
				window.location = '/user/playlists';
			}else
				$('.edit_menu .alert').summarise('set','error',{message:'The playlist could not be deleted because:',list:data.messages});
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
		
	$(document).on('click', '.subscribe_to_playlist .btn_subscribe', function(){
		
		var btn=$(this),
			container=$(this).parents('.subscribe_to_playlist');
		
		$.post('".glue::http()->url('/playlist/subscribe')."', {id:container.data('id')}, null, 'json')
		.done(function(data){
			if(data.success){
				btn.removeClass('btn-success btn_subscribe').addClass('btn-danger btn_unsubscribe').html('Unsubscribe');
			}
		});
	});
		
	$(document).on('click', '.subscribe_to_playlist .btn_unsubscribe', function(){
		
		var btn=$(this),
			container=$(this).parents('.subscribe_to_playlist');
		
		$.post('".glue::http()->url('/playlist/unsubscribe')."', {id:container.data('id')}, null, 'json')
		.done(function(data){
			if(data.success){
				btn.removeClass('btn-danger btn_unsubscribe').addClass('btn-success btn_subscribe').html('Subscribe to Playlist');
			}
		});
	});		
");
?>
<div class='edit_playlist_body'>
<?php if(glue::auth()->check(array('^'=>$model))){ ?>
<div class="top_grey_bar">
	<div class='edit_ribbon_menu grid-container'>
		<div class='edit_menu'>
			<div class='alert'></div>
			<input type="button" class="btn btn-primary btn_save" value="Save Changes"/>
			<div class="btn-group">
			<button type="button" id="settings_tab" class="btn btn-white btn-tab">Settings</button>
			</div>
			<button type="button" class='btn btn-danger btn_delete'>Delete</button>
			
			<span class="edit_menu_text"><?php echo $model->followers.($model->followers===1?' follower':' followers') ?></span>
		</div>
		<div class="edit_panes">
			<?php $form = html::activeForm(array('action' => '')) ?>
				<div class='edit_settings pane' id="settings_content">
				<div class="left">
					<div class="form-group"><?php echo html::label('Title', 'title') ?><?php echo html::activeTextField($model, 'title',array('class'=>'form-control')) ?></div>
					<div class="form-group"><?php echo html::label('Description', 'description')?><?php echo html::activeTextarea($model, 'description',array('class'=>'form-control')) ?></div>			
				</div>
				<div class='right'>
					<h4>Listing</h4>
					<?php $grp = html::activeRadio_group($model, 'listing') ?>
					<div class="label_options">
						<label class="radio"><?php echo $grp->add(0) ?>Listed</label>
						<p class='text-muted'>Your playlist is public to all users of StageX</p>
						<label class="radio"><?php echo $grp->add(1) ?>Unlisted</label>
						<p class='text-muted'>Your playlist is hidden from listings but can still be accessed directly using the video URL</p>
						<label class="radio"><?php echo $grp->add(2) ?>Private</label>
						<p class='text-muted'>No one but you can access this playlist</p>
					</div>
					<label class="checkbox"><?php echo html::activeCheckbox($model, 'allowFollowers',1)?>Allow people to follow this playlist</label>
				</div>
				<div class="clear"></div>						
				</div>
			<?php $form->end(); ?>
		</div>
	</div>
</div>
<?php }else{ ?>
<div class="author_top_bar">
<div class="grid-container">
	<div class="user_image">
	<img alt='thumbnail' class="thumbnail" src="<?php echo $model->author->getAvatar(30, 30); ?>"/>
	</div>
	<div class="user_text">
	<a href="<?php echo glue::http()->url('/user/view', array('id' => $model->author->_id)) ?>" class="h3"><?php echo $model->author->getUsername() ?></a><span class="sep h3">/</span><a href="<?php echo glue::http()->url('/user/viewPlaylists', array('id' => $model->author->_id)) ?>" class="h3">Playlists</a>
	</div>
	<div class='right'>
	<div class="subscribe_widget" data-user_id="<?php echo $model->author->_id ?>">
		<span class="follower_count text-muted"><?php echo $model->author->totalFollowers ?> Subscribers</span>
		<?php if(glue::session()->authed){ ?>
		<?php if(app\models\Follower::isSubscribed($model->author->_id)){ ?>
		<button type="button" class='unsubscribe button btn btn-danger'>Unsubscribe</button>
		<?php }else{ ?>
		<button type="button" class='subscribe btn btn-primary button'>Subscribe</button>
		<?php } ?>
		<?php } ?>
	</div>
	</div>
</div>
</div>
<?php } ?>

<div class="grid-container main_playlist_body">
	<?php echo app\widgets\UserMenu::run(array('tab'=>'playlists')); ?> 
	<div class='grid-col-41'>
	
	<h1><?php echo $model->title ?></h1>
	
	<div class="clearfix">
	<?php if(!glue::auth()->check(array('^'=>$model))&&glue::auth()->check(array('@'))){ ?>
	<div class="clearfix subscribe_to_playlist" data-id="<?php echo $model->_id ?>">
		<?php if(!$model->user_is_subscribed(glue::user())){ ?>
		<button class="btn btn-success btn_subscribe" type="button">Subscribe to Playlist</button>
		<?php }else{ ?>
		<button class="btn btn-danger btn_unsubscribe" type="button">Unsubscribe</button>
		<?php } ?>
		<span class="subscriber_count"><?php echo $model->followers ?></span>
	</div>
	<?php } ?>
	<div class="share_area col-25 clearfix">
		<label>Share</label>
		<a rel='new_window' class="share_network_btn fb_btn" href="http://www.facebook.com/sharer.php?u=<?php echo urlencode(glue::http()->url("/playlist/view", array("id"=>$model->_id))) ?>"></a>
		<a rel='new_window' class="share_network_btn twt_btn" href="http://twitter.com/share?url=<?php echo urlencode(glue::http()->url("/playlist/view", array("id"=>$model->_id))) ?>"></a>
		<a rel="new_window" class="share_network_btn google_btn" href="https://plus.google.com/u/0/share?url=<?php echo urlencode(glue::http()->url("/playlist/view", array("id"=>$model->_id))) ?>"></a>
		<input type="text" value="<?php echo glue::http()->url('/playlist/view', array('id' => $model->_id)) ?>" class="form-control select_all_onfoc col-25"/>
	</div>
	</div>	
	
	<p class="expandable playlist_description"><?php echo nl2br($model->description) ?></p>
	
	<?php if(glue::auth()->check(array('^'=>$model))){ ?>
	<?php ob_start(); ?>
		<div class='stickytoolbar-placeholder grey_sticky_toolbar'>
			<div class='stickytoolbar-bar'>
				<div class='inner_bar'>
					<div class='checkbox_button checkbox_input'><?php echo html::checkbox('selectAll', 1, 0, array('class' => 'selectAll_input')) ?></div>
					<button class='btn btn-danger btn_delete_videos'>Delete</button>
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
	<?php } ?>
	<div class='video_list'>
		<?php
		$videos = $model->get_sorted_videos();
		if(count($videos) > 0){ ?>
			<ul class='sortable_list playlist_video_list'>
			<?php foreach($videos as $k => $video){
				if(glue::auth()->check(array('^'=>$model))){
					echo $this->renderPartial('video/_video_row',array('item'=>$video,'show_sorter'=>true,'show_delete'=>false,'playlistId'=>$model->_id,'useLiTag'=>true, 'admin'=>true));
				}else{
					echo $this->renderPartial('video/_video_row',array('item'=>$video,'show_sorter'=>false,'show_delete'=>false,'playlistId'=>$model->_id,'useLiTag'=>true));
				}
			} ?>
			</ul>
		<?php }else{ ?>
			<div class='no_results_found'>No videos exist here</div>
		<?php } ?>
	</div>
	</div>
</div>	
</div>