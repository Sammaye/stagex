<table cellpadding="0" cellspacing="0" style='width:100%; width:550px; margin:auto;'>
	<tr>
		<td style='font-family:Arial; font-size:12px;'>
			<p><b>Hello <?php echo $username ?></b></p>

			<p><a href='<?php echo glue::http()->url('/user/view', array('id' => $from->_id)) ?>'><?php echo $from->getUsername() ?></a> has made a new reply to your video <a href='
				<?php echo glue::http()->url('/video/watch', array('id' => $video->_id)) ?>'><?php echo $video->title ?></a>:</p>
			<p>&nbsp;</p>

			<?php if($comment->type == 'video'){
				$video = $comment->reply_video; ?>
				<table>
					<tr>
						<td width='148px' height='77px'><a href='<?php echo glue::http()->url('/video/watch', array('id' => $video->_id)) ?>'>
							<img alt='thumbnail' src='<?php echo $video->getImage(138, 77) ?>'/></a>
						</td>
						<td height='77px' valign="middle"><a href='<?php echo glue::http()->url('/video/watch', array('id' => $video->_id)) ?>'><?php echo $video->title ?></a></td>
					</tr>
				</table>

			<?php }else{ ?>
				<p><?php echo nl2br(html::encode($comment->content)) ?></p>
			<?php } ?>

			<p>&nbsp;</p>
			<?php if(!$approved){ ?>
				<p>This comment requires your approval before it can be displayed to everyone. <a href='<?php
					echo glue::http()->url('/videoresponse/view_all', array('id' => $video->_id)) ?>'>Click here to view this videos comments and approve or reject them</a>.</p>
			<?php } ?>

			<p>You can reply to this comment by going to either its <a href='<?php echo glue::http()->url('/videoresponse/thread', array('id' => $comment->_id)) ?>'>thread page</a> or the
				<a href='<?php echo glue::http()->url('/videoresponse/view_all', array('id' => $video->_id)) ?>'>all comments page</a> for that video.</p>
		</td>
	</tr>
</table>