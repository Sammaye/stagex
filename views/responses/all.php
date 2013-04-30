<?php
ob_start(); ?>
	<div class='white_shaded_dropdown filters_menu'>
		<div class='item' data-caption='Showing All' data-sort='all'>All Responses</div>
		<div class='item' data-caption='Showing Moderated' data-sort='approved'>Approved Responses</div>
		<div class='item' data-caption='Showing Unmoderated' data-sort='unapproved'>Responses Awaiting Approval</div>
	</div><?php
	$filter_html = ob_get_contents();
ob_end_clean();


glue::clientScript()->addJsFile('j-dropdown', '/js/jdropdown.js');
glue::clientScript()->addJsScript('videoResponses.admin', "
	$(document).ready(function(){

		$('body').append($(".GClientScript::encode($filter_html)."));
		$('.selected_filter').jdropdown({
			'orientation': 'over',
			'menu_div': '.filters_menu',
			'item': '.filters_menu .item'
		});

		$(document).on('jdropdown.selectItem', '.filters_menu .item', function(e, event){
		    //event.preventDefault();
		    $('.video_response_list').data('sort', $(this).data('sort'));
			$('.selected_filter').html($(this).data('caption'));
			refresh_video_response_list();
		});

		$(document).on('click', '.select_all_responses', function(event){
			if($(this).attr('checked')){
				$('.response_selector').attr('checked', true);
			}else{
				$('.response_selector').attr('checked', false);
			}
		});

		$(document).on('click', '.responses_toolbar .delete', function(event){
			event.preventDefault();
			var ids = [];

			$('.response_selector:checked').each(function(i, item){
				ids[ids.length] = $(item).val();
			});

			$.get('/videoresponse/delete_many', { ids: ids, vid: '".strval($model->_id)."' }, function(data){
				if(data.success){
					forms.summary($('.responses_toolbar .block_summary'), true, data.messages[0]);

					$('.video_response_list .video_response_item input:checkbox:checked').each(function(){
						$(this).parents('.video_response_item').remove();
					});
				}else{
					forms.summary($('.responses_toolbar .block_summary'), false, 'Responses could not be deleted because:', data.messages);
				}
			}, 'json');
		});

		$(document).on('click', '.responses_toolbar .approve', function(event){
			event.preventDefault();
			var ids = [];

			$('.response_selector:checked').each(function(i, item){
				ids[ids.length] = $(item).val();
			});

			$.get('/videoresponse/approve_many', { ids: ids, vid: '".strval($model->_id)."' }, function(data){
				if(data.success){
					forms.summary($('.responses_toolbar .block_summary'), true, data.messages[0]);
					refresh_video_response_list('current');
				}else{
					forms.summary($('.responses_toolbar .block_summary'), false, 'Responses could not be approved because:', data.messages);
				}
			}, 'json');
		});
	});
") ?>


<div class='container_16 all_video_responses_body'>
	<div style='float:left; width:300px; margin-left:10px;'>

		<?php $videoResponseCount = $model->with('responses', array('type' => 'video', 'deleted' => 0))->count();
		$textResponseCount = $model->with('responses', array('type' => 'text', 'deleted' => 0))->count(); ?>

		<div class='video_details'>
			<div class='title'><a href='<?php echo Glue::url()->create("/video/watch", array("id"=>$model->_id)) ?>'><?php echo $model->title ?></a></div>
			<div class='left'><a href="<?php echo Glue::url()->create("/video/watch", array("id"=>$model->_id)) ?>" ><img alt='thumbnail' src="<?php echo $model->getImage(138, 77) ?>"/></a></div>
			<div class='right'>
				<div><?php echo $model->views ?> views</div>
				<div><?php echo $videoResponseCount ?> video responses</div>
				<div><?php echo $textResponseCount ?> text responses</div>
			</div>
			<div class='clearer'></div>
		</div>
		<div class='video_abstract'><?php echo $model->getLongAbstract() ?></div>
		<div class='uploaded_by_head'>Uploaded by:</div>
		<div class='avatar_block'>
			<div class='user_image'><img alt='thumbnail' src="<?php echo $model->author->getPic(48, 48); ?>"/></div>
			<div class='about_user'><a href='<?php echo glue::url()->create('/user/view', array('id' => strval($model->author->_id))) ?>'><?php echo $model->author->getUsername() ?></a>
			<div class='subs'><?php echo $model->author->total_subscribers ?> subscribers | <?php echo $model->author->total_uploads ?> videos</div>
			</div>
		</div>

		<?php $this->widget("application/widgets/Advertising/Ad_box.php", array( "configuration"=>'300_box' )); ?>
		<div style='margin-top:25px;'>
			<?php $this->widget("application/widgets/Advertising/Ad_box.php", array( "configuration"=>'300_box' )); ?>
		</div>
	</div>
	<div class='grid_5 alpha omega' style='width:634px;'>
		<div class='page_head'>View all Responses</div>

		<?php if(glue::roles()->checkRoles(array('^' => $model))){
			ob_start(); ?>
			<div class='stickytoolbar-placeholder responses_toolbar white_sticky_bar'>
				<div class='stickytoolbar-bar' style='z-index:999999;'>
					<div class='block_summary' style='display:none;'></div>
					<div class='inner_bar'>
						<div class='checkbox_input'><?php echo html::checkbox('select_all', 1, null, array('class' => 'select_all_responses')) ?></div>
						<div class='grey_css_button approve left_button'>Approve</div>
						<div class='grey_css_button delete float_left'>Delete</div>

						<div class='grey_css_button selected_filter right_button'>View All Responses</div>
					</div>
					<div class='grad_bar'>&nbsp;</div>
				</div>
			</div>
			<?php $html = ob_get_contents();
			ob_end_clean();
			$this->widget('application/widgets/stickytoolbar.php', array(
				"element" => '.responses_toolbar',
				"options" => array(
					'onFixedClass' => 'white_stickybar_fixed'
				),
				'html' => $html
			));
		}else{ ?>
			<div class='hr'>&nbsp;</div>
		<?php } ?>
		<div><?php $this->partialRender('responses/list', array('model' => $model, 'mode' => 'admin', 'comments' => $comments, 'comment_per_page' => 30)) ?></div>
	</div>
</div>

<div id='video_response_options' style='width:200px;'></div>
<div id='videoResponse_results' class=''></div>