<?php
glue::clientScript()->addJsFile('playlist_dropdown', '/js/playlist_dropdown.js');

glue::clientScript()->addJsScript('playlist_ops', "
	$(function(){
		$.playlist_dropdown();

		$(document).on('click', '.save_playlist', function(event){
			event.preventDefault();
//console.log(videos);
			var fields = [];
			fields = $('.playlist_form_el').serializeArray();
			fields[fields.length] = {name: 'id', value: '".strval($playlist->_id)."'};
			$('.sortable_list').find('.sortable_item').each(function(i){
				fields[fields.length] = {name: 'videos['+i+'][video_id]', value: $(this).data('id')};
				fields[fields.length] = {name: 'videos['+i+'][position]', value: i};
			});
//console.log(fields);
			$.post('/playlist/save_playlist', fields, function(data){
				if(data.success){
					window.location = '/user/playlists';
				}else{
					$('.form_summary').html(data.html);
				}
			}, 'json');
		});

		$(document).on('click', '.delete_playlist', function(event){
			event.preventDefault();

			$.getJSON('/playlist/delete', {id: '".strval($playlist->_id)."'}, function(data){
				if(data.success){
					window.location = '/user/playlists';
				}else{
					forms.summary($('.form_summary'), false, 'The playlist could not be deleted because:', data.messages);
				}
			});
		});

		$(document).on('click', '.video_item .delete', function(event){
			event.preventDefault();

			//$(this).parents('.video_item').removeClass('sortable_item').addClass('sortable_item_deleted');
			$(this).parents('.video_item').remove();
			$( '.sortable_list' ).data('sortable')._trigger('update');
		});

		$(document).on('click', '.mass_delete', function(event){
			event.preventDefault();

			$('.video_item .select_video:checked').each(function(i){
				$(this).parents('.video_item').remove();
			});
			$( '.sortable_list' ).data('sortable')._trigger('update');
			$('input#select_all').attr('checked', false);
		});

		$(document).on('click', '#select_all', function(event){
			if($(this).attr('checked')){
				$('.video_item .select_video').attr('checked', true);
			}else{
				$('.video_item .select_video').attr('checked', false);
			}
		});

		$( '.sortable_list' ).sortable({
			placeholder: 'playlist_sortable_highlight',
			handle: '.sortable_handle',
			update: function(event, ui){
				$('.sortable_list').find('.sortable_item').each(function(i){
					$(this).find('.position_val').text(i+1);
				});
			},
		});
		$( '.sortable_list' ).disableSelection();
	});
");
?>

<div class='playlist_edit_bar'>
	<div class='playlist_edit_bar_inner'>
		<div class='head'>Edit Playlist</div>
		<a href='#' class='green_css_button save_playlist'>Save</a>
		<a href='<?php echo glue::url()->create('/user/playlists') ?>' class='grey_css_button cancel_edit'>Cancel</a>
		<a href='#' class='grey_css_button delete_playlist'>Delete</a>
	</div>
</div>

<div class='edit_playlist_body container_16'>
	<div class='form_summary'></div>
	<?php $form = html::activeForm(array('class' => 'playlist_form_el')) ?>
	<div class='edit_form_outer'>
		<div class='grid_10 alpha'>
			<div class='playlist_title'>
				<div>Title:</div>
				<?php echo $form->textfield($playlist, 'title') ?>
			</div>

			<div class='playlist_description'>
				<div>Description:</div>
				<?php echo $form->textarea($playlist, 'description') ?>
			</div>
		</div>
		<div class='grid_5 omega playlist_edit_right'>
			<div class='playlist_settings'>
				<h2 class='head'>Listing</h2>
				<?php $rd_grp = $form->radio_group($playlist, 'listing') ?>
				<div class='label_options'>
					<label><?php echo $rd_grp->add(1) ?><span>Listed</span></label>
					<div class='light_caption'><p>Your playlist is public to all users of StageX</p></div>
					<label><?php echo $rd_grp->add(2) ?><span>Unlisted</span></label>
					<div class='light_caption'><p>Your playlist is hidden from listings but can still be accessed directly using the video URL</p></div>
					<label><?php echo $rd_grp->add(3) ?><span>Private</span></label>
					<div class='light_caption'><p>No one but you can access this playlist</p></div>
					<div class='further_opts'>
						<!-- <label style='margin-bottom:5px;'><?php //echo $form->checkbox($playlist, 'allow_embedding', 1) ?><span>Allow others to imbedd this playlist</span></label> -->
						<label><?php echo $form->checkbox($playlist, 'allow_like', 1) ?><span>Allow others to like this playlist</span></label>
					</div>
				</div>
			</div>
		</div>
		<div class='clearer'></div>
	</div>
	<?php $form->end() ?>
	<div class='clearer'></div>

	<div class='playlist_videos_edit_top'>
		<?php echo html::checkbox('select_all', 1, null, array('id' => 'select_all')) ?>
		<a href='#' class='grey_css_button mass_delete'>Delete</a>
	</div>
	<div class='videos_list'>
		<?php
		$videos = $playlist->get_sorted_videos();
		if(sizeof($videos) > 0){ ?>
			<ul class='sortable_list playlist_video_list'>
			<?php foreach($videos as $k => $video){ ?>
				<li class='sortable_item video_item' data-id='<?php echo $video->_id ?>'>
					<div class='checkbox_pane'><?php echo html::checkbox(strval($video->_id), 1, 0, array('class' => 'select_video')) ?></div>
					<div class='checkbox_pane'><img alt='sort' class='sortable_handle' src='/images/sortable_icon.png'/></div>
					<div class='video_thumb_pane video_thumbnail_pane'><a href="/video/watch?id=<?php echo strval($video->_id) ?>" ><img alt='thumbnail' class='video_img' src="<?php echo $video->getImage(124, 69) ?>"/></a><a class='playlist_button' href='#'><img alt='add to' src='/images/add_tooltip.png'/></a></div>
					<div class='more_info_pane'>
						<h3 class='title'><a href="/video/watch?id=<?php echo strval($video->_id) ?>"><?php echo $video->title ?></a></h3>
						By <a href="<?php echo glue::url()->create('/user/view', array('id' => $video->author->_id)) ?>"><?php echo $video->author->getUsername() ?></a>
					</div>
					<div class='delete'><a href='#'><?php echo utf8_decode('&#215;') ?></a></div>
					<div class='position_val'><?php echo $k+1 ?></div>
					<div class="clearer"></div>
				</li>
			<?php } ?>
			</ul>
		<?php }else{ ?>
			<div class='no_videos'>No videos exist here</div>
		<?php } ?>
	</div>
</div>