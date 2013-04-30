<?php
glue::clientScript()->addJsFile('jquery-expander', "/js/jquery-expander.js");

glue::clientScript()->addJsScript('user.subscribe', "
	$(function(){
		$('.expandable').expander({slicePoint: 90});
	});

	$('#profile_search_submit').on('click', function(){
		$(this).parents('form').submit();
	});


	$(document).on('click', '.subscribe', function(event){
		event.preventDefault();

		var el = $(this);
		$.getJSON('/user/subscribe', {id: '".strval($user->_id)."'}, function(data){
			if(data.success){
				el.removeClass('green_css_button subscribe').addClass('grey_css_button unsubscribe').find('div').html('Unsubscribe');
			}else{}
		});
	});

	$(document).on('click', '.unsubscribe', function(event){
		event.preventDefault();

		var el = $(this);
		$.getJSON('/user/unsubscribe', {id: '".strval($user->_id)."'}, function(data){
			if(data.success){
				el.removeClass('grey_css_button_right unsubscribe').addClass('green_css_button subscribe').find('div').html('Subscribe');
			}else{

			}
		});
	});

	$(document).on('click', '.expand_user_about', function(event){
		event.preventDefault();
		$('.mini_about').hide();
		$('.full_about').show();
	});

	$(document).on('click', '.shrink_user_about', function(event){
		event.preventDefault();
		$('.full_about').hide();
		$('.mini_about').show();
	});

") ?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html xmlns:fb="http://www.facebook.com/2008/fbml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta http-equiv="content-language" content="en"/>
		<meta name="description" content="<?php echo $this->pageDescription ?>" />
		<meta name="keywords" content="<?php echo $this->pageKeywords ?>" />

		<link rel="shortcut icon" href="/images/favicon.ico" />

		<title><?php echo $this->pageTitle ?></title>

		<?php
			glue::clientScript()->addCoreJsFile('jqueryui', '/js/jquery-ui.js');
			glue::clientScript()->addCoreJsFile('jquery', '/js/jquery.js');

			glue::clientScript()->addJsFile('facebox', "/js/facebox.js");
			glue::clientScript()->addJsFile("common", '/js/common.js');

			glue::clientScript()->addCoreCSSFile('reset', "/css/reset.css");
			glue::clientScript()->addCoreCSSFile('960', "/css/960.css");
			glue::clientScript()->addCoreCSSFile('main', "/css/main.css");
			glue::clientScript()->addCoreCSSFile('springhare', "/css/springhare.css");

			glue::clientScript()->addJsScript('ga_script', "var _gaq = _gaq || [];
			  _gaq.push(['_setAccount', 'UA-31049834-1']);
			  _gaq.push(['_trackPageview']);

			  (function() {
			    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		  	})();", GClientScript::HEAD);
		?>

	</head>

	<body style=''>
		<?php $this->widget('application/widgets/presenceBar.php') ?>

		<div class='container_16'><div class='grid_16 alpha omega user_profile_head'>

			<div class='top' style='position:relative; min-height:135px;'>
				<div class='head_outer'>
					<div class='user_image'><img src="<?php echo $user->getPic(125, 125); ?>" alt='thumbnail'/></div>
					<div class='user_about'>
						<h1 class='username'><?php echo $user->getUsername() ?></h1>
						<div class='expandable_small'>
							<div class='mini_about'><?php echo substr(htmlspecialchars($user->about), 0, 80); if($user->about): echo '...'; endif; echo html::a(array('href' => '#', 'text' => 'About this user', 'class' => 'expand_user_about')) ?></div>
							<div class='full_about' style='display:none;'>
								<p><?php echo $user->getAbout(); ?></p>

								<div style='margin-bottom:15px;'>
									<?php $urls = array_chunk(is_array($user->external_links) ? $user->external_links : array(), 3); ?>
									<?php for($i = 0, $size= sizeof($urls); $i < $size; $i++){ ?>
										<ul class='user_profile_url_list'>
											<?php for($j = 0, $url_s = sizeof($urls[$i]); $j < $url_s; $j++){ $row = $urls[$i][$j]; ?>
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

								<div class='shrink_about'><?php echo html::a(array('href' => '#', 'text' => 'Show Less', 'class' => 'shrink_user_about'))?></div>
							</div>
						</div>
					</div>
					<div class='user_subscription'>
						<?php if($user->_id != glue::session()->user->_id){
							if($_SESSION['logged']){
								if(Subscription::isSubscribed($user->_id)){ ?>
									<div class='unsubscribe grey_css_button_right'><div>Unsubscribe</div></div>
								<?php }else{ ?>
									<div class='subscribe green_css_button_right'><div>Subscribe</div></div>
								<?php }
							}
						}else{ ?>
							<a href='/user/profile' class='grey_css_button_right'>Edit Profile</a>
						<?php } ?>
						<div class='clearer'></div>
						<div class='subscribers'><span><?php echo $user->total_subscribers ?></span> Subscribers</div>
					</div>
					<div class='clearer'></div>
					<div class='nav_bar'>
						<ul>
							<li><a href='<?php echo glue::url()->create('/user/view', array('id' => strval($user->_id))) ?>' class='<?php if($selected_page == 'stream') echo "selected"; ?>'>Stream</a></li>
							<li><a href='<?php echo glue::url()->create('/user/view_videos', array('id' => strval($user->_id))) ?>' class='<?php if($selected_page == 'videos') echo "selected"; ?>'>Videos</a></li>
							<li><a href='<?php echo glue::url()->create('/user/view_playlists', array('id' => strval($user->_id))) ?>' class='<?php if($selected_page == 'playlists') echo "selected"; ?>'>Playlists</a></li>
						</ul>
						<div class='clearer'></div>
					</div>
				</div>
			</div>
		</div>
		<?php echo $page ?>
		</div>

	    <div class="playlistBottomBar_outer" id="playlist-root"></div>
		<div id="mainSearch_results"></div>
	</body>

</html>