<table cellpadding="0" cellspacing="0" style='width:100%; width:550px; margin:auto;'>
	<tr>
		<td style='font-family:Arial; font-size:12px;'>
			<p><b>Hello <?php echo $username ?></b></p>
			<p>Your video (<?php echo $video->title?>) has failed to encode. If you believe it should have worked you can <a href='https://getsatisfaction.com/stagex'>contact support here</a> quoting this video ID: <?php echo $video->_id ?></p>
		</td>
	</tr>
</table>