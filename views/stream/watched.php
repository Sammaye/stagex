<?php

use glue\Html;

$this->jsFile("/js/jquery.expander.js");

$this->js('watched_page', "
	//$.playlist_dropdown({ multi_seek_parent: true });
	$(function(){
		$('div.expandable').expander({slicePoint: 120});
		
		$( '#from' ).datepicker({
			defaultDate: 0,
			dateFormat: 'dd/mm/yy',
			changeMonth: true,
			changeYear: true,
			numberOfMonths: 1,
			onClose: function( selectedDate ) {
				$( '#to' ).datepicker( 'option', 'minDate', selectedDate );
			}
		});
		
		$( '#to' ).datepicker({
			defaultDate: '+1w',
			dateFormat: 'dd/mm/yy',
			changeMonth: true,
			changeYear: true,
			numberOfMonths: 1,
			onClose: function( selectedDate ) {
				$( '#from' ).datepicker( 'option', 'maxDate', selectedDate );
			}
		});		
	});

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
		$('.video_list .video_row .checkbox_col input:checked').each(function(i,item){
			params['ids[]'][params['ids[]'].length]=$(item).val();
		});

		$.post('/history/deleteRated', params, null, 'json').done(function(data){
			if(data.success){
				$('.grey_sticky_toolbar .block-alert').summarise('set', 'success','The videos you selected were deleted');
				$.each(params['ids[]'],function(i,item){
					$('.video_list .video_row[data-id='+item+']').remove();
				});
				reset_checkboxes();
			}else{
				$('.grey_sticky_toolbar .block-alert').summarise('set', 'error','The videos you selected could not be deleted');
			}
		}, 'json');			
	});		
		
	function reset_checkboxes(){
		$('.selectAll_input').prop('checked',true).trigger('click');
	}		

	$(document).on('click', '.clear_all', function(event){
		event.preventDefault();
		$.getJSON('/history/remove_all_watched', function(data){
			if(data.success){
				$('.video_list').html(data.html);
			}
		});
	});
");
?>
<div class="user_history_body">
	<div class="tabs-nav">
		<ul>
			<li><a href="/user/videos">Uploads</a></li>
			<li><a href="/history/watched" class="selected">Watched</a></li>
			<li><a href="/history/rated">Liked</a></li>
			<li><a href="/history/rated?filter=dislikes">Disliked</a></li>
			<a style='float:right;' class="btn-success" href="<?php echo glue::http()->url('/video/upload', array(), glue::$params['uploadBase']) ?>">Add New Upload</a>
		</ul>
	</div>
	<div class="advanced_filter_header">   
    	<div class='search form-search form-search_subs'>
		<?php $form = Html::form(array('method' => 'get')); ?>
			<div class="search_input"><?php echo html::textfield('query',htmlspecialchars(glue::http()->param('query',null)),array('placeholder'=>'Search Videos', 'autocomplete'=>'off')) ?></div>
			<button class="submit_search"><span>&nbsp;</span></button>
		<?php $form->end() ?>
		</div>    	
		<div class="date_filter">
			<?php $form = Html::form(array('method' => 'get')); ?>
			<input type="text" id="from" name="from_date" placeholder="Select a from date"/> <span class="sep">-</span> 
			<input type="text" id="to" name="to_date" placeholder="Select a to date" />	<button class="btn">Apply</button>
			<?php $form->end() ?>
		</div>		
		<div class="clear"></div>
    </div>	
	<?php ob_start(); ?>
		<div class='stickytoolbar-placeholder grey_sticky_toolbar'>
			<div class='stickytoolbar-bar'>
				<div class='inner_bar'>
					<div class='checkbox_button checkbox_input'><?php echo Html::checkbox('selectAll', 1, 0, array('class' => 'selectAll_input')) ?></div>
					<button class='btn-grey selected_actions btn_delete'>Delete</button>
					<div class="btn-group dropdown-group playlist-dropdown">
						<button class='btn-grey add_to_playlist dropdown-anchor'>Add To <span class="caret">&#9660;</span></button>
						<div class="dropdown-menu">
							<div class="head_ribbon">
								<a href="#" data-id="<?php echo glue::user()->watchLaterPlaylist()->_id ?>" class='watch_later playlist_link'>Watch Later</a>
								<input type="text" placeholder="Search for Playlists" class="search_input"/>
							</div>
							<div class="playlist_results">
							<div class='item'>
								Search for playlists above
							</div>
							</div>
						</div>
					</div>
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

	<div class='video_list'>
		<?php
		if($items->count() > 0){
			foreach($items as $k => $item){
				$item = (Object)$item;
				$video = app\models\Video::model()->findOne(array('_id' => $item->item));
				if($video instanceof app\models\Video){
					echo $this->renderPartial('video/_video_row', array('item' => $video, 'custid' => $item->_id, 'model' => $item, 'show_checkbox' => true));
				}
			}
		}else{ ?>
			<div class='no_results_found'>No watched history has been recorded</div>
		<?php } ?>
	</div>
	<?php if($items->count() > 20){ ?>
		<a class='load_more' href='#'>Load more history</a>
	<?php } ?>
</div>