<?php $this->addJsScript('submit_form', "
	$(document).ready(function(){
		$('.submit_changes').click(function(event){
			event.preventDefault();
			$(this).parents('form').submit();
		});
	});
") ?>

<?php echo html::form_summary($model, array(
	'errorHead' => '<h2>Could not Save Help Article</h2>The Help Article could not be saved because:'
)) ?>

<div class='form'>
	<?php $form = html::activeForm() ?>
		<div class='form_row'><?php echo html::label("Title:", 'title') ?><?php echo $form->textfield($model, 'title') ?></div>
		<div class='form_row'><?php echo html::label('Parent topic:', 'parent_topic') ?><?php echo $form->selectbox($model, 'parent_topic', HelpTopic::getSelectBox_list())?></div>
		<div class='ckeditor_content' style='margin:10px 0;'>
			<?php $this->widget('application/widgets/ckeditor.php', array(
				'model' => $model,
				'attribute' => 'content',
				'config' => array(
					'toolbar'=>array(
						array(
							'Bold','Italic','Underline','Strike','Subscript','Superscript','-',
							'NumberedList','BulletedList', 'Blockquote', '-',
							'Table','-',
							'Link','Unlink','Anchor', '-',
							'Outdent','Indent','-',
							'SpellChecker', 'RemoveFormat', '-',
							'Source'
						))
					)
			)) ?>
		</div>
		<div class='form_row'><?php echo html::label('Tags:', 'tagString') ?><?php echo $form->textfield($model, 'tagString') ?></div>
		<div class='form_row'><?php echo html::label('Position:', 'seq').$form->textfield($model, 'seq') ?></div>
		<div class="light_grey_high_button submit_row"><a href="#" class='submit_changes'><span><?php if($model->getIsNewRecord()): echo "Create Help Article"; else: echo "Save Help Article"; endif ?></span></a></div>
	<?php $form->end() ?>
</div>