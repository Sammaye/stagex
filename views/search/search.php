<?php

// Canonical URL
if($filter == 'videos'){
	$this->addHeadTag("<link rel='canonical' href='".Glue::url()->create('/search', array('filter' => 'videos'))."' />");
}elseif($filter == 'playlists'){
	$this->addHeadTag("<link rel='canonical' href='".Glue::url()->create('/search', array('filter' => 'playlists'))."' />");
}elseif($filter == 'users'){
	$this->addHeadTag("<link rel='canonical' href='".Glue::url()->create('/search', array('filter' => 'users'))."' />");
}else{
	$this->addHeadTag("<link rel='canonical' href='".Glue::url()->create('/search')."' />");
}


glue::clientScript()->addJsFile('jquery-expander', "/js/jquery-expander.js");
glue::clientScript()->addJsFile('playlist_dropdown', '/js/playlist_dropdown.js');
glue::clientScript()->addJsFile('j-dropdown', '/js/jdropdown.js');

ob_start(); ?>
	<div class='white_shaded_dropdown actions_menu sort_menu'>
		<div class='item' data-url='<?php echo glue::url()->create(array('sort' => 'relevance')) ?>'>Sorted by Revelance</div>
		<div class='item' data-url='<?php echo glue::url()->create(array('filter' => 'videos', 'sort' => 'upload_date')) ?>'>Sorted by Upload Date</div>
		<div class='item' data-url='<?php echo glue::url()->create(array('filter' => 'videos', 'sort' => 'views')) ?>'>Sorted by Views</div>
		<div class='item' data-url='<?php echo glue::url()->create(array('filter' => 'videos', 'sort' => 'rating')) ?>'>Sorted by Rating</div>
	</div><?php
	$sort_menu = ob_get_contents();
ob_end_clean();

ob_start(); ?>
	<div class='white_shaded_dropdown actions_menu duration_menu'>
		<div class='item' data-url='<?php echo glue::url()->create(array('filter' => 'videos', 'duration' => 'all')) ?>'>Show all Videos</div>
		<div class='item' data-url='<?php echo glue::url()->create(array('filter' => 'videos', 'duration' => 'ltthree')) ?>'>Show short videos</div>
		<div class='item' data-url='<?php echo glue::url()->create(array('filter' => 'videos', 'duration' => 'gtthree')) ?>'>Show long videos</div>
	</div><?php
	$duration_html = ob_get_contents();
ob_end_clean();

ob_start(); ?>
	<div class='white_shaded_dropdown actions_menu time_menu'>
		<div class='item' data-url='<?php echo glue::url()->create(array('time' => 'all')) ?>'>Show all time</div>
		<div class='item' data-url='<?php echo glue::url()->create(array('time' => 'today')) ?>'>Show today</div>
		<div class='item' data-url='<?php echo glue::url()->create(array('time' => 'week')) ?>'>Show this week</div>
		<div class='item' data-url='<?php echo glue::url()->create(array('time' => 'month')) ?>'>Show this month</div>
	</div><?php
	$time_html = ob_get_contents();
ob_end_clean();

