<div class="grid_12 alpha">

	<?php $model->getSummary() ?>

	<h1>Notice of Intellectual Property/Copyright Infringement</h1>
	<?php $form = $this->startWidget('glue.plugins.form.GFormHtml', array("model"=>$model, "method"=>"post")) ?>

	<div class="copyright_report_form_maino">
		<div class="formRow">
			<?php $form->label("name", "Name (full):") ?>
			<?php $form->textField("name") ?>
		</div>
		<div class="formRow copyright_report_form_address">
			<?php $form->label("address", "Address (full):") ?>
			<?php $form->textarea("address") ?>
		</div>
		<div class="formRow">
			<?php $form->label("telephone", "Telephone (inc. country code):") ?>
			<?php $form->textField("telephone") ?>
		</div>
		<div class="formRow">
			<?php $form->label("email", "Email Address:") ?>
			<?php $form->textField("email") ?>
		</div>
		<div class="formRow">
			<h2 class="report_form_large_textboxes_head">Who do you represent?</h2>
			<div class="formRowReportUser report_copyright_long_radio_b">
				<label>
					<?php $form->optionGroup('authed', new radioButton('authed', 1)) ?>
					<span>I represent the owner and/or legal representation of the ownership of this claim</span>
				</label>
			</div>
			<div class="formRowReportUser report_copyright_long_radio_b">
				<label>
					<?php $form->optionGroup('authed', new radioButton('authed', 0)) ?>
					<span>I do not represent the owner and/or legal representation of the ownership of this claim</span>
				</label>
			</div>
			<div class="clearer"></div>
		</div>
		<h2 class="report_form_large_textboxes_head">Explanation and Reason</h2>
		<div class="formRow report_form_large_textboxes">
			<?php $form->label("why", "Describe how the content infringes (include urls and media links):") ?>
			<?php $form->textarea("why") ?>
		</div>
		<div class="formRow report_form_large_textboxes report_form_large_textboxes_l">
			<?php $form->label("how", "Effect on your ownership:") ?>
			<?php $form->textarea("how") ?>
		</div>
		<div class="UIMessage warning report_user_warning_message">
			By submitting this notice you state that you are the owner and/or the legal representation of the copyright/intellectual ownership of the content in question.
			You also agree, by law, that everyting within this form is accurate and all data is correct without fault. If data is entered incorrectly or incorrect/lack of evidence is provided
			StageX, by law, reserves the right to either prosecute and/or sue and/or take further legal and/or non-legal actions (definition of non-legal being that of non-illegal actions).
		</div>
		<div class="formRow report_copyright_long_signature">
			<?php $form->label("signature", "Please enter your signature:") ?>
			<?php $form->textField("signature") ?>
		</div>
	</div>
	<div class="formReport_user_submit">
		<span class="user_report_input_cancel"><?php $form->submitButton("Cancel") ?></span>
		<span class="b_outer"><span class="b_inner"><?php $form->submitButton("Submit Notice") ?></span></span>
	</div>
	<?php $this->endWidget($form) ?>
</div>
<div class="grid_4 omega">
	&nbsp;
</div>