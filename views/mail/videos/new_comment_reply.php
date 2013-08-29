<table cellpadding="0" cellspacing="0" style='width:100%; width:550px; margin:auto;'>
	<tr>
		<td style='font-family:Arial; font-size:12px;'>
			<p><b>Hello <?php echo $username ?></b></p>

			<p><a href='<?php echo glue::http()->url('/user/view', array('id' => $from->_id)) ?>'><?php echo $from->getUsername() ?></a> has made a new reply to your comment on the video
				<a href='<?php echo glue::http()->url('/video/watch', array('id' => $video->_id)) ?>'><?php echo $video->title ?></a>:</p>

			<p>&nbsp;</p>
			<p><?php echo nl2br(html::encode($comment->content)) ?></p>
			<p>&nbsp;</p>

			<p>You can reply to this comment by going to either its <a href='<?php echo glue::http()->url('/videoresponse/thread', array('id' => $comment->_id)) ?>'>thread page</a> or the
				<a href='<?php echo glue::http()->url('/videoresponse/view_all', array('id' => $video->_id)) ?>'>all comments page</a> for that video.</p>
		</td>
	</tr>
</table>