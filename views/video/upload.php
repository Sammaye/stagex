<?php
$this->jsFile('video_uploader', '/js/views/video_uploader.js');
$this->js('upload', "
	$(function(){
		// Add a new upload form
		add_upload();
	});
");
?>

<div>
	<div class='upload_caption'>
	<div class="left"><span><?php
		echo  $model->bandwidthLeft > 0 ? convert_size_human($model->bandwidthLeft,false) : 0 ?>/<?php echo convert_size_human($model->get_allowed_bandwidth()) ?> left renewing on 
		<?php echo date('l',$model->nextBandwidthTopup) ?></span><p class="light">This figure is not realtime</p>
	</div>
	<div class="right">Max file size: 500MB</div>
	<div class="clear"></div>
	</div>

	<div class="upload_list"></div>
	<div id="u_iframe_container" class="u_iframe_container"></div>
</div>