<?php
$this->jsFile('video_uploader', '/js/video_uploader.js');
$this->js('upload_video', "
	$(function(){

		// Add a new upload form
		add_upload();

		$(document).on('change', '.upload_item input[type=file]', function(event){

			reset_bar_message($(this).parents('.upload_item'));

			if(check_upload($(this).parents('.upload_item').data().id)){
				$(this).parents('.upload_item').find('.uploadForm').hide();
				$(this).parents('.upload_item').find('.uploading_pane').show();
				$(this).parents('form').submit();
				add_upload();
			}else{
				alert('The file you selected did not match our requirements. Please try a different file.');
			}
		});

		$(document).on('click', '.upload_item .uploadBar .cancel', function(event){
			event.preventDefault();
			stop_upload($(this).parents('.upload_item').data().id);
		});

		$(document).on('click', '.upload_item .form_top .remove a', function(event){
			event.preventDefault();
			remove_upload($(this).parents('.upload_item').data().id);
		});

		$(document).on('click', '.toggle_panel', function(event){
			event.preventDefault();
			var form = $(this).parents('.upload_item').find('.upload_details');

			if(form.css('display') == 'block'){
				form.css({ 'display': 'none' });
				$(this).text('Show More Options');
			}else{
				form.css({ 'display': 'block' });
				$(this).text('Show Less Options');
			}
		});

		$(document).on('click', '.save_video_details', function(event){
			event.preventDefault();
			var this_o = $(this),
				data_a = this_o.parents('.upload_details').find('select, textarea, input').serializeArray();
			data_a[data_a.length] = {name: 'upload_id', value: this_o.parents('.upload_item').data().id};

			$.post('/video/upload_info_save', data_a, function(data){
				if(data.success){
					forms.summary(this_o.parents('.upload_details').find('.block_summary'), true,
						'The details to this upload were saved.', data.errors);
				}else{
					forms.summary(this_o.parents('.upload_details').find('.block_summary'), false,
						'The details to this upload could not be saved because:', data.errors);
				}
			}, 'json');
		});

		$(document).on('click', '.upload_item .bar_summary .close', function(event){
			event.preventDefault();
			reset_bar_message($(this).parents('.upload_item'));
		});
	});
");
?>


<div class="container_16 upload_video_body">
	<div class='upload_cap_not'><div class='float_left'><?php
		echo  $model->bandwidthLeft > 0 ? convert_size_human($model->bandwidthLeft) : 0 ?>/<?php echo convert_size_human($model->get_allowed_bandwidth()) ?> left renewing on 
		<?php echo $model->getTs($model->nextBandwidthTopup,'l') ?><span>This figure is not realtime so please plan ahead.</span></div>
		<div class='float_right'>Max file size: 500MB</div>
	</div>

	<div class="upload_container">
		<div id="uploadForm_container-outer"></div>
		<div id="u_iframe_container" class="u_iframe_container"></div>
	</div>
</div>