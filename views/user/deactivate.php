<div class='deactivate_body'>
	<h1>Account Deactivation</h1>

	<p>Please confirm that you wish to deactivate and permantly delete your StageX account.</p>

	<p>Note: Your videos and playlists and stream may continue to be visible post deletion for a while as your information is deleted. The duration of visible time depends on
	the size of your account however deletions will occur as fast as humanly (or robotly) possible.</p>

	<form method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
		<div class='btn-success go_back' onclick="window.location='/user/settings'; return false;">No, wait! I didn't mean to press it</div>
		<a href='<?php echo glue::http()->createUrl('/user/deactivate', array('delete' => 1)) ?>' class="submit">I am sure, delete me</a>
	</form>
	<div class="clear"></div>
</div>