<?php echo html::formSummary($model, array(
	'errorHead' => '<h4>Could not Save Help Topic</h4>The Help Topic could not be saved because:'
)) ?>

<?php $form = html::activeForm() ?>
	<div class='form-vertical form'>
		<div class='form_row'><?php echo html::label('Title:', 'title').$form->textfield($model, 'title') ?></div>
		<div class='form_row'><?php echo html::label('Position:', 'seq').$form->textfield($model, 'seq', array('class' => 'position')) ?></div>
		<div class='form_row'><?php echo html::label('Parent Topic:', 'parent').$form->selectbox($model, 'parent', $model->getSelectBox_list()) ?></div>
		<div class="submit_row"><input type="submit" class="btn-success" value="<?php if($model->getIsNewRecord()): echo "Create Help Topic"; else: echo "Save Help Topic"; endif ?>"/>
	</div>
<?php $form->end() ?>