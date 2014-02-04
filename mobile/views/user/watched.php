<?php

use glue\Html;

$this->jsFile("/js/jquery.expander.js");
$this->JsFile('/js/jdropdown.js');
$this->JsFile('/js/playlist_dropdown.js');

$this->js('watched_page', "
	//$.playlist_dropdown({ multi_seek_parent: true });
		
		$('.dropdown-group').jdropdown();
		$('.playlist-dropdown').playlist_dropdown();			
		
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

		$.post('/user/removeWatched', params, null, 'json').done(function(data){
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
		
	$(document).on('click', '.grey_sticky_toolbar .btn_clear', function(){
		$.post('/user/clearWatched', {}, null, 'json').done(function(data){
			if(data.success){
				$('.grey_sticky_toolbar .block-alert').summarise('set', 'success','Your watched history has been cleared');
				$('.video_list').html('');
			}
		});			
	});			
		
	function reset_checkboxes(){
		$('.selectAll_input').prop('checked',true).trigger('click');
	}		
");
?>
<div class="user_history_body">
	<a class="btn btn-success btn-upload" href="<?php echo glue::http()->url('/video/upload', array(), glue::$params['uploadBase']) ?>">Add New Upload</a>
	<div class="advanced_filter_header">
    	<div class='search clearfix row'>
		<?php $form = Html::form(array('method' => 'get', 'class' => '')); ?>
			<div class="col-md-6 form-inline-col"><?php echo html::textfield('query',htmlspecialchars(glue::http()->param('query',null)),array('placeholder'=>'Enter keywords to search by', 'autocomplete'=>'off', 'class'=>'search form-control')) ?></div>
			<div class="col-md-3 form-inline-col"><input type="text" id="from" class="date form-control" name="from_date" placeholder="Enter start date" value="<?php echo htmlspecialchars(glue::http()->param('from_date',null)) ?>"/> <span class="sep">-</span> 
			<input type="text" id="to" class="date form-control" name="to_date" placeholder="Enter end date" value="<?php echo htmlspecialchars(glue::http()->param('to_date',null)) ?>"/></div>
			<div class="col-md-2 form-inline-col"><button class="btn btn-default">Search</button></div>
			<?php $form->end() ?>
		</div>		
    </div>	
	<?php ob_start(); ?>
		<div class='stickytoolbar-placeholder grey_sticky_toolbar'>
			<div class='stickytoolbar-bar'>
				<div class='inner_bar'>
					<div class='checkbox_button checkbox_input'><?php echo Html::checkbox('selectAll', 1, 0, array('class' => 'selectAll_input')) ?></div>
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
<div class="btn-group">
  <button type="button" class="btn btn-danger btn_delete">Delete</button>
  <button type="button" class="btn btn-danger dropdown-toggle" data-toggle="dropdown">
    <span class="caret"></span>
    <span class="sr-only">Toggle Dropdown</span>
  </button>
  <ul class="dropdown-menu" role="menu">
    <li><a href="#" class="btn_clear">Clear Queue</a></li>
  </ul>
</div>					
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

	<div class='video_list'>
	
	<?php if($items->count() > 0){
		echo glue\widgets\ListView::run(array(
		'pageSize'	 => 20,
		'page' 		 => glue::http()->param('page',1),
		"cursor"	 => $items,
		'callback' => function($i,$item,$view){
			$v=app\models\Video::findOne(array('_id' => $item['item']));
			if(!$v instanceof app\models\Video)
				$v = new app\models\Video;
			echo glue::controller()->renderPartial($view, array('model' => $item, 'custid' => $item['_id'], 'item' => $v, 'show_checkbox' => true,'admin'=>true));				
		},
		'itemView' 	 => 'video/_video_row',
		));
	}else{ ?><div class='no_results_found'>No watched history has been recorded</div><?php } ?>		
	</div>
</div>