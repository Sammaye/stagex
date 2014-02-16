<div class="container_16">
	<div class="grid_8 push_4 age_veri_outer">
		<h1 class='head'><?php echo $video->title ?> is not suitable for normal viewing</h1>
		<p>This content has been marked as not suitable for the average user.</p>
		<p>This may imply that the video has content which could relate to excessive violence, continuous swearing, graphic content, mature content or Justin Bieber.</p>
		<p>If you are sure you wish to proceed you can click the "Watch Video" button below otherwise please click the "Cancel" link or press back in your browser.</p>
		<p>To stop this message from showing in the future please turn off your safe search.</p>
		<div class='options'>
			<a href="<?php echo glue::http()->url("/video/watch", array("id"=>$video->_id, "av"=>1)) ?>" class='grey_css_button float_right'>Watch Video</a>
			<a href="/" onclick="history.back(); return false;" class='back_link'>Cancel</a>
		</div>
	</div>
</div>