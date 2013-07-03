<?php
$this->jsFile('video_uploader', '/js/views/video_uploader.js');
$this->js('upload_video', "
	$(function(){

		// Add a new upload form
		add_upload();

		$(document).on('click', '.upload .uploadBar .cancel', function(event){
			event.preventDefault();
			stop_upload($(this).parents('.upload_item').data().id);
		});

		$(document).on('click', '.upload .form_top .remove a', function(event){
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

		$(document).on('click', '.upload .bar_summary .close', function(event){
			event.preventDefault();
			reset_bar_message($(this).parents('.upload_item'));
		});
	});
");
?>


<div>
	<div class='upload_cap_not'>
	<div style='float:left;'><span style='font-size:18px; line-height:24px;'><?php
		echo  $model->bandwidthLeft > 0 ? convert_size_human($model->bandwidthLeft) : 0 ?>/<?php echo convert_size_human($model->get_allowed_bandwidth()) ?> left renewing on 
		<?php echo date('l',$model->nextBandwidthTopup) ?></span><p class="light" style='font-size:12px;'>This figure is not realtime</p>
	</div>
	<div style='float:right; font-size:18px; line-height:24px;'>Max file size: 500MB</div>
	<div class="clear"></div>
	</div>

	<div class="upload_list"></div>
	<div id="u_iframe_container" class="u_iframe_container"></div>
</div>