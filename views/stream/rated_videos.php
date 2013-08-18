<?php

use glue\Html;

$this->JsFile("/js/jquery.expander.js");
$this->JsFile('/js/jdropdown.js');

$this->js('page', "
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
");
?>
<div class="rated_videos_body">
	<div class="tabs-nav">
		<ul>
			<li><a href="/user/videos">Uploads</a></li>
			<li><a href="/history/watched">Watched</a></li>
			<li><a href="/history/rated" <?php if(glue::http()->param('filter',null)!=='dislikes') echo 'class="selected"'; ?>>Liked</a></li>
			<li><a href="/history/rated?filter=dislikes" <?php if(glue::http()->param('filter',null)==='dislikes') echo 'class="selected"'; ?>>Disliked</a></li>
			<a style='float:right;' class="btn-success btn-upload" href="<?php echo glue::http()->url('/video/upload', array(), glue::$params['uploadBase']) ?>">Add New Upload</a>
		</ul>
	</div>

	<div class="advanced_filter_header">   
    	<div class='search'>
		<?php $form = Html::form(array('method' => 'get')); ?>
			<?php echo html::textfield('query',htmlspecialchars(glue::http()->param('query',null)),array('placeholder'=>'Enter keywords to search by', 'autocomplete'=>'off', 'class'=>'search')) ?>
			<input type="text" id="from" class="date" name="from_date" placeholder="Enter start date"/> <span class="sep">-</span> 
			<input type="text" id="to" class="date" name="to_date" placeholder="Enter end date" />	<button class="btn">Search</button>
			<?php $form->end() ?>
		</div>		
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
		));
	?>

	<div class='video_list'>
	<?php if($items->count() > 0){
		glue\widgets\ListView::widget(array(
		'pageSize'	 => 20,
		'page' 		 => glue::http()->param('page',1),
		"cursor"	 => $items,
		'callback' => function($i,$item,$view){
			$v=app\models\Video::model()->findOne(array('_id' => $item['item']));
			if(!$v instanceof app\models\Video)
				$v = new app\models\Video;
			echo glue::$controller->renderPartial($view, array('model' => $item, 'custid' => $item['_id'], 'item' => $v, 'show_checkbox' => true));				
		},
		'itemView' 	 => 'video/_video_row',
		));
	}else{ ?><div class='no_results_found'>No videos were found</div><?php } ?>		
	</div>
</div>