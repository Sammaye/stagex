<?php
if(!isset($view)) $view = '';
if(!isset($mode)) $mode = null;

if($item->type == "text"){ ?>
<div class='response video_text_response_item <?php if($view == 'thread' && (count(preg_split('/,/', $item->path)) > 1)): echo " thread_comment"; endif; ?>'
	data-id='<?php echo $item->_id ?>' style='margin-bottom:15px;'>

	<?php if(glue::auth()->check(array('^' => $item->video)) && $mode == 'admin'){ ?>
		<div class='checkbox_col' style='float:left;padding:15px 0 0 10px;'><div class="checkbox_input" style=''><?php echo html::checkbox('comment_id[]', strval(isset($custid) ? $custid : $item->_id), 0, 
				array('class' => 'response_selector')) ?></div></div>
	<?php } ?>
	
	<div style='<?php if(glue::auth()->check(array('^' => $item->video)) && $mode == 'admin'){ echo "margin-left:15px;"; } ?>float:left;'>
	<a style='font-size:14px;font-weight:bold;line-height:20px;' href='<?php echo glue::http()->url('/user/view', array('id' => strval($item->author->_id))) ?>' class='author'><?php echo $item->author->getUsername() ?></a>
	<span style='color:#999999;font-size:11px;margin-left:15px;line-height:20px;'><?php echo $item->ago($item->created) ?></span>
	<div class='response_content' style='line-height:20px;'>
		<?php if($item->thread_parent instanceof app\models\VideoResponse): 
			echo \html::a(glue::http()->url('/user/view', array('id' => strval($item->thread_parent->author->_id))),"@".$item->thread_parent->author->getUsername());
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
	<div class='response_footer' style='line-height:20px;margin-top:7px;'>
		<span class="btn_approved" style="<?php if(!$item->approved) echo "display:none;"; ?>">
		<?php if($item->video->voteableComments): ?>
			<span class="response_likes footer_block">
		 	<?php if($item->currentUserLikes()): ?><a href='#' class='btn_unlike'>Unlike</a><?php else: ?><a href='#' class='btn_like'>Like</a><?php endif; ?>
			<span class='likes'><?php if($item->likes > 0): echo "+".$item->likes; endif ?></span>
			</span>
		<?php endif; ?>
		<?php if($item->video->allowTextComments && !glue::auth()->check(array('^' => $item))){ ?><a href='#' class='btn_reply footer_block'>Reply</a><?php } ?>
		</span>	
		<?php if(!$item->approved): ?><span class='btn_pending footer_block' style='color:#C09853;'>Pending 
			<?php if(glue::auth()->check(array('^' => $item->video))): ?><a href="#" class="btn_approve">Approve</a><?php endif; ?></span><?php endif; ?>

		<?php if(glue::auth()->check(array('^' => $item)) || glue::auth()->check(array('^' => $item->video))){ ?>
			<a href='#' class='btn_delete footer_block'>Delete</a>
		<?php }
		if($item->thread_parent):
			echo "<a href='".glue::http()->url('/videoresponse/thread', array('id' => $item->_id))."' class='footer_block' target='_blank'>View thread</a> ";
		endif; ?>			
	</div>
	<?php endif; ?>	
	</div>
	<div class="clear"></div>

		<?php if(glue::auth()->check(array('@'))){ ?>
			<div class='reply' style='display:none;'>
				<div class='reply_inner'>
					<div class='block_summary' style='display:none;'></div>
					<div class='user_img'><img alt='thumbnail' src='<?php echo glue::user()->getAvatar(40, 40); ?>'/></div>
					<div class='reply_right'>
						<?php echo html::textarea('reply_comment_content', null, array('class' => 'reply_comment_content')) ?>
						<div class='reply_footer'>
							<div class='green_css_button post_comment_reply' style='float:left;'>Post reply</div>
							<div class='grey_css_button cancel' style='float:left; margin-left:7px;'>Cancel</div>
						</div>
					</div>
					<div class="clear"></div>
				</div>
			</div>
		<?php } ?>

		<?php if($item->thread_parent): ?>
			<div class='thread_parent_viewer'>
				<div class='indented_bar'><div>&nbsp;</div></div>
				<?php if($item->thread_parent->deleted == 1 || !$item->thread_parent){ ?>
					<i>This comment has since been removed</i>
				<?php }else{ ?>
					<div class='parent_content' style=''><span class='caption'><?php echo html::a(array('href' => glue::http()->url('/user/view', array('id' => strval($item->thread_parent->author->_id))),
						'text' => $item->thread_parent->author->getUsername()))." said: "; ?></span><?php echo html::encode($item->thread_parent->content); ?></div>
				<?php } ?>
			</div>
		<?php endif; ?>
	</div>
<?php }elseif($item->type == "video"){ ?>
	<div class='video_response_item video_video_response_item' data-id='<?php echo $item->_id ?>'>
		<?php if(glue::auth()->check(array('^' => $item->video)) && $mode == 'admin'){ ?>
			<div class='comment_select'><?php echo html::checkbox('selected_comment', strval($item->_id), 0, array('class' => 'response_selector')) ?></div>
		<?php } ?>
		<div>
			<?php if($item->reply_video && !(bool)$item->deleted){ ?>
				<div class='vid_img'><a href='<?php echo glue::http()->url('/video/watch', array('id' => strval($item->reply_video->_id))) ?>'><img alt='thumbnail' src="<?php echo $item->reply_video->getImage(124, 69) ?>"/></a></div>
				<div class='video_response_right'>
					<div class='title'><a href='<?php echo glue::http()->url('/video/watch', array('id' => strval($item->reply_video->_id))) ?>'><?php echo $item->reply_video->title ?></a></div>
					<a href='<?php echo glue::http()->url('/user/view', array('id' => strval($item->author->_id))) ?>'><?php echo "@".$item->author->getUsername() ?></a> <?php echo $item->ago($item->created) ?>
					<?php if($item->approved && (glue::auth()->check(array('^' => $item)) || glue::auth()->check(array('^' => $item->video)))){ ?>
						&nbsp;-&nbsp;<a href='#' class='delete_button'>Delete</a>
					<?php } ?>
					<?php if(!(bool)$item->approved){ ?>
						<div class='warning_message'>Comment Awaiting Moderation</div>
					<?php } ?>
				</div>
				<div class="clear"></div>
				<?php if(!$item->approved && glue::auth()->check(array('^' => $item->video))){ ?>
					<div class="moderate_response_actions">
						<div class='grey_button_left delete_button'>Remove</div>
						<div class='green_button_right approve_button'>Approve</div>
					</div>
				<?php } ?>
			<?php }else{ ?>
				<span class='small'><i>This response has been deleted</i></span>
			<?php } ?>
		</div>
	</div>
<?php } ?>