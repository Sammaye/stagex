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
		
	$(document).on('click', '.condition_row .condition_remove', function(event){
		$(this).parents('.condition_row').remove();
	});
");
?>
<div class="user_videos_body">
	<a class="btn btn-success btn-upload" href="<?php echo glue::http()->url('/video/upload', array(), glue::$params['uploadBase']) ?>">Add New Upload</a>
	<div class="header">
    		<div class='search form-search'>
			<?php $form = Html::form(array('method' => 'get')); ?>
				<div class="form-group">
				<label class="sr-only" for="query">Search Query</label>
				<?php echo $form->textField('query', glue::http()->param('query'), array('placeholder' => 'Search your videos', 'class' => 'form-control')) ?>
				</div><button class="btn btn-default submit_search">Search</button>
				
			<a class="btn  btn-link" href="#">Advanced Search</a>
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
			</div><span class='text-muted small amount_found'><?php echo $video_rows->count() ?> found</span>
			<div class="advnaced_search">
				<div class="row condition_row">
				<div class="form-group col-md-2 col-sm-2 col-xs-6 condition_field"><?php echo $form->selectbox('field[]', app\models\Video::advancedSearchFields(), null, array('class' => 'form-control')) ?></div>
				<div class="form-group col-md-2 col-sm-2 col-xs-6 condition_type" style='width:140px;'>
				<?php echo $form->selectbox('search_op[]', array('=' => '=', '>' => '>', '>=' => '>=', '<' => '<', '<=' => '<=', '!=' => '!=', 
					'in' => 'IN', 'not_in' => 'NOT IN', 'like' => 'LIKE', 'not_like' => 'NOT LIKE', 'null' => 'Is Empty'), null, array('class' => 'form-control')) ?>
				</div>
				<div class="form-group col-md-5 col-sm-5 col-xs-7 condition_value">
				<div class="textvalue-value"><?php echo $form->textfield('value[]', null, array('placeholder' => 'Enter Search Query', 'class' => 'form-control')) ?></div>
				</div>
				<div class="form-group col-md-2 col-sm-2 col-xs-3 condition_operand" style='width:140px;'>
				<?php echo $form->selectbox('operand[]', array('and' => 'AND', 'or' => 'OR', 'and_or' => 'AND OR'), null, array('class' => 'form-control')) ?>
				</div>
				<div class="form-group col-md-1 col-sm-1 col-xs-2"><a class="condition_remove" href="#">Remove</a></div>
				</div>
				<div><a href="#" class="btn btn-link">Add condition</a></div>
			</div>
			<?php $form->end() ?>
			</div>    	
    	<div class="clear"></div>
    </div>

	<?php ob_start(); ?>
		<div class='stickytoolbar-placeholder grey_sticky_toolbar'>
			<div class='stickytoolbar-bar'>
				<div class='inner_bar'>
					<div class='checkbox_button checkbox_input'><?php echo Html::checkbox('selectAll', 1, 0, array('class' => 'selectAll_input')) ?></div>
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