<?php
$js=<<<JS

$('.account_settings_part .form').css({display:'none'});

$('.account_settings_part').on('click','.edit',function(e){
	e.preventDefault();
	var part=$(this).parents('.account_settings_part');
	part.find('.value').css({display:'none'});
	part.find('.form').css({display:'block'});
	$(this).removeClass('edit').addClass('cancel_edit').text('Cancel edit');
});

$('.account_settings_part').on('click','.cancel_edit',function(e){
	e.preventDefault();
	var part=$(this).parents('.account_settings_part');
	part.find('.value').css({display:'block'});
	part.find('.form').css({display:'none'});
	$(this).removeClass('cancel_edit').addClass('edit').text($(this).attr('title'));
});

JS;
$this->js('accountsettings',$js);

$this->js('autosharesettings', '
	var wins = [], unauth_window;

	$(document).ready(function(){
		getAccountStatus("fb");
		getAccountStatus("twt");
		//getAccountStatus("lnkd");

		$(".submit_changes").click(function(event){
			event.preventDefault();
			$(".invisible_submit").trigger("click");
		});
	});

	function rebind_auth_window(){
		$(".authSocialAccount").unbind("click");
		$(".openNewWindow").unbind("click");

		$(".authSocialAccount").click(function(event){
			event.preventDefault();
			openAuthWindow($(this).attr("id"), $(this).attr("href"));
		});

		$(".openNewWindow").click(function(event){
			event.preventDefault();
			openWindow($(this).attr("href"));
		});
	}

	function openAuthWindow($i, $url){
		if(wins[$i] == null || wins[$i].closed){
			wins[$i] = window.open($url, "Authorise Account", "location=1,status=1,scrollbars=1,width=500,height=400");
		}else{
			wins[$i].focus();
		}
		return false;
	}

	function openWindow($url){
		unauth_window = window.open($url, "Connected Account", "location=1,status=1,scrollbars=1,width=500,height=400");
		return false;
	}

	function getAccountStatus($type){
		$.getJSON("/autoshare/status", {"network": $type, "action": "status" }, function(data){
			switch($type){
				case "fb":
					$(".fb_acc_status").html(data.response);
					break;
				case "twt":
					$(".twt_acc_status").html(data.response);
					break;
				case "lnkd":
					$(".lnkd_acc_status").html(data.response);
					break;
			}
			rebind_auth_window();
		});
	}'
);
?>

