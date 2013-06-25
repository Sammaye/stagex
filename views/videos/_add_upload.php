<div class="upload_item" data-id='<?php echo $u_id ?>' id="upload_item_<?php echo $u_id ?>">

	<div class="uploadForm" id="uploadForm_<?php echo $u_id ?>">
		<form action="<?php echo glue::url()->create('/video/upload_to_server', array('id' => $u_id), glue::$params['uploadBase']) ?>" target="u_ifr<?php echo $u_id ?>" method="post" enctype="multipart/form-data">
			<a href="javascript: void(0)" class='add_upload'>Click here to Upload a Video
			<input type="hidden" name="UPLOAD_IDENTIFIER" value="<?php echo $u_id ?>" /><input type="file" name="<?php echo $u_id ?>" id="<?php echo $u_id ?>" class='file_upload' /></a>
		</form><iframe name="u_ifr<?php echo $u_id ?>" style="display:none;" id="u_ifr<?php echo $u_id ?>"></iframe>
	</div>

	<div class="uploading_pane" id="uploading_pane_<?php echo $u_id ?>" style='display:none;'>
		<div class='bar_summary'><span></span><div class='close'><a href='#'><?php echo utf8_decode('&#215;') ?></a></div></div>
		<div class='inner_padded'>
			<div class="form_top">
				<h1 class="file_title"><img alt='video' src="/images/videos_small.png"/> <span></span></h1>
				<div class="uploadBar">
					<div class="uploadProgOuter"><div class="uploadProgInner">&nbsp;</div></div>
					<span class="percent_complete">0%</span><a href="#" class="cancel">Cancel</a><div class="clear"></div>
					<div class="message" id="upload_status_<?php echo $u_id ?>"><span>Connecting to server...</span></div>
				</div>
				<div class='remove'><a href='#'><?php echo utf8_decode('&#215;') ?></a></div>
			<div class='clearer'></div></div>
			<a href='#' class='toggle_panel'>Show more options</a>

			<div class="upload_details" style='display:none;'>

				<div class='block_summary' style='display:none;'>
					<div class='tl'></div><div class='tr'></div><div class='bl'></div><div class='br'></div>
					<div class='close'><a href='#'><?php echo utf8_decode('&#215;') ?></a></div>
					<div class='message_content'></div>
				</div>

				<?php $form = html::activeForm(array('action' => '')) ?>
					<div class='form'><div class='grid_block form_left alpha'>
						<div class="form_row"><div class='caption'><?php echo html::label('Title', 'title') ?></div><?php echo html::activeTextField($model, 'title', array('id' => 'video_title_input')) ?></div>
						<div class="form_row"><div class='caption'><?php echo html::label('Description', 'description')?></div><?php echo html::activeTextarea($model, 'description') ?></div>
						<div class="form_row last"><div class='caption'><?php echo html::label('Tags', 'string_tags') ?></div><?php echo html::activeTextField($model, 'string_tags') ?></div>
					</div>
					<div class='grid_block form_right omega'>
						<div class='options_box'>
							<h2>Category</h2><?php echo html::activeSelectbox($model, 'category', $model->categories('selectBox')) ?>
						</div>
						<div class="options_box">
							<h2>Adult Content</h2>
							<div class="label_options"><label><?php echo $form->checkbox($model, 'adult_content', 1) ?><span>This video is not suitable for family viewing</span></label></div>
						</div>
						<div class="options_box">
							<h2>Listing</h2>
							<?php $grp = html::activeRadio_group($model, 'listing') ?>
							<div class="label_options">
								<label><?php echo $grp->add(1) ?><span>Listed</span></label>
								<div class='light_caption'><p>Your video is public to all users of StageX</p></div>
								<label><?php echo $grp->add(2) ?><span>Unlisted</span></label>
								<div class='light_caption'><p>Your video is hidden from listings but can still be accessed directly using the video URL</p></div>
								<label><?php echo $grp->add(3) ?><span>Private</span></label>
								<div class='light_caption'><p>No one but you can access this video</p></div>
							</div>
						</div>
						<div class="options_box licence_options">
							<h2>Licence (<a href='#'>Learn More</a>)</h2>
							<?php $grp = html::activeRadio_group($model, 'licence') ?>
							<div class="label_options">
								<label class='first'><?php echo $grp->add('1') ?><span>Standard StageX Licence</span></label>
								<label><?php echo $grp->add('2') ?><span>Creative Commons Licence</span></label>
							</div>
						</div>
						<a href="#" class='save_video_details green_css_button float_right'>Save Details</a>
					</div><div class='clearer'></div></div>
				<?php $form->end() ?>
			</div><div class='clearer'></div>
		</div>
	</div><div class='clearer'></div>
</div>