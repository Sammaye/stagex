<?php use glue\Html; ?>
<div class='user_create_body grid-container'>
		<?php echo html::formSummary($model, array(
			'errorHead' => '<h4>Could not complete registration</h4>Your account could not be created because:'
		)) ?>
	<h1>Register for a StageX account</h1>
	<?php $form = html::activeForm(array('class'=>'')); ?>
		<?php echo $form->hiddenfield($model, "hash", array("value"=>glue::http()->getCsrfToken())) ?>
		<div class="form">
			<div class="form-group">
				<?php echo $form->label($model, 'username', "Username:"); echo $form->textfield($model, "username", array('class' => 'form-control input-lg')) ?>
			</div>
			<div class="form-group">
				<?php echo $form->label($model, "password", "Password:"); echo $form->passwordfield($model, "password", array('class' => 'form-control input-lg')) ?>
			</div>
			<div class="form-group">
				<?php echo $form->label($model, 'email', "Email:"); echo $form->textfield($model, "email", array('class' => 'form-control input-lg')) ?>
			</div>
			<div class="submit_row">
				<?php echo html::submitbutton('Create Account', array('class' => 'btn btn-success btn-lg')) ?>
			</div>
		</div>
		<p class="text-muted small declaration_footer">By clicking "Create Account" you agree to the <a href="http://www.stagex.co.uk/help/view?title=terms-and-conditions">terms and conditions</a> laid out by StageX.</p>
	<?php $form->end() ?>

	<div class='social_logins_outer'>
		<ul>
			<li class="first"><a href='<?php echo glue::facebook()->getLoginUrl(array( "scope"=>"email" )) ?>'>
				<span class="facebook-social-icon"></span><span class="caption">Login with Facebook</span></a></li>
			<li style='margin-left:45px;'><a href='<?php echo glue::google()->getLoginURI(array('email', 'profile')) ?>'>
				<span class="google-social-icon"></span><span class="caption">Login with Google Accounts</span></a></li>
		</ul>
		<div class='clearer'></div>
	</div>

</div>
