<?php
use glue\Html;

$this->jsFile('/js/jdropdown.js');
$this->js('admin', "
	$('.grey_sticky_toolbar .block-alert').summarise()
	$(function(){
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
								
	$('.selectAll_input').click(function(){
		if($(this).prop('checked')==true){
			$('.video_response_list .checkbox_col input:checkbox').prop('checked', false).trigger('click');
		}else{
			$('.video_response_list .checkbox_col input:checkbox').prop('checked', true).trigger('click');
		}
	});			

	$(document).on('click', '.stickytoolbar-bar .btn_delete', function(event){
		params={'ids[]':[]};
		$('.video_response_list .checkbox_col input:checked').each(function(i,item){
			params['ids[]'][params['ids[]'].length]=$(item).val();
		});
		params['video_id']='".$model->_id."';

		$.post('/videoresponse/delete', params, null, 'json').done(function(data){
			if(data.success){
				$('.grey_sticky_toolbar .block-alert').summarise('set', 'success','The responses you selected were deleted');
				$.each(params['ids[]'],function(i,item){
					$('.video_response_list .response[data-id='+item+']').remove();
				});
				reset_checkboxes();
			}else{
				$('.grey_sticky_toolbar .block-alert').summarise('set', 'error','The responses you selected could not be deleted');
			}
		}, 'json');			
	});

	$(document).on('click', '.stickytoolbar-bar .btn_approve', function(event){
		params={'ids[]':[]};
		$('.video_response_list .checkbox_col input:checked').each(function(i,item){
			params['ids[]'][params['ids[]'].length]=$(item).val();
		});
		params['video_id']='".$model->_id."';
		
		$.post('/videoresponse/approve', params, null, 'json').done(function(data){
			if(data.success){
				$('.grey_sticky_toolbar .block-alert').summarise('set', 'success','The responses you selected were approved');
				$.each(params['ids[]'],function(i,item){
					$('.video_response_list .response[data-id='+item+']').find('.btn_pending').remove();
					$('.video_response_list .response[data-id='+item+']').find('.btn_approved').css({display:'inline-block'});
				});
				reset_checkboxes();
			}else{
				$('.grey_sticky_toolbar .block-alert').summarise('set', 'error','The responses you selected could not be approved');
			}
		}, 'json');			
	});		
") ?>

<div class='list_responses_body'>
	<h1 style='margin:0 0 20px 0;'>Responses to <a href='<?php echo glue::http()->url('/video/watch', array('id' => $model->_id)) ?>'><?php echo html::encode($model->title) ?></a></h1>
	<div class="tabs-nav videos_nav_top">
		<ul>
			<li><a href="<?php echo glue::http()->url('/videoresponse/list',array('id'=>$model->_id)) ?>" <?php if($pending===false) echo 'class="selected"'; ?>>Approved <span class="badge"><?php echo $model->totalResponses ?></span></a></li>
			<li><a href="<?php echo glue::http()->url('/videoresponse/pending',array('id'=>$model->_id)) ?>" <?php if($pending) echo 'class="selected"'; ?>>Pending <span class="badge"><?php echo '2' ?></span></a></li>
		</ul>
	</div>	

	<div class="advanced_filter_header">   
    	<div class='search'>
		<?php $form = Html::form(array('method' => 'get')); ?>
			<?php app\widgets\Jqautocomplete::widget(array(
					'attribute' => 'query',
					'value' => urldecode(htmlspecialchars(isset($_GET['query']) ? $_GET['query'] : '')),
					'placeholder' => 'Enter author name to search by',
					'options' => array(
						'appendTo' => '#user_results',
						'source' => '/user/searchSuggestions',
						'minLength' => 2,
					),
					'renderItem' => "
						return $( '<li></li>' )
							.data( 'item.autocomplete', item )
							.append( '<a class=\'content\'><span>' + item.label + '</span></div></a>' )
							.appendTo( ul );
			"))  ?>
			<input type="text" id="from" class="date" name="from_date" placeholder="Enter start date" value="<?php echo htmlspecialchars(glue::http()->param('from_date',null)) ?>"/> <span class="sep">-</span> 
			<input type="text" id="to" class="date" name="to_date" placeholder="Enter end date" value="<?php echo htmlspecialchars(glue::http()->param('to_date',null)) ?>"/>	<button class="btn">Search</button>
			<?php $form->end() ?>
		</div>		
    </div>	

	<?php ob_start(); ?>
		<div class='stickytoolbar-placeholder grey_sticky_toolbar'>
			<div class='stickytoolbar-bar'>
				<div class='inner_bar'>
					<?php if(glue::auth()->check(array('^' => $model))): ?>
					<div class='checkbox_button checkbox_input'><?php echo Html::checkbox('selectAll', 1, 0, array('class' => 'selectAll_input')) ?></div>
					<button class='btn btn-success selected_actions btn_approve'>Approve</button>
					<button class='btn-grey selected_actions btn_delete'>Delete</button>
					<?php endif; ?>
					<a href="<?php echo glue::http()->url(array("sort"=>'created','order'=>'-1')) ?>">Newest</a>
					<a href="<?php echo glue::http()->url(array("sort"=>'likes','order'=>'-1')) ?>">Most Liked</a>
					<a href="<?php echo glue::http()->url(array("filter"=>'type','filter_value'=>'text')) ?>">Text (<?php echo $model->totalTextResponses ?>)</a>
					<a href="<?php echo glue::http()->url(array("filter"=>'type','filter_value'=>'video')) ?>">Video (<?php echo $model->totalVideoResponses ?>)</a>
				</div>
				<div class="alert block-alert"></div>
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
	<div style='margin:20px 0 0;'> 
	<?php echo $this->renderPartial('response/list', array('model' => $model, 'mode' => 'admin', 'comments' => $comments, 'pageSize' => 30, 'hideSelector' => $pending?true:false)) ?>
	</div>
</div>

<div id="user_results"></div>
<div id='video_response_options' style='width:200px;'></div>
<div id='videoResponse_results' class=''></div>