<div class="account_settings_body">

	<?php echo html::form_summary($model, array(
		'errorHead' => '<h4>Could not save settings</h4><p>Your account settings could not be saved because:</p>'
	)) ?>

	<div class="row">
	<div class="account_settings_part edit_email_address col-md-4">
		<h3>Email Address</h3>
		<div><div class="value"><?php echo $model->email ?></div><a href="#" class="edit" title="Change Email Address">Change Email Address</a></div>
		<div class="clear"></div>
		<div class="form">
			<?php $form = html::activeForm() ?>
				<p><b>Note:</b> Verfication email will need to be confirmed before change takes effect.</p>
				<div class="form-group"><?php echo html::label("Email Address:", "newEmail",'grid-col-10') ?>
					<?php echo $form->textField($model, "newEmail", array('value'=>$model->email, 'class'=>'form-control grid-col-20')) ?></div>
				<?php echo html::submitbutton("Change Email Address", array('class' => 'btn btn-success')) ?>
			<?php $form->end() ?>
		</div>
	</div>
	</div>

	<div class="row">
	<div class="account_settings_part edit_password col-md-4">
		<h3>Password</h3>
		<a href="#" class="edit" title="Change Password">Change Password</a>
		<div class="clear"></div>
		<div class="form">
			<?php $form = html::activeForm() ?>
					<div class="form-group"><?php echo html::label("Old Password:", "oldPassword", 'grid-col-10') ?>
						<?php echo $form->passwordField($model, "oldPassword", array('class'=>'form-control grid-col-20')) ?></div>
					<div class="form-group"><?php echo html::label("New Password:", "newPassword", 'grid-col-10') ?>
						<?php echo $form->passwordField($model, "newPassword", array('class'=>'form-control grid-col-20')) ?></div>
					<div class="form-group"><?php echo html::label("Confirm Password:", "confirmPassword", 'grid-col-10') ?>
						<?php echo $form->passwordField($model, "confirmPassword", array('class'=>'form-control grid-col-20')) ?></div>
					<?php echo $form->hiddenField($model, "action", array("value"=>"updatePassword")) ?>
					<?php echo html::submitbutton("Change Password", array('class' => 'btn btn-success')) ?>
			<?php $form->end() ?>
		</div>
	</div>
	</div>

	<div class='hr'>&nbsp;</div>
	
	<?php $form = html::activeForm() ?>
		<?php echo html::submitbutton("Save Account Settings",array('class'=>'btn btn-success')) ?>
		
		<h3 class='section_head'>Security</h3>
		<label class='checkbox'><?php echo $form->checkbox($model, "singleSignOn", 1) ?>Allow single sign-on</label>
		<div class='help-block'>
			<p class="light">Single Sign-on means that only one device can be logged onto this account at any given point in time. It will logout all devices before
			logging in the user.</p>
		</div>
		<label class='checkbox'><?php echo $form->checkbox($model, "emailLogins", 1) ?>Notify me via email of new logins</label>
		
		<h3 class='section_head'>Browsing</h3>
		<label class='checkbox'><?php echo $form->checkbox($model, 'safeSearch', 1) ?>Use Safe Search to hide mature videos</label>
		<label class='checkbox'><?php echo $form->checkbox($model, 'useDivx', 1) ?>Use DivX Player</label>
		<label class='checkbox'><?php echo $form->checkbox($model, 'autoplayVideos', 1) ?>Automatically play videos</label>

		<h3 class='section_head'>Email Notifications</h3>
		<label class='checkbox'><?php echo $form->checkbox($model, 'emailEncodingResult', 1) ?>When one of my new uploads fails or finishes encoding</label>
		<label class='checkbox'><?php echo $form->checkbox($model, 'emailVideoResponses', 1) ?>When someone replies to me</label>
		<!-- <label class='block_label'><?php //echo $form->checkbox($model, 'emailVideoResponseReplies', 1) ?>When someone replies to one of my comments</label> -->
		<label class='checkbox'><?php echo $form->checkbox($model, 'emailWallComments', 1) ?>When someone comments on my profile</label>

		<h3 class='section_head'>Analytics</h3>
		<p>Entering your Clicky site ID will allow you to track via Clicky. Enter a site ID to start tracking:</p>
		<div class="row"><div class="form-group col-md-4"><label class="grid-col-10">Clicky Site ID:</label> <?php echo $form->textfield($model, 'clickyUid','form-control grid-col-10') ?></div></div>

		<h3 class='section_head'>Default Video Settings</h3>
		<p>These settings change what options, by default, any uploaded videos will get. Please be aware that changing these settings will not change the settings of
		any videos previously uploaded, only future uploads.</p>
		<div class="upload_settings row">
			<div class="left col-md-3">
				<h5>Listing</h5>
				<?php $group = $form->radio_group($model, "defaultVideoSettings[listing]") ?>
				<label class="radio"><?php echo $group->add(0) ?>Public</label>
				<label class="radio"><?php echo $group->add(1) ?>Unlisted</label>
				<label class="radio"><?php echo $group->add(2) ?>Private</label>
				<h5>Licence</h5>
				<?php $grp = $form->radio_group($model, 'defaultVideoSettings[licence]') ?>
				<div>
					<label class='radio'><?php echo $grp->add(1) ?>Standard StageX Licence</label>
					<label class='radio'><?php echo $grp->add(2) ?>Creative Commons Licence</label>
				</div>
			</div>

			<div class='right col-md-4'>
				<label class='checkbox'><?php echo $form->checkbox($model, "defaultVideoSettings[embeddable]", 1) ?>Allow embedding of my video</label>
				<label class='checkbox'><?php echo $form->checkbox($model, "defaultVideoSettings[moderated]", 1) ?>Moderate Responses</label>
				<label class='checkbox'><?php echo $form->checkbox($model, "defaultVideoSettings[voteableComments]", 1) ?>Allow users to vote on responses</label>
				<label class='checkbox'><?php echo $form->checkbox($model, "defaultVideoSettings[allowVideoComments]", 1) ?>Allow video responses</label>
				<label class='checkbox'><?php echo $form->checkbox($model, "defaultVideoSettings[allowTextComments]", 1) ?>Allow text responses</label>
				<label class='checkbox'><?php echo $form->checkbox($model, "defaultVideoSettings[voteable]", 1) ?>Allow users to vote on this video</label>
				<label class='checkbox'><?php echo $form->checkbox($model, "defaultVideoSettings[privateStatistics]", 1) ?>Make my statistics private</label>
			</div>
		</div>			
		<div class="clear"></div>

		<h3 class='section_head'>Auto-Sharing</h3>
		<p>Auto-sharing allows you to connect your favourite social networks directly to your profile. When connected your social actions across this site will be
		echoed onto your social profiles allowing your friends to join in with the fun.</p>

		<div class="autoshare_settings row">
		<div class="left col-md-3">
			<p>Include the following actions in my feed:</p>
			<label class="checkbox"><?php echo $form->checkbox($model, "autoshareUploads", 1) ?>Upload a video</label>
			<label class="checkbox"><?php echo $form->checkbox($model, "autoshareAddToPlaylist", 1) ?>Add a video to playlist</label>
			<label class="checkbox"><?php echo $form->checkbox($model, "autoshareLikes", 1) ?>Like or dislike something</label>
			<label class="checkbox"><?php echo $form->checkbox($model, "autoshareResponses", 1) ?>Comment on a video</label>
		</div>
		<div class="right autoshare_networks col-md-4">
			<p>Automatically share my feed with these sites:</p>
			<div class="facebook">
				<span><b>Facebook</b></span>
				<span class="fb_acc_status"><img alt='loading' src="/images/ajax_loader.gif"/></span>
				<!--  AJAX -->
			</div>
			<div class="twitter">
				<span><b>Twitter</b></span>
				<span class="twt_acc_status"><img alt='loading' src="/images/ajax_loader.gif"/></span>
				<!-- AJAX -->
			</div>
		</div>
		</div>
		<div class="clear"></div>
		<div class="footer_submit"><?php echo html::submitbutton("Save Account Settings", array('class' => 'btn btn-success')) ?></div>

	<?php $form->end() ?>
	<a class="deactivate btn btn-danger" href="<?php echo glue::http()->url("/user/deactivate") ?>">Deactivate Account</a>
</div>