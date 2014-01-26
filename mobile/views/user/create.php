<div class='user_create_body'>
	<?php echo html::formSummary($model, array(
	'errorHead' => '<h4>Could not complete registration</h4>Your account could not be created because:'
	)) ?>
	<?php $form = html::activeForm(); ?>
		<?php echo $form->hiddenfield($model, "hash", array("value"=>glue::http()->getCsrfToken())) ?>
		<div class="form">
		<div class="form-group">
		<?php echo html::label("Username:", "username"); echo $form->textfield($model, "username", array('class' => 'form-control input-lg')) ?>
		</div>
		<div class="form-group">
		<?php echo html::label("Password:", "password"); echo $form->passwordfield($model, "password", array('class' => 'form-control input-lg')) ?>
		</div>
		<div class="form-group">
		<?php echo html::label("Email:", "email"); echo $form->textfield($model, "email", array('class' => 'form-control input-lg')) ?>
		</div>
		<div class="submit_row">
		<?php echo html::submitbutton('Create Account', array('class' => 'btn btn-success btn-lg')) ?>
		</div>
		</div>
		<p class="text-muted small declaration_footer">By clicking "Create Account" you agree to the <a href="http://www.stagex.co.uk/help/view?title=terms-and-conditions">terms and conditions</a> laid out by StageX.</p>
	<?php $form->end() ?>

	<div class='social_logins_outer row'>
	<div class="col-md-4 social_login_option"><a href='<?php echo glue::facebook()->getLoginUrl(array( "scope"=>"email" )) ?>'>
	<span class="facebook-social-icon"></span>Login with Facebook</a></div>
	<div class="col-md-4 social_login_option"><a href='<?php echo glue::google()->getLoginURI(array('email', 'profile')) ?>'>
	<span class="google-social-icon"></span>Login with Google Accounts</a></div>
	</div>
</div>