glue::clientScript()->addJsScript('sortChange', "
	$(function(){
		$.playlist_dropdown({
			'singleadd_selector': '.playlist_button'
		});

		$('.expandable').expander({slicePoint: 120});

		$('#mainSearchSort').change(function(){
			//console.log('location', window.location);
			var params = location.search,
				parts = params.substr(1, params.length).split('&'),
				edited = false;

			for(var i=0; i<parts.length; i++){
				if(parts[i].match(/sort=/i)){
					parts[i] = 'sort='+$(this).find(':selected').val();
					edited = true;
				}
			}

			if(!edited){
				parts[parts.length] = 'sort='+$(this).find(':selected').val();
			}

			//console.log('parts', '/search?'+parts.join('&'));
			window.location = '/search?'+parts.join('&');
		});

		$('body').append($(".GClientScript::encode($sort_menu)."));
		$('.sort_actions').jdropdown({
			'orientation': 'over',
			'menu_div': '.sort_menu',
			'item': '.sort_menu .item'
		});

		$('body').append($(".GClientScript::encode($duration_html)."));
		$('.duration_actions').jdropdown({
			'orientation': 'over',
			'menu_div': '.duration_menu',
			'item': '.duration_menu .item'
		});

		$('body').append($(".GClientScript::encode($time_html)."));
		$('.time_actions').jdropdown({
			'orientation': 'over',
			'menu_div': '.time_menu',
			'item': '.time_menu .item'
		});

		$(document).on('jdropdown.selectItem', '.actions_menu .item', function(e, event){
		     //event.preventDefault();
			//$('.selected_filter').html($(this).data('caption'));
			window.location = $(this).data('url');
		});
	});
") ?>
<div class='body' style='margin-bottom:250px;'>
<div class='search_main_head'>
	<div class='head'>Search Results for <?php echo strlen(strip_whitespace(glue::http()->param('mainSearch'))) > 0 ? glue::http()->param('mainSearch') : 'everything'  ?></div>
	<div class='total_found'><?php echo $sphinx->total_found > 0 ? $sphinx->total_found.' results' : 'None found' ?></div>
</div>
<div class='search_filter_bar'>
	<div class='search_filter_bar_inner'>

		<div class='search_text_filter_bar'>
			<?php if($filter == 'all' || !$filter): ?>
				<span class='search_text_filt'>Everything</span>
			<?php else: ?>
				<a class='active' href='<?php echo Glue::url()->create('/search', array('filter' => 'all', 'mainSearch' => glue::http()->param('mainSearch'))) ?>'>Everything</a>
			<?php endif; ?>
				<span class='divider'>|</span>
			<?php if($filter == 'videos'): ?>
				<span class='search_text_filt'>Videos</span>
			<?php else: ?>
				<a class='active' href='<?php echo Glue::url()->create('/search', array('filter' => 'videos', 'mainSearch' => glue::http()->param('mainSearch'))) ?>'>Videos</a>
			<?php endif; ?>
				<span class='divider'>|</span>
			<?php if($filter == 'playlists'): ?>
				<span class='search_text_filt'>Playlists</span>
			<?php else: ?>
				<a class='active' href='<?php echo Glue::url()->create('/search', array('filter' => 'playlists', 'mainSearch' => glue::http()->param('mainSearch'))) ?>'>Playlists</a>
			<?php endif; ?>
				<span class='divider'>|</span>
			<?php if($filter == 'users'): ?>
				<span class='search_text_filt'>Users</span>
			<?php else: ?>
				<a class='active' href='<?php echo Glue::url()->create('/search', array('filter' => 'users', 'mainSearch' =>glue::http()->param('mainSearch'))) ?>'>Users</a>
			<?php endif; ?>
		</div>
		<div class='grey_css_button time_actions float_right'>
			<?php if($time_show == 'all' || !$time_show){ ?>
				Showing all time
			<?php }elseif($time_show == 'today'){ ?>
				Showing today
			<?php }elseif($time_show == 'week'){ ?>
				Showing this week
			<?php }elseif($time_show == 'month'){ ?>
				Showing this month
			<?php } ?>
		</div>
		<?php if($filter == 'videos' || $filter == 'all' || !$filter){ ?>
			<div class='grey_css_button duration_actions float_right'>
				<?php if($duration == 'all' || !$duration){ ?>
					Showing all lengths
				<?php }elseif($duration == 'ltthree'){ ?>
					Showing short videos
				<?php }elseif($duration == 'gtthree'){ ?>
					Showing long videos
				<?php } ?>
			</div>
			<div class='grey_css_button sort_actions float_right'>
				<?php if($sort == 'relevance' || !$sort){ ?>
					Sorted by Revelance
				<?php }elseif($sort == 'upload_date'){ ?>
					Sorted by Upload Date
				<?php }elseif($sort == 'views'){ ?>
					Sorted by Views
				<?php }elseif($sort == 'rating'){ ?>
					Sorted by Rating
				<?php } ?>
			</div>
		<?php } ?>
		<div class='float_right safe_search'><a href='<?php echo glue::url()->create('/user/settings') ?>'>
			<?php if(glue::session()->user->safe_srch == "S" || !glue::session()->authed){ ?>
				Safe Search On
			<?php }else{ ?>
				Safe Search Off
			<?php } ?></a>
		</div>
	</div>
</div>
<div class="container_16 search_body" style='width:970px;'>
	<div class="grid_12 alpha alpha result_list" style='width:650px;'>
		<?php
		if(count($sphinx->matches) > 0){
			foreach($sphinx->matches as $k => $model){

				if(!$model)
					continue;

				if($model instanceof Video){
					?>
					<div class='video_item video_search_item' data-id='<?php echo $model->_id ?>'>
						<div class='video_thumbnail_pane'>
							<a href="<?php echo Glue::url()->create("/video/watch", array("id"=>$model->_id)) ?>" ><img alt='thumbnail' src="<?php echo $model->getImage(138, 77) ?>"/></a>
								<div class='duration_hover'><span><?php echo $model->get_time_string() ?></span></div>
								<?php if(glue::roles()->checkRoles(array('@'))): ?><a class='playlist_button' href='#'><img alt='add to' src='/images/add_tooltip.png'/></a><?php endif; ?>
						</div>
						<div class='details'>
							<h3 class='title'><a href="<?php echo Glue::url()->create("/video/watch", array("id"=>$model->_id)) ?>"><?php echo $model->title ?></a></h3>
							<?php if($model->author): ?>
								<div class='info'>
									Uploaded by <a href="<?php echo glue::url()->create('/user/view', array('id' => strval($model->author->_id))) ?>"><?php echo $model->author->getUsername() ?></a> <span style='color:#ebebeb; margin:0 5px;'>|</span> <?php echo $model->views ?> views
								</div>
							<?php endif; ?>
							<div class='expandable'><?php echo $model->description ?></div>
						</div>
						<div class="clear"></div>
					</div>
					<?php
				}elseif($model instanceof User){
					?>
					<div class='user_search_item'>
						<div class='user_image'><a href="/user/view?id=<?php echo strval($model->_id) ?>" ><img alt='thumbnail' src="<?php echo $model->getAvatar(55, 55) ?>"/></a></div>
						<div class='details'>
							<div class='title'><a href="/user/view?id=<?php echo strval($model->_id) ?>"><?php echo $model->getUsername() ?></a></div>
							<div class='info'><?php echo $model->total_subscribers ?> subscribers <span class='divider'>|</span> <?php echo $model->total_uploads ?> videos <span class='divider'>|</span> <?php echo $model->total_playlists ?> playlists</div>
							<div class='about'><?php echo substr($model->about, 0, 60) ?></div>
						</div>
						<div class="clear"></div>
					</div>
					<?php
				}elseif($model instanceof Playlist){ ?>
					<div class='playlist_item search_playlist_item playlist_search_item'>
						<div class='thumb_block'>
							<?php
								$pics = $model->get4Pics();
								$large_pic = $pics[0];
							?>
							<img alt='thumbnail' src='<?php echo $large_pic ?>' class='large_pic'/>
							<?php for($i = 1; $i < count($pics); $i++){ ?>
								<img alt='thumbnail' src='<?php echo $pics[$i] ?>' class='smaller <?php if($i==3) echo 'last' ?>'/>
							<?php } ?>
						</div>
						<div class='details'>
							<div class='title'><a href='<?php echo glue::url()->create('/playlist/view', array('id' => strval($model->_id))) ?>'><?php echo $model->title ?></a></div>
							<div class='info'>
								<div class='videos'><?php echo count($model->videos) ?><div>videos</div></div>
								<?php if($model->author): ?>
									<div class='compiled_user'><span class='divider'>|</span>Compiled by <a href="<?php echo glue::url()->create('/user/view', array('id' => strval($model->author->_id))) ?>"><?php echo $model->author->getUsername() ?></a></div>
								<?php endif; ?>
							</div>
							<div class='expandable'><?php echo $model->description ?></div>
						</div>
						<div class='clearer'></div>
					</div>
				<?php }

			}
		}else{ ?>
			<div class="warning_message_curved">
				<div class="tl"></div><div class="tr"></div><div class="bl"></div><div class="br"></div>
				<div class="content">
					<h2>No results found for "<?php echo glue::http()->param('mainSearch') ?>"</h2>
					<p>Oh noes! You have two choices from here:</p>
					<ul>
						<li>Try a less specific search that might wield results or browse our site further.</li>
						<li>If that fails you can upload the video to this site</li>
					</ul>
				</div>
			</div>
		<?php } ?>

		<div class='search_pager'><?php echo $sphinx->renderPager('grid_list_pager') ?><div class="clear"></div></div>
	</div>
	<div class="grid_4 omega" style='width:300px;'>
		<?php $this->widget("application/widgets/Advertising/Ad_box.php", array( "configuration"=>'300_box' )); ?>
		<div style='margin-top:25px;'>
			<?php $this->widget("application/widgets/Advertising/Ad_box.php", array( "configuration"=>'300_box' )); ?>
		</div>
	</div>
</div>
</div>