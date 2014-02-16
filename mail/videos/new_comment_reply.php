<p><?php echo html::a(array('href'=>array('/user/view','id'=>$from->_id),'text'=>$from->getUsername())) ?> has replied to one of your responses on 
<?php echo html::a(array('href'=>array('/video/watch','id'=>$video->_id),'text'=>$video->title)) ?></p>
<p>&nbsp;</p>
<p><?php echo nl2br(html::encode($comment->content)) ?></p>
<p>&nbsp;</p>
<p><?php echo html::a(array('href'=>array('/videoresponse/thread', 'id' => $comment->_id),'text'=>'You can view the entire thread'))?> or 
<?php echo html::a(array('href'=>array('/videoresponse/list','id'=>$video->_id),'text'=>'view all responses')) ?> for that video.</p>			
<p>&nbsp;</p>

<p>---- Your comment ----</p>
<p><?php echo nl2br(html::encode($comment->thread_parent->content)) ?></p>
<p>----------------------</p>