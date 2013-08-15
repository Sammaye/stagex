<?php

use glue\Html;

$this->jsFile('j-dropdown', '/js/jdropdown.js');

ob_start(); ?>
	<div class='white_shaded_dropdown actions_menu_menu'>
		<div class='dividing_header'>Set Privacy</div>
		<div class='item' data-action='set_privacy' data-val='1'>Listed</div>
		<div class='item' data-action='set_privacy' data-val='2'>Unlisted</div>
		<div class='item' data-action='set_privacy' data-val='3'>Private</div>
	</div>
	<?php $actions_html = ob_get_contents();
ob_end_clean();

ob_start(); ?>
	<div class='white_shaded_dropdown filters_menu'>
		<div class='item' data-caption='Showing All Playlists' data-url='<?php echo glue::http()->url('/user/playlists') ?>'>All Playlists</div>
		<div class='item' data-caption='Showing Listed Playlists' data-url='<?php echo glue::http()->url(array('filter' => 'listed')) ?>'>Listed Playlists</div>
		<div class='item' data-caption='Showing Unlisted Playlists' data-url='<?php echo glue::http()->url(array('filter' => 'unlisted')) ?>'>Unlisted Playlists</div>
		<div class='item' data-caption='Showing Private Playlists' data-url='<?php echo glue::http()->url(array('filter' => 'private')) ?>'>Private Playlists</div>
	</div><?php
	$filter_html = ob_get_contents();
ob_end_clean();

ob_start(); ?>
	<h2 class='diag_header'>Add new Playlist</h2>
		<div class='form playlist_form'>

			<div class='block_summary error_summary'>The playlist could not be created, be sure you entered a playlist name.</div>

			<div class='row'><div>Name:</div><?php echo html::textfield('title', null) ?></div>
			<div class='row'><div>Description:</div><?php echo html::textarea('description', null) ?></div>
			<a href='#' class='green_css_button add_playlist_button'>Create</a> <a href='#' class='grey_css_button cancel_add_playlist'>Cancel</a>
		</div><?php
	$html = ob_get_contents();
ob_end_clean();

