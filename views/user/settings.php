
<?php $this->js('account_settings', '
	$(document).ready(function(){
		account_edit_click();
	});

	function account_edit_click(){
		$(".edit_account_part").unbind("click");
		$(".cancel_edit_part").unbind("click");
		$(".submit_changes").unbind("click");

		$(".edit_account_part").click(function(event){
			event.preventDefault();
			$(this).parents(".account_part").find(".account_part_edit").css({ "display": "block" });
			$(this).parents(".account_part").find(".c_val").css({ "display": "none" });
			$(this).removeClass("edit_account_part").addClass("cancel_edit_part").text("Cancel Edit");
			account_edit_click();
		});

		$(".cancel_edit_part").click(function(event){
			event.preventDefault();
			$(this).parents(".account_part").find(".account_part_edit").css({ "display": "none" });
			$(this).parents(".account_part").find(".c_val").css({ "display": "block" });
			$(this).removeClass("cancel_edit_part").addClass("edit_account_part");

			if($(this).parents(".account_part").hasClass("edit_username")){ $(this).text("Change Username"); account_edit_click(); return; }
			if($(this).parents(".account_part").hasClass("edit_email_address")){ $(this).text("Change Email Address"); account_edit_click(); return; }
			if($(this).parents(".account_part").hasClass("edit_password")){ $(this).text("Change Password"); account_edit_click(); return; }

			$(this).text("Edit");
			account_edit_click();
		});

		$(".submit_changes").click(function(event){
			event.preventDefault();
			$(this).parents(".account_part").find(".invisible_submit").trigger("click");
		});
	}
') ?>

<div class="account_settings_body">

	<?php echo html::form_summary($model, array(
		'errorHead' => '<h2>Could not save settings</h2>Your account settings could not be saved because:'
	)) ?>

	<h1 class='section_head' style='<?php ?>'>General</h1>

	<div class="account_part edit_username">
		<div>
			<div class='caption'>Username</div>
			<div class='edit_link'><a href="#" class="edit_account_part">Change Username</a></div>
			<div class="clearer"></div>
		</div>
		<div class="c_val"><?php echo $model->username ?></div>
		<div class="small_submit_form_account account_part_edit">
			<?php $form = html::activeForm() ?>
				<ul class="description_ul">
					<li>Usernames can be numbers and letters</li>
					<li>Usernames are permitted to contain underscores (_)</li>
					<li>Your username is your profile presence name when no other information is provided</li>
				</ul>
				<div class="account_settings_form_outer">
					<div class="form_row"><?php echo $form->textField($model, "username") ?></div>
				</div>
				<?php echo $form->hiddenfield($model, "action", array("value"=>"updateUsername")) ?>
				<?php echo html::submitbutton("save", array('class' => 'invisible_submit')) ?>
			<?php $form->end() ?>
		</div>
		<div class="clearer"></div>
	</div>

	<div class="account_part edit_email_address">
		<div>
			<div class='caption'>Email Address</div>
			<div class='edit_link'><a href="#" class="edit_account_part">Change Email Address</a></div>
			<div class="clearer"></div>
		</div>
		<div class="c_val"><?php echo $model->email ?></div>
		<div class="small_submit_form_account account_part_edit">
			<?php $form = html::activeForm() ?>
				<p><b>Note:</b> Verfication of existance of inbox must be provided before change is saved.</p>
				<div class="account_settings_form_outer">
					<div class="form_row"><?php echo html::label("Email Address:", "newEmail") ?><?php echo $form->textField($model, "newEmail") ?></div>
					<div class="clearer"></div>
				</div>
				<?php echo html::submitbutton("save", array('class' => 'invisible_submit')) ?>
			<?php $form->end() ?>
		</div>
		<div class="clearer"></div>
	</div>

	<div class="account_part edit_password">
		<div>
			<div class='caption'>Password</div>
			<div class='edit_link'><a href="#" class="edit_account_part">Change Password</a></div>
			<div class="clearer"></div>
		</div>
		<div class="account_part_edit">
			<?php $form = html::activeForm() ?>
				<ul class="description_ul">
					<li>Changing your password will sign out all devices</li>
					<li>Do Not use the same password as you do for other sites</li>
					<li>The password must be at least 6 characters in length</li>
					<li>Use a combination of upper case, numbers and symbols</li>
				</ul>

				<div class='account_settings_form_outer'>
					<div class="form_row"><?php echo html::label("Old Password:", "o_password") ?><?php echo $form->passwordField($model, "o_password") ?></div>
					<div class="form_row"><?php echo html::label("New Password:", "n_password") ?><?php echo $form->passwordField($model, "new_password") ?></div>
					<div class="form_row"><?php echo html::label("Confirm Password:", "cn_password") ?><?php echo $form->passwordField($model, "cn_password") ?></div>
					<?php echo $form->hiddenField($model, "action", array("value"=>"updatePassword")) ?>
					<div class="grey_css_button submit_changes" style='font-size:12px;'>Change Password</div>
				</div>
				<?php echo html::submitbutton("save", array('class' => 'invisible_submit')) ?>
			<?php $form->end() ?>
		</div>
		<div class="clearer"></div>
	</div>

	<div class='section_hr'>
		&nbsp;
	</div>
	<?php $form = html::activeForm() ?>
			<?php echo $form->hiddenfield($model, "action", array("value"=>"")) ?>
		<?php echo html::submitbutton("save", array('class' => 'invisible_submit')) ?>
	<h1 class='section_head'>Security</h1>
	<div class="account_part details_account_part">
		<div class='edit_link'><a href="#" class="edit_account_part">Edit</a></div>
		<div class="c_val">
			<div>Single sign-on turned <?php if((bool)$model->signleSignOn){ echo "on"; }else{ echo "off"; } ?></div>
			<div>Email notifications turned <?php if((bool)$model->emailLogins){ echo "on"; }else{ echo "off"; } ?></div>
		</div>
		<div class="account_part_edit">
			<div class="clearer"></div>
			<div class='security_form'>
				<p>Note: These are advanced settings and should only be used by people who are like-wise in their knowledge of computing.</p>
					<label class='block_label'><?php echo $form->checkbox($model, "singleSignOn", 1) ?><span>Allow single sign-on</span></label>
					<div class='light_capt'>
						<p>Single Sign-on means that only one device can be logged onto this account at any given point in time. The system works by "newest take all".</p>
						<p>This method allows for the user to logout all devices automatically before logging in.</p>
					</div>
					<label class='block_label'><?php echo $form->checkbox($model, "emailLogins", 1) ?><span>Notify me via email of new logins</span></label>
			</div>
			<div class="clearer"></div>
		</div>
	</div>

	<div class='section_hr'>
		&nbsp;
	</div>

	<h1 class='section_head'>Safe Search</h1>
	<div class="account_part details_account_part">
		<div class='edit_link'><a href="#" class="edit_account_part">Edit</a></div>
		<div class="c_val"><div><?php switch($model->safeSearch){
				case 0:
					echo "Off";
					break;
				case 2:
					echo "Normal";
					break;
				case 1:
					echo "Strict";
					break;
			} ?></div></div>

		<div class="account_part_edit">
				<?php $opts = $form->radio_group($model, "safeSearch") ?>
				<div class="safe_search_form">
					<label class='block_label'><?php echo $opts->add(1) ?><span>Strict</span></label>
					<div class='light_capt'><p>This is the safest way to browse this site, stops any and all potientially bad content from reaching your eyes.</p></div>
					<label class='block_label'><?php echo $opts->add("0") ?><span>Off</span></label>
					<div class='light_capt'><p>This gives you everything in its raw form. Not for children!</p></div>
				</div>
		</div>
		<div class="clearer"></div>
	</div>

	<div class='section_hr'>
		&nbsp;
	</div>

	<h1 class='section_head'>Playback</h1>
	<div class="account_part details_account_part">
		<div class='edit_link'><a href="#" class="edit_account_part">Edit</a></div>
		<div class='c_val'><div><?php echo $model->useDivx ? "Videos Play in DivX web player" : "DivX web player turned off" ?></div></div>
		<div class='c_val'><div><?php echo $model->autoplayVideos ? "Autoplay is turned on" : "Autoplay is turned off" ?></div></div>
		<div class="clearer"></div>
		<div class="account_part_edit">
				<div class="privacy_form">
					<label class='block_label'><?php echo $form->checkbox($model, 'useDivx', 1) ?><span>Use DivX Player</span></label>
					<label class='block_label'><?php echo $form->checkbox($model, 'autoplayVideos', 1) ?><span>Automatically play videos</span></label>
					<div class='light_capt'><p>This will automatically play any videos you view on this site.</p></div>
				</div>
		</div>
		<div class="clearer"></div>
	</div>

	<div class='section_hr'>
		&nbsp;
	</div>


	<h1 class='section_head'>Email Notifications</h1>
	<div class="account_part details_account_part">
		<div class='edit_link'><a href="#" class="edit_account_part">Edit</a></div>
		<div class="c_val">
			<?php if($model->emailEncodingResult){ ?><div>When my videos finish/fail encoding</div><?php } ?>
			<?php if($model->emailVideoResponses){ ?><div>When someone replies to one of my videos</div><?php } ?>
			<?php if($model->emailVideoResponseReplies){ ?><div>When someone replies to one of my comments</div><?php } ?>
			<?php if($model->emailWallComments){ ?><div>When someone comments on my profile</div><?php } ?>
		</div>
		<div class="clearer"></div>
		<div class="account_part_edit">
				<div class="privacy_form">
					<label class='block_label'><?php echo $form->checkbox($model, 'emailEncodingResult', 1) ?><span>When one of my new uploads fails or finishes encoding</span></label>
					<label class='block_label'><?php echo $form->checkbox($model, 'emailVideoResponses', 1) ?><span>When someone replies to one of my videos</span></label>
					<label class='block_label'><?php echo $form->checkbox($model, 'emailVideoResponseReplies', 1) ?><span>When someone replies to one of my comments</span></label>
					<label class='block_label'><?php echo $form->checkbox($model, 'emailWallComments', 1) ?><span>When someone comments on my profile</span></label>
					<div class="clearer"></div>
				</div>
		</div>
		<div class="clearer"></div>
	</div>

	<div class='section_hr'>
		&nbsp;
	</div>

	<h1 class='section_head'>Analytics</h1>
	<div class="account_part details_account_part">
		<div class='edit_link'><a href="#" class="edit_account_part">Edit</a></div>
		<div class="c_val">
			<?php if(strlen($model->clickyUid) > 0){ ?><div>You are using Clicky Analytics</div><?php } ?>
		</div>
		<div class="clearer"></div>
		<div class="account_part_edit">
				<div class="privacy_form">

					<label class='block_label textbox'><span>Clicky Account:</span><?php echo $form->textfield($model, 'clickyUid') ?></label>
					<div class='light_capt'><p>Entering your Clicky account number will allow you to track via Clicky.</p>
					<p>Enter your site ID in order to begin tracking on your video pages.</p>
					<p>Note: It would be a good idea to make a new site detached from your normal site and use that new sites ID naming the site, as an example: stagex.co.uk</p></div>
					<div class="clearer"></div>
				</div>
		</div>
		<div class="clearer"></div>
	</div>

	<?php $form->end() ?>

	<div class='section_hr'>
		&nbsp;
	</div>

	<h1 class='section_head'>Services</h1>

	<!-- <div class='services_footer_part'>
		<div class='caption'>Download All Information</div>
		<div class='link'><a href="#">Download a copy of all information held by StageX</a></div>
		<div class='clear_left'></div>
	</div> -->

	<div class='services_footer_part'>
		<div class="caption">Delete Account</div>
		<div class='link'><a href="<?php echo glue::http()->createUrl("/user/deactivate") ?>">Delete account and all data</a></div>
		<div class='clear_left'></div>
	</div>
</div>

<?php $this->js('account_settings', '
	$(document).ready(function(){
		$(".submit_changes").click(function(event){
			event.preventDefault();
			$(".invisible_submit").trigger("click");
		});
	});
') ?>

<div class="uploadPref_body">

	<?php echo html::form_summary($model, array(
		'errorHead' => '<h2>Could not save upload preferences</h2>Your upload preferences could not be saved because:',
		'successMessage' => 'Your upload settings have been saved'
	)) ?>

	<h1 style='<?php if($model->hasSummary()) echo "margin-top:15px;" ?>'>Upload Preferences</h1>

	<?php $form= html::activeForm() ?>
		<div class="preferences_form">
		<div>
			<div class='form_part'>
				<div class="grid_5 push_1 alpha watch_video_edit_listing">
					<?php $group = $form->radio_group($model, "defaultVideoSettings[listing]") ?>
					<label><?php echo $group->add(0) ?><span>Public</span></label>
					<label><?php echo $group->add(1) ?><span>Unlisted</span></label>
					<label><?php echo $group->add(2) ?><span>Private</span></label>

					<div class='bordered_form_section'>
						<label class='block_label'><?php echo $form->checkbox($model, "defaultVideoSettings[embeddable]", 1) ?><span>Allow embedding of my video</span></label>
					</div>
				</div>
			</div>
			<div class="clearer"></div>
		</div>

		<div class='spaced_part'>
			<div class='form_part'>
				<div class="grid_5 omega push_1 upload_pref_right">
					<?php $group = $form->radio_group($model, "defaultVideoSettings[moderated]") ?>
					<div class="video_watch_edit_reponses">
						<label><?php echo $group->add(0) ?><span>Automatically post all comments</span></label>
						<label><?php echo $group->add(1) ?><span>Make all moderated</span></label>
					</div>
					<div class='bordered_form_section'>
						<label class='block_label'><?php echo $form->checkbox($model, "defaultVideoSettings[voteableComments]", 1) ?><span>Allow users to vote on responses</span></label>
						<label class='block_label'><?php echo $form->checkbox($model, "defaultVideoSettings[allowVideoComments]", 1) ?><span>Allow video responses</span></label>
						<label class='block_label'><?php echo $form->checkbox($model, "defaultVideoSettings[allowTextComments]", 1) ?><span>Allow text responses</span></label>
					</div>
				</div>
			</div>
			<div class="clearer"></div>
		</div>

		<div class='spaced_part'>
			<div class='form_part'>
				<div class="grid_5 alpha omega push_1 padded_cell">
					<label class='block_label'><?php echo $form->checkbox($model, "defaultVideoSettings[privateStatistics]", 1) ?><span>Make my statistics private</span></label>
				</div>
			</div>
			<div class="clearer"></div>
		</div>

		<div class='spaced_part'>
			<div class='form_part'>
				<div class="grid_5 alpha omega push_1 padded_cell">
					<label class='block_label'><?php echo $form->checkbox($model, "defaultVideoSettings[voteable]", 1) ?><span>Allow users to vote on this video</span></label>
				</div>
			</div>
			<div class="clearer"></div>
		</div>

		<div class='spaced_part'>
			<div class='form_part'>
				<div class="grid_5 alpha omega push_1 padded_cell">

					<?php $grp = $form->radio_group($model, 'defaultVideoSettings[licence]') ?>
					<div class="label_options">
						<label class='first block_label'><?php echo $grp->add('1') ?><span>Standard StageX Licence</span></label>
						<label class='block_label'><?php echo $grp->add('2') ?><span>Creative Commons Licence</span></label>
					</div>

					<?php //echo $form->selectbox($model, 'licence', array("1"=>"StageX Licence", "2"=>"Creative Commons Licence")) ?>
				</div>
			</div>
			<div class="clearer"></div>
		</div>
		<div class="grey_css_button submit_changes" style='font-size:12px; margin-top:10px;'>Save Changes</div>
		</div>
		<?php echo $form->hiddenfield($model, "action", array("value"=>"")) ?>
		<?php echo html::submitbutton("save", array('class' => 'invisible_submit')) ?>
	<?php $form->end() ?>
</div>