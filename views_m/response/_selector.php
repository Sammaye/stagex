<div class='video_response_selector' style='margin:10px 0 30px 0;'>
	<div class='alert' style='display:none;'></div>
	<?php if(($model->allowTextComments || $model->allowVideoComments) && glue::auth()->check(array('@')) && (!isset($hideSelector)||$hideSelector===false)){ ?>
		<?php if($model->allowTextComments){ ?>
			<div class='response_pane text_response_content'>
				<div><?php echo app\widgets\autoresizetextarea::run(array(
					'attribute' => 'text_comment_content', 'class' => 'text_comment_content form-control'
				)) ?></div><input type="button" value="Post Response" class="btn btn-success post_response" style='margin-top:10px;'/>
			</div>
		<?php } ?>
		<div class="clear"></div>
	<?php } ?>
</div>