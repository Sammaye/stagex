<?php

use glue\Html;

$this->JsFile('jquery-expander', "/js/jquery-expander.js");
$this->JsFile('j-dropdown', '/js/jdropdown.js');
$this->JsFile('playlist_dropdown', '/js/playlist_dropdown.js');

$this->js('videos.selectAll', "
	$(function(){
		//$.playlist_dropdown();

		$('.selectAll_input').click(function(){
			if($(this).prop('checked')==true){
				$('.video_list input:checkbox').prop('checked', false).trigger('click');
			}else{
				$('.video_list input:checkbox').prop('checked', true).trigger('click');
			}
		});

		$('.dropdown-group').jdropdown();
			
			$('#video_search_submit').on('click', function(){
				$(this).parents('form').submit();
			});

			$('.videos_toolbar .search_widget .submit a').click(function(event){
				$(this).parents('form').submit();
			});

			//$('div.expandable').expander({slicePoint: 60});

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
<div class="user_videos_body">

	<div class="header">
		<div class="left">
    	    <a class="btn-success" href="<?php echo glue::http()->createUrl('/video/upload', array(), glue::$params['uploadBase']) ?>">Add New Upload</a>
    	</div>
    	<div class="right" style=''>   
    		<span class='light small amount_found'><?php echo $video_rows->count() ?> found</span>
    		<div class='search form-search'>
			<?php $form = Html::form(array('method' => 'get')); ?><div class="search_input">
				<?php app\widgets\Jqautocomplete::widget(array(
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
				"))  ?></div><button class="submit_search"><span>&nbsp;</span></button>
			<?php $form->end() ?>
			</div>    	
    	</div>
    	<div class="clear"></div>
    </div>
    
    <div style='margin:0 0 15px 0; background:#f5f5f5; padding:10px;'>
    	<?php $form=Html::activeForm(); 
    	
    	$vModel=new app\models\Video();
    	$vModel->populateDefaults(); 
    	
    	?>
	   	<div style='margin:0 0 10px 0;'>
    		<h3 style='float:left; margin-right:15px;'>Edit Videos</h3>
			<div class="btn-group dropdown-group">
				<button class='btn-grey selected_actions dropdown-anchor'>Add section <span class="caret">&#9660;</span></button>
				<div class="dropdown-menu">
					<div class="item" data-section="title">Title</div>
					<div class="item" data-section="description">Description</div>
					<div class="item" data-section="listing">Listing</div>
					<div class="item" data-section="licence">Licence</div>
					<div class="item" data-section="statistics">Statistics</div>
					<div class="item" data-section="voting">Voting</div>
					<div class="item" data-section="embedding">Embedding</div>
					<div class="item" data-section="comments">Comments</div>
				</div>
			</div>    	
    	
    		<input type="button" class="btn-success" style='float:right;' value="Save"/>
    		<input type="button" class="btn-grey" style='float:right; margin-right:15px;' value="Cancel"/>
    		<div class="clear"></div>
    	</div>    	
    	
    	<div class="mass_edit_block form-stacked">
    		<div class="remove">
    			<a href="#">Remove</a>
    		</div><div>    	
    		<label>Title:</label><?php echo $form->textField($vModel,'title') ?>
    		</div><div class="clear"></div>
    	</div>
    	<div class="mass_edit_block form-stacked">
    		<div class="remove">
    			<a href="#">Remove</a>
    		</div>
    		<div>
    		<label>Description:</label><?php echo $form->textArea($vModel,'description') ?>
    		</div><div class="clear"></div>
    	</div>
    	<div class="mass_edit_block form-stacked">
    		<div class="remove">
    			<a href="#">Remove</a>
    		</div>
    		<div>
			<label>Tags:</label>
    	    <?php echo html::activeTextField($vModel, 'string_tags') ?>	
			</div><div class="clear"></div>
    	</div>     	
    	<div class="mass_edit_block form-stacked">
    		<div class="remove">
    			<a href="#">Remove</a>
    		</div>
    		<div>
			<label>Category:</label><?php echo html::activeSelectbox($vModel, 'category', $vModel->categories('selectBox')) ?>
			</div><div class="clear"></div>
    	</div>    	
    	<div class="mass_edit_block">
    		<div class="remove">
    			<a href="#">Remove</a>
    		</div>
    		<div>
			<?php $grp = html::activeRadio_group($vModel, 'listing') ?>
			<div class="label_options">
				<label class="radio"><?php echo $grp->add(0) ?>Listed</label>
				<p class='light'>Your video is public to all users of StageX</p>
				<label class="radio"><?php echo $grp->add(1) ?>Unlisted</label>
				<p class='light'>Your video is hidden from listings but can still be accessed directly using the video URL</p>
				<label class="radio"><?php echo $grp->add(2) ?>Private</label>
				<p class='light'>No one but you can access this video</p>
			</div>
			</div><div class="clear"></div>
    	</div>
    	<div class="mass_edit_block form-stacked">
    		<div class="remove">
    			<a href="#">Remove</a>
    		</div>
    		<div>
			<label>Licence:</label>
    	    <?php $grp = html::activeRadio_group($vModel, 'licence') ?>
			<div class="label_options">
				<label class="radio"><?php echo $grp->add('1') ?>Standard StageX Licence</label>
				<label class="radio"><?php echo $grp->add('2') ?>Creative Commons Licence</label>
			</div>			
			</div><div class="clear"></div>
    	</div>     	
    	<div class="mass_edit_block">
    		<div class="remove">
    			<a href="#">Remove</a>
    		</div><div>    	
    		<label class="checkbox"><?php echo $form->checkbox($vModel, 'mature') ?>This video is not suitable for family viewing</label>
    		</div><div class="clear"></div>
    	</div>    	
    	<div class="mass_edit_block">
    		<div class="remove">
    			<a href="#">Remove</a>
    		</div><div>    	
    		<label class="checkbox"><?php echo $form->checkbox($vModel,'privateStatistics') ?>Make my statistics private</label>
    		</div><div class="clear"></div>
    	</div>
    	<div class="mass_edit_block">
    		<div class="remove">
    			<a href="#">Remove</a>
    		</div><div>    	
    		<label class='checkbox'><?php echo $form->checkbox($vModel, "voteable", 1) ?>Allow users to vote on this video</label>
    		</div><div class="clear"></div>
    	</div>
    	<div class="mass_edit_block">
    		<div class="remove">
    			<a href="#">Remove</a>
    		</div><div>    	
    		<label class="checkbox"><?php echo $form->checkbox($vModel,'embeddable') ?>Allow my video to be embedded</label>
    		</div><div class="clear"></div>
    	</div>
    	<div class="mass_edit_block">
    		<div class="remove">
    			<a href="#">Remove</a>
    		</div><div>    	
			<label class='checkbox'><?php echo $form->checkbox($vModel, "moderated") ?>Moderate Responses</label>
			<label class='checkbox'><?php echo $form->checkbox($vModel, "voteableComments") ?>Allow users to vote on responses</label>
			<label class='checkbox'><?php echo $form->checkbox($vModel, "allowVideoComments") ?>Allow video responses</label>
			<label class='checkbox'><?php echo $form->checkbox($vModel, "allowTextComments") ?>Allow text responses</label>
			</div><div class="clear"></div>
    	</div>
    	<div class="clear"></div>
    	<?php $form->end(); ?>
    </div>

	<?php ob_start(); ?>
		<div class='stickytoolbar-placeholder grey_sticky_toolbar'>
			<div class='stickytoolbar-bar' style='background:#ffffff;'>
				<div class='block_summary'></div>
				<div class='inner_bar'>
					<div class='checkbox_button checkbox_input'><?php echo Html::checkbox('selectAll', 1, 0, array('class' => 'selectAll_input')) ?></div>
					<button class='btn-grey selected_actions dropdown-anchor'>Edit</button>
					<button class='btn-grey selected_actions dropdown-anchor'>Delete</button>
					<div class="btn-group dropdown-group playlist-drodown">
						<button class='btn-grey add_to_playlist'>Add To <span class="caret">&#9660;</span></button>
						<div class="dropdown-menu"></div>
					</div>
				</div>
			</div>
		</div>
		<?php $html = ob_get_contents();
	ob_end_clean();

	app\widgets\stickytoolbar::widget(array(
		"element" => '.grey_sticky_bar',
		"options" => array(
			'onFixedClass' => 'grey_sticky_bar-fixed'
		),
		'html' => $html
	)); ?>

	<?php if($video_rows->count() > 0){
		ob_start();
			?> <div class='video_list'>{items}</div><div style='margin:7px;'>{pager}<div class="clear"></div></div> <?php
			$template = ob_get_contents();
		ob_end_clean();

		glue\widgets\ListView::widget(array(
			'pageSize'	 => 20,
			'page' 		 => isset($_GET['page']) ? $_GET['page'] : 1,
			"cursor"	 => $video_rows,
			'template' 	 => $template,
			'itemView' 	 => 'video/_video.php',
			'pagerCssClass' => 'grid_list_pager'
		));
	}else{ ?>
		<div class=''>No videos were found for you</div>
	<?php } ?>
</div>