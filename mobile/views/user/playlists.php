<?php
use glue\Html;

$this->JsFile("/js/jquery.expander.js");
$this->jsFile('/js/jdropdown.js');
$this->js('new_playlist', "
		
	$('.expandable').expander({slicePoint:40});
	$('.grey_sticky_toolbar .block-alert').summarise()
	$('.mass_edit_form .alert').summarise();

	$('.selectAll_input').click(function(){
		if($(this).prop('checked')==true){
			$('.playlist_list input:checkbox').prop('checked', false).trigger('click');
		}else{
			$('.playlist_list input:checkbox').prop('checked', true).trigger('click');
		}
	});
		
	$(document).on('submit', '#create_form', function(){
		$.post('/playlist/create', $(this).serialize(), function(data){
			if(data.success){
				window.location='/playlist/view?id='+data._id;
			}else{
				$('.create_playlist_form .alert').summarise();
				$('.create_playlist_form .alert').summarise('set', 'error', {message:'<p>'+data.message+'</p>',list:data.messages});	
			}
		}, 'json');
		return false;
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
		$('.playlist_list .playlist .checkbox_col input:checked').each(function(i,item){
			params[params.length]={name:['ids['+id_length+']'],value:$(item).val()};
			id_length++;
		});		
	
		$.post('/playlist/batchSave', params, null, 'json').done(function(data){
			if(data.success){
				$('.mass_edit_form .alert').summarise('set', 'success', data.updated + ' of ' + data.total + ' of the playlists you selected were saved');
			}else{
				$('.mass_edit_form .alert').summarise('set', 'error', 'The playlists you selected could not be saved');
			}
		});
	});		

	$(document).on('click', '.btn_delete', function(event){
		params={'ids[]':[]};
		$('.playlist_list .playlist .checkbox_col input:checked').each(function(i,item){
			params['ids[]'][params['ids[]'].length]=$(item).val();
		});

		$.post('/playlist/batchDelete', params, null, 'json').done(function(data){
			if(data.success){
				$('.grey_sticky_toolbar .block-alert').summarise('set', 'success','The playlists you selected were deleted');
				$.each(params['ids[]'],function(i,item){
					$('.playlist_list .playlist[data-id='+item+']').remove();
					//$('.playlist_list .playlist[data-id='+item+']').children().not('.deleted').css({display:'none'});
					//$('.playlist_list .playlist[data-id='+item+'] .deleted').css({display:'block'});
				});
				$('.grey_sticky_toolbar .block-alert').summarise('set', 'success',
					'The playlists you selected have been deleted');		
				reset_checkboxes();
			}else{
				$('.grey_sticky_toolbar .block-alert').summarise('set', 'error','The playlists you selected could not be deleted');
			}
		}, 'json');			
	});
		
	function reset_checkboxes(){
		$('.selectAll_input').prop('checked',true).trigger('click');
	}		
"); ?>
<div class="user_playlists_page">
	<a class="btn btn-success btn-upload" data-toggle="modal" data-target="#myModal">Add New Playlist</a>
	<div class="header">
    		<div class='search form-search'>
			<div class="form-group"><?php $form = Html::form(array('method' => 'get')); ?>
			<label class="sr-only" for="query">Search Query:</label>
				<?php echo $form->textField('query', glue::http()->param('query'), array('placeholder' => 'Search your playlists', 'class' => 'form-control')) ?>
			</div><button class="btn btn-default submit_search">Search</button>
			<div class="btn-group">
				<button type="button" class="btn btn-link dropdown-toggle"
					data-toggle="dropdown">
					Sort<?php if(glue::http()->param('sorton') == 'created'){
						if(glue::http()->param('orderby') == -1)
							echo ': Newest';
						elseif(glue::http()->param('orderby') == 1)
							echo ": Oldest";
					}elseif(glue::http()->param('sorton') == 'followers'){
						echo ': Followers';
					}elseif(glue::http()->param('sorton') == 'totalVideos'){
						echo ': Videos';
					} ?>
					<span class="caret"></span>
				</button>
				<ul class="dropdown-menu" role="menu"
					aria-labelledby="dropdownMenu1">
					<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('sorton'=>'created','orderby'=>-1)) ?>">Newest</a></li>
					<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('sorton'=>'created','orderby'=>1)) ?>">Oldest</a></li>
					<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('sorton'=>'followers','orderby'=>-1)) ?>">Followers</a></li>
					<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('sorton'=>'totalVideos','orderby'=>-1)) ?>">Videos</a></li>
				</ul>
			</div><span class='text-muted small amount_found'><?php echo $playlist_rows->count() ?> found</span>
			<?php $form->end() ?>
			</div>    	
    </div>	
    
	<?php ob_start(); ?>
		<div class='stickytoolbar-placeholder grey_sticky_toolbar'>
			<div class='stickytoolbar-bar'>
				<div class='inner_bar'>
					<div class='checkbox_button checkbox_input'><?php echo Html::checkbox('selectAll', 1, 0, array('class' => 'selectAll_input')) ?></div>
					<button class='btn btn-danger selected_actions btn_delete'>Delete</button>
				</div>
				<div class="alert block-alert" style='display:none;'></div>
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
	<div class='playlist_list'>
	<?php if($playlist_rows->count() > 0){
		echo glue\widgets\ListView::run(array(
			'pageSize'	 => 20,
			'sortableAttributes' => array('followers', 'totalVideos', 'created'),
			'page' 		 => glue::http()->param('page',1),
			"cursor"	 => $playlist_rows,
			'itemView' 	 => 'playlist/_playlist.php',
		));
	}else{ ?>
		<div class='no_results_found'>No playlists were found for you</div>
	<?php } ?>
	</div>
</div>

<!-- Create Playlist Modal -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog create_playlist_modal">
  <?php $model=new app\models\Playlist; ?>
  <?php $form = html::activeForm(array('id' => 'create_form')) ?>
    <div class="modal-content">
      <div class="modal-body create_playlist_form row clearfix">
		<div class="alert"></div>
		<div class="col-md-6">
			<div class="form-group"><?php echo $form->label($model, 'title', 'Title') ?><?php echo html::activeTextField($model, 'title', 'form-control') ?></div>
			<div class="form-group"><?php echo $form->label($model, 'description', 'Description') ?><?php echo html::activeTextarea($model, 'description', 'form-control') ?></div>			
		</div>
		<div class='col-md-6'>
			<h4>Listing</h4>
			<?php $grp = html::activeRadio_group($model, 'listing') ?>
			<div class="label_options">
				<label class="radio"><?php echo $grp->add(0) ?>Listed</label>
				<p class='text-muted'>Your video is public to all users of StageX</p>
				<label class="radio"><?php echo $grp->add(1) ?>Unlisted</label>
				<p class='text-muted'>Your video is hidden from listings but can still be accessed directly using the video URL</p>
				<label class="radio"><?php echo $grp->add(2) ?>Private</label>
				<p class='text-muted'>No one but you can access this video</p>
			</div>
		</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-success">Create Playlist</button>
      </div>
    </div>
    <?php $form->end() ?>
  </div>
</div>