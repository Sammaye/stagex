<div style='margin-top:30px;'>
<?php $this->addJsScript('updateProfile', "
	$(function(){
		$('.submit_info_changes,.submit_social_changes').click(function(event){
			event.preventDefault();
			$(this).parents('form').submit();
		});
	});
") ?>

<?php echo html::form_summary($model, array(
	'errorHead' => '<h2>Could not save profile settings</h2>Your profile settings could not be saved because:',
	'successMessage' => $success_message
)) ?>

<div class="grid_10 push_1" style='<?php if($model->hasSummary()) echo "margin-top:15px;" ?>'>

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
			<div class='float_left'><?php $this->widget('application/widgets/JqselectBox.php', array(
				'model' => $model,
				'attribute' => 'profile_privacy[gender]',
				"items" => array(
					0	=>	'Public',
					1	=>	'Private',
				)
			)) ?></div>
			<div class='float_left'><?php echo $form->selectBox($model, "gender", array(""=>"Select Gender:", "m"=>"Male", "f"=>"Female")) ?></div>
			<div class="clearer"></div>
		</div>
		<div class="form_row user_profile_birthday">
			<?php echo html::label("Birthday:", "birthday"); ?>
			<div class='float_left'>
				<?php $this->widget('application/widgets/JqselectBox.php', array(
					'model' => $model,
					'attribute' => 'profile_privacy[birthday]',
					"items" => array(
						0	=>	'Public',
						1	=>	'Private',
					)
				)) ?>
			</div>
			<div class='float_left'><?php echo $form->selectBox($model, "birth_day", getDaysOfMonth(), array("head"=>array("", "Day:"), 'class' => 'birth_day'));
			echo $form->selectBox($model, "birth_month", getMonthsOfYear(), array("head"=>array("", "Month:"), 'class' => 'birth_month'));
			echo $form->selectBox($model, "birth_year", getYearRange(), array("head"=>array("", "Year:"))) ?></div>
			<div class="clearer"></div>
		</div>
		<div class="form_row">
			<?php echo html::label("Country:", "country"); ?>
			<div class='float_left'>
				<?php $this->widget('application/widgets/JqselectBox.php', array(
					'model' => $model,
					'attribute' => 'profile_privacy[country]',
					"items" => array(
						0	=>	'Public',
						1	=>	'Private',
					)
				)) ?>
			</div>
			<div class='float_left'><?php echo $form->selectBox($model, "country", new GListProvider('countries', array("code", "name")), array("head"=>array("", "Country:"))) ?></div>
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
	<?php $form->end();

	$form = html::activeForm() ?>
		<div class="clearer"></div>
		<div class='form_divider'>
			&nbsp;
		</div>
		<h1 class='social_form_head'>Connected URLs</h1>
		<p>Connecting URLs (otherwise known as other websites) to your profile can help bring extra life to your information and/or identity.</p>
		<p>You can currently add upto 6 other websites to your StageX profile.</p>

		<div>
			<div id="UserSocialProfiles">
				<?php
				$socialProfiles = is_array($model->external_links) ? $model->external_links : array();

				$i = 0;
				foreach($socialProfiles as $socialProfile){
					?>
					<div class="socialProfile">
						<div class='inner_block'>
						<div class='input_block'><?php echo html::label('Url:').$form->textfield($model, "external_links[$i][url]", array('class' => 'url')) ?></div>
						<div class='input_block'><?php echo html::label('Link Title (optional):').$form->textfield($model, "external_links[$i][title]", array('class' => 'url')) ?></div>
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
						<div class='input_block'><?php echo html::label('Url:').html::textfield("[]url", null, array('class' => 'url')) ?></div>
						<div class='input_block'><?php echo html::label('Link Title (optional):').html::textfield("[]title", null, array('class' => 'title')) ?></div>
					</div>
					<a href="#" class="removeSocialProfile">Remove</a>
				</div><?php
				$item_html = ob_get_contents();
			ob_end_clean();


			$this->addJsScript('manageSocialProfiles', "
				$(function(){

					var maxSocialProfiles = ".$i++.";

					$('.addSocialProfile').click(function(event){
						event.preventDefault();

						var social_item = $(".GClientScript::encode($item_html).");
						social_item.find('input.url').attr('name', 'User[external_links]['+maxSocialProfiles+'][url]');
						social_item.find('input.title').attr('name', 'User[external_links]['+maxSocialProfiles+'][title]');

						$('#UserSocialProfiles').append(social_item);
						maxSocialProfiles++;
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

			<div class="grey_css_button addSocialProfile" style='font-size:12px;'>Add New External Link</div>
			<div class="clearer"></div>
		</div>

		<?php echo $form->hiddenField($model, "action", array('value' => 'updateSocialProfiles')) ?>
		<?php echo html::submitbutton('Save Social Profiles', array('class' => 'invisible_submit'))?>
		<div class="grey_css_button submit_social_changes" style='font-size:12px;'>Save External Links</div>
	<?php $form->end() ?>
</div>
<div class="clearer"></div>
</div>