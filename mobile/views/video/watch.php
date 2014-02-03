<?php

use \app\models\VideoResponse;

$this->jsFile('/js/playlist_bar.js');
// This include of a script is temp to just get this working.
$this->jsFile('/js/jdropdown.js');
$this->jsFile('/js/subscribeButton.js');
$this->JsFile("/js/jquery.expander.js");

$this->js('page', "
		
	$.playlist_bar();
	var video_id = '". $model->_id ."';
		
	$('.expandable').expander();
	$('.video_actions .alert').summarise();
	$('.subscribe_widget').subscribeButton();

	$('.video_actions .tab').click(function(event){
		event.preventDefault();
		$('.video_actions .alert').summarise('close');
		tabClass=$(this).attr('id').replace(/_tab/,'_content');
		pane=$('.video_actions .'+tabClass);		
		
		if(pane.length>0){
			if(pane.css('display') == 'none'){
				$('.video_actions .video_actions_pane').not(pane).css({ 'display': 'none' });
				$('.video_actions .simple-nav .tab').not($(this)).removeClass('selected');
				pane.css({ 'display': 'block' });
				$(this).addClass('selected');
			}else{
				pane.css({ 'display': 'none' });
				$('.video_actions .simple-nav .tab').removeClass('selected');
			}
		}
	});
		
	$('.report_content .btn-success').click(function(event){
		event.preventDefault();
		$.post('/video/report', {id: '".strval($model->_id)."', reason: 
			$('.report_content .reason input:checked').val()}, null, 'json'
		).done(function(data){
			if(data.success){
				$('.video_actions .alert').summarise('set','success','Thank you for helping make the StageX community safer for everyone.');
			}else{
				$('.video_actions .alert').summarise('set','error', 
					'We could not report this video. We are unsure why but someone is looking into it.');
			}
		});
	});

	$(document).on('click', '.btn-like', function(event){
		event.preventDefault();
		var el = $(this);
		$.getJSON('/video/like', {id: '".strval($model->_id)."'}, function(data){
			if(data.success){
				if(!$('.btn-like').hasClass('active')){
					$('#share_tab').trigger('click');
					$('.btn-like').addClass('active');
					$('.btn-dislike').removeClass('active');
				}
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

		if(!textarea.hasClass('share_status_text_unchanged') && textarea.val().length > 0)
			text = textarea.val();
		$.getJSON('/stream/share', {'type': 'video', 'id': '".strval($model->_id)."', text: text}, function(data){
			if(data.success){
				$('.video_actions .alert').summarise('set','success',data.messsage);
				$('.share_status_text').val('');
			}else
				$('.video_actions .alert').summarise('set','error',data.messsage);
		});
	});
		
	$('#search_playlists').on('keyup', function(e){
		if($(this).val().length>3){
			$.get('/playlist/suggestions',{term:$(this).val()},null,'json').done(function(data){
				if(data.success){
					var container=$('.playlists_content .results');
					container.empty();
					if(data.results.length>0){
						$.each(data.results,function(i,item){
							container.append($('<div/>').data({id:item._id}).addClass('playlist playlist_item')
								.append($('<span/>').addClass('name').text(item.title))
								.append($('<span/>').addClass('video_count').text('('+item.totalVideos+')'))
								.append($('<span/>').addClass('created').text(item.created))
							);
						});
					}else{
						container.append($('<div class=\"no_results_found\"/>').text('No results found'));
					}
				}
			});
		}
	});
		
	$(document).on('click', '.playlists_content .playlist_item', function(e){
		e.preventDefault();
		var params = [{name:'playlist_id',value:$(this).data().id},{name:'video_ids[0]',value:'".$model->_id."'}];
		$.post('/playlist/addVideo', params, null, 'json').done(function(data){
			if(data.success){
				$('.video_actions .alert').summarise('set','success','Video added to playlist');
			}else{
				$('.video_actions .alert').summarise('set','error','Video could not be added to playlist');
			}
		});
	});		
");

$this->js('edit', "

	$('.edit_menu .alert').summarise();
		
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

	$('.edit_menu .save_video').click(function(event){
		event.preventDefault();

		var fields = $('.edit_panes input,.edit_panes select,.edit_panes textarea').serializeArray();
		fields[fields.length] = {name: 'id', value: '".strval($model->_id)."'};

		$.post('/video/save', fields, null, 'json').done(function(data){
			if(!data.success){
				$('.edit_menu .alert').summarise('set','error',
					{message:'<h4>Could not save video</h4>The changes to this video could not be saved because:',list:data.messages});
			}else{
				$('.edit_menu .alert').summarise('set','success', 'The changes you made were successfully saved');		

				/*
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
				*/
			}
		});
	});

	$(document).on('click', '.delete_video', function(event){
		event.preventDefault();
		params={'ids[]':['".strval($model->_id)."']};
		$.post('/video/delete', params, null, 'json').done(function(data){
			if(data.success){
				window.location = '/user/videos';
			}else
				$('.edit_menu .alert').summarise('set','error','There was error while trying to delete your video');
		});			
	});

	$('.delete_all_responses').click(function(event){
		event.preventDefault();
		var type = $(this).data().type;

		$.getJSON('/video/deleteResponses', {id: '".strval($model->_id)."', type: type}, function(data){
			if(data.success){
				if(type == 'video'){
					$('.edit_menu .alert').summarise('set','success','All video responses have been removed from this video successfully');
				}else
					$('.edit_menu .alert').summarise('set','success','All text responses have been removed from this video successfully');
			}else
				$('.edit_menu .alert').summarise('set','error',
					{message:'There was an error while trying to remove the responses from this video. Please try again later.',list:data.messages});
			refresh_video_response_list();
		});
	});
"); ?>
<div class="watch_page">
	<?php if(!glue::auth()->check(array('^'=>$model))){ ?>
<div class="author_top_bar">
<div class="container">
	<div class="user_image">
	<img alt='thumbnail' class="thumbnail" src="<?php echo $model->author->getAvatar(30, 30); ?>"/>
	</div>
	<div class="user_text">
	<a href="<?php echo glue::http()->url('/user/view', array('id' => $model->author->_id)) ?>" class="h4"><?php echo $model->author->getUsername() ?></a><span class="sep h4">/</span><a href="<?php echo glue::http()->url('/user/viewVideos', array('id' => $model->author->_id)) ?>" class="h4">Videos</a>
	</div>
	<div class='right'>
	<div class="subscribe_widget" data-user_id="<?php echo $model->author->_id ?>">
		<span class="follower_count text-muted"><?php echo $model->author->totalFollowers ?> Subscribers</span>
		<?php if(glue::session()->authed){ ?>
		<?php if(app\models\Follower::isSubscribed($model->author->_id)){ ?>
		<button type="button" class='unsubscribe button btn btn-danger'>Unsubscribe</button>
		<?php }else{ ?>
		<button type="button" class='subscribe btn btn-primary button'>Subscribe</button>
		<?php } ?>
		<?php } ?>
	</div>
	</div>
</div>
</div>	
	<?php }else{ ?>
	<div class='edit_ribbon_menu'>
		<div class="container video_edit_menu_container">
		<div class='edit_menu form-inline'>
			<div class='alert'></div>
			<input type="button" class="btn btn-primary save_video" value="Save Changes"/>
			<div class="btn-group">
			<button type="button" id="settings_tab" class="btn btn-default btn-tab">Settings</button>
			<button type="button" id="details_tab" class="btn btn-default btn-tab">Details</button>
			</div>
			<button type="button" class='delete_video btn btn-danger'>Delete</button>
			<a href='<?php echo glue::http()->url('/video/analytics', array('id' => $model->_id)) ?>' class='btn btn-link'>Analytics</a>
			<a href='<?php echo glue::http()->url('/videoresponse/list', array('id' => $model->_id)) ?>' class='btn btn-link'>Responses (<?php echo $model->getRelated('responses',array('videoId'=>$model->_id,'approved'=>false))->count() ?> pending)</a>
		</div>
		<div class="edit_panes row">
			<?php $form = html::activeForm(array('action' => '')) ?>
				<div class='edit_settings pane clearfix' id="settings_content">
				<div class="col-md-8">
					<div class="form-group"><?php echo html::label('Title', 'title') ?><?php echo html::activeTextField($model, 'title',array('class'=>'form-control')) ?></div>
					<div class="form-group"><?php echo html::label('Description', 'description')?><?php echo html::activeTextarea($model, 'description',array('class'=>'form-control')) ?></div>
					<div class="form-group"><?php echo html::label('Tags', 'stringTags') ?><?php echo html::activeTextField($model, 'stringTags',array('class'=>'form-control')) ?></div>			
				</div>
				<div class='col-md-4'>
					<h4>Category</h4><?php echo html::activeSelectbox($model, 'category', $model->categories('selectBox'),array('class'=>'form-control')) ?>
					<h4 class="adult">Adult Content</h4>
					<label class="checkbox"><?php echo $form->checkbox($model, 'mature', 1) ?>This video is not suitable for family viewing</label>
					<h4>Listing</h4>
					<?php $grp = html::activeRadio_group($model, 'listing') ?>
					<div class="label_options">
						<label class="radio"><?php echo $grp->add(0) ?>Listed</label>
						<p class='text-muted'>Your video is public to all users of StageX</p>
						<label class="radio"><?php echo $grp->add(1) ?>Unlisted</label>
						<p class='text-muted'>Your video is hidden from listings but can still be accessed directly using the video URL</p>
						<label class="radio"><?php echo $grp->add(2) ?>Private</label>
						<p class='text-muted'>No one but you can access this video</p>
					</div>
					<h4>Licence (<a href='#'>Learn More</a>)</h4>
					<?php $grp = html::activeRadio_group($model, 'licence') ?>
					<div class="label_options">
						<label class="radio"><?php echo $grp->add('1') ?>Standard StageX Licence</label>
						<label class="radio"><?php echo $grp->add('2') ?>Creative Commons Licence</label>
					</div>
				</div>
				</div>
				<div class='edit_details pane' id="details_content">
				<div class="col-md-4">
					<label class='checkbox'><?php echo $form->checkbox($model, "voteable", 1) ?>Allow users to vote on this video</label>
					<label class='checkbox'><?php echo $form->checkbox($model, "embeddable", 1) ?>Allow embedding of my video</label>
					<label class='checkbox'><?php echo $form->checkbox($model, "privateStatistics", 1) ?>Make all statistics private</label>
				</div>
				<div class='col-md-4'>
					<label class='checkbox'><?php echo $form->checkbox($model, "moderated", 1) ?><span>Moderate responses</span></label>
					<label class='checkbox'><?php echo $form->checkbox($model, "voteableComments", 1) ?><span>Allow users to vote on responses</span></label>
					<label class='checkbox'><?php echo $form->checkbox($model, "allowVideoComments", 1) ?><span>Allow video responses</span></label>
					<label class='checkbox'><?php echo $form->checkbox($model, "allowTextComments", 1) ?><span>Allow text responses</span></label>
				</div>
				<div class='button_group col-md-3'>
					<div class='btn btn-default delete_all_responses' data-type='video'>Delete all video responses</div>
					<div class='btn btn-default delete_all_responses' data-type='text'>Delete all text responses</div>
				</div>
				<div class="clear"></div>
				</div>
			<?php $form->end(); ?>
		</div>
		</div>
	</div>
	<?php } ?>

	<div class="video_body container">
	
	<div class='video_element'>
		<?php
		if($model->state == 'failed'){
			?><div class='status-message'>KaBoom! We could not complete this video, sorry! &lt;/3</div><?php
		}elseif($model->state == 'uploading' || $model->isProcessing()){
			?><div class='status-message'>Hold on, we're processing...</div><?php
		}else{
			echo app\widgets\videoPlayer::run(array(
				"mp4"=>$model->mp4, "ogg"=>$model->ogg, "width"=>823, "height"=>463, 'mobile' => true
			));
		} ?>
	</div>	
	
		<a class="category" href="<?php echo glue::http()->url('/search', array('filter_type' => 'video', 'filter_category' => $model->category)) ?>"><?php echo $model->get_category_text()?></a>
		<h1 class="h3 title"><?php echo $model->title ?></h1>		
		
		<div class="details clearfix">
				<?php if(strlen($model->description) > 0): ?><div class="expandable description"><?php echo nl2br(htmlspecialchars($model->description)) ?></div><?php endif ?>
				<?php if(count($model->tags) > 0 && is_array($model->tags)): ?>
				<div class="tags">
					<?php foreach($model->tags as $tag){
						?><a href="<?php echo glue::http()->url("/search", array("query"=>$tag)) ?>">#<?php echo $tag ?></a><?php
					} ?>
				</div>
				<?php endif; ?>
				<div class="licence">
				<b>License: </b><span><?php echo $model->get_licence_text() ? $model->get_licence_text() : "StageX Licence" ?></span>
				</div>
				<div class="views infocons"><?php echo !$model->privateStatistics ? '<strong>'.$model->uniqueViews.'</strong> views' : '' ?>
					<?php if($model->isUnlisted()){ ?>
						<span class="listing unlisted-setting-icon"></span>
					<?php }elseif($model->isPrivate()){ ?>
						<span class="listing private-setting-icon"></span>
					<?php } ?>
					<?php if(!$model->allowTextComments && !$model->allowVideoComments){ ?>
						<span class="comments comments-disabled-setting-icon"></span>
					<?php }elseif($model->moderated){ ?>
						<span class="comments moderated-setting-icon"></span>
					<?php } ?>
				</div>
		</div>

		<div class="video_actions">
		<div class="actions_menu">
			<?php if($model->voteable && glue::auth()->check(array('@'))): ?>
			<div class="btn-group">
				<button type="button" class="btn <?php if($model->currentUserLikes()): echo "active"; endif ?> btn-success btn-like"><span class="caret arrow-up">&nbsp;</span> <span class="btn-caption"><?php echo '+'.$model->likes; ?></span></button>
				<button type="button" class="btn <?php if($model->currentUserDislikes()): echo "active"; endif ?> btn-danger btn-dislike"><span class="caret">&nbsp;</span> <span class="btn-caption"><?php echo '-'.$model->dislikes ?></span></button>
			</div>
			<?php endif; ?>
			<div class="simple-nav">		
			<?php if(!$model->privateStatistics): ?><a href="#" id="statistics_tab" class="tab">Statistics</a><?php endif; ?>
			<?php if(glue::auth()->check(array('@'))): ?><a href="#" id="playlists_tab" class="tab">Add to Playlist</a><?php endif; ?>
			<a href="#" id="share_tab" class="tab">Share</a>
			<?php if(glue::auth()->check(array('@'))): ?><a href="#" id="report_tab" class="tab">Report</a><?php endif; ?>
			</div>
			<div class="clear"></div>
		</div>
		
		<div class="alert"></div>
		
		<div class="share_content video_actions_pane row clearfix">
		<div class="head">Why not spread the love?</div>
		<?php if(glue::session()->authed){ ?>
			<div class='share_item_with_subs col-md-6'>
			<p>Share with your subscribers</p>
			<?php echo html::textarea('share_status_text', 'Add some text here if you wish to describe why you shared this video or just click the share button to continue', 
					array('class' => 'share_status_text share_status_text_unchanged form-control')) ?>
			<div><input type="button" class="btn btn-success" value="Share"/></div>
			</div>
		<?php } ?>
		<div class="share_other col-md-6">
			<p>Share Elsewhere</p>
			<ul class="network_bc">
				<li><a rel='new_window' class="facebook-social-icon" href="http://www.facebook.com/sharer.php?u=<?php echo urlencode(glue::http()->url("/video/watch", array("id"=>$model->_id))) ?>"></a></li>
				<li><a rel='new_window' class="twitter-social-icon" href="http://twitter.com/share?url=<?php echo urlencode(glue::http()->url("/video/watch", array("id"=>$model->_id))) ?>"></a></li>
				<li><a rel='new_window' class="tumblr-social-icon" href="http://tumblr.com/share?s=&v=3&t=<?php echo urlencode($this->title) ?>&u=<?php echo urlencode(glue::http()->url("/video/watch", array("id"=>$model->_id))) ?>"></a></li>
				<li><a rel='new_window' class="reddit-social-icon" href="http://reddit.com/submit?url=<?php echo urlencode(glue::http()->url("/video/watch", array("id"=>$model->_id))) ?>"></a></li>
				<li><a rel='new_window' class="google-social-icon" href="https://plus.google.com/u/0/share?url=<?php echo urlencode(glue::http()->url("/video/watch", array("id"=>$model->_id))) ?>"></a></li>
			</ul>		
			<div class="clear"></div>
			<input type="text" class="select_all_onfoc form-control" value="<?php echo glue::http()->url("/video/watch", array("id"=>$model->_id)) ?>" />
			<div class="clear"></div>	
			<?php if($model->embeddable){ ?>
			<p class="embed">Embed:</p>
			<textarea rows="" cols="" class="select_all_onfoc form-control"><iframe style="width:560px; height:315px; border:0;" frameborder="0" src="<?php echo glue::http()->url("/video/embedded", array("id"=>$model->_id)) ?>"></iframe></textarea>
			<?php } ?>
		</div>
		</div>
					
		<div class="report_content video_actions_pane clearfix">
			<div class="head">Report</div>
			<p>Pick a reason out of the list below and then click "report":</p>
			<?php $grp=html::radio_group('report_reason'); ?>
			<div class="reason">
			<label class="radio"><?php echo $grp->add('sex'); ?>Sexual Content</label>
			<label class="radio"><?php echo $grp->add('abuse'); ?>Harmful/Voilent Acts &amp; (Child) Abuse</label>
			<label class="radio"><?php echo $grp->add('spam'); ?>Spam</label>
			<label class="radio"><?php echo $grp->add('religious'); ?>Hate Preaching/Religious</label>
			<label class="radio"><?php echo $grp->add('dirty'); ?>Just plain dirty</label>
			
			<input type="button" class="btn btn-success" value="Report Video"/>
			<p class="light">Abuse of this function may result in account deletion</p>			
			</div>
		</div>

		<?php if(!$model->private_stats){ ?>
			<div class="statistics_content video_actions_pane">
				<div class="head">Statistics</div>

				<div class='views_stats clearfix'>
					<div class='all_views stats_block'><?php echo $model->views ?> views</div>
					<div class='unique_views stats_block'><?php echo $model->uniqueViews ?> unique views</div>
					<div class="text_responses stats_block">
					<?php $textResponseCount = $model->getRelated('responses', array('type' => 'text'))->count() ?>
					<?php echo $textResponseCount ?> text <?php if($textResponseCount > 1): echo "responses"; else: echo "response"; endif ?>
					</div>
					<div class="video_responses stats_block">
					<?php $videoResponseCount = $model->getRelated('responses', array('type' => 'video', 'deleted' => 0))->count() ?>
					<?php echo $videoResponseCount ?> video <?php if($videoResponseCount > 1): echo "responses"; else: echo "response"; endif ?>					
					</div>
				</div>
				<div id="chartdiv" style="height:200px;width:100%; position:relative; margin-top:20px;"></div>
				<?php
				$video_stats = $model->getStatisticsDateRange(mktime(0, 0, 0, date("m"), date("d")-7, date("Y")), mktime(0, 0, 0, date("m"), date("d"), date("Y")));
				echo app\widgets\highCharts::run(array(
					'chartName' => 'video_views_plot',
					'appendTo' => 'chartdiv',
					'series' => $video_stats['hits'] //array(array('name'=>'c','data'=>array(array(1,1),array(2,2))))		
				)) ?>
			</div>
		<?php } ?>	
		
		<?php if(glue::auth()->check(array('@'))): ?>
		<div class="playlists_content playlists_pane video_actions_pane">
			<div class='search'>
				<a href="#" class="playlist_item watch_later" data-id="<?php echo glue::user()->watchLaterPlaylist()->_id ?>">Add to Watch Later</a>
				<input id="search_playlists" type="text" class="form-control" placeholder="Enter a search term for playlists"/>
			</div>
			<div class="results"><div class="no_results_found">Search for a playlist</div></div>
		</div>
		<?php endif; ?>

		</div>
		
	<?php if(glue::auth()->check(array('viewable' => $playlist))){ ?>
	<div class="playlist_bar_outer" data-id='<?php echo $playlist->_id ?>'>
		<div class='playlist_bar_head'>
			<div class="row">
			<div class='head_left col-md-9 col-sm-7 col-xs-8'>Playlist: <a href='<?php echo glue::http()->url('/playlist/view', array('id' => $playlist->_id)) ?>'><?php echo html::encode($playlist->title) ?></a> (<?php echo count($playlist->videos) ?> Videos)
			- By <b><a href='<?php echo glue::http()->url('/user/view', array('id' => $playlist->userId)) ?>'><?php echo $playlist->author->getUsername(); ?></a></b></div>
			<div class="col-md-3 col-sm-5 col-xs-4"><button class='view_all_videos btn btn-default btn-xs'>View All Videos</button></div>
			</div>
		</div>
		<div class='playlist_content'>
			<button class='move_left hidden-xs hidden-sm'></button>
			<button class='move_right hidden-xs hidden-sm'></button>
			<div class='playlist_video_list'>
				<div class='tray_content'>Loading</div></div>
		</div>
	</div>
	<?php } ?>		

		<?php if($model->allowTextComments||$model->allowVideoComments): ?>
		<div class="video_comments">
			<div class="head"><?php echo $model->totalResponses ?> responses</div>
			<a class='view_all' href="<?php echo glue::http()->url("/videoresponse/list", array("id"=>$model->_id)) ?>">View All Responses</a>
			<?php echo $this->renderPartial('response/list', array('model' => $model, 'comments' => 
					app\models\VideoResponse::find(array('videoId'=>$model->_id))->visible($model)->sort(array('created'=>-1))
			, 'pageSize' => 10, 'ajaxPagination'=>true)) ?>
		</div>
		<?php endif ?>
</div>
</div>

<div id='video_response_options' style='width:200px;'></div>
<div id='videoResponse_results' class=''></div>