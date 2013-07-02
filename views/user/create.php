<?php use glue\Html; ?>
<div class='user_create_body'>
	<h1>Register for a StageX account</h1>
	<?php $form = html::activeForm(); ?>
		<?php echo html::form_summary($model, array(
			'errorHead' => '<h4>Could not complete registration</h4>Your account could not be created because:'
		)) ?>

		<?php echo $form->hiddenfield($model, "hash", array("value"=>glue::http()->getCsrfToken())) ?>
		<div class="form">
			<div class="form_row">
				<?php echo html::label("Username:", "username"); echo $form->textfield($model, "username", array('class' => 'input-large')) ?>
			</div>
			<div class="form_row">
				<?php echo html::label("Password:", "password"); echo $form->passwordfield($model, "password", array('class' => 'input-large')) ?>
			</div>
			<div class="form_row">
				<?php echo html::label("Email:", "email"); echo $form->textfield($model, "email", array('class' => 'input-large')) ?>
			</div>
			<div class="submit_row">
				<?php echo html::submitbutton('Create Account', array('class' => 'btn-success')) ?>
				<div class="clear"></div>
			</div>
		</div>
		<p class="light small declaration_footer">By clicking "Create Account" you agree to the <a href="http://www.stagex.co.uk/help/view?title=terms-and-conditions">terms and conditions</a> laid out by StageX.</p>
	<?php $form->end() ?>

	<div class='social_logins_outer'>
		<ul>
			<li class="first"><a href='<?php echo glue::facebook()->getLoginUrl(array( "scope"=>"email" )) ?>'>
				<img src='/images/fb_large.png'/><span>Login with Facebook</span></a></li>
			<li style='margin-left:45px;'><a href='<?php echo glue::google()->getLoginURI(array('email', 'profile')) ?>'>
				<img src='/images/google_large.png'/><span>Login with Google Accounts</span></a></li>
		</ul>
		<div class='clearer'></div>
	</div>

</div>
