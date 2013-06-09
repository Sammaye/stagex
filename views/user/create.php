<?php 

use glue\Html;

$this->js('account_settings', "
	$(document).ready(function(){
		$('.register_submit').click(function(event){
			event.preventDefault();
			$(this).parents('form').submit();
		});
	});
") ?>

<div class='user_create_body'>
	<?php $form = html::activeForm(); ?>
		<h1 class='head'>Register for a StageX account</h1>

		<?php echo html::form_summary($model, array(
			'errorHead' => '<h2>Could not complete registration</h2>Your account could not be created because:'
		)) ?>

		<?php echo $form->hiddenfield($model, "hash", array("value"=>glue::http()->getCsrfToken())) ?>
		<div class="form" style='<?php if($model->hasSummary()) echo "margin-top:15px;"; ?>'>
			<div class="form_row">
				<?php echo html::label("Username:", "username"); echo $form->textfield($model, "username") ?>
			</div>
			<div class="form_row">
				<?php echo html::label("Password:", "password"); echo $form->passwordfield($model, "password") ?>
			</div>
			<div class="form_row">
				<?php echo html::label("Email:", "email"); echo $form->textfield($model, "email") ?>
			</div>
			<div class="submit_row">
				<?php echo html::submitbutton('Create Account', array('class' => 'green_css_button')) ?>
				<div class="clearer"></div>
			</div>
		</div>
		<div class='form_footer'>
			<p>By clicking "Create Account" you agree to the <a href="http://www.stagex.co.uk/help/view?title=terms-and-conditions">terms and conditions</a> laid out by StageX.</p>
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
