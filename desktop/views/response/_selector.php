<div class='video_response_selector clearfix' style='margin:10px 0 30px 0;'>
	<div class='alert' style='display:none;'></div>
	<?php if(($model->allowTextComments) && glue::auth()->check(array('@')) && (!isset($hideSelector) || $hideSelector === false)){ ?>
	<div class='response_pane text_response_content'>
		<div><?php echo app\widgets\AutosizeTextarea::run(array(
		'attribute' => 'text_comment_content', 'class' => 'text_comment_content form-control'
		)) ?></div><input type="button" value="Post Response" class="btn btn-success post_response" style='margin-top:10px;'/>
	</div>
	<?php } ?>
</div>