<?php
if(!isset($view)) $view = '';
if(!isset($mode)) $mode = null;

if(!glue::auth()->check(array('viewable' => $item))){
	$item->author = new app\models\User;
	$item->author->username = '[Deleted]';
	$item->content = '[Deleted]';
}
?>
<div class='response video_text_response_item 
	<?php if($view == 'thread' && (count(preg_split('/,/', $item->path)) > 1)): echo " thread_comment"; endif; ?>
	<?php if($view == 'ajaxthread') echo ' ajax_thread_comment'; ?>'
	data-id='<?php echo $item->_id ?>'
>
	<div class="response_parents"></div>
	
	<div class="response_inner">
	<?php if(glue::auth()->check(array('^' => $item->video)) && $mode == 'admin'){ ?>
		<div class='checkbox_col'><div class="checkbox_input" style=''><?php echo html::checkbox('comment_id[]', strval(isset($custid) ? $custid : $item->_id), 0, 
				array('class' => 'response_selector')) ?></div></div>
	<?php } ?>
	
	<div class="content" style='<?php if(glue::auth()->check(array('^' => $item->video)) && $mode == 'admin'){ echo "margin-left:15px;"; } ?> float:left;'>
	<a class="author" href='<?php echo glue::http()->url('/user/view', array('id' => strval($item->author->_id))) ?>' class='author'><?php echo $item->author->getUsername() ?></a>
	<span class="date_created"><?php echo $item->ago($item->created) ?></span>
	<div class='response_content'>
		<?php if($item->thread_parent instanceof app\models\VideoResponse): 
			echo \html::a(array('href' => glue::http()->url('/user/view', array('id' => strval($item->thread_parent->author->_id))),
				'text'=>"@".$item->thread_parent->author->getUsername(), 'class' => $view === 'ajaxthread' ? '' : 'btn-load-parents'));
		elseif($item->threadParentId instanceof \MongoId):
			echo "@".$item->threadParentUsername;
		endif;
		echo ' ';	
		
		if(!$item->deleted){
			echo nl2br(html::encode($item->content));
		}else{ ?>
			<i class='small'>This response has been deleted</i>
		<?php } ?>
	</div>
	<?php if(glue::auth()->check(array('@'))||$item->thread_parent): ?>
	<?php if($view !== 'ajaxthread'){ ?>
	<div class='response_footer'>
		<span class="btn_approved" style="<?php if(!$item->approved) echo "display:none;"; ?>">
		<?php if($item->video->voteableComments): ?>
			<span class="response_likes footer_block">
		 	<?php if($item->currentUserLikes()): ?><a href='#' class='btn_unlike active'>Unlike</a><?php else: ?><a href='#' class='btn_like'>Like</a><?php endif; ?>
			<span class='likes'><?php if($item->likes > 0): echo "+".$item->likes; endif ?></span>
			</span>
		<?php endif; ?>
		<?php if($item->video->allowTextComments && !glue::auth()->check(array('^' => $item))){ ?><a href='#' class='btn_reply footer_block'>Reply</a><?php } ?>
		<?php if($view !== 'thread' && $item->replies > 0 && $mode !== 'singleThread'){
			?><a href="#" class="btn-load-children footer_block">View all <?= $item->replies ?> replies</a><?php 
		} ?>
		</span>
		<?php if(!$item->approved): ?><span class='btn_pending footer_block'><span class="text-warning">Pending</span> 
			<?php if(glue::auth()->check(array('^' => $item->video))): ?><a href="#" class="btn_approve">Approve</a><?php endif; ?></span><?php endif; ?>

		<?php if(glue::auth()->check(array('^' => $item)) || glue::auth()->check(array('^' => $item->video))){ ?>
			<a href='#' class='btn_delete footer_block'>Delete</a>
		<?php }
		if($item->thread_parent && $mode !== 'singleThread'):
			echo "<a href='".glue::http()->url('/videoresponse/thread', array('id' => $item->_id))."' class='footer_block' target='_blank'>View thread</a> ";
		endif; ?>
		<a class="btn-close-thread" href="#">Close thread</a>
	</div>
	<?php } ?>
	<?php endif; ?>	
	</div>
	<div class="clear"></div>

	<?php if(glue::auth()->check(array('@'))&&$item->video->allowTextComments){ ?>
	<div class='reply'>
		<div class='alert'></div>
		<div class='user_img'><img alt='thumbnail' src='<?php echo glue::user()->getAvatar(40, 40); ?>'/></div>
		<div class='reply_right'>
			<div class="form-group"><?php echo html::textarea('reply_comment_content', null, array('class' => 'reply_comment_content form-control')) ?></div>
			<div class='reply_footer'>
			<input type="button" class="btn btn-success btn_post_reply" value="Post"/>
			<input type="button" class="btn btn-default btn_cancel" value="Cancel"/>
			</div>
		</div>
		<div class="clear"></div>
	</div>
	<?php } ?>
	</div>
	
	<div class="response_children"></div>
</div>