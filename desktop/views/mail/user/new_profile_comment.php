<table cellpadding="0" cellspacing="0" style='width:100%; width:550px; margin:auto;'>
	<tr>
		<td style='font-family:Arial; font-size:12px;'>
			<p><b>Hello <?php echo $username ?></b></p>

			<p><a href='<?php echo glue::url()->create('/user/view', array('id' => $from->_id)) ?>'><?php echo $from->getUsername() ?></a> has posted a new comment on your profile:</p>
			<p>&nbsp;</p>
			<p><?php echo nl2br(html::encode($comment)) ?></p>
			<p>&nbsp;</p>
			<p><a href='<?php echo glue::url()->create('/stream#wall_post_reply/'.$from->_id) ?>'>Reply</a> | <a href='<?php echo glue::url()->create('/stream') ?>'>View stream</a></p>
		</td>
	</tr>
</table>