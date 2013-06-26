<?php use glue\Html; ?>
<div class="profile_settings">

	<?php echo html::form_summary($model, array(
		'errorHead' => '<h4>Could not save profile settings</h4>Your profile settings could not be saved because:',
		'successMessage' => ''
	)) ?>

	<?php $form = html::activeForm(array("enctype"=>"multipart/form-data")) ?>
		<div class="upload_avatar">
			<div class="left"><img alt='thumbnail' src="<?php echo $model->getAvatar(125, 125) ?>"/></div>
			<div class="right">
				<p><b>Choose a profile picture:</b></p>
				<?php echo $form->filefield($model, "avatar") ?>
				<?php echo $form->hiddenField($model, "action", array('value' => "updatePic")) ?>
				<p class="light">Maximum upload size: 2 Megabytes</p>
				<?php echo html::submitButton("Set Profile Picture", array('class'=>'btn-success')) ?>
			</div>
			<div class="clear"></div>
		</div>
	<?php $form->end() ?>

	<?php $form = html::activeForm(); ?>
		<div class="form_row">
			<?php echo html::label("Username:", "username") ?>
			<div class="form_row"><?php echo $form->textField($model, "username") ?></div>
			<div class="help-block"><p class="light">Usernames can contain numbers, letters and _</p></div>
		</div>

		<div class="form_row">
			<?php echo html::label("Name / Nick Name:", "name") ?>
			<?php echo $form->textField($model, "name") ?>
		</div>
		<div class="form_row">
			<?php echo html::label("Gender:", "gender") ?>
			<?php echo $form->selectBox($model, "gender", array(""=>"Select Gender:", "m"=>"Male", "f"=>"Female")) ?>
			<label class="checkbox show_on_profile"><?php echo $form->checkbox($model, 'genderPrivacy') ?>Show on profile</label>
		</div>
		<div class="form_row birthday">
			<?php echo html::label("Birthday:", "birthday"); ?>
			<?php echo $form->selectBox($model, "birthDay", glue\util\DateTime::getDaysOfMonth(), array("head"=>array("", "Day:"), 'class' => 'birth_day'));
			echo $form->selectBox($model, "birthMonth", glue\util\DateTime::getMonthsOfYear(), array("head"=>array("", "Month:"), 'class' => 'birth_month'));
			echo $form->selectBox($model, "birthYear", glue\util\DateTime::getYearRange(), array("head"=>array("", "Year:"))) ?>
			<label class="checkbox show_on_profile"><?php echo $form->checkbox($model, 'birthdayPrivacy') ?>Show on profile</label>
		</div>
		<div class="form_row">
			<?php echo html::label("Country:", "country"); ?>
			<?php echo $form->selectBox($model, "country", new Collection('countries', array("code", "name")), array("head"=>array("", "Country:"))) ?>
			<label class="checkbox show_on_profile"><?php echo $form->checkbox($model, 'countryPrivacy') ?>Show on profile</label>
		</div>
		<div class="form_row about">
			<?php echo html::label("About Yourself:", "about"); ?>
			<?php echo $form->textarea($model, "about") ?>
		</div>
	
		<p class="external_links_head"><b>External Links</b></p>
		<p>Place links of upto 6 other websites you own/like here to display them on your profile</p>

		<div class="external_links">
			<div class="list">
				<?php
				$socialProfiles = is_array($model->externalLinks) ? $model->externalLinks : array();

				$i = 0;
				foreach($socialProfiles as $socialProfile){
					?>
					<div class="external_link">
						<div><?php echo html::label('URL:').$form->textfield($model, "[externalLinks][$i]url", array('value' => $socialProfile['url'])) ?></div>
						<div><?php echo html::label('Link Title (optional):').$form->textfield($model, "[externalLinks][$i]title", array('value' => $socialProfile['title'])) ?></div>
						<a href="#" class="remove">Remove</a>
					</div>
					<?php $i++;
				} ?>
			</div>

			<?php
			ob_start();
				?><div class="external_link">
					<div><?php echo html::label('URL:').html::activeTextField($model,"[externalLinks][0]url") ?></div>
					<div><?php echo html::label('Link Title (optional):').html::activeTextField($model,"[externalLinks][0]title") ?></div>
					<a href="#" class="remove">Remove</a>
				</div><?php
				$item_html = js_encode(ob_get_contents());
			ob_end_clean();


			$this->js('manageSocialProfiles', "
				$(function(){

					var socialProfileCount = ".$i++.";

					$('.addExternalLink').click(function(event){
						event.preventDefault();
						var html=$item_html;
						html=html.replace(/\[0\]/g,'['+socialProfileCount+']');
						$('.external_links .list').append(html);
						socialProfileCount++;
					});

					$(document).on('click', '.external_links .remove', function(event){
						event.preventDefault();
						var el = $(this);

						$(this).parents('.external_link').fadeOut('slow', function(){
							el.parents('.external_link').remove();
						});
					});
				});
			"); ?>

			<a href="#" class="addExternalLink">Add New External Link</a>
		</div>
		
		<?php echo html::submitbutton('Save Profile Information', array('class' => 'btn-success'))?>
	<?php $form->end() ?>
</div>