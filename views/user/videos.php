<?php

use glue\Html;

$this->JsFile("/js/jquery.expander.js");
$this->JsFile('/js/jdropdown.js');
$this->JsFile('/js/playlist_dropdown.js');

$this->js('videos.selectAll', "
	$(function(){
		//$.playlist_dropdown();
		
		$('.user_videos_body .alert').summarise();

		$('.selectAll_input').click(function(){
			if($(this).prop('checked')==true){
				$('.video_list input:checkbox').prop('checked', false).trigger('click');
			}else{
				$('.video_list input:checkbox').prop('checked', true).trigger('click');
			}
		});

		$('.dropdown-group').jdropdown();
		
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
			$('.video_list .video .checkbox_col input:checked').each(function(i,item){
				params[0]['ids['+i+']']=$(item).val();
			});
			$.post('/video/massEdit', params).done(function(data){

			});
		});

		$(document).on('click', '.grey_sticky_toolbar .btn_delete', function(){
			params={'ids[]':[]};
			$('.video_list .video .checkbox_col input:checked').each(function(i,item){
				params['ids[]'][params['ids[]'].length]=$(item).val();
			});

			$.post('/video/delete', params, null, 'json').done(function(data){
				if(data.success){
					$('.user_videos_body .alert').summarise('set', 'success','The videos you selected were deleted');
					$.each(params['ids[]'],function(i,item){
						$('.video_list .video[data-id='+item+']').children().not('.deleted').css({display:'none'});
					});
				}else{
		
				}
			}, 'json');			
		});
	});
	");
?>
<div class="user_videos_body">

	<div class="header">
		<div class="left">
    	    <a class="btn-success" href="<?php echo glue::http()->createUrl('/video/upload', array(), glue::$params['uploadBase']) ?>">Add New Upload</a>
    	</div>
    	<div class="right">   
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
    
    <div class='alert'></div>
    
    <div class="mass_edit_form">
    	<?php $form=Html::activeForm(); 
    	
    	$vModel=new app\models\Video();
    	$vModel->populateDefaults(); 
    	
    	?>
	   	<div class="header">
    		<h3>Edit Videos</h3>
    		<input type="button" class="btn-success save" value="Save"/>
    		<input type="button" class="btn-grey cancel" value="Cancel"/>
    		<div class="clear"></div>
    	</div>    	
    	
    	<div class="mass_edit_block form-stacked">
    		<a href="#" class="edit">+ Edit Title</a>
    		<div class="form">
    		<a href="#" class="remove">Remove</a>
    		<div class="right">    	
    		<label>Title:</label><?php echo $form->textField($vModel,'title') ?>
    		</div></div><div class="clear"></div>
    	</div>
    	<div class="mass_edit_block form-stacked">
    		<a href="#" class="edit">+ Edit Description</a>
    		<div class="form">
    		<a href="#" class="remove">Remove</a>
    		<div class="right">
    		<label>Description:</label><?php echo $form->textArea($vModel,'description') ?>
    		</div></div><div class="clear"></div>
    	</div>
    	<div class="mass_edit_block form-stacked">
    		<a href="#" class="edit">+ Edit Tags</a>
    		<div class="form">
    		<a href="#" class="remove">Remove</a>
    		<div class="right">
			<label>Tags:</label>
    	    <?php echo html::activeTextField($vModel, 'stringTags') ?>	
			</div></div><div class="clear"></div>
    	</div>     	
    	<div class="mass_edit_block form-stacked">
    		<a href="#" class="edit">+ Edit Category</a>
    		<div class="form">
    		<a href="#" class="remove">Remove</a>
    		<div class="right">
			<label>Category:</label><?php echo html::activeSelectbox($vModel, 'category', $vModel->categories('selectBox')) ?>
			</div></div><div class="clear"></div>
    	</div>    	
    	<div class="mass_edit_block">
    		<a href="#" class="edit">+ Edit Listing</a>
    		<div class="form">
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
			</div><div class="clear"></div>
    	</div>
    	<div class="mass_edit_block form-stacked">
    		<a href="#" class="edit">+ Edit Licence</a>
    		<div class="form">
    		<a href="#" class="remove">Remove</a>
    		<div class="right">
			<label>Licence:</label>
    	    <?php $grp = html::activeRadio_group($vModel, 'licence') ?>
			<label class="radio"><?php echo $grp->add('1') ?>Standard StageX Licence</label>
			<label class="radio"><?php echo $grp->add('2') ?>Creative Commons Licence</label>
			</div>			
			</div><div class="clear"></div>
    	</div>     	
    	<div class="mass_edit_block">
    		<a href="#" class="edit">+ Edit Mature Rating</a>
    		<div class="form">
    		<a href="#" class="remove">Remove</a>
    		<div class="right">    	
    		<label class="checkbox"><?php echo $form->checkbox($vModel, 'mature') ?>This video is not suitable for family viewing</label>
    		</div></div><div class="clear"></div>
    	</div>    	
    	<div class="mass_edit_block">
    		<a href="#" class="edit">+ Edit Statistics</a>
    		<div class="form">
    		<a href="#" class="remove">Remove</a>
    		<div class="right">    	
    		<label class="checkbox"><?php echo $form->checkbox($vModel,'privateStatistics') ?>Make my statistics private</label>
    		</div></div><div class="clear"></div>
    	</div>
    	<div class="mass_edit_block">
    		<a href="#" class="edit">+ Edit Voting</a>
    		<div class="form">
    		<a href="#" class="remove">Remove</a>
    		<div class="right">    	
    		<label class='checkbox'><?php echo $form->checkbox($vModel, "voteable", 1) ?>Allow users to vote on this video</label>
    		</div></div><div class="clear"></div>
    	</div>
    	<div class="mass_edit_block">
    		<a href="#" class="edit">+ Edit Embedding</a>
    		<div class="form">
    		<a href="#" class="remove">Remove</a>
    		<div class="right">    	
    		<label class="checkbox"><?php echo $form->checkbox($vModel,'embeddable') ?>Allow my video to be embedded</label>
    		</div></div><div class="clear"></div>
    	</div>
    	<div class="mass_edit_block">
    		<a href="#" class="edit">+ Edit Comments</a>
    		<div class="form">
    		<a href="#" class="remove">Remove</a>
    		<div class="right">    	
			<label class='checkbox'><?php echo $form->checkbox($vModel, "moderated") ?>Moderate Responses</label>
			<label class='checkbox'><?php echo $form->checkbox($vModel, "voteableComments") ?>Allow users to vote on responses</label>
			<label class='checkbox'><?php echo $form->checkbox($vModel, "allowVideoComments") ?>Allow video responses</label>
			<label class='checkbox'><?php echo $form->checkbox($vModel, "allowTextComments") ?>Allow text responses</label>
			</div></div><div class="clear"></div>
    	</div>
    	<?php $form->end(); ?>
    </div>

	<?php ob_start(); ?>
		<div class='stickytoolbar-placeholder grey_sticky_toolbar'>
			<div class='stickytoolbar-bar'>
				<div class='inner_bar'>
					<div class='checkbox_button checkbox_input'><?php echo Html::checkbox('selectAll', 1, 0, array('class' => 'selectAll_input')) ?></div>
					<button class='btn-grey selected_actions dropdown-anchor edit_videos_button'>Edit</button>
					<button class='btn-grey selected_actions dropdown-anchor btn_delete'>Delete</button>
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