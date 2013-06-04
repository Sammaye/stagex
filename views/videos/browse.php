<?php

// Canonical URL
if($cat){
	$this->addHeadTag("<link rel='canonical' href='".Glue::url()->create('/video', array('cat' => $cat))."' />");
}else{
	$this->addHeadTag("<link rel='canonical' href='".Glue::url()->create('/video')."' />");
}


glue::clientScript()->addJsFile('jquery-expander', "/js/jquery-expander.js");
glue::clientScript()->addJsFile('playlist_dropdown', '/js/playlist_dropdown.js');
glue::clientScript()->addJsFile('j-dropdown', '/js/jdropdown.js');

glue::clientScript()->addJsFile('qtip', "/js/jquery.qtip.min.js");
glue::clientScript()->addCssFile('qtip_css', "/css/jquery.qtip.min.css");

glue::clientScript()->addJsScript('qtip_page', "
	$(function(){
		$('.video_item_qtip').qtip({
			content: {
				text: function(api){
					//console.log($(this).data());
					var xinfo = $(this).data('xinfo');
					return '<h2 style=\'font-size:14px; line-height:17px;\'>'+xinfo.title+'</h2><p style=\'font-size:13px; line-height:17px;\'>'+xinfo.description+'</p>';
				}
			},
			style: {
				classes: 'ui-tooltip-shadow ui-tooltip-light'
			},
			show: {
				delay: 1000
			},
			position: {
				my: 'left center',
				at: 'right center',
				adjust: {
					//y: -30
				}
			}
		});
	});
");

$video = new Video();
$category_url_array = $video->categories();

ob_start(); ?>
	<div class='actions_menu_menu category_menu'>
		<div class='item' data-url='<?php echo glue::url()->create(array('cat' => '')) ?>'><?php echo 'All Categories' ?></div>
		<?php foreach($category_url_array as $k=>$v){ ?>
		<div class='item' data-url='<?php echo glue::url()->create(array('cat' => $k)) ?>'><?php echo $v[0] ?></div>
		<?php } ?>
	</div><?php
	$cat_menu = ob_get_contents();
ob_end_clean();

ob_start(); ?>
	<div class='actions_menu_menu sort_menu'>
		<div class='item' data-url='<?php echo glue::url()->create(array('sort' => 'upload_date')) ?>'>Sorted by Upload Date</div>
		<div class='item' data-url='<?php echo glue::url()->create(array('sort' => 'views')) ?>'>Sorted by Views</div>
		<div class='item' data-url='<?php echo glue::url()->create(array('sort' => 'rating')) ?>'>Sorted by Rating</div>
	</div><?php
	$sort_menu = ob_get_contents();
ob_end_clean();

ob_start(); ?>
	<div class='actions_menu_menu duration_menu'>
		<div class='item' data-url='<?php echo glue::url()->create(array('duration' => 'all')) ?>'>Show all Videos</div>
		<div class='item' data-url='<?php echo glue::url()->create(array('duration' => 'ltthree')) ?>'>Show short videos</div>
		<div class='item' data-url='<?php echo glue::url()->create(array('duration' => 'gtthree')) ?>'>Show long videos</div>
	</div><?php
	$duration_html = ob_get_contents();
ob_end_clean();

ob_start(); ?>
	<div class='actions_menu_menu time_menu'>
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

		$('body').append($(".GClientScript::encode($cat_menu)."));
		$('.category_actions').jdropdown({
			'orientation': 'over',
			'menu_div': '.category_menu',
			'item': '.category_menu .item'
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

		$(document).on('jdropdown.selectItem', '.actions_menu_menu .item', function(e, event){
		     //event.preventDefault();
			//$('.selected_filter').html($(this).data('caption'));
			window.location = $(this).data('url');
		});
	});
") ?>
<div class='video_browse_body'>
	<div class='video_browse_bar'>
		<div class='inner'>

			<div class='float_left'>
				<div class='grey_css_button category_actions'>
					<?php if(!$cat || $cat == ''){ ?>
						All Categories
					<?php }else{
						echo $category_url_array[$cat][0];
					} ?>
				</div>
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
					<?php if($sort == 'upload_date' || !$sort){ ?>
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
			<div class='clearer'></div>
		</div>
	</div>
	<div class='grid_970'>
		<div class='center_list browse_left'>
			<?php if($sphinx->total_found > 0){ ?>
			<ul class='browse_video_list'>
				<?php foreach($sphinx->matches as $k => $v){
					if($v instanceof Video){ ?>
					<li><div class='video_item <?php if(strlen($v->description) > 10): echo 'video_item_qtip'; endif; ?>' data-id='<?php echo $v->_id ?>' data-xinfo='<?php echo json_encode(array( 'title' => $v->getHTML_safeTitle(), 'description' => $v->getLongAbstract() )) ?>'>
						<div class='thumb_outer' style='position:relative; width:138px; height:77px;'>
							<a href='<?php echo Glue::url()->create("/video/watch", array("id"=>$v->_id)) ?>'><img alt='thumbnail' class='thumb' src='<?php echo $v->getImage(138, 77) ?>' /></a>
							<div class='duration_hover'><span><?php echo $v->get_time_string() ?></span></div>
							<?php if(glue::session()->authed): ?><a class='playlist_button' href='#'><img alt='add to' src='/images/add_tooltip.png'/></a><?php endif; ?>
						</div>
						<?php if($v->author): ?>
							<div class='details_cap'><h3 class='title'><a href='<?php echo Glue::url()->create("/video/watch", array("id"=>$v->_id)) ?>'><?php echo strlen($v->title) > 17 ? substr($v->title, 0, 17).'...' : $v->title ?></a></h3>
								<span class='upload_user'>By <a href='<?php echo Glue::url()->create("/user/view", array("id"=>$v->author->_id)) ?>'><?php echo $v->author->getUsername() ?></a></span></div>
						<?php endif; ?>
					</div></li>
					<?php } ?>
				<?php } ?>
			</ul>
			<div class='clearer'></div><div style='margin-top:10px;'><?php echo $sphinx->renderPager('grid_list_pager') ?><div class="clearer"></div></div>
			<?php }else{ ?>
				<div class='no_videos'>No videos found in this category</div>
			<?php } ?>
		</div>
		<div style='float:left; width:300px; margin-left:25px;'>
			<?php $this->widget("application/widgets/Advertising/Ad_box.php", array( "configuration"=>'300_box' )); ?>
			<div style='margin-top:25px;'>
				<?php $this->widget("application/widgets/Advertising/Ad_box.php", array( "configuration"=>'300_box' )); ?>
			</div>
		</div>
		<div class='clearer'></div>
	</div>
</div>