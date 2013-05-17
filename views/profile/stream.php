<?php
ob_start();
	?>
		<div class='white_shaded_dropdown filters_menu'>
			<div class='item' data-caption='Showing All Activity' data-filter='all'>All Activity</div>
			<div class='item' data-caption='Showing Actions Activity' data-filter='actions'>Actions Only</div>
			<div class='item' data-caption='Showing Comment Activity' data-filter='comments'>Comments Only</div>
		</div>
	<?php
	$menu_html = ob_get_contents();
ob_end_clean();

ob_start(); ?>
	<h2 class='diag_header'>Reply</h2>
		<div class='form reply_form'>
			<div class='row'><?php echo html::hiddenfield('user_id').html::textarea('message', null) ?></div>
			<a href='#' class='green_css_button add_reply float_left'>Reply</a> <a href='#' class='grey_css_button cancel'>Cancel</a>
		</div><?php
	$reply_diag_html = ob_get_contents();
ob_end_clean();

glue::clientScript()->addJsFile('j-dropdown', '/js/jdropdown.js');

glue::clientScript()->addJsScript('user.stream', "
	$(function(){
		$('.expandable').expander({slicePoint: 200});

		var hash_action = getActionHash();
		if(hash_action[0] == 'wall_post_reply'){
			$.facebox(".GClientScript::encode($reply_diag_html).", 'add_wall_post_diag');
			$('.add_wall_post_diag input[name=user_id]').val(hash_action[1]);
			window.location.hash = '';
		}

		$(document).on('click', '.stream_comment_reply a', function(event){
			event.preventDefault();
			$.facebox(".GClientScript::encode($reply_diag_html).", 'add_wall_post_diag');
			$('.add_wall_post_diag input[name=user_id]').val($(this).parents('.streamitem').data('target_user'));
		});

		$(document).on('click', '.add_wall_post_diag .cancel', function(e){
			e.preventDefault();
			$('.add_wall_post_diag textarea').val('');
			$.facebox.close();
		});

		$(document).on('click', '.add_wall_post_diag .add_reply', function(e){
			e.preventDefault();
			$.post('/stream/add_comment', {text: $('.add_wall_post_diag textarea').val(), user_id: $('.add_wall_post_diag input[name=user_id]').val()}, function(data){
				if(data.success){
					$('.add_wall_post_diag textarea').val('');
					$('.add_wall_post_diag input[name=user_id]').val('');
					$.facebox.close();
					$('.list').prepend($(data.html));
				}
			}, 'json');
		});

		$(document).on('click focus', '.profile_comment_textarea', function(event){
			if($('.profile_comment_textarea').hasClass('profile_comment_textarea_unchanged')){
				$('.profile_comment_textarea').removeClass('profile_comment_textarea_unchanged').val('');
			}
		});

		$(document).on('click', '.submit_comment', function(event){
			var text = $('.profile_comment_textarea').val();
			if($('.profile_comment_textarea').hasClass('profile_comment_textarea_unchanged')){
				var text = '';
			}
			$.post('/stream/add_comment', {text: text, user_id: '".strval($user->_id)."'}, function(data){
				if(data.success){
					$('.list').prepend($(data.html));
					$('.profile_comment_textarea').val('');
				}else{
					// Show error
				}
			}, 'json');
		});

		$(document).on('click', '.load_more', function(event){
			event.preventDefault();
			var last_ts = $('.list .streamitem').last().data('ts'),
				filter = $('.list').data('sort');
			$.getJSON('/stream/get_stream', {ts: last_ts, filter: filter, uid: '".$user->_id."', hide_del: 1 }, function(data){
				if(data.success){
					$('.list').append(data.html);
				}else{
					if(data.noneleft){
						$('.load_more').html(data.messages[0]);
					}
				}
			});
		});

		$('body').append($(".GClientScript::encode($menu_html)."));
		$('.selected_filter').jdropdown({
			'orientation': 'over',
			'menu_div': '.filters_menu',
			'item': '.filters_menu .item'
		});

	    $(document).on('jdropdown.selectItem', '.filters_menu .item', function(e, event){
	        //event.preventDefault();
			$('.selected_filter').html($(this).data('caption'));
			$('.list').data('sort', $(this).data('filter'));

			$.getJSON('/stream/get_stream', { filter: $(this).data('filter'), uid: '".$user->_id."', hide_del: 1 }, function(data){
				if(data.success){
					$('.list').html(data.html);
					$('.load_more').html('Load more stream');
				}else{
					if(data.noneleft){
						$('.list').html('<div style=\'font-size:16px; font-weight:normal; padding:21px;\'>'+data.initMessage+'</div>');
						$('.load_more').html(data.messages[0]);
					}
				}
			});
	    });
	});

"); ?>

<div class='grid_16 alpha omega profile_stream_body'>
	<div class='main_content_inner'>
		<div class='grid_5 alpha stream_left'>
			<div class='stream_head'>
				<div class='header'>Stream</div>
				<div class='grey_css_button selected_filter'>Showing All Activity</div>
			</div>
			<div class='profile_stream_content'>
				<?php if($_SESSION['logged']){ ?>
					<div class='comment_area'>
						<?php echo html::textarea('comment', 'Post a message to this user or share something with them', array('class' => 'profile_comment_textarea profile_comment_textarea_unchanged')) ?>
						<div class='green_css_button submit_comment'>Post Comment</div>
					</div>
				<?php } ?>
				<div class='clearer'></div>

				<div class='list' style='margin-top:20px;'>
					<?php
					if($stream->count() > 0){
						foreach($stream as $k => $item){
							$this->partialRender('stream/streamitem', array('item' => $item, 'hideDelete' => true));
						}
					}else{ ?>
						<div class='no_stream'>No stream has yet been recorded for this user</div>
					<?php } ?>
				</div>
				<?php if($stream->count() > 20){ ?>
					<a class='load_more' href='#'>Load more stream</a>
				<?php } ?>
			</div>
		</div>
		<div class='grid_3 omega stream_right'>
			<h3 class='about_head'>About <?php echo $user->getUsername() ?></h3>

			<p class='expandable'><?php echo $user->getAbout(); ?></p>

			<div style='margin-bottom:15px;'>
				<?php $urls = array_chunk(is_array($user->external_links) ? $user->external_links : array(), 3); ?>
				<?php for($i = 0, $size= count($urls); $i < $size; $i++){ ?>
					<ul class='user_profile_url_list'>
						<?php for($j = 0, $url_s = count($urls[$i]); $j < $url_s; $j++){ $row = $urls[$i][$j]; ?>
							<li><?php echo html::a(array('href' => $row['url'], 'text' => $row['title'] ? $row['title'] : $row['url'], 'rel' => 'nofollow')) ?></li>
						<?php } ?>
					</ul>
				<?php } ?>
			</div>

			<div class='user_details'>
				<?php if(($user->profile_privacy['gender'] != 1 || glue::session()->user->_id == $user->_id) && $user->gender): ?><div><b>Gender:</b> <?php echo $user->gender == 'm' ? "Male" : "Female" ?></div><?php endif; ?>
				<?php if(($user->profile_privacy['birthday'] != 1 || glue::session()->user->_id == $user->_id)
					&& $user->birth_day && $user->birth_month && $user->birth_year): ?><div><b>Birthday:</b> <?php echo date('d M Y', mktime(0, 0, 0, $user->birth_month, $user->birth_day, $user->birth_year))?></div><?php endif; ?>
				<?php if(($user->profile_privacy['country'] != 1 || glue::session()->user->_id == $user->_id) && $user->country): ?><div><b>Country:</b> <?php $countries = new GListProvider('countries', array("code", "name")); echo $user->country ? $countries[$user->country] : "N/A"; ?></div><?php endif; ?>
				<div><b>Date Joined:</b> <?php echo date('d M Y', $user->getTs())?></div>
			</div>

			<div style='margin-top:25px;'>
			<?php $this->widget("application/widgets/Advertising/Ad_box.php", array( "configuration"=>'300_box' )); ?>
			</div>
		</div>
		<div class='clearer'></div>
	</div>
</div>