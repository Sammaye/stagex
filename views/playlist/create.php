<div class="playlist_form">

<?php echo html::form_summary($model, array(
	'errorHead' => '<h4>Could not create playlist</h4>The playlist could not be created because:'
)) ?>

<?php $form = html::activeForm(array('action' => '')) ?>
<div class="form-stacked left" style='float:left;width:400px;'>
	<div class="form_row"><?php echo html::label('Title', 'title') ?><?php echo html::activeTextField($model, 'title') ?></div>
	<div class="form_row"><?php echo html::label('Description', 'description')?><?php echo html::activeTextarea($model, 'description') ?></div>			
	<input type="submit" class="btn-success" value="Create Playlist"/>
</div>
<div class='right' style='float:right;width:400px;'>
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
	<label class="checkbox"><?php echo $form->checkbox($model, 'allowFollowers',1)?>Allow people to follow this playlist</label>
</div>
<div class="clear"></div>
<?php $form->end() ?>
</div>