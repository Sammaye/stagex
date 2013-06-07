<div style='margin-top:30px;'>
<?php

use glue\Html;

$this->js('updateProfile', "
	$(function(){
		$('.submit_info_changes,.submit_social_changes').click(function(event){
			event.preventDefault();
			$(this).parents('form').submit();
		});
	});
") ?>

<?php

$this->js('autoshare.networks', '
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
); ?>

<?php echo html::form_summary($model, array(
	'errorHead' => '<h2>Could not save profile settings</h2>Your profile settings could not be saved because:',
	'successMessage' => ''
)) ?>

<div class="grid_10 push_1" style=''>

	<div class="profile_setpicture_outer">
		<?php $form = html::activeForm(array("enctype"=>"multipart/form-data")) ?>
			<div class="account_profile_ppicture_image"><img alt='thumbnail' src="<?php echo $model->getAvatar(125, 125) ?>"/></div>
			<div class="account_profile_ppicture_upload">
				<h2>Choose a profile picture:</h2>
				<?php echo $form->filefield($model, "avatar", array('id' => 'profile_pic_uploader')) ?>
				<?php echo $form->hiddenField($model, "action", array('value' => "updatePic")) ?>
				<div class="account_profile_ppicture_capt">
					<p>Maximum upload size: 2 Megabytes</p>
				</div>
				<span class="account_profile_ppicture_span"><?php echo html::submitButton("Set Profile Picture") ?></span>
			</div>
			<div class="clearer"></div>
		<?php $form->end() ?>
	</div>
</div>

<div class="grid_10 user_profile_body" style='margin-bottom:250px;'>
	<?php $form = html::activeForm(); ?>
		<div class="form_row">
			<?php echo html::label("Username:", "username") ?>
			<span>Usernames can be numbers, letters and _</span>
			<div class="form_row"><?php echo $form->textField($model, "username") ?></div>
		</div>

		<div class="form_row">
			<?php echo html::label("Name / Nick Name:", "name") ?>
			<div class="textfield"><?php echo $form->textField($model, "name") ?></div>
			<div class="clearer"></div>
		</div>
		<div class="form_row">
			<?php echo html::label("Gender:", "gender") ?>
			<div class='float_left'><?php echo $form->selectBox($model, "gender", array(""=>"Select Gender:", "m"=>"Male", "f"=>"Female")) ?></div>
			<div class='float_left'>
				<?php echo $form->checkbox($model, 'genderPrivacy') ?>
				<span>Show on profile</span>
			</div>
			<div class="clearer"></div>
		</div>
		<div class="form_row user_profile_birthday">
			<?php echo html::label("Birthday:", "birthday"); ?>
			<div class='float_left'><?php echo $form->selectBox($model, "birthDay", glue\util\DateTime::getDaysOfMonth(), array("head"=>array("", "Day:"), 'class' => 'birth_day'));
			echo $form->selectBox($model, "birthMonth", glue\util\DateTime::getMonthsOfYear(), array("head"=>array("", "Month:"), 'class' => 'birth_month'));
			echo $form->selectBox($model, "birthYear", glue\util\DateTime::getYearRange(), array("head"=>array("", "Year:"))) ?></div>
			<div class='float_left'>
				<?php echo $form->checkbox($model, 'birthdayPrivacy') ?>
				<span>Show on profile</span>
			</div>
			<div class="clearer"></div>
		</div>
		<div class="form_row">
			<?php echo html::label("Country:", "country"); ?>
			<div class='float_left'><?php echo $form->selectBox($model, "country", new Collection('countries', array("code", "name")), array("head"=>array("", "Country:"))) ?></div>
			<div class='float_left'>
			<div class='float_left'>
				<?php echo $form->checkbox($model, 'countryPrivacy') ?>
				<span>Show on profile</span>
			</div>
			<div class="clearer"></div>
		</div>
		<div class="form_row about">
			<?php echo html::label("About Yourself:", "about"); ?>
			<div class="textarea"><?php echo $form->textarea($model, "about") ?></div>
			<div class="clearer"></div>
		</div>
		<?php echo $form->hiddenField($model, "action", array('value' => 'updateProfile')) ?>
		<?php echo html::submitbutton('Save Profile Information', array('class' => 'invisible_submit'))?>
		<div class="grey_css_button submit_info_changes" style='font-size:12px;'>Change Profile Information</div>

		<div class="clearer"></div>
		<div class='form_divider'>
			&nbsp;
		</div>
		<h1 class='social_form_head'>External Links</h1>
		<p>Place links of upto 6 other websites you own/like here to display them on your profile</p>

		<div>
			<div id="UserSocialProfiles">
				<?php
				$socialProfiles = is_array($model->externalLinks) ? $model->externalLinks : array();

				$i = 0;
				foreach($socialProfiles as $socialProfile){
					?>
					<div class="socialProfile">
						<div class='inner_block'>
						<div class='input_block'><?php echo html::label('Url:').$form->textfield($model, "[externalLinks][$i]url", array('value' => $socialProfile['url'])) ?></div>
						<div class='input_block'><?php echo html::label('Link Title (optional):').$form->textfield($model, "[externalLinks][$i]title", array('value' => $socialProfile['title'])) ?></div>
						</div>
						<a href="#" class="removeSocialProfile">Remove</a>
					</div>
					<?php $i++;
				} ?>
			</div>

			<?php
			ob_start();
				?><div class="socialProfile">
					<div class='inner_block'>
						<div class='input_block'><?php echo html::label('Url:').html::activeTextField($model,"[externalLinks][0]url") ?></div>
						<div class='input_block'><?php echo html::label('Link Title (optional):').html::activeTextField($model,"[externalLinks][0]title") ?></div>
					</div>
					<a href="#" class="removeSocialProfile">Remove</a>
				</div><?php
				$item_html = js_encode(ob_get_contents());
			ob_end_clean();


			$this->js('manageSocialProfiles', "
				$(function(){

					var socialProfileCount = ".$i++.";

					$('.addSocialProfile').click(function(event){
						event.preventDefault();
						var html=$item_html;
						html=html.replace(/\[0\]/g,'['+socialProfileCount+']');
						$('#UserSocialProfiles').append(html);
						socialProfileCount++;
					});

					$(document).on('click', '.removeSocialProfile', function(event){
						event.preventDefault();
						var el = $(this);

						$(this).parents('.socialProfile').fadeOut('slow', function(){
							el.parents('.socialProfile').remove();
						});
					});
				});
			"); ?>

			<a href="#" class="addSocialProfile">Add New External Link</a>
			<div class="clearer"></div>
		</div>
	<?php $form->end() ?>
</div>
<div class="clearer"></div>
</div>
<div style='margin-top:30px;'>

	<?php echo html::form_summary($model, array(
		'showOnlyFirstError' => true, 'successMessage' => 'Your sharing settings have been saved'
	)) ?>

	<h1 style='<?php ?>'>Auto-Sharing</h1>

	<?php $form = html::activeForm() ?>

		<p class='user_settings_autoshare_p'>Auto-sharing allows you to connect your favourite social networks directly to your profile. When connected your social actions across this site will be
		echoed onto your social profiles allowing your friends to join in with the fun.</p>

		<div class="clear_right"></div>
		<div class="account_sharing_main_left">
			<h2>What to share</h2>
			<p>Include the following actions in my feed:</p>

			<ul class="account_sharing_auto_list">
				<li><label class="nostyle_long_checkbox-container"><?php echo $form->checkbox($model, "autoshareUploads", 1) ?><span>Upload a video</span></label></li>
				<li><label class="nostyle_long_checkbox-container"><?php echo $form->checkbox($model, "autoshareAddToPlaylist", 1) ?><span>Add a video to playlist</span></label></li>
				<li><label class="nostyle_long_checkbox-container"><?php echo $form->checkbox($model, "autoshareLikes", 1) ?><span>Like or dislike something</span></label></li>
				<li><label class="nostyle_long_checkbox-container"><?php echo $form->checkbox($model, "autoshareResponses", 1) ?><span>Comment on a video</span></label></li>
			</ul>
		</div>
		<div class="account_sharing_main_right">
			<h2>Connected Accounts</h2>
			<p>Automatically share my feed with these sites:</p>

			<div class="account_sharing_newtworks_li account_sharing_newtworks_li_first">
				<span><b>Facebook</b></span>
				<span class="fb_acc_status"><img alt='loading' src="/images/ajax_loader.gif"/></span>
				<!--  AJAX -->
			</div>
			<div class="account_sharing_newtworks_li">
				<span><b>Twitter</b></span>
				<span class="twt_acc_status"><img alt='loading' src="/images/ajax_loader.gif"/></span>
				<!-- AJAX -->
			</div>
			<?php echo $form->hiddenfield($model, 'action', array('value' => 'UserAutoShare')) ?>
			<div class="grey_css_button submit_changes">Save Changes</div>
		</div>
		<div class="clearer"></div>
		<?php echo html::submitbutton("save", array('class' => 'invisible_submit')) ?>
	<?php $form->end() ?>
</div>