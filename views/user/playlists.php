<?php
use glue\Html;

ob_start(); 
$model=new app\models\Playlist; ?>
<div class="create_playlist_form">
<div class="alert"></div>
<?php $form = html::activeForm(array('id' => 'create_form')) ?>
<div class="form-stacked form_left">
	<div class="form_row"><?php echo html::label('Title', 'title') ?><?php echo html::activeTextField($model, 'title') ?></div>
	<div class="form_row"><?php echo html::label('Description', 'description')?><?php echo html::activeTextarea($model, 'description') ?></div>			
	<input type="submit" class="btn-success btn_create" value="Create Playlist"/>
</div>
<div class='form_right'>
	<h4>Listing</h4>
	<?php $grp = html::activeRadio_group($model, 'listing') ?>
	<div class="label_options">
		<label class="radio"><?php echo $grp->add(0) ?>Listed</label>
		<p class='light'>Your video is public to all users of StageX</p>
		<label class="radio"><?php echo $grp->add(1) ?>Unlisted</label>
		<p class='light'>Your video is hidden from listings but can still be accessed directly using the video URL</p>
		<label class="radio"><?php echo $grp->add(2) ?>Private</label>
		<p class='light'>No one but you can access this video</p>
	</div>
	<label class="checkbox"><?php echo $form->checkbox($model, 'allowFollowers',1)?>Allow people to follow this playlist</label>
</div>
<div class="clear"></div>
<?php $form->end() ?>
</div>
<?php $createModal=ob_get_clean();


$this->JsFile("/js/jquery.expander.js");
$this->jsFile('/js/jdropdown.js');
$this->jsFile("/js/modal.js");
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
		
	$(document).on('click', '.btn_modal', function(e){
		event.preventDefault();
		$.modal({html:".js_encode($createModal)."});
	});
		
	$(document).on('submit', '#create_form', function(){
		$.post('/playlist/create', $(this).serialize(), function(data){
			if(data.success){
				window.location='/playlist/edit?id='+data._id;
			}else{
				$('.create_playlist_form .alert').summarise();
				$('.create_playlist_form .alert').summarise('set', 'error', {message:data.message,list:data.messages});	
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

	<div class="tabs-nav videos_nav_top">
		<ul>
			<li><a href="/user/playlists" class="selected">My Playlists</a></li>
			<li><a href="/playlist/followed">Followed</a></li>
		</ul>
		<a class="btn-success btn-upload btn_modal" href="<?php echo glue::http()->url('/playlist/create') ?>">Add Playlist</a>
	</div>

	<div class="header">   
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
		<div class="clear"></div>
		</div>    	
    </div>
    
    <div class="mass_edit_form">
    	<?php $form=Html::activeForm(); 
    	$pModel=new app\models\Playlist(); ?>
	   	<div class="header">
    		<h3>Edit Playlists</h3>
    		<input type="button" class="btn-success save" value="Save"/>
    		<input type="button" class="btn-grey cancel" value="Cancel"/>
    		<div class="clear"></div>
    	</div>    	
    	
    	<div class='alert'></div>
    	
    	<div class="mass_edit_block form-stacked">
    		<a href="#" class="edit">+ Edit Title</a>
    		<div class="form">
    		<a href="#" class="remove">Remove</a>
    		<div class="right">    	
    		<label>Title:</label><?php echo $form->textField($pModel,'title') ?>
    		</div></div><div class="clear"></div>
    	</div>
    	<div class="mass_edit_block form-stacked">
    		<a href="#" class="edit">+ Edit Description</a>
    		<div class="form">
    		<a href="#" class="remove">Remove</a>
    		<div class="right">
    		<label>Description:</label><?php echo $form->textArea($pModel,'description') ?>
    		</div></div><div class="clear"></div>
    	</div>
    	<div class="mass_edit_block">
    		<a href="#" class="edit">+ Edit Listing</a>
    		<div class="form">
    		<a href="#" class="remove">Remove</a>
    		<div class="right">
			<?php $grp = html::activeRadio_group($pModel, 'listing') ?>
			<label class="radio"><?php echo $grp->add(0) ?>Listed</label>
			<p class='light'>Your video is public to all users of StageX</p>
			<label class="radio"><?php echo $grp->add(1) ?>Unlisted</label>
			<p class='light'>Your video is hidden from listings but can still be accessed directly using the video URL</p>
			<label class="radio"><?php echo $grp->add(2) ?>Private</label>
			<p class='light'>No one but you can access this video</p>
			</div>
			</div><div class="clear"></div>
    	</div>
    	<div class="mass_edit_block">
    		<a href="#" class="edit">+ Edit Followable</a>
    		<div class="form">
    		<a href="#" class="remove">Remove</a>
    		<div class="right">    	
    		<label class="checkbox"><?php echo $form->checkbox($pModel,'allowFollowers', 1) ?>Allow users to follow my playlist</label>
    		</div></div><div class="clear"></div>
    	</div>
    	<?php $form->end(); ?>
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
	<div class='playlist_list'>
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