<?php
$this->jsFile('video_uploader', '/js/views/video_uploader.js');
$this->js('upload_video', "
	$(function(){

		// Add a new upload form
		add_upload();

		$(document).on('click', '.upload_details .btn-success', function(event){
			event.preventDefault();
			var this = $(this),
				data = this.parents('form').find('select, textarea, input').serializeArray();
			data[data.length] = {name: 'upload_id', value: this.parents('.upload').data().id};

			$.post('/video/ajaxSave', data, function(data){
				if(data.success){
					forms.summary(this_o.parents('.upload_details').find('.block_summary'), true,
						'The details to this upload were saved.', data.errors);
				}else{
					forms.summary(this_o.parents('.upload_details').find('.block_summary'), false,
						'The details to this upload could not be saved because:', data.errors);
				}
			}, 'json');
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