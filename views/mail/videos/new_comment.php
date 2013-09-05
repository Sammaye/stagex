<p><?php echo html::a(array('href'=>array('/user/view','id'=>$from->_id),'text'=>$from->getUsername()))?> has made a new response to your video 
<?php echo html::a(array('href'=>array('/video/watch','id'=>$video->_id),'text'=>$video->title))?>:</p>
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
	<p style='color:#b94a48;'>This response requires your approval before it can be displayed to everyone</p>
<?php } ?>
<p><?php echo html::a(array('href'=>array('/videoresponse/list','id'=>$video->_id),'text'=>'Click here to view all responses and administrate them (including replying)'))?></p>