$this->js('new_playlist', "
	$(function(){

		$('#playlist_search_submit').on('click', function(){
			$(this).parents('form').submit();
		});

		$('.selectAll_input').click(function(){
			if($(this).attr('checked')){
				$('.playlist_list input:checkbox').attr('checked', true);
			}else{
				$('.playlist_list input:checkbox').attr('checked', false);
			}
		});

		$('#new_playlist').click(function(event){
			event.preventDefault();
			$.facebox(".js_encode($html).", 'add_playlist_diag');
		});

		$(document).on('click', '.add_playlist_button', function(event){
			event.preventDefault();

			var form_vars = $('.playlist_form input,.playlist_form textarea').serializeArray();
			$.post('/playlist/add', form_vars, function(data){
				if(data.success){
					window.location = '/playlist/edit?id='+data.id;
				}else{
					//$('.playlist_form .block_summary').show();
					forms.summary($('.playlist_form .block_summary'), false, 'The playlist could not be created because:', data.messages);
				}
			}, 'json');
		});

		$(document).on('click', '.cancel_add_playlist', function(event){
			event.preventDefault();
			$.facebox.close();
			//$.facebox('close').close();
			//$(document).trigger('facebox.close');
		});

		$(document).on('click', '.grey_sticky_bar .delete', function(event){
			var selected = [];

			$('.playlist_list .playlist_item input:checkbox').each(function(){
				if($(this).attr('checked')){
					selected[selected.length] = $(this).attr('name');
				}
			});

			$.post('/playlist/batch_delete', { playlists: selected }, function(data){
				if(data.success){
					forms.summary($('.grey_sticky_bar .block_summary'), true, 'The playlists you selected were deleted', data.messages);
					$('.playlist_list .playlist_item').each(function(){
						if($(this).find('.checkbox_pane input:checkbox').attr('checked')){
							$(this).empty().addClass('deleted').html('This playlist has been deleted.');
						}
					});
				}else{
					forms.summary($('.grey_sticky_bar .block_summary'), false, 'The playlists you selected could not be deleted because:', data.messages);
				}
			}, 'json');
		});

		$('body').append($(".js_encode($filter_html)."));
		$('.selected_filter').jdropdown({
			'orientation': 'over',
			'menu_div': '.filters_menu',
			'item': '.filters_menu .item'
		});

	    $(document).on('jdropdown.selectItem', '.filters_menu .item', function(e, event){
	        //event.preventDefault();
			$('.selected_filter').html($(this).data('caption'));
			window.location = $(this).data('url');
	    });

		$('body').append($(".js_encode($actions_html)."));
		$('.selected_actions').jdropdown({
			'orientation': 'left',
			'menu_div': '.actions_menu_menu',
			'item': '.actions_menu_menu .item'
		});

	    $(document).on('jdropdown.selectItem', '.actions_menu_menu .item', function(e, event){

			var action = $(this).data('action'),
				value = $(this).data('val'),
				selected = [];

			$('.playlist_list .playlist_item input:checkbox').each(function(){
				if($(this).attr('checked')){
					selected[selected.length] = $(this).attr('name');
				}
			});

			if(action == 'set_privacy'){
				field = action == 'set_privacy' ? 'listing' : null;
				$.post('/playlist/set_detail', { field: field, value: value, playlists: selected }, function(data){
					if(data.success){
						forms.summary($('.grey_sticky_bar .block_summary'), true, 'Playlists settings changes were saved', data.messages);
						$('.playlist_list .playlist_item').each(function(){
							if($(this).find('.checkbox_pane input:checkbox').attr('checked')){
								if(value == 1){
									$(this).find('.playlist_listing').html('');
								}else if(value == 2 && field == 'listing'){
									$(this).find('.playlist_listing').html('<img alt=\'unlisted\' src=\'/images/unlisted_icon.png\' style=\'opacity:0.4; margin-right:7px;\'/>');
								}else if(value == 3 && field == 'listing'){
									$(this).find('.playlist_listing').html('<img alt=\'private\' src=\'/images/private_icon.png\' style=\'opacity:0.4; margin-right:7px;\'/>');
								}
							}
						});
					}else{
						forms.summary($('.grey_sticky_bar .block_summary'), false, 'Playlists settings changes could not be saved because:', data.messages);
					}
				}, 'json');
			}
	    });
	});
"); ?>
<div class="boxed_page_layout_outer user_playlists_page">

	<div class="tabs-nav">
		<ul>
			<li><a href="/user/playlists" class="selected">My Playlists</a></li>
			<li><a href="/playlist/followed">Followed</a></li>
			<a style='float:right;' class="btn-success" href="<?php echo glue::http()->url('/playlist/add') ?>">Add Playlist</a>
		</ul>
	</div>

	<div class="header">
		<!-- <div class="left">
    	    <a class="btn-success" href="<?php //echo glue::http()->url('/video/upload', array(), glue::$params['uploadBase']) ?>">Add New Upload</a>
    	</div> -->
    	<div class="right">   
    		<div class='search form-search'>
			<?php $form = Html::form(array('method' => 'get')); ?><div class="search_input">
				<?php app\widgets\Jqautocomplete::widget(array(
					'attribute' => 'query',
					'value' => urldecode(htmlspecialchars(isset($_GET['query']) ? $_GET['query'] : '')),
					'placeholder' => 'Search Playlists',
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
				"))  ?></div><button class="submit_search"><span>&nbsp;</span></button>
			<?php $form->end() ?>
			</div>    	
    	</div>
    	<div class="clear"></div>
    </div>
	<?php ob_start(); ?>
		<div class='stickytoolbar-placeholder grey_sticky_toolbar'>
			<div class='stickytoolbar-bar'>
				<div class='inner_bar'>
					<div class='checkbox_button checkbox_input'><?php echo Html::checkbox('selectAll', 1, 0, array('class' => 'selectAll_input')) ?></div>
					<button class='btn btn-dark selected_actions edit_videos_button'>Edit</button>
					<button class='btn-grey selected_actions btn_delete'>Delete</button>
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
	));
	?>

	<?php if($playlist_rows->count() > 0){
		ob_start();
			?> <div class='playlist_list'>{items}</div><div style='margin:7px;'>{pager}<div class="clear"></div></div> <?php
			$template = ob_get_contents();
		ob_end_clean();
		glue\widgets\ListView::widget(array(
				'pageSize'	 => 20,
				'page' 		 => isset($_GET['page']) ? $_GET['page'] : 1,
				"cursor"	 => $playlist_rows,
				'template' 	 => $template,
				'itemView' 	 => 'Playlist/_playlist.php',
				'pagerCssClass' => 'grid_list_pager'
		));
	}else{ ?>
		<div class='no_results_found'>No playlists were found for you</div>
	<?php } ?>
</div>