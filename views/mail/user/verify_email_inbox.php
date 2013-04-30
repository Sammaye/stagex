<table cellpadding="0" cellspacing="0" style='width:100%; width:550px; margin:auto;'>
	<tr>
		<td style='font-family:Arial; font-size:12px;'>
			<p><b>Hello <?php echo $username ?></b></p>

			<p>You have requested that your account be moved to a different inbox located at <?php echo $new_email ?>. In order for your account to be moved
			you must first verify this is your inbox by clicking here: <?php echo $verify_url ?></p>

			<p><b>Note:</b> Some mail clients will not transform the above link into a clickable URL. If this has happened to you please copy and paste
			the link into your nearest browser.</p>
		</td>
	</tr>
</table>