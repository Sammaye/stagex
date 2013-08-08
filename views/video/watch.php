<?php

$this->jsFile('/js/views/playlist_bar.js');
// This include of a script is temp to just get this working.
$this->jsFile('/js/jdropdown.js');
$this->jsFile('/js/views/subscribeButton.js');
$this->JsFile("/js/jquery.expander.js");

$this->js('video_tabs', "
		
	$.playlist_bar();
	var video_id = '". $model->_id ."';
		
	$('.expandable').expander();
	$('.video_action_tabs .alert').summarise();

	$('.video_action_tabs .tab').click(function(event){
		event.preventDefault();
		$('.video_action_tabs .alert').summarise('close');
		tabClass=$(this).attr('id').replace(/_tab/,'_content');
		pane=$('.video_action_tabs .'+tabClass);		
		
		if(pane.length>0){
			if(pane.css('display') == 'none'){
				$('.video_action_tabs .video_details_pane').not(pane).css({ 'display': 'none' });
				$('.video_action_tabs .simple-nav .tab').not($(this)).removeClass('selected');
				pane.css({ 'display': 'block' });
				$(this).addClass('selected');
			}else{
				pane.css({ 'display': 'none' });
				$('.video_action_tabs .simple-nav .tab').removeClass('selected');
			}
		}
	});
		
	$('.report_content .btn-success').click(function(event){
		event.preventDefault();
		$.get('/video/report', {id: '".strval($model->_id)."', reason: 
			$('#report_reason input,#report_reason select,#report_reason textarea').serialize()}, null, 'json'
		).done(function(data){
			if(data.success){
				$('.video_action_tabs .alert').summarise('set','success','Thank you for helping make the StageX community safer for everyone.');
			}else{
				$('.video_action_tabs .alert').summarise('set','error', 
					'We could not report this video. We are unsure why but someone is looking into it.');
			}
		});
	});

	$(document).on('click', '.btn-like', function(event){
		event.preventDefault();
		var el = $(this);
		$.getJSON('/video/like', {id: '".strval($model->_id)."'}, function(data){
			if(data.success){
				pane=$('.video_action_tabs .like_content');
				$('.video_action_tabs .simple-nav .tab').removeClass('selected');
				$('.video_action_tabs .video_details_pane').not(pane).css({ 'display': 'none' });
				pane.css({ 'display': 'block' });
				$('.btn-like').addClass('active');
				$('.btn-dislike').removeClass('active');
			}
		});
	});

	$(document).on('click', '.btn-dislike', function(event){
		event.preventDefault();
		$.getJSON('/video/dislike', {id: '".strval($model->_id)."'}, function(data){
			if(data.success){
				$('.btn-dislike').addClass('active');
				$('.btn-like').removeClass('active');
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
				<span class="follower_count">1,000,000 Subscribers</span>
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
				<div class='edit_menu' style='height:30px; padding:10px 0px 10px 45px;'>
					<div class='alert' style='display:none;'></div>
					<input type="button" class="btn btn-primary save_video" value="Save Changes"/>
					<input type="button" id="settings_tab" class="btn btn-dark btn-inline left btn-tab" value="Settings"/><input type="button" id="details_tab" class="btn btn-dark btn-tab btn-inline right" value="Details"/>
					<a href='<?php echo glue::http()->url('/video/delete', array('id' => $model->_id)) ?>' class='delete_video'>Delete</a>
				</div>
				<div class="edit_panes" style='width:980px; margin-left:25px;'>
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

	<div class="main_body" style='margin-left:45px;min-height:800px;'>
	<div style='float:left; width:145px;'>
		<?php if(glue::auth()->check(array('@'))) 
			app\widgets\UserMenu::widget(); 
		else
			echo '&nbsp;';
		?>
	</div>
	<div style='float:left; width:825px; margin-left:10px;'>
		<div style='font-size:12px;'><a href=""><?php echo $model->get_category_text()?></a></div>
		<h1 style='margin-top:4px; font-size:24px; line-height:27px;'><?php echo $model->title ?></h1>
		<div class='video_element'>
			<?php
			if($model->state == 'failed'){
				?><div class='progress'>KaBoom! We could not complete this video, sorry! &lt;/3</div><?php
			}elseif($model->state == 'uploading' || $model->isProcessing()){
				?><div class='progress'>Hold on, we're processing...</div><?php
			}else{
				app\widgets\videoPlayer::widget(array(
					"mp4"=>$model->mp4, "ogg"=>$model->ogg, "width"=>825, "height"=>464
				));
			} ?>
		</div>
		
		<div class="collapsable" style='padding:20px 15px 0 15px;'>
			<div class='left' style='float:left; width:70%; margin-right:10px;'>
				<?php if(strlen($model->description) > 0): ?><div class="expandable" style='font-size:14px; line-height:17px; margin-bottom:10px;'><?php echo nl2br(htmlspecialchars($model->description)) ?></div><?php endif ?>
				<?php if(count($model->tags) > 0 && is_array($model->tags)): ?>
				<div style='margin-bottom:10px;'>
					<?php foreach($model->tags as $tag){
						?><a style='margin-right:10px;' href="<?php echo glue::http()->url("/search", array("query"=>$tag)) ?>">#<?php echo $tag ?></a><?php
					} ?>
				</div>
				<?php endif; ?>
				<div style='font-size:12px; color:#666666;'>
				<b>License: </b><span id='video_licence'><?php echo $model->get_licence_text() ? $model->get_licence_text() : "StageX Licence" ?></span>
				</div>
			</div>
			<div class='right' style='float:left; text-align:right; width:220px;'>
				<div class="views" style='color: #999999; font-size: 16px; font-weight: bold; float:left;'><?php echo !$model->privateStatistics ? '<strong>'.$model->views.'</strong> views' : '' ?></div>
				
				<div class='infocons' style='float:right;'>
			
					<span class='listing'>
						<?php if($model->isUnlisted()){ ?>
							<img alt='Unlisted' src='/images/unlisted_icon.png'/>
						<?php }elseif($model->isPrivate()){ ?>
							<img alt='Private' src='/images/private_icon.png'/>
						<?php } ?>
					</span>	
			
					<span class='comments'>
						<?php if(!$model->allowTextComments && !$model->allowVideoComments){ ?>
							<img alt='Comments Allowed' src='/images/comments_disabled_icon.png'/>
						<?php }elseif($model->moderated){ ?>
							<img alt='Moderated' src='/images/moderated_icon.png'/>
						<?php } ?>
					</span>	
				</div>					
			</div>			
			<div class="clear"></div>
		</div>

		<div class="video_action_tabs">
		<div class="simple-nav left" style='border-bottom:1px solid #cccccc;'>
		
			<?php if($model->voteable && glue::auth()->check(array('@'))): ?>
			<div class="btn-group" style='margin:8px 0 0 0;'>
				<input type="button" class="btn <?php if($model->currentUserLikes()): echo "active"; endif ?> btn-white btn-like" value="+<?php echo $model->likes>0?$model->likes:1 ?>"/>
				<input type="button" class="btn <?php if($model->currentUserDislikes()): echo "active"; endif ?> btn-white btn-dislike" value="-<?php echo $model->dislikes>0?$model->dislikes:1 ?>"/>
			</div>
			<?php endif; ?>
			<div style='float:right;'>		
			<?php if(!$model->privateStatistics): ?><a href="#" id="statistics_tab" class="tab">Statistics</a><?php endif; ?>
			<?php if(glue::auth()->check(array('@'))): ?><a href="#" id="playlists_tab" class="tab">Add to Playlist</a><?php endif; ?>
			<a href="#" id="share_tab" class="tab">Share</a>
			<?php if(glue::auth()->check(array('@'))): ?><a href="#" id="report_tab" class="tab">Report</a><?php endif; ?>
			</div>
			<div class="clear"></div>
		</div>
		
		<div class="alert" style='display:none; margin-top:10px;'></div>
		
		<div class="share_content like_content video_details_pane">
		<div class="" style='margin:10px 0 15px 0; font-weight:bold; color:#444444; font-size:16px;'>You liked this video, why not spread the love?</div>
		<?php if(glue::session()->authed){ ?>
			<div class='share_item_with_subs' style='float:left; margin-right:45px;'>
			<h4>Share with your subscribers</h4>
			<?php echo html::textarea('share_status_text', 'Add some text here if you wish to describe why you shared this video or just click the share button to continue', array('class' => 'share_status_text share_status_text_unchanged')) ?>
			<div><input type="button" class="btn-success" value="Share"/></div>
			</div>
		<?php } ?>
		<div style='float:left;'>
			<h4>Share Elsewhere</h4>
			<ul class="network_bc">
				<li><a rel='new_window' href="http://www.facebook.com/sharer.php?u=<?php echo urlencode(glue::http()->url("/video/watch", array("id"=>$model->_id))) ?>"><img alt='fb' src="/images/fb_large.png"/></a></li>
				<li><a rel='new_window' href="http://twitter.com/share?url=<?php echo urlencode(glue::http()->url("/video/watch", array("id"=>$model->_id))) ?>"><img alt='twt' src="/images/twt_large.png"/></a></li>
				<li><a rel='new_window' href="http://www.plurk.com/?status=<?php echo urlencode(glue::http()->url("/video/watch", array("id"=>$model->_id))) ?>"><img alt='plurk' src="/images/plurk_large.png"/></a></li>
				<li><g:plusone size="medium" annotation="inline" href="<?php echo glue::http()->url('/video/watch', array('id' => $model->_id)) ?>"></g:plusone></li>
			</ul>		
			<div class="clear"></div>
			<input type="text" style='width:330px; margin-top:10px;' class="select_all_onfoc" value="<?php echo glue::http()->url("/video/watch", array("id"=>$model->_id)) ?>" />
			<div class="clear"></div>	
			<?php if($model->embeddable){ ?>
			<h4>Embedd:</h4>
			<textarea rows="" cols="" style='width:330px; height:50px;' class="select_all_onfoc"><iframe style="width:560px; height:315px; border:0;" frameborder="0" src="<?php echo glue::http()->url("/video/embedded", array("id"=>$model->_id)) ?>"></iframe></textarea>
			<?php } ?>
		</div>
		<div class="clear"></div>
		</div>
					
		<div class="report_content video_details_pane">
			<div class="" style='margin:10px 0 15px 0; font-weight:bold; color:#444444; font-size:16px;'>Report</div>
			<p>Pick a reason out of the list below and then click "report":</p>
			<?php $grp=html::radio_group('report_reason'); ?>
			<div style='float:left;' id="report_reason">
			<label class="radio"><?php echo $grp->add('sex'); ?>Sexual Content</label>
			<label class="radio"><?php echo $grp->add('abuse'); ?>Harmful/Voilent Acts &amp; (Child) Abuse</label>
			<label class="radio"><?php echo $grp->add('spam'); ?>Spam</label>
			<label class="radio"><?php echo $grp->add('religious'); ?>Hate Preaching/Religious</label>
			<label class="radio"><?php echo $grp->add('dirty'); ?>Just plain dirty</label>
			</div>
			<div style='float:left; margin:40px 0 0 95px;'>
			<input type="button" class="btn-success" value="Report Video"/>
			<p class="light" style='margin-top:5px; font-size:12px;'>Abuse of this function may result in account deletion</p>
			</div><div class="clear"></div>
		</div>

		<?php if(!$model->private_stats){ ?>
			<div class="statistics_content video_details_pane">
				<div class="" style='margin:10px 0 15px 0; font-weight:bold; color:#444444; font-size:16px;'>Statistics</div>

				<div class='views_status'>
					<div class='' style='float:left; width:200px; text-align:center; font-size:16px;'><?php echo $model->views ?> views</div>
					<div class='' style='float:left; width:200px; text-align:center; font-size:16px;'><?php echo $model->uniqueViews ?> unique views</div>
					<div style='float:left; width:200px; text-align:center; font-size:16px;'>
					<?php $textResponseCount = $model->with('responses', array('type' => 'text', 'deleted' => 0))->count()?>
					<?php echo $textResponseCount ?> text <?php if($textResponseCount > 1): echo "responses"; else: echo "response"; endif ?>
					</div>
					<div>
					<?php $videoResponseCount = $model->with('responses', array('type' => 'video', 'deleted' => 0))->count()?>
					<?php echo $videoResponseCount ?> video <?php if($videoResponseCount > 1): echo "responses"; else: echo "response"; endif ?>					
					</div>
					<div class="clear"></div>
				</div>
				<div id="chartdiv" style="height:200px;width:800px; position:relative; margin-top:20px;"></div>

				<?php
				$video_stats = $model->getStatistics_dateRange(mktime(0, 0, 0, date("m"), date("d")-7, date("Y")), mktime(0, 0, 0, date("m"), date("d"), date("Y")));
				app\widgets\highCharts::widget(array(
					'chartName' => 'video_views_plot',
					'appendTo' => 'chartdiv',
					'series' => $video_stats['hits']							
				)) ?>
			</div>
		<?php } ?>	
		</div>		

		<?php if($model->allowTextComments||$model->allowVideoComments): ?>
		<div>
			<div style='font-size:16px; font-weight:bold; color:#444444; margin:20px 0 5px 0;'><?php echo $model->totalResponses ?> responses</div>
			<a class='float_right' style='font-size:12px;' href="<?php echo glue::http()->url("/videoresponse/viewAll", array("id"=>$model->_id)) ?>">View All Responses</a>
			<?php $this->renderPartial('response/list', array('model' => $model, 'comments' => 
				glue::auth()->check(array("^"=>$model)) ? 
					app\models\VideoResponse::model()->moderated()->find(array('videoId'=>$model->_id)) :
					app\models\VideoResponse::model()->public()->find(array('videoId'=>$model->_id))
			, 'pageSize' => 10)) ?>
		</div>
		<?php endif ?>
	</div>
	<div class="clear"></div>
</div>
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