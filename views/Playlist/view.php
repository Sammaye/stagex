<?php
glue::clientScript()->addJsFile('jquery-expander', "/js/jquery-expander.js");
glue::clientScript()->addJsFile('j-dropdown', '/js/jdropdown.js');
glue::clientScript()->addJsFile('playlist_dropdown', '/js/playlist_dropdown.js');

glue::clientScript()->addJsScript('page', "
	$(function(){
		$.playlist_dropdown();
		$('.expandable').expander({slicePoint: 30});
	});
");

glue::clientScript()->addJsScript('user.subscribe', "
	$(document).on('click', '.subscribe', function(event){
		event.preventDefault();

		var el = $(this);
		$.getJSON('/user/subscribe', {id: '".strval($user->_id)."'}, function(data){
			if(data.success){
				el.removeClass('green_css_button subscribe').addClass('grey_css_button unsubscribe').find('div').html('Unsubscribe');
			}
		});
	});

	$(document).on('click', '.unsubscribe', function(event){
		event.preventDefault();

		var el = $(this);
		$.getJSON('/user/unsubscribe', {id: '".strval($user->_id)."'}, function(data){
			if(data.success){
				el.removeClass('grey_css_button unsubscribe').addClass('green_css_button subscribe').find('div').html('Subscribe');
			}
		});
	});


	$(document).on('click', '.like', function(event){
		event.preventDefault();

		var el = $(this);
		$.getJSON('/playlist/like', {id: '".strval($playlist->_id)."'}, function(data){
			if(data.success){
				el.removeClass('green_css_button like').addClass('grey_css_button unlike').html('Unlike');
			}
		});
	});

	$(document).on('click', '.unlike', function(event){
		event.preventDefault();

		var el = $(this);
		$.getJSON('/playlist/unlike', {id: '".strval($playlist->_id)."'}, function(data){
			if(data.success){
				el.removeClass('grey_css_button unlike').addClass('green_css_button like').html('Like');
			}
		});
	});

	$(document).on('click', '.xshare_playlist', function(event){
		event.preventDefault();
		$('.xshare_playlist_body').css({ 'display': 'block' });
	});

	$(document).on('focus', '.share_text', function(event){
		if(!$(this).hasClass('clicked')){
			$(this).addClass('clicked');
			$(this).val('');
		}
	});

	$(document).on('click', '.cancel_xshare', function(event){
		event.preventDefault();
		$('.xshare_playlist_body').css({ 'display': 'none' });
	});

	$(document).on('click', '.xshare_confirm', function(event){
		event.preventDefault();
		var text = '';

		if($('.share_text').hasClass('clicked') && $('.share_text').val().length > 0){
			text = $('.share_text').val();
		}

		$.getJSON('/stream/share', { type: 'playlist', id: '".$playlist->_id."', text: text }, function(data){
			if(data.success){
				forms.summary($('.xshare_playlist_body .block_summary'), true, data.message);
				$('.share_text').val('');
			}else{
				forms.summary($('.xshare_playlist_body .block_summary'), false, data.message);
			}
		});
	});
");

$video_count = count($playlist->videos);
?>
<div class='container_16'>
<div class='grid_16 alpha omega view_playlist_body'>
	<div class='main_content_outer'>
		<div class='grid_5 alpha main_content_left'>
			<div class='head_outer'>
				<h1 class='head'><?php echo html::encode($playlist->title) ?></h1>
				<?php if(glue::session()->authed){ ?>
					<?php if(glue::roles()->checkRoles(array('^' => $playlist))){ ?>
						<a href='<?php echo glue::url()->create('/playlist/edit', array('id' => strval($playlist->_id))) ?>' class='grey_css_button'>Edit Playlist</a>
					<?php }else{ ?>
						<?php if($playlist->current_user_likes()){ ?>
							<div class='grey_css_button unlike'>Unlike</div>
						<?php }else{ ?>
							<div class='green_css_button like'>Like</div>
						<?php } ?>
					<?php } ?>
				<?php } ?>
			</div>
			<div class='head_stats'><?php echo $video_count ?> videos<span class='divider'>|</span><?php echo $playlist->likes ?> like this</div>
			<div class='clearer'></div>
			<?php if($playlist->description){ ?>
				<div class='playlist_description'><?php echo html::nl2br($playlist->description) ?></div>
			<?php } ?>

			<?php if($video_count <= 0){ ?>
				<div class='no_videos'>No videos were found for this playlist</div>
			<?php }else{ ?>
				<div class='playlist_video_list'>
				<?php
				$i = 0;
				foreach($playlist->videos as $k=>$v){
					$model = Video::model()->findOne(array('_id' => $v['_id'])); ?>
					<div class='video_item' data-id='<?php echo strval($model->_id) ?>' style='<?php if($i != ($video_count-1)): echo "border-bottom:1px solid #ebebeb;"; endif ?>'>
						<?php if(!$model->_id instanceof MongoId){
							$model = new Video; ?>
							<div class='video_thumb_pane video_thumbnail_pane' style='position:relative;'>
								<a href="<?php echo glue::url()->create('/video/watch') ?>" ><img alt='thumbnail' src="<?php echo $model->getImage(138, 77) ?>"/></a></div>
							<div class='more_info_pane'>
								<h3 class='title'><a href="/video/watch">[Video Deleted]</a></h3>
							</div>
						<?php }else{ ?>
							<div class='video_thumb_pane video_thumbnail_pane' style='position:relative;'>
								<a href="<?php echo glue::url()->create('/video/watch', array('id' => $model->_id, 'plid' => $playlist->_id)) ?>" ><img alt='thumbnail' src="<?php echo $model->getImage(138, 77) ?>"/></a>
								<div class='duration_hover'><span><?php echo $model->get_time_string() ?></span></div>
								<?php if(glue::session()->authed){ ?><a class='playlist_button' href='#'><img alt='add_to' src='/images/add_tooltip.png'/></a><?php } ?></div>
							<div class='more_info_pane'>
								<h3 class='title'><a href="<?php echo glue::url()->create('/video/watch', array('id' => $model->_id, 'plid' => $playlist->_id)) ?>"><?php echo $model->title ?></a></h3>
								<div class='uploaded'>
									Uploaded by <a href="<?php echo glue::url()->create('/user/view', array('id' => $model->author->_id)) ?>"><?php echo $model->author->getUsername() ?></a> <span class='divider'>|</span> <?php echo $model->views ?> views
									<?php if($model->likes+$model->dislikes > 0){ ?><span class='divider'>|</span> <?php echo $model->likes.' likes ' ?><span class='divider'>|</span><?php echo $model->dislikes ?> dislikes<?php } ?>
								</div>
								<?php if($model->description){ ?>
									<div class='expandable description'><?php echo $model->description ?></div>
								<?php } ?>
							</div>
						<?php } ?>
						<div class="clearer"></div>
					</div>
					<?php $i++;
				} ?>
				<div class='clearer'></div>
				</div>
			<?php } ?>
		</div>

		<div class='grid_3 omega main_content_right'>

			<div class='avatar_block'>
				<div class='user_image'><img alt='thumbnail' src="<?php echo $user->getAvatar(48, 48); ?>"/></div>
				<div class='about_user'><a href='<?php echo glue::url()->create('/user/view', array('id' => strval($user->_id))) ?>'><?php echo $user->getUsername() ?></a><div class='subs'><?php echo $user->total_subscribers ?> subscribers</div>
				</div>
			</div>
			<?php if($user->_id != glue::session()->user->_id && glue::session()->authed){ ?>
				<div class='user_subscribe'>
					<?php if(Subscription::isSubscribed($user->_id)){ ?>
						<div class='unsubscribe grey_css_button'><div>Unsubscribe</div></div>
					<?php }else{ ?>
						<div class='subscribe green_css_button'><div>Subscribe</div></div>
					<?php } ?>
				</div>
			<?php } ?>

			<p class='expandable'><?php echo $user->getAbout(); ?></p>

			<div class='share_bloc'>
				<div class='share_capt'>Share Playlist</div>
				<?php if(glue::session()->authed){ ?>
					<div class='xshare_button'><a href='#' class='green_css_button float_left block xshare_playlist'>Share to Subscribers</a></div>
					<div class='xshare_playlist_body'>
						<div class='block_summary' style='display:none;'></div>
						<div class='share_txt_outer'><?php echo html::textarea('share_text', 'Write a reason for sharing this, you don\'t have to :)', array('class' => 'share_text')) ?></div>
						<a href='#' class='green_css_button float_left block xshare_confirm'>Share</a>
						<a href='#' class='grey_css_button float_left block cancel_xshare'>Cancel</a>
					</div>
				<?php } ?>
				<div class='clearer'></div>
				<div style='margin-top:10px;'>
					<a rel='new_window' href="http://www.facebook.com/sharer.php?u=<?php echo urlencode(Glue::url()->create("/playlist/view", array("id"=>$playlist->_id))) ?>"><img alt='fb' src="/images/fb_large.png"/></a>
					<a rel='new_window' href="http://twitter.com/share?url=<?php echo urlencode(Glue::url()->create("/playlist/view", array("id"=>$playlist->_id))) ?>"><img alt='twt' src="/images/twt_large.png"/></a>
					<a rel='new_window' href="http://www.plurk.com/?status=<?php echo urlencode(Glue::url()->create("/playlist/view", array("id"=>$playlist->_id))) ?>"><img alt='plurk' src="/images/plurk_large.png"/></a>
					<g:plusone size="medium" annotation="inline" href="<?php echo glue::url()->create('/playlist/view', array('id' => $playlist->_id)) ?>"></g:plusone>
				</div>
			</div>

			<div style='margin-top:25px;'>
				<?php $this->widget("application/widgets/Advertising/Ad_box.php", array( "configuration"=>'300_box' )); ?>
			</div>
		</div>
		<div class='clearer'></div>
	</div>
</div>
</div>