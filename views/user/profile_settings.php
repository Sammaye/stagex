<?php use glue\Html; ?>
<div class="profile_settings">

	<?php echo html::form_summary($model, array(
		'errorHead' => '<h4>Could not save profile settings</h4><p>Your profile settings could not be saved because:</p>',
		'successMessage' => ''
	)) ?>

	<?php $form = html::activeForm(array("enctype"=>"multipart/form-data")) ?>
		<div class="upload_avatar clearfix">
			<div class="left"><img alt='thumbnail' src="<?php echo $model->getAvatar(125, 125) ?>"/></div>
			<div class="right">
				<p><b>Choose a profile picture:</b></p>
				<?php echo $form->filefield($model, "avatar") ?>
				<?php echo $form->hiddenField($model, "action", array('value' => "updatePic")) ?>
				<p class="light">Maximum upload size: 2 Megabytes</p>
				<?php echo html::submitButton("Set Profile Picture", array('class'=>'btn btn-success')) ?>
			</div>
		</div>
	<?php $form->end() ?>

	<?php $form = html::activeForm(); ?>
		<div class="form-group">
			<?php echo html::label("Username:", "username",'grid-col-10') ?>
			<div class="grid-col-20">
			<?php echo $form->textField($model, "username",array("class"=>"form-control")) ?>
			<p class="help-block">Usernames can contain numbers, letters and _</p>
			</div>
		</div>

		<div class="form-group">
			<?php echo html::label("Name / Nick Name:", "name",'grid-col-10') ?>
			<?php echo $form->textField($model, "name",array('class'=>'form-control grid-col-20')) ?>
		</div>
		<div class="form-group">
			<?php echo html::label("Gender:", "gender", array('class'=>'grid-col-10')) ?>
			<div class="grid-col-15"><?php echo $form->selectBox($model, "gender", array(""=>"Select Gender:", "m"=>"Male", "f"=>"Female"),array('class'=>'form-control')) ?></div>
			<label class="checkbox show_on_profile"><?php echo $form->checkbox($model, 'genderPrivacy') ?>Show on profile</label>
		</div>
		<div class="form-group birthday">
			<?php echo html::label("Birthday:", "birthday", array('class'=>'grid-col-10')); ?>
			<?php echo $form->selectBox($model, "birthDay", glue\util\DateTime::getDaysOfMonth(), array("head"=>array("", "Day:"), 'class' => 'birth_day form-control grid-col-5'));
			echo $form->selectBox($model, "birthMonth", glue\util\DateTime::getMonthsOfYear(), array("head"=>array("", "Month:"), 'class' => 'birth_month form-control grid-col-8'));
			echo $form->selectBox($model, "birthYear", glue\util\DateTime::getYearRange(), array("head"=>array("", "Year:"),"class"=>'form-control grid-col-6')) ?>
			<label class="checkbox show_on_profile"><?php echo $form->checkbox($model, 'birthdayPrivacy') ?>Show on profile</label>
		</div>
		<div class="form-group">
			<?php echo html::label("Country:", "country", array('class'=>'grid-col-10')); ?>
			<?php echo $form->selectBox($model, "country", new Collection('countries', array("code", "name")), array("head"=>array("", "Country:"),"class"=>"form-control grid-col-18")) ?>
			<label class="checkbox show_on_profile"><?php echo $form->checkbox($model, 'countryPrivacy') ?>Show on profile</label>
		</div>
		<div class="form-group about">
			<?php echo html::label("About Yourself:", "about"); ?>
			<?php echo $form->textarea($model, "about",array('class'=>'form-control')) ?>
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
					<div class="external_link form-group">
						<?php echo html::label('URL:','url','grid-col-4') ?><div class="grid-col-12"><?php echo $form->textfield($model, "[externalLinks][$i]url", array('value' => $socialProfile['url'], 'class'=>'form-control')) ?></div>
						<?php echo html::label('Link Title (optional):','title','grid-col-10') ?><div class="grid-col-10"><?php echo $form->textfield($model, "[externalLinks][$i]title", array('value' => $socialProfile['title'], 'class'=>'form-control')) ?></div>
						<a href="#" class="remove">Remove</a>
					</div>
					<?php $i++;
				} ?>
			</div>

			<?php
			ob_start();
				?><div class="external_link form-group">
					<?php echo html::label('URL:','url','grid-col-4') ?><div class="grid-col-12"><?php echo html::activeTextField($model,"[externalLinks][0]url", 'form-control') ?></div>
					<?php echo html::label('Link Title (optional):','title','grid-col-10') ?><div class="grid-col-10"><?php echo html::activeTextField($model,"[externalLinks][0]title",'form-control') ?></div>
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
		
		<?php echo html::submitbutton('Save Profile Information', array('class' => 'btn btn-success'))?>
	<?php $form->end() ?>
</div>