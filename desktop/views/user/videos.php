<?php

use glue\Html;

$this->JsFile("/js/jquery.expander.js");
$this->JsFile('/js/jdropdown.js');
$this->JsFile('/js/playlist_dropdown.js');

$this->js('videos', "
	$(function(){
		$('.expandable').expander();
		
		$('.dropdown-group').jdropdown();
		$('.playlist-dropdown').playlist_dropdown();		
		
		$('.mass_edit_form .alert').summarise();
		$('.grey_sticky_toolbar .block-alert').summarise()

		$('.selectAll_input').click(function(){
			if($(this).prop('checked')==true){
				$('.video_list input:checkbox').prop('checked', false).trigger('click');
			}else{
				$('.video_list input:checkbox').prop('checked', true).trigger('click');
			}
		});

		$(document).on('click', '.edit_videos_button', function(){
			$('.mass_edit_form').css({display:'block'});
		});
		
		$(document).on('click', '.mass_edit_block .edit', function(e){
			e.preventDefault();
			$(this).parents('.mass_edit_block').addClass('active').find('.form').css({display:'block'});
			$(this).css({display:'none'});
		});
		
		$(document).on('click', '.mass_edit_block .remove', function(e){
			e.preventDefault();
			$(this).parents('.form').css({display:'none'});
			$(this).parents('.mass_edit_block').removeClass('active').find('.edit').css({display:'block'});
		});			
		
		$(document).on('click', '.mass_edit_form .cancel', function(){
			$('.mass_edit_form').css({display:'none'});
		});
		
		$(document).on('click', '.mass_edit_form .save', function(){
			var params = $(this).parents('.mass_edit_form').find('\
				.mass_edit_block.active input,.mass_edit_block.active textarea,.mass_edit_block.active select\
			').serializeArray();
		
			id_length=0;
			$('.video_list .video .checkbox_col input:checked').each(function(i,item){
				params[params.length]={name:['ids['+id_length+']'],value:$(item).val()};
				id_length++;
			});		
		
			$.post('/video/batchSave', params, null, 'json').done(function(data){
				if(data.success){
					$('.mass_edit_form .alert').summarise('set', 'success', data.updated + ' of ' + data.total + ' of the videos you selected were saved');
				}else{
					$('.mass_edit_form .alert').summarise('set', 'error', 'The videos you selected could not be saved');
				}
			});
		});

		$(document).on('click', '.grey_sticky_toolbar .btn_delete', function(){
			params={'ids[]':[]};
			$('.video_list .video .checkbox_col input:checked').each(function(i,item){
				params['ids[]'][params['ids[]'].length]=$(item).val();
			});

			$.post('/video/delete', params, null, 'json').done(function(data){
				if(data.success){
					$('.grey_sticky_toolbar .block-alert').summarise('set', 'success','The videos you selected were deleted');
					$.each(params['ids[]'],function(i,item){
						$('.video_list .video[data-id='+item+']').children().not('.deleted').css({display:'none'});
						$('.video_list .video[data-id='+item+'] .deleted').css({display:'block'});
					});
					reset_checkboxes();
				}else{
					$('.grey_sticky_toolbar .block-alert').summarise('set', 'error','The videos you selected could not be deleted');
				}
			}, 'json');			
		});
		
		$(document).on('click', '.video .encoding_failed .btn', function(e){
			videoEl=$(this).parents('.video');
			$.post('/video/delete', {'ids[]':[videoEl.data().id]}, null, 'json').done(function(data){
				if(data.success){
					videoEl.remove();
				}else{
					$('.grey_sticky_toolbar .block-alert').summarise('set', 'error','The video you selected could not be deleted');
				}
			}, 'json');	
		});
		
		$(document).on('click', '.video .deleted .undo', function(e){
			e.preventDefault();
		
			elData=$(this).parents('.video').data();
			$.post('/video/undoDelete',{id:elData.id},null,'json').done(function(data){
				if(data.success){
					$('.video_list .video[data-id='+elData.id+']').children().not('.deleted').css({display:'block'});
					$('.video_list .video[data-id='+elData.id+'] .deleted').css({display:'none'});					
				}else{
					$(this).parents('.deleted').html('This video could not be recovered. Most likely because it has been processed and is deleted.');
				}
			});
		});
	});
		
	function reset_checkboxes(){
		$('.selectAll_input').prop('checked',true).trigger('click');
	}
");
?>
<div class="user_videos_body">

	<div class="videos_nav_top">
		<ul class="nav nav-tabs">
			<li class="active"><a href="/user/videos">Uploads</a></li>
			<li><a href="/user/watched">Watched</a></li>
			<li><a href="/user/rated">Liked</a></li>
			<li><a href="/user/rated?tab=dislikes">Disliked</a></li>
		</ul>
		<a class="btn btn-success btn-upload" href="<?php echo glue::http()->url('/video/upload', array(), glue::$params['uploadBase']) ?>">Add New Upload</a>
	</div>	

	<div class="header">
    		<div class='search form-search'>
			<?php $form = Html::form(array('method' => 'get')); ?>
			<label class="sr-only" for="query">Search Query</label>
			<?php echo $form->textField('query', glue::http()->param('query'), array('placeholder' => 'Search your videos', 'class' => 'form-search-input col-37')) ?>
			<button class="btn submit_search"><span class="search-dark-icon">&nbsp;</span></button>				
				<span class='text-muted small amount_found'><?php echo $video_rows->count() ?> found</span>
			<?php $form->end() ?>
			
			</div>
			<div class="btn-group">
				<button type="button" class="btn btn-link dropdown-toggle"
					data-toggle="dropdown">
					Sort<?php if(glue::http()->param('sorton') == 'created'){
						if(glue::http()->param('orderby') == -1)
							echo ': Newest';
						elseif(glue::http()->param('orderby') == 1)
							echo ": Oldest";
					}elseif(glue::http()->param('sorton') == 'likes'){
						if(glue::http()->param('orderby') == -1)
							echo ': Liked';
						elseif(glue::http()->param('orderby') == 1)
							echo ": Disliked";
					}elseif(glue::http()->param('sorton') == 'views'){
						if(glue::http()->param('orderby') == -1)
							echo ': Most Viewed';
						elseif(glue::http()->param('orderby') == 1)
							echo ": Least Viewed";
					} ?>
					<span class="caret"></span>
				</button>
				<ul class="dropdown-menu" role="menu"
					aria-labelledby="dropdownMenu1">
					<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('sorton'=>'created','orderby'=>-1)) ?>">Newest</a></li>
					<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('sorton'=>'created','orderby'=>1)) ?>">Oldest</a></li>
					<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('sorton'=>'likes','orderby'=>-1)) ?>">Liked</a></li>
					<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('sorton'=>'dislikes','orderby'=>-1)) ?>">Disliked</a></li>
					<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('sorton'=>'views','orderby'=>-1)) ?>">Most Viewed</a></li>
					<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('sorton'=>'views','orderby'=>1)) ?>">Least Viewed</a></li>
				</ul>
			</div>			
    	<div class="clear"></div>
    </div>
    
    <div class="mass_edit_form">
    	<?php $form=Html::activeForm(); 
    	
    	$vModel=new app\models\Video();
    	$vModel->populateDefaults(); 
    	
    	?>
	   	<div class="header clearfix">
    		<h3>Edit Videos</h3>
    		<button type="button" class="btn btn-success save">Save</button>
    		<button type="button" class="btn btn-default cancel">Cancel</button>
    	</div>    	
    	
    	<div class='alert'></div>
    	
    	<div class="mass_edit_block form-stacked">
    		<a href="#" class="edit">+ Edit Title</a>
    		<div class="form clearfix">
    		<a href="#" class="remove">Remove</a>
    		<div class="right">    	
    		<label>Title:</label><?php echo $form->textField($vModel,'title',array('class'=>'form-control')) ?>
    		</div></div>
    	</div>
    	<div class="mass_edit_block form-stacked">
    		<a href="#" class="edit">+ Edit Description</a>
    		<div class="form clearfix">
    		<a href="#" class="remove">Remove</a>
    		<div class="right">
    		<label>Description:</label><?php echo $form->textArea($vModel,'description',array('class'=>'form-control')) ?>
    		</div></div>
    	</div>
    	<div class="mass_edit_block form-stacked">
    		<a href="#" class="edit">+ Edit Tags</a>
    		<div class="form clearfix">
    		<a href="#" class="remove">Remove</a>
    		<div class="right">
			<label>Tags:</label>
    	    <?php echo html::activeTextField($vModel, 'stringTags',array('class'=>'form-control')) ?>	
			</div></div>
    	</div>     	
    	<div class="mass_edit_block form-stacked">
    		<a href="#" class="edit">+ Edit Category</a>
    		<div class="form clearfix">
    		<a href="#" class="remove">Remove</a>
    		<div class="right">
			<label>Category:</label><?php echo html::activeSelectbox($vModel, 'category', $vModel->categories('selectBox'),array('class'=>'form-control')) ?>
			</div></div>
    	</div>    	
    	<div class="mass_edit_block">
    		<a href="#" class="edit">+ Edit Listing</a>
    		<div class="form clearfix">
    		<a href="#" class="remove">Remove</a>
    		<div class="right">
			<?php $grp = html::activeRadio_group($vModel, 'listing') ?>
			<label class="radio"><?php echo $grp->add(0) ?>Listed</label>
			<p class='light'>Your video is public to all users of StageX</p>
			<label class="radio"><?php echo $grp->add(1) ?>Unlisted</label>
			<p class='light'>Your video is hidden from listings but can still be accessed directly using the video URL</p>
			<label class="radio"><?php echo $grp->add(2) ?>Private</label>
			<p class='light'>No one but you can access this video</p>
			</div>
			</div>
    	</div>
    	<div class="mass_edit_block form-stacked">
    		<a href="#" class="edit">+ Edit Licence</a>
    		<div class="form clearfix">
    		<a href="#" class="remove">Remove</a>
    		<div class="right">
    	    <?php $grp = html::activeRadio_group($vModel, 'licence') ?>
			<label class="radio"><?php echo $grp->add('1') ?>Standard StageX Licence</label>
			<label class="radio"><?php echo $grp->add('2') ?>Creative Commons Licence</label>
			</div>			
			</div>
    	</div>     	
    	<div class="mass_edit_block">
    		<a href="#" class="edit">+ Edit Mature Rating</a>
    		<div class="form clearfix">
    		<a href="#" class="remove">Remove</a>
    		<div class="right">    	
    		<label class="checkbox"><?php echo $form->checkbox($vModel, 'mature', 1) ?>This video is not suitable for family viewing</label>
    		</div></div>
    	</div>    	
    	<div class="mass_edit_block">
    		<a href="#" class="edit">+ Edit Statistics</a>
    		<div class="form clearfix">
    		<a href="#" class="remove">Remove</a>
    		<div class="right">    	
    		<label class="checkbox"><?php echo $form->checkbox($vModel,'privateStatistics', 1) ?>Make my statistics private</label>
    		</div></div>
    	</div>
    	<div class="mass_edit_block">
    		<a href="#" class="edit">+ Edit Voting</a>
    		<div class="form clearfix">
    		<a href="#" class="remove">Remove</a>
    		<div class="right">    	
    		<label class='checkbox'><?php echo $form->checkbox($vModel, "voteable", 1) ?>Allow users to vote on this video</label>
    		</div></div>
    	</div>
    	<div class="mass_edit_block">
    		<a href="#" class="edit">+ Edit Embedding</a>
    		<div class="form clearfix">
    		<a href="#" class="remove">Remove</a>
    		<div class="right">    	
    		<label class="checkbox"><?php echo $form->checkbox($vModel,'embeddable', 1) ?>Allow my video to be embedded</label>
    		</div></div>
    	</div>
    	<div class="mass_edit_block">
    		<a href="#" class="edit">+ Edit Comments</a>
    		<div class="form clearfix">
    		<a href="#" class="remove">Remove</a>
    		<div class="right">    	
			<label class='checkbox'><?php echo $form->checkbox($vModel, "moderated", 1) ?>Moderate Responses</label>
			<label class='checkbox'><?php echo $form->checkbox($vModel, "voteableComments", 1) ?>Allow users to vote on responses</label>
			<label class='checkbox'><?php echo $form->checkbox($vModel, "allowTextComments", 1) ?>Allow text responses</label>
			</div></div>
    	</div>
    	<?php $form->end(); ?>
    </div>

	<?php ob_start(); ?>
		<div class='stickytoolbar-placeholder grey_sticky_toolbar'>
			<div class='stickytoolbar-bar'>
				<div class='inner_bar'>
					<div class='checkbox_button checkbox_input'><?php echo Html::checkbox('selectAll', 1, 0, array('class' => 'selectAll_input')) ?></div>
					<button class='btn btn-default selected_actions edit_videos_button'>Edit</button>
					<button class='btn btn-danger selected_actions btn_delete'>Delete</button>
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
				</div>
				<div class="alert block-alert"></div>
			</div>
		</div>
		<?php $html = ob_get_contents();
	ob_end_clean();

	echo app\widgets\Stickytoolbar::run(array(
		"element" => '.grey_sticky_toolbar',
		"options" => array(
			'onFixedClass' => 'grey_sticky_bar-fixed'
		),
		'html' => $html
	)); ?>

	<div class="video_list">
	<?php if($video_rows->count() > 0){
		echo glue\widgets\ListView::run(array(
			'sortableAttributes' => array('dislikes', 'likes', 'created', 'views'),
			'pageSize'	 => 20,
			'page' 		 => isset($_GET['page']) ? $_GET['page'] : 1,
			"cursor"	 => $video_rows,
			'itemView' 	 => 'video/_video.php',
		));
	}else{ ?>
		<div class='no_results_found'>No videos were found</div>
	<?php } ?>
	</div>
</div>