<?php
if(!isset($view)) $view = '';
if(!isset($mode)) $mode = null;

if($item->type == "text"){ ?>
	<div class='video_response_item video_text_response_item <?php if($view == 'thread' && (sizeof(preg_split('/,/', $item->path)) > 1)): echo " thread_comment"; endif; ?>'
		data-id='<?php echo $item->_id ?>'>

			<?php if(glue::roles()->checkRoles(array('^' => $item->video)) && $mode == 'admin'){ ?>
				<div class='comment_select'><?php echo html::checkbox('selected_comment', strval($item->_id), 0, array('class' => 'response_selector')) ?></div>
			<?php } ?>
			<div style='<?php if(glue::roles()->checkRoles(array('^' => $item->video)) && $mode == 'admin'){ echo "margin-left:29px;"; } ?>'>

			<div class='response_content'>
				<a href='<?php echo glue::url()->create('/user/view', array('id' => strval($item->author->_id))) ?>' class='author'><?php echo $item->author->getUsername() ?></a>&nbsp;-&nbsp;
					<?php if($item->thread_parent): echo "<a href='".glue::url()->create('/user/view', array('id' => strval($item->thread_parent->author->_id)))."' class='expand_comment_parent'>@".$item->thread_parent->author->getUsername()."</a> "; endif;

					if(!$item->thread_parent && strlen($item->reply_tousername) > 0) echo "@".$item->reply_tousername.' ';

				if(!(bool)$item->deleted){
					echo nl2br(html::encode($item->content));
				}else{ ?>
					<i class='small'>This response has been deleted</i>
				<?php } ?>
			</div>

			<div class='response_footer'>
				 <?php echo ago($item->ts->sec) ?>
				 <?php if(glue::roles()->checkRoles(array('@'))):
					if($item->approved): ?>
						<?php if($item->video->voteable_comments){ echo utf8_decode('&#183;').' '; if($item->currentUserLikes()): ?><a href='#' class='unlike'>Unlike</a><?php else: ?><a href='#' class='like'>Like</a><?php endif; } ?>
						<span class='likes'><?php if($item->likes > 0): echo "+".$item->likes; endif ?></span>
						<?php if($item->video->txt_coms_allowed && !glue::roles()->checkRoles(array('^' => $item))){ ?>&nbsp;-&nbsp;<a href='#' class='reply_button'>Comment</a><?php } ?>
						<?php if(glue::roles()->checkRoles(array('^' => $item)) || glue::roles()->checkRoles(array('^' => $item->video))){ ?>
							&nbsp;-&nbsp;<a href='#' class='delete_button'>Delete</a>
						<?php }
						if($item->thread_parent):
							echo "&nbsp;-&nbsp;<a href='".glue::url()->create('/videoresponse/thread', array('id' => $item->_id))."' target='_blank'>View thread</a> ";
						endif; ?>
					<?php else: ?>
						<span class='divider'>-</span><span class='warning_message'>Comment Awaiting Moderation</span>
					<?php endif;
				endif; ?>
			</div>

			<?php if(!$item->approved && glue::roles()->checkRoles(array('^' => $item->video))){ ?>
				<div class="moderate_response_actions">
					<div class='grey_button_left delete_button'>Remove</div>
					<div class='green_button_right approve_button'>Approve</div>
				</div>
			<?php } ?>
		</div>

		<?php if(glue::roles()->checkRoles(array('@'))){ ?>
			<div class='reply'>
				<div class='reply_inner'>
					<div class='block_summary' style='display:none;'></div>
					<div class='user_img'><img alt='thumbnail' src='<?php echo glue::session()->user->getPic(40, 40); ?>'/></div>
					<div class='reply_right'>
						<?php echo html::textarea('reply_comment_content', null, array('class' => 'reply_comment_content')) ?>
						<div class='reply_footer'>
							<div class='green_css_button post_comment_reply' style='float:left;'>Post reply</div>
							<div class='grey_css_button cancel' style='float:left; margin-left:7px;'>Cancel</div>
						</div>
					</div>
					<div class="clearer"></div>
				</div>
			</div>
		<?php } ?>

		<?php if($item->thread_parent): ?>
			<div class='thread_parent_viewer'>
				<div class='indented_bar'><div>&nbsp;</div></div>
				<?php if($item->thread_parent->deleted == 1 || !$item->thread_parent){ ?>
					<i>This comment has since been removed</i>
				<?php }else{ ?>
					<div class='parent_content' style=''><span class='caption'><?php echo html::a(array('href' => glue::url()->create('/user/view', array('id' => strval($item->thread_parent->author->_id))),
						'text' => $item->thread_parent->author->getUsername()))." said: "; ?></span><?php echo html::encode($item->thread_parent->content); ?></div>
				<?php } ?>
			</div>
		<?php endif; ?>
	</div>
<?php }elseif($item->type == "video"){ ?>
	<div class='video_response_item video_video_response_item' data-id='<?php echo $item->_id ?>'>
		<?php if(glue::roles()->checkRoles(array('^' => $item->video)) && $mode == 'admin'){ ?>
			<div class='comment_select'><?php echo html::checkbox('selected_comment', strval($item->_id), 0, array('class' => 'response_selector')) ?></div>
		<?php } ?>
		<div>
			<?php if($item->reply_video && !(bool)$item->deleted){ ?>
				<div class='vid_img'><a href='<?php echo glue::url()->create('/video/watch', array('id' => strval($item->reply_video->_id))) ?>'><img alt='thumbnail' src="<?php echo $item->reply_video->getImage(124, 69) ?>"/></a></div>
				<div class='video_response_right'>
					<div class='title'><a href='<?php echo glue::url()->create('/video/watch', array('id' => strval($item->reply_video->_id))) ?>'><?php echo $item->reply_video->title ?></a></div>
					<a href='<?php echo glue::url()->create('/user/view', array('id' => strval($item->author->_id))) ?>'><?php echo "@".$item->author->getUsername() ?></a> <?php echo ago($item->ts->sec) ?>
					<?php if($item->approved && (glue::roles()->checkRoles(array('^' => $item)) || glue::roles()->checkRoles(array('^' => $item->video)))){ ?>
						&nbsp;-&nbsp;<a href='#' class='delete_button'>Delete</a>
					<?php } ?>
					<?php if(!(bool)$item->approved){ ?>
						<div class='warning_message'>Comment Awaiting Moderation</div>
					<?php } ?>
				</div>
				<div class="clearer"></div>
				<?php if(!$item->approved && glue::roles()->checkRoles(array('^' => $item->video))){ ?>
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