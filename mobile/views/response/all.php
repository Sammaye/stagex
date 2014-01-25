<?php
use glue\Html;

$this->jsFile('/js/jdropdown.js');
$this->jsFile('/js/select2/select2.js');
$this->cssFile('/js/select2/select2.css');
$this->js('admin', "
		$('.dropdown-group').jdropdown();
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

	function reset_checkboxes(){
		$('.selectAll_input').prop('checked',true).trigger('click');
	}		
		
	$('#filter-username').select2({
		placeholder: 'Search upto 5 usernames',
		minimumInputLength: 3,
		maximumSelectionSize: 5,
		multiple: true,
		ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
			url: '".glue::http()->url('/user/ajaxsearch')."',
			dataType: 'json',
			data: function (term, page) {
				return {
					term: term, // search term
					limit: 10,
				};
			},
			results: function (data, page) {
				$(data.users).each(function(i){
					data.users[i]={id:this._id['\$id'],text:this.username};
				});
		console.log(data);
				return {results: data.users};
			}
		},
        initSelection: function(element, callback) {
            var data = [];
			var new_value='';
            $((element.val()||'').split(',')).each(function(i) {
                var item = this.split(':');
                data.push({
                    id: item[0],
                    text: item[1]
                });
				new_value=item[0]+',';
            });
            $(element).val(new_value.replace(/,+$/,''));
            callback(data);
        }		
	});
") ?>

<div class='list_responses_body'>
	<h3 style='margin:0 0 20px 0;'>Responses to <a href='<?php echo glue::http()->url('/video/watch', array('id' => $model->_id)) ?>'><?php echo html::encode($model->title) ?></a></h3>
	
	<?php //echo $this->renderPartial('response/_selector',array('model'=>$model)); ?>
	
	<?php if(glue::auth()->check(array('^'=>$model))){ ?>
	<div class="videos_nav_top">
		<ul class="nav nav-tabs">
			<li <?php if($pending===false) echo 'class="active"'; ?>><a href="<?php echo glue::http()->url('/videoresponse/list',array('id'=>$model->_id)) ?>">Approved</a></li>
			<li <?php if($pending) echo 'class="active"'; ?>><a href="<?php echo glue::http()->url('/videoresponse/pending',array('id'=>$model->_id)) ?>">Pending 
			<span class="badge"><?php echo $model->getRelated('responses', array('videoId'=>$model->_id,'approved'=>false))->count() ?></span></a></li>
		</ul>
	</div>		
	<?php } ?>

	<div class="advanced_filter_header">   
    	<div class='search clearfix row'>
		<?php $form = Html::form(array('method' => 'get', 'class' => '')); ?>
			<?php echo $form->hiddenField('id',$model->_id) ?>
			<div class='col-md-3 long_input form-inline-col'><?php echo html::textfield('filter-keywords',htmlspecialchars(glue::http()->param('filter-keywords',null)),array('placeholder'=>'Enter keywords to search by', 'autocomplete'=>'off', 'class'=>'search form-control')) ?></div>
			<div class='col-md-3 long_input form-inline-col'><input type="hidden" id="filter-username" name="filter-username" value="<?php echo $username_filter_string ?>" style="width:100%;"/></div>
			<div class="col-md-3 form-inline-col"><input type="text" id="from" class="date form-control" name="from_date" placeholder="Enter start date" value="<?php echo htmlspecialchars(glue::http()->param('from_date',null)) ?>"/> <span class="sep">-</span> 
			<input type="text" id="to" class="date form-control" name="to_date" placeholder="Enter end date" value="<?php echo htmlspecialchars(glue::http()->param('to_date',null)) ?>"/></div>
			<div class="col-md-1 form-inline-col"><button class="btn btn-default">Search</button></div>
			<?php $form->end() ?>
		</div>		
    </div>		

	<?php ob_start(); ?>
		<div class='stickytoolbar-placeholder grey_sticky_toolbar'>
			<div class='stickytoolbar-bar'>
				<div class='inner_bar row'>
				<div class="col-md-4">
					<?php if(glue::auth()->check(array('^' => $model))): ?>
					<div class='checkbox_button checkbox_input'><?php echo Html::checkbox('selectAll', 1, 0, array('class' => 'selectAll_input')) ?></div>
					<button class='btn btn-success selected_actions btn_approve'>Approve</button>
					<button class='btn-danger btn selected_actions btn_delete'>Delete</button>
					<?php endif; ?>
				</div>
					<div class="col-md-8">
					<div class="dropdown-group btn_sort">
						<button class='btn btn-default dropdown-anchor'>Sort<?php
						if(glue::http()->param('sorton')=='created'){
							if(glue::http()->param('orderby')==-1)
								echo ': Newest';
							elseif(glue::http()->param('orderby')==1)
								echo ": Oldest";
						}elseif(glue::http()->param('sorton')=='likes'){
							if(glue::http()->param('orderby')==-1)
								echo ': Liked';
							elseif(glue::http()->param('orderby')==1)
								echo ": Disliked";
						}
						?> <span class="caret"></span></button>
						<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu1">
						<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('sorton'=>'created','orderby'=>-1)) ?>">Newest</a></li>
						<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('sorton'=>'created','orderby'=>1)) ?>">Oldest</a></li>
						<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('sorton'=>'likes','orderby'=>-1)) ?>">Liked</a></li>
						<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('sorton'=>'likes','orderby'=>1)) ?>">Disliked</a></li>
						</ul>
				</div>
				<a class="btn btn-link btn_sort <?php echo glue::http()->param('filter-type')=='video'?'active':'' ?>" href="<?php echo glue::http()->url(array('filter-type'=>'video')) ?>">Video</a>
				<a class="btn btn-link btn_sort <?php echo glue::http()->param('filter-type')=='text'?'active':'' ?>" href="<?php echo glue::http()->url(array('filter-type'=>'text')) ?>">Text</a>
				<a class="btn btn-link btn_sort" href="<?php echo glue::http()->url(array('filter-type'=>'all')) ?>">All</a>
				</div>
				</div>
				<div class="alert block-alert"></div>
			</div>
		</div>
		<?php $html = ob_get_contents();
	ob_end_clean();
	echo app\widgets\stickytoolbar::run(array(
		"element" => '.grey_sticky_toolbar',
		"options" => array(
			'onFixedClass' => 'grey_sticky_bar-fixed'
		),
		'html' => $html
	)); ?>
	<div style='margin:20px 0 0;'> 
	<?php echo $this->renderPartial('response/list', array('model' => $model, 'mode' => 'admin', 'comments' => $comments, 'pageSize' => 30)) ?>
	</div>
</div>

<div id="user_results"></div>
<div id='video_response_options' style='width:200px;'></div>
<div id='videoResponse_results' class=''></div>