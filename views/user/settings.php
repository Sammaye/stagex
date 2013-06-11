<?php
$js=<<<JS

$('.edit_acccount_form').css({display:'none'});

$('.account_settings_part').on('click','.edit',function(e){
	e.preventDefault();
	var part=$(this).parents('.account_settings_part');
	part.find('.value').css({display:'none'});
	part.find('.edit_acccount_form').css({display:'block'});
	$(this).removeClass('edit').addClass('cancel_edit').text('Cancel edit');
});

$('.account_settings_part').on('click','.cancel_edit',function(e){
	e.preventDefault();
	var part=$(this).parents('.account_settings_part');
	part.find('.value').css({display:'block'});
	part.find('.edit_acccount_form').css({display:'none'});
	$(this).removeClass('cancel_edit').addClass('edit').text($(this).attr('title'));
});

JS;
$this->js('accountsettings',$js);
?>

<div class="account_settings_body">

	<?php echo html::form_summary($model, array(
		'errorHead' => '<h6>Could not save settings</h6>Your account settings could not be saved because:'
	)) ?>

	<div class="account_settings_part edit_email_address">
		<h1>Email Address</h1>
		<div><div class="value" style='float:left;'><?php echo $model->email ?></div><a href="#" class="edit" title="Change Email Address">Change Email Address</a></div>
		<div class="small_submit_form_account edit_acccount_form">
			<?php $form = html::activeForm() ?>
				<p><b>Note:</b> Verfication email will need to be confirmed before change takes effect.</p>
				<div class="account_settings_form_outer">
					<div class="form_row"><?php echo html::label("Email Address:", "newEmail") ?><?php echo $form->textField($model, "newEmail", array('value'=>$model->email)) ?></div>
					<div class="clearer"></div>
				</div>
				<?php echo html::submitbutton("save", array('class' => 'invisible_submit')) ?>
			<?php $form->end() ?>
		</div>
		<div class="clearer"></div>
	</div>

	<div class="account_settings_part edit_password">
		<h1>Password</h1>
		<a href="#" class="edit" title="Change Password" style='display:block;float:left;'>Change Password</a>
		<div class="edit_acccount_form">
			<?php $form = html::activeForm() ?>
					<div class="form_row"><?php echo html::label("Old Password:", "oldPassword") ?><?php echo $form->passwordField($model, "oldPassword") ?></div>
					<div class="form_row"><?php echo html::label("New Password:", "newPassword") ?><?php echo $form->passwordField($model, "newPassword") ?></div>
					<div class="form_row"><?php echo html::label("Confirm Password:", "confirmPassword") ?><?php echo $form->passwordField($model, "confirmPassword") ?></div>
					<?php echo $form->hiddenField($model, "action", array("value"=>"updatePassword")) ?>
					<?php echo html::submitbutton("Change Password") ?>
			<?php $form->end() ?>
		</div>
		<div class="clearer"></div>
	</div>

	<div class='section_hr'>
		&nbsp;
	</div>
	<?php $form = html::activeForm() ?>
	<button type="button" class="btn">Save Settings</button>
		<?php echo html::submitbutton("Save Settings",array('class'=>'btn-success')) ?>
	<h1 class='section_head'>Security</h1>
		<div class='security_form'>
			<label class='checkbox'><?php echo $form->checkbox($model, "singleSignOn", 1) ?>Allow single sign-on</label>
			<div class='light_capt'>
				<p>Single Sign-on means that only one device can be logged onto this account at any given point in time. It will logout all devices before
				logging in the user.</p>
			</div>
			<label class='checkbox'><?php echo $form->checkbox($model, "emailLogins", 1) ?>Notify me via email of new logins</label>
		</div>
		<div class="clearer"></div>
		<h1 class='section_head'>Browsing</h1>
		<label class='checkbox'><?php echo $form->checkbox($model, 'safeSearch', 1) ?><span>Use Safe Search to hide mature videos</span></label>
		<label class='checkbox'><?php echo $form->checkbox($model, 'useDivx', 1) ?><span>Use DivX Player</span></label>
		<label class='checkbox'><?php echo $form->checkbox($model, 'autoplayVideos', 1) ?><span>Automatically play videos</span></label>
		<div class="clearer"></div>
		<h1 class='section_head'>Email Notifications</h1>
		<div class="privacy_form">
			<label class='checkbox'><?php echo $form->checkbox($model, 'emailEncodingResult', 1) ?><span>When one of my new uploads fails or finishes encoding</span></label>
			<label class='checkbox'><?php echo $form->checkbox($model, 'emailVideoResponses', 1) ?><span>When someone replies to me</span></label>
			<!-- <label class='block_label'><?php //echo $form->checkbox($model, 'emailVideoResponseReplies', 1) ?><span>When someone replies to one of my comments</span></label> -->
			<label class='checkbox'><?php echo $form->checkbox($model, 'emailWallComments', 1) ?><span>When someone comments on my profile</span></label>
		</div>
		<div class="clearer"></div>
		<h1 class='section_head'>Analytics</h1>
			<p>Entering your Clicky site ID will allow you to track via Clicky. Enter a site ID to start tracking:</p>
			<div class=''><label>Clicky Site ID:</label> <?php echo $form->textfield($model, 'clickyUid') ?></div>
		<div class="clearer"></div>

	<h1 class='section_head'>Default Video Settings</h1>
		<p>These settings change what options, by default, any uploaded videos will get. Please be aware that changing these settings will not change the settings of
		any videos previously uploaded, only future uploads.</p>
			<div class="privacy_form">
				<div class="listing_settings" style='float:left;'>
					<h5>Listing</h5>
					<?php $group = $form->radio_group($model, "defaultVideoSettings[listing]") ?>
					<label class="radio"><?php echo $group->add(0) ?><span>Public</span></label>
					<label class="radio"><?php echo $group->add(1) ?><span>Unlisted</span></label>
					<label class="radio"><?php echo $group->add(2) ?><span>Private</span></label>
					<h5>Licence</h5>
					<?php $grp = $form->radio_group($model, 'defaultVideoSettings[licence]') ?>
					<div>
						<label class='radio'><?php echo $grp->add('1') ?><span>Standard StageX Licence</span></label>
						<label class='radio'><?php echo $grp->add('2') ?><span>Creative Commons Licence</span></label>
					</div>
				</div>

				<div class='bordered_form_section' style='float:left; margin-left:120px;'>
					<label class='checkbox'><?php echo $form->checkbox($model, "defaultVideoSettings[embeddable]", 1) ?>Allow embedding of my video</label>
					<label class='checkbox'><?php echo $form->checkbox($model, "defaultVideoSettings[moderated]", 1) ?>Moderate Responses</label>
					<label class='checkbox'><?php echo $form->checkbox($model, "defaultVideoSettings[voteableComments]", 1) ?>Allow users to vote on responses</label>
					<label class='checkbox'><?php echo $form->checkbox($model, "defaultVideoSettings[allowVideoComments]", 1) ?>Allow video responses</label>
					<label class='checkbox'><?php echo $form->checkbox($model, "defaultVideoSettings[allowTextComments]", 1) ?>Allow text responses</label>
					<label class='checkbox'><?php echo $form->checkbox($model, "defaultVideoSettings[voteable]", 1) ?>Allow users to vote on this video</label>
					<label class='checkbox'><?php echo $form->checkbox($model, "defaultVideoSettings[privateStatistics]", 1) ?>Make my statistics private</label>
				</div>
			</div>
<div class="clearer"></div>
	<?php $form->end() ?>
	<div class='section_hr'>
		&nbsp;
	</div>

	<div class='services_footer_part'>
		<a href="<?php echo glue::http()->createUrl("/user/deactivate") ?>">Deactivate Account</a>
	</div>
</div>