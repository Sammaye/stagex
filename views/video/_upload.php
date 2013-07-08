<div class="upload" data-id='<?php echo $u_id ?>'>

	<div class="upload_form">
		<?php $form = html::activeForm(array(
				'action' => $this->createUrl('/video/createUpload', array('id' => $u_id), glue::$params['uploadBase']), 
				'method' => 'post', 'enctype' => 'multipart/form-data', 'target' => "u_ifr$u_id"
		)) ?>
			<a href="javascript: void(0)" class='add_upload'>Click here to Upload a Video
			<input type="hidden" name="UPLOAD_IDENTIFIER" value="<?php echo $u_id ?>" />
			<input type="file" name="<?php echo $u_id ?>" id="<?php echo $u_id ?>" class='file_upload' /></a>
		<?php $form->end() ?>
		<iframe name="u_ifr<?php echo $u_id ?>" style="display:none;" id="u_ifr<?php echo $u_id ?>"></iframe>
	</div>

	<div class="upload_details" style='display:none;'>
		<div class='alert' style='display:none;'><a href='#' class="close"><?php echo utf8_decode('&#215;') ?></a></div>
		<div class='inner'>
			<div class="progress_bar">
				<div class="progress">&nbsp;</div>
			</div>
			<span class="status">0% Connecting to server...</span><a href="#" class="cancel">Cancel</a>
			<div class="edit_information">

				<div class='alert' style='display:none;'>
					<a href='#' class="close"><?php echo utf8_decode('&#215;') ?></a>
				</div>

				<?php $form = html::activeForm(array('action' => '')) ?>
					<div class="form-stacked left">
						<div class="form_row"><?php echo html::label('Title', 'title') ?><?php echo html::activeTextField($model, 'title') ?></div>
						<div class="form_row"><?php echo html::label('Description', 'description')?><?php echo html::activeTextarea($model, 'description') ?></div>
						<div class="form_row last"><?php echo html::label('Tags', 'string_tags') ?><?php echo html::activeTextField($model, 'string_tags') ?></div>			
						<input type="button" class="btn-success" value="Save Video Details"/>
					</div>
					<div class='right'>
						<h4>Category</h4><?php echo html::activeSelectbox($model, 'category', $model->categories('selectBox')) ?>
						<h4>Adult Content</h4>
						<label class="checkbox"><?php echo $form->checkbox($model, 'adult_content', 1) ?>This video is not suitable for family viewing</label>
						<h4>Listing</h4>
						<?php $grp = html::activeRadio_group($model, 'listing') ?>
						<div class="label_options">
							<label class="radio"><?php echo $grp->add(0) ?>Listed</label>
							<p class='light'>Your video is public to all users of StageX</p>
							<label class="radio"><?php echo $grp->add(1) ?>Unlisted</label>
							<p class='light'>Your video is hidden from listings but can still be accessed directly using the video URL</p>
							<label class="radio"><?php echo $grp->add(2) ?>Private</label>
							<p class='light'>No one but you can access this video</p>
						</div>
						<h4>Licence (<a href='#'>Learn More</a>)</h4>
						<?php $grp = html::activeRadio_group($model, 'licence') ?>
						<div class="label_options">
							<label class="radio"><?php echo $grp->add('1') ?>Standard StageX Licence</label>
							<label class="radio"><?php echo $grp->add('2') ?>Creative Commons Licence</label>
						</div>
					</div>
					<div class="clear"></div>
				<?php $form->end() ?>
			</div>
		</div>
	</div>
</div>