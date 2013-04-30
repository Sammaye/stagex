<?php $this->addJsScript('submit_form', "
	$(document).ready(function(){
		$('.submit_changes').click(function(event){
			event.preventDefault();
			$(this).parents('form').submit();
		});
	});
") ?>

<?php echo html::form_summary($model, array(
	'errorHead' => '<h2>Could not Save Help Topic</h2>The Help Topic could not be saved because:'
)) ?>

<?php $form = html::activeForm() ?>
	<div class='form'>
		<div class='form_row'><?php echo html::label('Title:', 'title').$form->textfield($model, 'title') ?></div>
		<div class='form_row'><?php echo html::label('Position:', 'seq').$form->textfield($model, 'seq', array('class' => 'position')) ?></div>
		<div class='form_row'><?php echo html::label('Parent Topic:', 'parent').$form->selectbox($model, 'parent', $model->getSelectBox_list()) ?></div>
		<div class="light_grey_high_button submit_row"><a href="#" class='submit_changes'><span><?php if($model->getIsNewRecord()): echo "Create Help Topic"; else: echo "Save Help Topic"; endif ?></span></a></div>
	</div>
<?php $form->end() ?>