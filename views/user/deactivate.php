<?php
glue::clientScript()->addJsScript('page', "
	$(function(){
		$(document).on('click', '.go_back', function(event){
			window.location = '/user/settings';
		});
	});
");
?>

<div class='singular_column'>
	<h1>Account Deactivation</h1>

	<p>Please confirm that you wish to deactivate and permantly delete your StageX account.</p>

	<p>Note: Your videos and playlists and stream may continue to be visible post deletion for a while as your information is deleted. The duration of visible time depends on
	the size of your account however deletions will occur as fast as humanly (or robotly) possible.</p>

	<p>Deleting your account will remove the ability, instantly, for users to view your videos or playlists however, they can still appear as not deleted in the search etc until
	they are actually removed from the system. You will appear as deleted to your subscribers however they will not stop receiving your wall posts until your account information
	has been scrubbed from the system. You will stop receiving emails about activity on this site immediately.</p>

	<form method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">

		<div class="account_deactivate-submit">
			<div class='green_css_button go_back' style='float:left; margin-right:25px;'>No, wait! I didn't mean to press it</div>
			<a href='<?php echo glue::url()->create('/user/deactivate', array('delete' => 1)) ?>' class='grey_css_button'>I am sure, delete me</a>
		</div>

	</form>
</div>