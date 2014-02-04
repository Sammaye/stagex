<?php 

glue::controller()->jsFile('/js/select2/select2.js');
glue::controller()->cssFile('/js/select2/select2.css');

glue::controller()->js('dfg', "
	$('#video_response_id').select2({
		placeholder: 'Select a video',
		minimumInputLength: 3,
		width:400,
		multiple: false,
		ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
			url: '".glue::http()->url('/video/suggestions')."',
			dataType: 'json',
			data: function (term, page) {
				return {
					term: term, // search term
					limit: 10,
				};
			},
			results: function (data, page) {
				$(data.results).each(function(i){
					data.results[i]={id:this._id['\$id'],text:this.title};
				});
				return {results: data.results};
			}
		},
        initSelection: function(element, callback) {
            var data = [];
            $((element.val()||'').split(',')).each(function(i) {
                var item = this.split(':');
                data.push({
                    id: item[0],
                    title: item[1]
                });
            });
            //$(element).val('');
            callback(data);
        }		
	});		
");

?>

<div class='video_response_selector' style='margin:10px 0 30px 0;'>
	<div class='alert' style='display:none;'></div>
	<?php if(($model->allowTextComments || $model->allowVideoComments) && glue::auth()->check(array('@')) && (!isset($hideSelector)||$hideSelector===false)){ ?>
		<ul class="tabs">
			<li>Respond with:</li>
			<?php if($model->allowTextComments): ?><li><a href="#" id="text_response_tab" class="response_tab text_response_tab selected">Comment</a></li><?php endif ?>
			<?php if($model->allowVideoComments): ?><li><a href="#" id="video_response_tab" class="response_tab video_response_tab">Video</a></li><?php endif ?>
		</ul>
		<?php if($model->allowTextComments){ ?>
			<div class='response_pane text_response_content'>
				<div><?php echo app\widgets\AutosizeTextarea::run(array(
					'attribute' => 'text_comment_content', 'class' => 'text_comment_content form-control'
				)) ?></div><input type="button" value="Post Response" class="btn btn-success post_response" style='margin-top:10px;'/>
			</div>
		<?php } ?>

		<?php if($model->allowVideoComments){ ?>
			<div class='response_pane video_response_content' style='display:none;'>
					<input type='hidden' id='video_response_id' name='video_response_id'/>
					<input type="button" value="Post Response" class="btn btn-success post_response"/>
					<p class='text-muted' style='margin:10px 0 0;'>Don't see your video there? <?php echo html::a(array('href' => 'http://upload.stagex.co.uk/video/upload', 'text' => 'Upload more videos'))?></p>
			</div>
		<?php } ?>
		<div class="clear"></div>
	<?php } ?>
</div>