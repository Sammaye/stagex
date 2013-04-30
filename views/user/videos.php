<?php

glue::clientScript()->addJsFile('jquery-expander', "/js/jquery-expander.js");
glue::clientScript()->addJsFile('j-dropdown', '/js/jdropdown.js');
glue::clientScript()->addJsFile('playlist_dropdown', '/js/playlist_dropdown.js');

glue::clientScript()->addJsScript('videos.selectAll', "
	$(function(){
		$.playlist_dropdown();

		$('.selectAll_input').click(function(){
			if($(this).attr('checked')){
				$('.video_list input:checkbox').attr('checked', true);
			}else{
				$('.video_list input:checkbox').attr('checked', false);
			}
		});
	});
");

ob_start(); ?>
	<div class='actions_menu_menu'>
		<div class='item' data-action='delete'>Delete</div>
		<div class='dividing_header'>Set Privacy</div>
		<div class='item' data-action='set_privacy' data-val='1'>Listed</div>
		<div class='item' data-action='set_privacy' data-val='2'>Unlisted</div>
		<div class='item' data-action='set_privacy' data-val='3'>Private</div>
		<div class='dividing_header'>Set Licensing</div>
		<div class='item' data-action='set_lic' data-val='1'>Stagex Common Licence</div>
		<div class='item' data-action='set_lic' data-val='2'>Creative Commons Licence</div>
	</div><?php
	$html = ob_get_contents();
ob_end_clean();

ob_start(); ?>
	<div class='filters_menu'>
		<div class='item' data-caption='Showing All Videos' data-url='<?php echo glue::url()->create('/user/videos') ?>'>All Videos</div>
		<div class='item' data-caption='Showing Listed Videos' data-url='<?php echo glue::url()->create(array('filter' => 'listed')) ?>'>Listed Videos</div>
		<div class='item' data-caption='Showing Unlisted Videos' data-url='<?php echo glue::url()->create(array('filter' => 'unlisted')) ?>'>Unlisted Videos</div>
		<div class='item' data-caption='Showing Private Videos' data-url='<?php echo glue::url()->create(array('filter' => 'private')) ?>'>Private Videos</div>
	</div><?php
	$filter_html = ob_get_contents();
ob_end_clean();


	glue::clientScript()->addJsScript('user_videos_page.base', "
		$(function(){

			$('#video_search_submit').on('click', function(){
				$(this).parents('form').submit();
			});

			$('.videos_toolbar .search_widget .submit a').click(function(event){
				$(this).parents('form').submit();
			});

			$('div.expandable').expander({slicePoint: 60});

			$('body').append($(".GClientScript::encode($filter_html)."));
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

			$('body').append($(".GClientScript::encode($html)."));
			$('.selected_actions').jdropdown({
				'orientation': 'left',
				'menu_div': '.actions_menu_menu',
				'item': '.actions_menu_menu .item'
			});

		    $(document).on('jdropdown.selectItem', '.actions_menu_menu .item', function(e, event){

				var action = $(this).data('action'),
					value = $(this).data('val'),
					selected = [];

				$('.video_list .video_item input:checkbox').each(function(){
					if($(this).attr('checked')){
						selected[selected.length] = $(this).attr('name');
					}
				});

				switch(true){
					case action == 'delete':
						$.post('/video/batch_delete', { videos: selected }, function(data){
							if(data.success){
								forms.summary($('.grey_sticky_bar .block_summary'), true, 'The videos you selected were deleted', data.messages);
								$('.video_list .video_item').each(function(){
									if($(this).find('.checkbox_pane input:checkbox').attr('checked')){
										$(this).empty().addClass('deleted').html('This video has been deleted.');
									}
								});
							}else{
								forms.summary($('.grey_sticky_bar .block_summary'), false, 'The videos you selected could not be deleted because:', data.messages);
							}
						}, 'json');
						break;
					case action == 'set_privacy' || action == 'set_lic':
						field = action == 'set_privacy' ? 'listing' : 'licence';
						$.post('/video/set_detail', { field: field, value: value, videos: selected }, function(data){
							if(data.success){
								forms.summary($('.grey_sticky_bar .block_summary'), true, 'Video settings changes were saved', data.messages);
								$('.video_list .video_item').each(function(){
									if($(this).find('.checkbox_pane input:checkbox').attr('checked')){
										if(value == 1){
											$(this).find('.video_listing').html('');
										}else if(value == 2 && field == 'listing'){
											$(this).find('.video_listing').html('<img alt=\'unlisted\' src=\'/images/unlisted_icon.png\' style=\'opacity:0.4; margin-right:7px;\'/>');
										}else if(value == 3 && field == 'listing'){
											$(this).find('.video_listing').html('<img alt=\'private\' src=\'/images/private_icon.png\' style=\'opacity:0.4; margin-right:7px;\'/>');
										}
									}
								});
							}else{
								forms.summary($('.grey_sticky_bar .block_summary'), false, 'Video settings changes could not be saved because:', data.messages);
							}
						}, 'json');
						break;
				}
		    });
		});
	");
?>
<div class="boxed_page_layout_outer user_videos_body">

	<div class='borderless_head'>
		<div class='head'>Videos</div>
    	<div class='amnt_found'><?php echo $video_rows->count() ?> found</div>
    </div>

	<?php ob_start(); ?>
		<div class='stickytoolbar-placeholder grey_sticky_bar'>
			<div class='stickytoolbar-bar'>
				<div class='block_summary'></div>
				<div class='inner_bar'>

					<div class='checkbox_input'><?php echo html::checkbox('selectAll', 1, 0, array('class' => 'selectAll_input')) ?></div>
					<div class='grey_css_button add_to_playlist left_button'>Add To</div>
					<div class='grey_css_button selected_actions float_left'>Edit</div>
					<div class='search_widget'>
						<?php $form = html::form(array('method' => 'get')) ?>
						<div class='middle'><?php
						$this->widget('application/widgets/Jqautocomplete.php', array(
							'attribute' => 'query',
							'value' => urldecode(htmlspecialchars(isset($_GET['query']) ? $_GET['query'] : '')),
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
							")) ?></div><a href='#' id='video_search_submit' class='submit'><img alt='search' src='/images/search_icon_small.png'/></a>
						<?php echo html::submitbutton('Search', array('class' => 'invisible_submit')); $form->end() ?>
					</div>
					<div class='grey_css_button selected_filter right_button'>
						<?php if($filter == 'listed'){ ?>
							Showing Listed Videos
						<?php }elseif($filter == 'unlisted'){ ?>
							Showing Unlisted Videos
						<?php }elseif($filter == 'private'){ ?>
							Showing Private Videos
						<?php }else{ ?>
							Showing All Videos
						<?php } ?>
					</div>
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
	)); ?>

	<?php if($video_rows->count() > 0){
		ob_start();
			?> <div class='video_list'>{items}</div><div style='margin:7px;'>{pager}<div class="clearer"></div></div> <?php
			$template = ob_get_contents();
		ob_end_clean();

		$this->widget('glue/widgets/GListView.php', array(
			'pageSize'	 => 20,
			'page' 		 => isset($_GET['page']) ? $_GET['page'] : 1,
			"cursor"	 => $video_rows,
			'template' 	 => $template,
			'itemView' 	 => 'videos/_video.php',
			'pagerCssClass' => 'grid_list_pager'
		));
	}else{ ?>
		<div class='padded_list_not_found'>No videos were found for you</div>
	<?php } ?>
</div>