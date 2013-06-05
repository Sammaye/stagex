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

<?php echo html::form_summary($model, array(
	'errorHead' => '<h2>Could not save profile settings</h2>Your profile settings could not be saved because:',
	'successMessage' => ''
)) ?>

<div class="grid_10 push_1" style=''>

	<div class="profile_setpicture_outer">
		<?php $form = html::activeForm(array("enctype"=>"multipart/form-data")) ?>
			<div class="account_profile_ppicture_image"><img alt='thumbnail' src="<?php echo $model->getPic(125, 125) ?>"/></div>
			<div class="account_profile_ppicture_upload">
				<h2>Choose a profile picture:</h2>
				<?php echo $form->filefield($model, "profile_image", array('id' => 'profile_pic_uploader')) ?>
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
			<div class='float_left'><?php echo $form->selectBox($model, "birth_day", glue\util\DateTime::getDaysOfMonth(), array("head"=>array("", "Day:"), 'class' => 'birth_day'));
			echo $form->selectBox($model, "birth_month", glue\util\DateTime::getMonthsOfYear(), array("head"=>array("", "Month:"), 'class' => 'birth_month'));
			echo $form->selectBox($model, "birth_year", glue\util\DateTime::getYearRange(), array("head"=>array("", "Year:"))) ?></div>
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