<?php 
glue::controller()->js('page',"
	$(function(){
		//$('div.expandable').expander({slicePoint: 120});
		
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
");
?>
<div class='profile_videos_body'>

	<div class="advanced_filter_header user_profile_main_nav">   
    	<div class='search clearfix'>
		<?php $form = html::form(array('method' => 'get')); ?>
			<?php echo html::textfield('query',htmlspecialchars(glue::http()->param('query',null)),array('placeholder'=>'Enter keywords to search by', 'autocomplete'=>'off', 'class'=>'search form-control')) ?>
			<input type="text" id="from" class="date form-control" name="from_date" placeholder="Enter start date" value="<?php echo htmlspecialchars(glue::http()->param('from_date',null)) ?>"/> <span class="sep">-</span> 
			<input type="text" id="to" class="date form-control" name="to_date" placeholder="Enter end date" value="<?php echo htmlspecialchars(glue::http()->param('to_date',null)) ?>"/>	<button class="btn btn-default">Search</button>
			<?php $form->end() ?>
		</div>		
    </div>

	<div class='video_list'>
		<?php
		if($sphinx_cursor->totalFound> 0){
			glue\widgets\ListView::run(array(
			'pageSize'	 => 20,
			'page' 		 => isset($_GET['page']) ? $_GET['page'] : 1,
			"cursor"	 => $sphinx_cursor,
			'itemView' 	 => 'video/_video_tile.php',
			));
		}else{ ?>
			<div class='no_results_found'>No public videos were found</div>
		<?php } ?>			
	</div>
</div>