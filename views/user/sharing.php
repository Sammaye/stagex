<?php

glue::clientScript()->addJsScript('autoshare.networks', '
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

<div style='margin-top:30px;'>

	<?php echo html::form_summary($model, array(
		'showOnlyFirstError' => true, 'successMessage' => 'Your sharing settings have been saved'
	)) ?>

	<h1 style='<?php if($model->hasSummary()) echo "margin-top:15px;" ?>'>Auto-Sharing</h1>

	<?php $form = html::activeForm() ?>

		<p class='user_settings_autoshare_p'>Auto-sharing allows you to connect your favourite social networks directly to your profile. When connected your social actions across this site will be
		echoed onto your social profiles allowing your friends to join in with the fun.</p>

		<div class="clear_right"></div>
		<div class="account_sharing_main_left">
			<h2>What to share</h2>
			<p>Include the following actions in my feed:</p>

			<ul class="account_sharing_auto_list">
				<li><label class="nostyle_long_checkbox-container"><?php echo $form->checkbox($model, "autoshare_opts[upload]", 1) ?><span>Upload a video</span></label></li>
				<li><label class="nostyle_long_checkbox-container"><?php echo $form->checkbox($model, "autoshare_opts[video_2_pl]", 1) ?><span>Add a video to playlist</span></label></li>
				<li><label class="nostyle_long_checkbox-container"><?php echo $form->checkbox($model, "autoshare_opts[lk_dl]", 1) ?><span>Like or dislike something</span></label></li>
				<li><label class="nostyle_long_checkbox-container"><?php echo $form->checkbox($model, "autoshare_opts[c_video]", 1) ?><span>Comment on a video</span></label></li>
			</ul>
		</div>
		<div class="account_sharing_main_right">
			<h2>Connected Accounts</h2>
			<p>Automatically share my feed with these sites:</p>

			<div class="account_sharing_newtworks_li account_sharing_newtworks_li_first">
				<?php //echo $form->checkbox($model, "facebook", 1) ?>
				<span><b>Facebook</b></span>
				<span class="fb_acc_status"><img alt='loading' src="/images/ajax_loader.gif"/></span>
				<!--  AJAX -->
			</div>
			<div class="account_sharing_newtworks_li">
				<?php //echo $form->checkbox($model, "twitter", 1) ?>
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