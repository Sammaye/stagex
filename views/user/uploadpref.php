<?php $this->addJsScript('account_settings', '
	$(document).ready(function(){
		$(".submit_changes").click(function(event){
			event.preventDefault();
			$(".invisible_submit").trigger("click");
		});
	});
') ?>

<div class="uploadPref_body">

	<?php echo html::form_summary($defaults_model, array(
		'errorHead' => '<h2>Could not save upload preferences</h2>Your upload preferences could not be saved because:',
		'successMessage' => 'Your upload settings have been saved'
	)) ?>

	<h1 style='<?php if($defaults_model->hasSummary()) echo "margin-top:15px;" ?>'>Upload Preferences</h1>

	<?php $form= html::activeForm() ?>
		<div class="preferences_form">
		<div>
			<div class='descriptor'>
				<h2>Visibility</h2>
				<p>There are various methods of wathcing your video. The most common are from this website and on a mobile device.</p>
				<p>These settings allow you to decide how your video will display within search or direct hits from either our site or an external one.</p>
			</div>
			<div class='form_part'>
				<div class="grid_5 push_1 alpha watch_video_edit_listing">
					<?php $group = $form->radio_group($defaults_model, "default_video_settings[listing]") ?>
					<label><?php echo $group->add(1) ?><span>Public</span></label>
					<label><?php echo $group->add(2) ?><span>Unlisted</span></label>
					<label><?php echo $group->add(3) ?><span>Private</span></label>

					<div class='bordered_form_section'>
						<label class='block_label'><?php echo $form->checkbox($defaults_model, "default_video_settings[embeddable]", 1) ?><span>Allow embedding of my video</span></label>
					</div>
				</div>
			</div>
			<div class="clearer"></div>
		</div>

		<div class='spaced_part'>
			<div class='descriptor'>
				<h2>Responses</h2>
				<p>Other users on StageX can respond to your video allowing them to express what they think of it and how it effects them.</p>
				<p>These settings allow you to decide what other users can share with you through video responses and which response type they are allowed to share with you.</p>
			</div>
			<div class='form_part'>
				<div class="grid_5 omega push_1 upload_pref_right">
					<?php $group = $form->radio_group($defaults_model, "default_video_settings[mod_comments]") ?>
					<div class="video_watch_edit_reponses">
						<label><?php echo $group->add(0) ?><span>Automatically post all comments</span></label>
						<label><?php echo $group->add(1) ?><span>Make all moderated</span></label>
					</div>
					<div class='bordered_form_section'>
						<label class='block_label'><?php echo $form->checkbox($defaults_model, "default_video_settings[voteable_comments]", 1) ?><span>Allow users to vote on responses</span></label>
						<label class='block_label'><?php echo $form->checkbox($defaults_model, "default_video_settings[vid_coms_allowed]", 1) ?><span>Allow video responses</span></label>
						<label class='block_label'><?php echo $form->checkbox($defaults_model, "default_video_settings[txt_coms_allowed]", 1) ?><span>Allow text responses</span></label>
					</div>
				</div>
			</div>
			<div class="clearer"></div>
		</div>

		<div class='spaced_part'>
			<div class='descriptor'>
				<h2>Video Statistics</h2>
				<p>Do you wish to make your video statistics private by default?</p>
				<p>Video statistics allow users to gain more insight about your video and who is viewing it. It does not give them access to unrestricted statistics
				such as browsers or date constricted viewings but does allow them to see the last 7 days of viewings.</p>
			</div>
			<div class='form_part'>
				<div class="grid_5 alpha omega push_1 padded_cell">
					<label class='block_label'><?php echo $form->checkbox($defaults_model, "default_video_settings[p_stats]", 1) ?><span>Make my statistics private</span></label>
				</div>
			</div>
			<div class="clearer"></div>
		</div>

		<div class='spaced_part'>
			<div class='descriptor'>
				<h2>Voting</h2>
				<p>Do you want to allow users to up and down vote your video?</p>
			</div>
			<div class='form_part'>
				<div class="grid_5 alpha omega push_1 padded_cell">
					<label class='block_label'><?php echo $form->checkbox($defaults_model, "default_video_settings[voteable]", 1) ?><span>Allow users to vote on this video</span></label>
				</div>
			</div>
			<div class="clearer"></div>
		</div>

		<div class='spaced_part'>
			<div class='descriptor'>
				<h2>Video Licencing</h2>
				<p>Do your videos have a default licencing scheme?</p>
			</div>
			<div class='form_part'>
				<div class="grid_5 alpha omega push_1 padded_cell">

					<?php $grp = $form->radio_group($defaults_model, 'default_video_settings[licence]') ?>
					<div class="label_options">
						<label class='first block_label'><?php echo $grp->add('1') ?><span>Standard StageX Licence</span></label>
						<label class='block_label'><?php echo $grp->add('2') ?><span>Creative Commons Licence</span></label>
					</div>

					<?php //echo $form->selectbox($defaults_model, 'licence', array("1"=>"StageX Licence", "2"=>"Creative Commons Licence")) ?>
				</div>
			</div>
			<div class="clearer"></div>
		</div>
		<div class="grey_css_button submit_changes" style='font-size:12px; margin-top:10px;'>Save Changes</div>
		</div>
		<?php echo html::submitbutton("save", array('class' => 'invisible_submit')) ?>
	<?php $form->end() ?>
</div>