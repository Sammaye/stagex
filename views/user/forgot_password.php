<?php $this->js('account_settings', "
	$(document).ready(function(){
		$('.recover_submit').click(function(event){
			event.preventDefault();
			$(this).parents('form').submit();
		});
	});
") ?>

<div class="recover_account_body">
	<?php $form = html::activeForm() ?>

		<div class='form'>
			<?php echo html::form_summary($model, array(
				'errorHead' => 'Could not recover your account because:',
			)) ?>

			<div class="form-caption">
				<h1 class='head'>Forgot Password</h1>
				<p>Forgotten your password? No problem just fill in the email address you used to register with and the Re-captcha below and we send you a new password straight to your inbox.</p>
			</div>

			<?php echo $form->hiddenField($model, "hash", array('value'=>glue::http()->getCsrfToken())) ?>
			<div class="form_row email_address">
				<?php echo html::label("Email Address:", "email") ?>
				<?php echo $form->textField($model, "email") ?>
			</div>
		</div>

		<div class="password-Captcha">
			<?php
				app\widgets\reCaptcha\recaptcha::widget(array(
					"public_key"=>"6LfCNb0SAAAAAF4EZ2hV_4JCxbY3lfq0ren11EfM",
					"errors"=>$model->captchaError)
				);
			?>
		</div>
		<div class="submit_row">
			<div class="green_css_button recover_submit" style='float:left;'>Generate New Password</div>
			<?php echo html::submitbutton('Generate New Password', array('class' => 'invisible_submit')) ?>
		</div>
	<?php $form->end() ?>
</div>
