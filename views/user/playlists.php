<?php
use glue\Html;

$this->JsFile("/js/jquery.expander.js");
$this->jsFile('/js/jdropdown.js');
$this->js('new_playlist', "
		
	$('.expandable').expander();
		
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
"); ?>
<div class="boxed_page_layout_outer user_playlists_page">

	<div class="tabs-nav videos_nav_top">
		<ul>
			<li><a href="/user/playlists" class="selected">My Playlists</a></li>
			<li><a href="/playlist/followed">Followed</a></li>
		</ul>
		<a class="btn-success btn-upload" href="<?php echo glue::http()->url('/playlist/create') ?>">Add Playlist</a>
	</div>

	<div class="header">   
    	<div class='search form-search' style='margin:20px 0 10px 0;'>
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
		<div class="clear"></div>
		</div>    	
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
	)); ?>
	<div class='playlists'>
	<?php if($playlist_rows->count() > 0){
		glue\widgets\ListView::widget(array(
			'pageSize'	 => 20,
			'page' 		 => glue::http()->param('page',1),
			"cursor"	 => $playlist_rows,
			'itemView' 	 => 'playlist/_playlist.php',
		));
	}else{ ?>
		<div class='no_results_found'>No playlists were found for you</div>
	<?php } ?>
	</div>
</div>