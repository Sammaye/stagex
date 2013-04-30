<table cellpadding="0" cellspacing="0" style='width:100%; width:550px; margin:auto;'>
	<tr>
		<td style='font-family:Arial; font-size:12px;'>
			<p><b>Hello <?php echo $username ?></b></p>
			<p>Your video (<?php echo $video->title?>) has finished encoding and is ready for viewing here: <a href='<?php echo glue::url()->create('/video/watch', array('id' => $video->_id)) ?>'><?php echo $video->title ?></a></p>
		</td>
	</tr>
</table>