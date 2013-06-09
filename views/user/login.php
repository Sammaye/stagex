<?php $this->js('account_settings', "
	$(document).ready(function(){
		$('.login_submit').click(function(event){
			event.preventDefault();
			$(this).parents('form').submit();
		});
	});
"); ?>

<div class="user_login_body">
	<?php $form = html::activeForm(); ?>

		<?php echo html::form_summary($model, array(
			'errorHead' => '<h2>You could not be Authenticated</h2>', 'showOnlyFirstError' => true
		)) ?>

		<div class="mainSiteLoginUI">
			<?php echo $form->hiddenField($model, 'hash', array('value' => glue::http()->getCsrfToken())) ?>
			<div class="formRow">
				<?php echo html::label("Email Address:", "email") ?>
				<?php echo $form->textField($model, "email") ?>
			</div>
			<div class="formRow">
				<?php echo html::label("Password:", "password") ?>
				<?php echo $form->passwordfield($model, "password") ?>
			</div>
			<div class="formRow persistent">
				<label>
					<?php echo $form->checkbox($model, "remember", 1) ?>
					<span>Keep me logged in</span>
				</label>
			</div>

			<?php if($attempts > 3){ ?>
				<div class='captcha_outer'>
					<h2>Captcha</h2>
					<p class='smallArial'><b>Please note:</b> Since you have unsuccessfully logged in 3 times now you must also fill in the captcha to prove you are human.</p>
					<?php
					app\widgets\reCaptcha\recaptcha::widget(array(
						"public_key"=>"6LfCNb0SAAAAAF4EZ2hV_4JCxbY3lfq0ren11EfM",
						"errors"=>$model->captchaError
					)) 
					?>
				</div>
			<?php } ?>

			<div class="NonInput_Singular">
				<div class="form_row-inner">
					<?php echo html::submitbutton('Sign in', array('class' => 'float_left green_css_button')) ?>
				</div>
			</div>
			<div class="clearer"></div>
		</div>
		<div class='form_footer'>
			<p>Cannot get into your account? <a href='<?php echo Glue::http()->createUrl("/user/recover") ?>'>Recover your account details here</a></p>
		</div>
	<?php $form->end() ?>

	<div class='social_login_opts_outer'>
		<ul class='social_login_options'>
			<li><a href='<?php echo glue::facebook()->getLoginUrl(array( "scope"=>"email" )) ?>'>
				<img src='/images/fb_large.png'/><span>Login with Facebook</span></a></li>
			<li style='margin-left:45px;'><a href='<?php echo glue::google()->getLoginURI(array('email', 'profile')) ?>'>
				<img src='/images/google_large.png'/><span>Login with Google Accounts</span></a></li>
		</ul>
		<div class='clearer'></div>
	</div>
</div>
