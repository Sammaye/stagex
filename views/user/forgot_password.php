<div class="grid-container recover_password_body">
	<?php $form = html::activeForm() ?>

		<div class='form col-30'>
			<?php echo html::form_summary($model, array(
				'errorHead' => 'Could not recover your account because:',
			)) ?>

			<h1 class='head'>Password Recovery</h1>
			<p>Forgotten your password? No problem just fill in the email address you used to register with and the Re-captcha below and we send you a new password straight to your inbox.</p>

			<?php echo $form->hiddenField($model, "hash", array('value'=>glue::http()->getCsrfToken())) ?>
			<div class="form-group email_address">
				<?php echo html::label("Email Address:", "email") ?>
				<?php echo $form->textField($model, "email",array('class'=>'form-control')) ?>
			</div>

			<div class="password-Captcha">
				<?php
					app\widgets\reCaptcha\recaptcha::run(array(
						"public_key"=>"6LfCNb0SAAAAAF4EZ2hV_4JCxbY3lfq0ren11EfM",
						"errors"=>$model->captchaError)
					);
				?>
			</div>
			<div class="form-group form_submit">
				<?php echo html::submitbutton('Email Me a New Password', array('class' => 'btn btn-success')) ?>
			</div>
		</div>
	<?php $form->end() ?>
</div>
