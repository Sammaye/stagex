<?php echo html::formSummary($model, array(
	'errorHead' => '<h4>Could not Save Help Article</h4>The Help Article could not be saved because:'
)) ?>

<div class='form form-vertical'>
	<?php $form = html::activeForm() ?>
		<div class='form_row'><?php echo $form->label($model, "title", 'Title') ?><?php echo $form->textfield($model, 'title') ?></div>
		<div class='form_row'><?php echo $form->label($model, 'parent', 'Parent Topic') ?><?php echo $form->selectbox($model, 'parent', app\models\HelpTopic::getSelectBox_list())?></div>
		<div class='ckeditor_content' style='margin:10px 0;'>
			<?php echo app\widgets\CkEditor::run(array(
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
		<div class='form_row'><?php echo $form->label($model, 'tagString', 'Tags:') ?><?php echo $form->textfield($model, 'tagString') ?></div>
		<div class='form_row'><?php echo $form->label($model, 'seq', 'Position:').$form->textfield($model, 'seq') ?></div>
		<div class="submit_row"><?php echo html::submitButton($model->getIsNewRecord()?'Add Article':'Save Article',array('class' => 'btn-success')) ?></div>
	<?php $form->end() ?>
</div>