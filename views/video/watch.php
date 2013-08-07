<?php

$this->jsFile('/js/views/playlist_bar.js');
// This include of a script is temp to just get this working.
$this->jsFile('/js/jdropdown.js');
$this->jsFile('/js/views/subscribeButton.js');

$this->js('video_tabs', "
		
	$.playlist_bar();
	var video_id = '". $model->_id ."';

	$('.video_action_tabs .tab').click(function(event){
		event.preventDefault();
		//Lets get the content name
		var target_el = $('.tab_content_container').find('.'+$(this).data('tab'));

		if(target_el.css('display') == 'none'){
			target_el.css({ 'display': 'block' });
			$('.tab_content_container').children().not(target_el).css({ 'display': 'none' });
		}else{
			target_el.css({ 'display': 'none' });
		}
	});

	$('.report_video_submit').click(function(event){
		event.preventDefault();
		$.getJSON('/video/report', {id: '".strval($model->_id)."', reason: $('#report_reason').val()}, function(data){
			if(data.success){
				forms.summary($('.video_actions .block_summary'), true, 'Thank you for helping make the StageX community safer for everyone.');
			}else{
				forms.summary($('.video_actions .block_summary'), false, 'We could not report this video. We are unsure why but please be sure some one is looking into it right now.');
			}
		});
	});

	$('.tab_like').click(function(event){
		event.preventDefault();
		var el = $(this);
		$.getJSON('/video/like', {id: '".strval($model->_id)."'}, function(data){
			if(data.success){
				$('.tab_content_container').children().not($('.action_like')).css({ 'display': 'none' });
				$('.action_like').css({ 'display': 'block' });
				$('.tab_dislike').removeClass('active');
				el.addClass('active');
			}
		});
	});

	$('.tab_dislike').click(function(event){
		event.preventDefault();
		$.getJSON('/video/dislike', {id: '".strval($model->_id)."'}, function(data){
			if(data.success){
				$('.tab_content_container').children().not($('.tab_content_container .action_dislike')).css({ 'display': 'none' });
				$('.tab_content_container .action_dislike').css({ 'display': 'block' });
				$('.tab_dislike').addClass('active');
				$('.tab_like').removeClass('active');
				$('.action_dislike .likes_amount').text(data.likes);
				$('.action_dislike .dislikes_amount').text(data.dislikes);
			}
		});
	});

	$('.share_status_text').on('click focus', function(event){
		if($(this).hasClass('share_status_text_unchanged')){
			$(this).val('').removeClass('share_status_text_unchanged');
		}
	});

	$('.share_video_as_status').on('click', function(event){
		event.preventDefault();
		var text = '', textarea = $(this).parents('.share_item_with_subs').find('.share_status_text');

		if(!textarea.hasClass('share_status_text_unchanged') && textarea.val().length > 0){
			text = textarea.val();
		}

		$.getJSON('/stream/share', {'type': 'video', 'id': '".strval($model->_id)."', text: text}, function(data){
			if(data.success){
				forms.summary($('.video_actions .block_summary'), true, data.message);
				$('.share_status_text').val('');
			}else{
				forms.summary($('.video_actions .block_summary'), false, data.message);
			}
		});
	});
		
	$(function(){
		add_expandable_details_link();
	});

	function add_expandable_details_link(){
		if($('#details').find('.collapsable').height() > 94){
			// Add the expand link. It is already bound.
			$('.watch_video_body #details .expand_info').unbind('click');
			$('#details').find('.collapsable').addClass('collapsed').css({ 'height': '94px' });
			$('#details .collapsable .expand_info').text('Show More Information').css({ 'display': 'block' });

			$('#details .collapsable .expand_info').click(function(event){
				event.preventDefault();
				if($(this).parents('#details').find('.collapsable').hasClass('collapsed')){
					$(this).parents('#details').find('.collapsable').addClass('collapsed').css({ 'height':
						($(this).parents('#details').find('.collapsable').find('.inner_div').height()+43)+'px' });
		
					$(this).parents('#details').find('.collapsable').removeClass('collapsed');
					$('#details .collapsable .expand_info').text('Show Less Information');
				}else{
					$(this).parents('#details').find('.collapsable').addClass('collapsed').css({ 'height': '94px' });
					$('#details .collapsable .expand_info').text('Show More Information');
				}
			});
		}else{
			$('#details .collapsable .expand_info').hide();
		}
	}
");

$this->js('watch.edit_video', "

	$(document).on('click', '.edit_menu .btn-tab', function(event){
		
		tabClass=$(this).attr('id').replace(/_tab/,'_content');
		pane=$('.edit_panes #'+tabClass);
		
		if(pane.length>0){
			if(pane.css('display') == 'none'){
				$('.edit_panes .pane').not(pane).css({ 'display': 'none' });
				$('.edit_menu .btn-tab').not($(this)).removeClass('active');
				pane.css({ 'display': 'block' });
				$(this).addClass('active');
			}else{
				pane.css({ 'display': 'none' });
				$('.edit_menu .btn-tab').removeClass('active');
			}
		}
	});


		$('.save_video_edits').click(function(event){
			event.preventDefault();

			var fields = $('.video_edit_panes input,.video_edit_panes select,.video_edit_panes textarea').serializeArray();
			fields[fields.length] = {name: 'Video[id]', value: '".strval($model->_id)."'};

			$.post('/video/save', fields, function(data){
				if(!data.success){
					forms.summary($('#video_author_bar .block_summary'), false,
						'<h2>Could not save video</h2>The changes to this video could not be saved because:', data.errors);
				}else{
					forms.summary($('#video_author_bar .block_summary'), true,
						'The changes you made were successfully saved.', data.errors);

					$('#video_title').html(data.data.title);
					$('#video_description').html(data.data.description);

					if(!data.data.tags){
						$('#video_tags').html('');
					}else{
						$('#video_tags').html('');
						for(var i=0; i<data.data.tags.length; i++){
							$('#video_tags').append('<a href=\'/search?mainSearch='+data.data.tags[i]+'\'><span>'+data.data.tags[i]+'</span></a>');
						}
					}

					$('#video_licence').html(data.data.licence);
					$('#video_category').html(data.data.category);

					add_expandable_details_link();
				}
			}, 'json');
		});

		$(document).on('click', '.delete_video', function(event){
			event.preventDefault();
			$.getJSON('/video/remove', {id: '".strval($model->_id)."'}, function(data){
				if(data.success){
					window.location = '/user/videos';
				}else{
					forms.summary($('#video_author_bar .block_summary'), false, 'There was error while trying to delete your video. We are not sure what but will look into it.');
				}
			});
		});

		$('.select_video_thumbnail .video_thumb_chng').click(function(event){
			event.preventDefault();
			$('.select_video_thumbnail .smallThumbOuter').not($(this).parents('.smallThumbOuter')).removeClass('video_thumbnail_selected').find('input').removeAttr('checked');
			$(this).parents('.smallThumbOuter').addClass('video_thumbnail_selected').find('input').attr('checked', true);
		});

		$('.delete_all_responses').click(function(event){
			event.preventDefault();
			var type = $(this).data().type;

			$.getJSON('/video/delete_responses', {id: '".strval($model->_id)."', type: type}, function(data){
				if(data.success){
					if(type == 'video'){
						forms.summary($('#video_author_bar .block_summary'), true,
							'All video responses have been removed from this video successfully.', data.errors);
					}else{
						forms.summary($('#video_author_bar .block_summary'), true,
							'All text responses have been removed from this video successfully.', data.errors);
					}
				}else{
					forms.summary($('#video_author_bar .block_summary'), false,
						'There was an error while trying to remove the responses from this video. Please try again later.', data.errors);
				}
				refresh_video_response_list();
			});
		});
"); ?>

<div class="watch_page">

	<?php if(!glue::auth()->check(array('^'=>$model))){ ?>
	<div style='background:#4b4b4b; height:30px; padding:15px 20px; color:#fff;'>
		<img alt='thumbnail' style='border-radius:50px; float:left;' src="<?php echo $model->author->getAvatar(30, 30); ?>"/>
		<a style='color:#fff;font-size:20px; font-weight:normal; display:inline-block; margin:5px 10px 0 10px; line-height:22px;' href='<?php echo glue::http()->url('/user/view', array('id' => $model->author->_id)) ?>'><?php echo $model->author->getUsername() ?></a>
		<span style='display:inline-block; font-size:12px;' class='uploaded'><?php echo $model->ago($model->created) ?></span>
		<?php if(glue::session()->authed){ ?>
			<div class='right' style='float:right;'>
			<div class="subscribe_widget">
				<span class="follower_count">1,000,000 Followers</span>
				<?php if(app\models\Follower::isSubscribed($model->author->_id)){ ?>
				<input type="button" class='unsubscribe btn button' value="Unsubscribe"/>
				<?php }else{ ?>
				<input type="button" class='subscribe btn-success button' value="Subscribe"/>
				<?php } ?>
			</div>
			</div>
		<?php } ?>
		<div class="clear"></div>
	</div>
	<?php }else{ ?>
			<div class='' style='background:#e5e5e5;'>
				<div class='edit_menu' style='height:30px; padding:10px 20px;'>
					<div class='alert' style='display:none;'></div>
					<input type="button" class="btn btn-primary save_video" value="Save Changes"/>
					<input type="button" id="settings_tab" class="btn btn-dark btn-inline left btn-tab" value="Settings"/><input type="button" id="details_tab" class="btn btn-dark btn-tab btn-inline right" value="Details"/>
					<a href='<?php echo glue::http()->url('/video/delete', array('id' => $model->_id)) ?>' class='delete_video'>Delete</a>
				</div>
				<div class="edit_panes" style='width:980px;'>
					<?php $form = html::activeForm(array('action' => '')) ?>
						<div class='edit_settings pane' id="settings_content">
						<div class="form-stacked left" style='float:left;'>
							<div class="form_row"><?php echo html::label('Title', 'title') ?><?php echo html::activeTextField($model, 'title') ?></div>
							<div class="form_row"><?php echo html::label('Description', 'description')?><?php echo html::activeTextarea($model, 'description') ?></div>
							<div class="form_row last"><?php echo html::label('Tags', 'stringTags') ?><?php echo html::activeTextField($model, 'string_tags') ?></div>			
						</div>
						<div class='right' style='float:right;width:450px;'>
							<h4>Category</h4><?php echo html::activeSelectbox($model, 'category', $model->categories('selectBox')) ?>
							<h4>Adult Content</h4>
							<label class="checkbox"><?php echo $form->checkbox($model, 'mature', 1) ?>This video is not suitable for family viewing</label>
							<h4>Listing</h4>
							<?php $grp = html::activeRadio_group($model, 'listing') ?>
							<div class="label_options">
								<label class="radio"><?php echo $grp->add(0) ?>Listed</label>
								<p class='light'>Your video is public to all users of StageX</p>
								<label class="radio"><?php echo $grp->add(1) ?>Unlisted</label>
								<p class='light'>Your video is hidden from listings but can still be accessed directly using the video URL</p>
								<label class="radio"><?php echo $grp->add(2) ?>Private</label>
								<p class='light'>No one but you can access this video</p>
							</div>
							<h4>Licence (<a href='#'>Learn More</a>)</h4>
							<?php $grp = html::activeRadio_group($model, 'licence') ?>
							<div class="label_options">
								<label class="radio"><?php echo $grp->add('1') ?>Standard StageX Licence</label>
								<label class="radio"><?php echo $grp->add('2') ?>Creative Commons Licence</label>
							</div>
						</div>						
						</div>
						<div class="clear"></div>

						<div class='pane' id="details_content">
						<div class="left" style='float:left; margin-right:40px;'>
							<label class='checkbox'><?php echo $form->checkbox($model, "voteable", 1) ?>Allow users to vote on this video</label>
							<label class='checkbox'><?php echo $form->checkbox($model, "embeddable", 1) ?>Allow embedding of my video</label>
							<label class='checkbox'><?php echo $form->checkbox($model, "private_stats", 1) ?>Make all statistics private</label>
						</div>
						<div class='left' style='float:left; margin-right:40px;'>
							<h4>Responses</h4>
							<?php $group = $form->radio_group($model, "mod_comments") ?>
							<label class='checkbox'><?php echo $group->add(0) ?><span>Automatically post all comments</span></label>
							<label class='checkbox'><?php echo $group->add(1) ?><span>Make all moderated</span></label>
							<label class='checkbox'><?php echo $form->checkbox($model, "voteable_comments", 1) ?><span>Allow users to vote on responses</span></label>
							<label class='checkbox'><?php echo $form->checkbox($model, "vid_coms_allowed", 1) ?><span>Allow video responses</span></label>
							<label class='checkbox'><?php echo $form->checkbox($model, "txt_coms_allowed", 1) ?><span>Allow text responses</span></label>
						</div>
						<div class='right' style='float:left;'>
							<div class='btn delete_all_responses' data-type='video' style='margin-bottom:15px; display:block;'>Delete all video responses</div>
							<div class='btn delete_all_responses' data-type='text'>Delete all text responses</div>
						</div>
						</div>
						<div class="clear"></div>
					<?php $form->end(); ?>
				</div>
			</div>
		<?php } ?>

	<div class="main_body">
		<h1><?php echo $model->title ?></h1>
		<div class='video_element'>
			<?php
			if($model->state == 'failed'){
				?><div class='progress'>KaBoom! We could not complete this video, sorry! &lt;/3</div><?php
			}elseif($model->state == 'uploading' || $model->isProcessing()){
				?><div class='progress'>Hold on, we're processing...</div><?php
			}else{
				app\widgets\videoPlayer::widget(array(
					"mp4"=>$model->mp4, "ogg"=>$model->ogg, "width"=>970, "height"=>444
				));
			} ?>
		</div>
		
		<div class="btn-toolbar">

		</div>
		<div>
		<div class="simple-nav left">
		
			<?php if($model->voteable && glue::auth()->check(array('@'))): ?>
			<div class="btn-group">
				<input type="button" class="btn <?php if($model->currentUserLikes()): echo "active"; endif ?>" value="+1"/>
				<input type="button" class="btn <?php if($model->currentUserDislikes()): echo "active"; endif ?>" value="-1"/>
			</div>
			<?php endif; ?>		
		
			<a href="#" class="selected" data-filter="all">Details</a>
			<?php if(!$model->privateStatistics): ?><a href="#" data-filter="posts">Statistics</a><?php endif; ?>
			<?php if(glue::auth()->check(array('@'))): ?><a href="#" data-filter="comments">Add to Playlist</a><?php endif; ?>
			<a href="#" data-filter="liked">Share</a>
			<?php if(glue::auth()->check(array('@'))): ?><a href="#" data-filter="watched">Report</a><?php endif; ?>
		</div>
		
		<?php if($model->voteable){ ?>
		<div class="curved_white_filled_box video_action_dialog action_like">
			<div class='header_outer'>
				<div class="box_head">You applauded this video. Why not share the love around?</div>
				<div class="close"><a href="#"><?php echo utf8_decode('&#215;') ?></a></div>
			</div>
			<div class='share_item_with_subs'>
				<?php echo html::textarea('share_status_text', 'Add some text here if you wish to describe why you shared this video or just click the share button to continue', array('class' => 'share_status_text share_status_text_unchanged')) ?>
				<div class='green_css_button share_video_as_status'>Share</div>
			</div>
			<div class='clearer'></div>
			<div class='margin_top_10'>
				<ul class="video_watch_broadcast_share">
					<li class="caption">Share with other networks:</li>
					<li><a rel='new_window' href="http://www.facebook.com/sharer.php?u=<?php echo urlencode(glue::http()->url("/video/watch", array("id"=>$model->_id))) ?>"><img alt='fb' src="/images/fb_large.png"/></a></li>
					<li><a rel='new_window' href="http://twitter.com/share?url=<?php echo urlencode(glue::http()->url("/video/watch", array("id"=>$model->_id))) ?>"><img alt='twt' src="/images/twt_large.png"/></a></li>
					<li><a rel='new_window' href="http://www.plurk.com/?status=<?php echo urlencode(glue::http()->url("/video/watch", array("id"=>$model->_id))) ?>"><img alt='plurk' src="/images/plurk_large.png"/></a></li>
				<li>
				<g:plusone size="medium" annotation="inline" href="<?php echo glue::http()->url('/video/watch', array('id' => $model->_id)) ?>"></g:plusone>
				</li>
				</ul>
				<div class="clear"></div>
			</div>
		</div>

		<div class="curved_white_filled_box video_action_dialog action_dislike">
			<div class='header_outer'>
				<div class="box_head">You booed! Obviously this wasn't to your taste.</div>
				<div class="close"><a href="#"><?php echo utf8_decode('&#215;') ?></a></div>
			</div>
			<div class='video_ratings'>
				<div class='caption' style=''>The score currently stands at:</div>
				<img alt='like' src='/images/thumb_up_active.png'/><span class='likes_amount'><?php echo $model->likes ?></span>
				<img alt='dislike' src='/images/thumb_down_active.png'/><span class='dislikes_amount'><?php echo $model->dislikes ?></span>
			</div>
		</div>
		<?php } ?>	
		
								<div class="curved_white_filled_box video_action_dialog video_action_share" id='broadcast_video'>
						<div class='header_outer'>
							<div class="box_head">Spread this Video</div>
							<div class="close"><a href="#"><?php echo utf8_decode('&#215;') ?></a></div>
						</div>
						<?php if(glue::session()->authed){ ?>
							<div class='share_item_with_subs'>
								<?php echo html::textarea('share_status_text', 'Add some text here if you wish to describe why you shared this video or just click the share button to continue', array('class' => 'share_status_text share_status_text_unchanged')) ?>
								<div class='green_css_button share_video_as_status'>Share</div>
							</div>
						<?php } ?>
						<div class='clearer'></div>

						<div class='margin_top_10 link_to_video'>
							<div>Link to this video:</div>
							<input type="text" class="select_all_onfoc" value="<?php echo glue::http()->url("/video/watch", array("id"=>$model->_id)) ?>" />
						</div>
						<?php if($model->embeddable){ ?>
							<div class='embed_video'>
								<div>Embedded Player:</div>
								<textarea rows="" cols="" class="select_all_onfoc"><iframe style="width:560px; height:315px; border:0;" frameborder="0" src="<?php echo glue::http()->url("/video/embedded", array("id"=>$model->_id)) ?>"></iframe></textarea>
							</div>
						<?php } ?>
						<div class='margin_top_10'>
							<ul class="video_watch_broadcast_share">
								<li class="caption"><span>Broadcast:</span></li>
								<li><a rel='new_window' href="http://www.facebook.com/sharer.php?u=<?php echo urlencode(glue::http()->url("/video/watch", array("id"=>$model->_id))) ?>"><img alt='fb' src="/images/fb_large.png"/></a></li>
								<li><a rel='new_window' href="http://twitter.com/share?url=<?php echo urlencode(glue::http()->url("/video/watch", array("id"=>$model->_id))) ?>"><img alt='twt' src="/images/twt_large.png"/></a></li>
								<li><a rel='new_window' href="http://www.plurk.com/?status=<?php echo urlencode(glue::http()->url("/video/watch", array("id"=>$model->_id))) ?>"><img alt='plurk' src="/images/plurk_large.png"/></a></li>
								<li><g:plusone size="medium" annotation="inline" href="<?php echo glue::http()->url('/video/watch', array('id' => $model->_id)) ?>"></g:plusone></li>
							</ul>
							<div class="clear"></div>
						</div>
					</div>
					
					<div class="curved_white_filled_box video_action_dialog video_action_report">
							<div class='header_outer'>
								<div class="box_head">Report Video</div>
								<div class="close"><a href="#"><?php echo utf8_decode('&#215;') ?></a></div>
							</div>

							<?php /* 
							$this->widget('application/widgets/JqselectBox.php', array(
								'attribute' => 'report_reason',
								'id' => 'report_reason',
								'class' => 'report_reason_select',
								"items" => array(
									'extreme_v' => 'Extreme Voilence',
									'sex' => 'Sexual Content',
									'harmful_act' => 'Harmful/Dangerous Acts',
									'child_abuse' => 'Child Abuse',
									'spam' => 'Spam',
									'rights_voil' => 'Rights Voilation',
									'religious' => 'Hate Preaching/Religious Reasons',
									'dirty' => 'Just Plain Dirty'
								)
							)) */ ?>
							<a href='#' class='grey_css_button report_video_submit float_right'>Report Video</a>
							<div class="clear"></div>
					</div>

					<?php if(!$model->private_stats){ ?>
						<div class="curved_white_filled_box video_action_dialog video_action_stats">
							<div class='header_outer'>
								<div class="box_head">Statistics</div>
								<div class="close"><a href="#"><?php echo utf8_decode('&#215;') ?></a></div>
							</div>

							<div class='views_status'>
								<div class='float_left'><span><?php echo $model->views ?></span> views</div>
								<div class='float_right'><span><?php echo $model->uniqueViews ?></span> unique views</div>
							</div>
							<!-- <h2 style='font-size:13px; margin-top:12px;'>Video Statistics for the last week</h2> -->
							<div id="chartdiv" style="height:200px;width:600px; position:relative;"></div>

							<?php
							$video_stats = $model->getStatistics_dateRange(mktime(0, 0, 0, date("m"), date("d")-7, date("Y")), mktime(0, 0, 0, date("m"), date("d"), date("Y")));
							app\widgets\highCharts::widget(array(
							'chartName' => 'video_views_plot',
							'appendTo' => 'chartdiv',
							'series' => $video_stats['hits']							
							)) ?>

							<div class='demo_block_outer'>
								<div class='demo_block_left'>
									<h2>Like Demographics</h2>
									<?php if($model->likes <= 0 && $model->dislikes <= 0){ ?>
										<p>No one has liked or disliked this video yet</p>
									<?php }else{ ?>
										<div class='ratings_block like_block'>
											<div style='border:1px solid #006600; background:#5bd85b; width:<?php echo ($model->likes/($model->likes+$model->dislikes))*100 > 0 ? (($model->likes/($model->likes+$model->dislikes))*100)."%;" : "5px;" ?>'></div>
											<span><?php if($model->likes <= 0): echo "No one has liked this video yet"; elseif($model->likes == 1): echo $model->likes." person liked this video"; else: echo $model->likes." people liked this video"; endif; ?></span>
										</div>
										<div class="ratings_block dislike_block">
											<div style='border:1px solid #cc0000; background:#fb5353; width:<?php echo ($model->dislikes/($model->likes+$model->dislikes))*100 > 0 ? (($model->dislikes/($model->likes+$model->dislikes))*100)."%;" : "5px;" ?>'></div>
											<span><?php if($model->dislikes <= 0): echo "No one has disliked this video yet"; elseif($model->dislikes == 1): echo $model->dislikes." person disliked this video"; else: echo $model->dislikes.' people disliked this video'; endif; ?></span>
										</div>
									<?php } ?>
								</div>
								<div class='demo_block_right'>
									<h2>Response Demographics</h2>
									<?php $videoResponseCount = $model->with('responses', array('type' => 'video', 'deleted' => 0))->count()?>
									<?php $textResponseCount = $model->with('responses', array('type' => 'text', 'deleted' => 0))->count()?>
									<p><?php echo $textResponseCount ?> text <?php if($textResponseCount > 1): echo "responses"; else: echo "response"; endif ?></p>
									<p><?php echo $videoResponseCount ?> video <?php if($videoResponseCount > 1): echo "responses"; else: echo "response"; endif ?></p>
								</div>
								<div class="clear"></div>
							</div>
						</div>
					<?php } ?>					
						<div id="details">
					<div class="collapsable">
						<div class='inner_div'>
							<div class='left'>
								<?php if(strlen($model->description) > 0): ?><p id="video_description" class="description"><?php echo nl2br(htmlspecialchars($model->description)) ?></p><?php endif ?>
								<?php if(count($model->tags) > 0){ ?>
									<div class='tags' id='video_tags'><?php foreach($model->tags as $tag){
											?><a href="<?php echo glue::http()->url("/search", array("mainSearch"=>$tag)) ?>"><span><?php echo $tag ?></span></a><?php
										} ?></div>
								<?php } ?>

								<?php if(strlen($model->description) <= 0 && count($model->tags) <= 0): ?><div style='margin-top:15px;'><?php endif ?>
								<p class='licence'><b>Licensed under:</b> <span id='video_licence'><?php echo $model->get_licence_text() ? $model->get_licence_text() : "StageX Licence" ?></span></p>
								<p class='category'><b>Category:</b> <span id='video_category'><?php echo $model->get_category_text() ?></span></p>
								<?php if(strlen($model->description) <= 0 && count($model->tags) <= 0): ?></div><?php endif ?>
							</div>
							<div class='right'>
								<div class="views"><?php echo !$model->private_stats ? '<strong>'.$model->views.'</strong> views' : '' ?></div>
								<?php if($model->voteable && ($model->likes+$model->dislikes > 0)){
									$like_percent = $model->likes > 0 ? 163*($model->likes/($model->dislikes+$model->likes)) : 0;
									$dislike_percent = $model->dislikes > 0 ? 163*($model->dislikes/($model->likes+$model->dislikes)) : 0;

									$like_width = $like_percent > 0 ? $like_percent-1 : 0;
									$dislike_width = $dislike_percent > 0 ? $dislike_percent-1 : 0;

									$like_border_css = $like_width > 0 && $dislike_width > 0 ? 'border-right:1px solid #ffffff;' : '';
									$dislike_border_css = $like_width > 0 && $dislike_width > 0 ? 'border-left:1px solid #ffffff;' : '';
//var_dump($like_percent);
//var_dump($dislike_percent);
									?>
									<div class="like_bar">
										<div class='bordered_outer'>
											<div class='likes' style='width:<?php echo $like_width."px" ?>; <?php echo $like_border_css ?>'>&nbsp;</div>
											<div class='dislikes' style='width:<?php echo $dislike_width."px" ?>; <?php echo $dislike_border_css ?>'>&nbsp;</div>
										</div><strong class='caption'><?php echo $model->likes > 0 ? $model->likes : "None" ?> liked this, <?php echo $model->dislikes > 0 ? $model->dislikes : "None" ?> Did not</strong>
									</div>
								<?php } ?>
							</div>
							<div class="clear"></div>
							<a href="#" class='expand_info expand_video_details_info'>Show Less Information</a>
						</div>
					</div>
				</div>
		
		</div>

		<div>
			<div class='grey_bordered_head'>
				<span><?php echo $model->totalResponses ?> responses</span>
				<a class='float_right' href="<?php echo glue::http()->url("/videoresponse/view_all", array("id"=>$model->_id)) ?>">View All Responses</a>
			</div>
			<?php $this->renderPartial('response/list', array('model' => $model, 'comments' => 
				glue::auth()->check(array("^"=>$model)) ? 
					app\models\VideoResponse::model()->moderated()->find(array('videoId'=>$model->_id)) :
					app\models\VideoResponse::model()->public()->find(array('videoId'=>$model->_id))
			, 'pageSize' => 10)) ?>
		</div>
	</div>
	<div class="clear"></div>
</div>

<div id='video_response_options' style='width:200px;'></div>
<div id='videoResponse_results' class=''></div>

<?php if(glue::auth()->check(array('viewable' => $playlist))){ ?>
<div class="playlist_bar_outer" data-id='<?php echo $playlist->_id ?>'>
	<div class='playlist_bar_head'>
		<div class='float_left head_left'>Playlist: <a href='<?php echo glue::http()->url('/playlist/view', array('id' => $playlist->_id)) ?>'><?php echo html::encode($playlist->title) ?></a> (<?php echo count($playlist->videos) ?> Videos)
		- By <b><a href='<?php echo glue::http()->url('/user/view', array('id' => $playlist->userId)) ?>'><?php echo $playlist->author->getUsername(); ?></a></b></div>
		<button class='float_right view_all_videos'>View All Videos</button>
	</div>
	<div class='playlist_content'>
		<button class='move_left'></button>
		<button class='move_right'></button>
		<div class='playlist_video_list'>
			<div class='tray_content'>Loading</div></div>
	</div>
</div>
<?php } ?>