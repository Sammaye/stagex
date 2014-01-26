<div class="user_login_body">
	<?php $form = html::activeForm(array('class'=>'')); ?>

		<?php echo html::formSummary($model, array(
			'errorHead' => '<h4>You could not be Authenticated</h4>', 'showOnlyFirstError' => true
		)) ?>

		<div class="form">
			<?php echo $form->hiddenField($model, 'hash', array('value' => glue::http()->getCsrfToken())) ?>
			<div class="form-group">
				<?php echo html::label("Email Address:", "email") ?>
				<?php echo $form->textField($model, "email", array('class' => 'form-control input-lg')) ?>
			</div>
			<div class="form-group">
				<?php echo html::label("Password:", "password") ?>
				<?php echo $form->passwordfield($model, "password", array('class' => 'form-control input-lg')) ?>
			</div>
			<div class="checkbox">
			  <label>
				<?php echo $form->checkbox($model, "remember", 1) ?>
				Keep me logged in
			  </label>
			</div>			

			<?php if($attempts > 3){ ?>
				<div class='captcha noninput_row'>
					<p class='small'><b>Please note:</b> Since you have unsuccessfully logged in 3 times now you must also fill in the captcha to prove you are human.</p>
					<?php
					echo app\widgets\reCaptcha\recaptcha::run(array(
						"public_key"=>"6LfCNb0SAAAAAF4EZ2hV_4JCxbY3lfq0ren11EfM",
						"errors"=>$model->captchaError
					)) 
					?>
				</div>
			<?php } ?>

			<?php echo html::submitbutton('Sign in', array('class' => 'btn btn-success btn-lg', 'type'=>'submit')) ?>
			<p class="text-muted small help-block">Cannot get into your account? <a href='<?php echo Glue::http()->url("/user/recover") ?>'>Recover your account details here</a></p>				
		</div>
	<?php $form->end() ?>

	<div class='social_logins_outer row'>
	<div class="col-md-4 social_login_option"><a href='<?php echo glue::facebook()->getLoginUrl(array( "scope"=>"email" )) ?>'>
	<img src='/images/fb_large.png'/><span>Login with Facebook</span></a></div>
	<div class="col-md-4 social_login_option"><a href='<?php echo glue::google()->getLoginURI(array('email', 'profile')) ?>'>
	<img src='/images/google_large.png'/><span>Login with Google Accounts</span></a></div>
	</div>
</div>
