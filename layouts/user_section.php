<?php

use \glue\Html;

$this->beginPage() ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html xmlns:fb="http://www.facebook.com/2008/fbml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta http-equiv="content-language" content="en"/>

		<link rel="shortcut icon" href="/images/favicon.ico" />

		<title><?php echo Html::encode($this->title) ?></title>

		<?php
			echo Html::jsFile('/js/jquery.js')."\n";
			echo Html::jsFile('/js/jquery-ui.js')."\n";

			echo Html::jsFile("/js/facebox.js")."\n";
			echo Html::jsFile('/js/common.js')."\n";

			echo Html::cssFile("/css/reset.css")."\n";
			echo Html::cssFile("/css/960.css")."\n";
			echo Html::cssFile("/css/main.css")."\n";

			$this->js('ga_script', "var _gaq = _gaq || [];
			  _gaq.push(['_setAccount', 'UA-31049834-1']);
			  _gaq.push(['_trackPageview']);

			  (function() {
			    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		  	})();", self::HEAD);

			$this->js('gplus_one', "(function() {
		    	var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
		    	po.src = 'https://apis.google.com/js/plusone.js';
		    	var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
		  	})();");

			$this->head();
		?>
	</head>
	<body>
		<?php $this->beginBody() ?>
			<?php app\widgets\Menu::widget() ?>
			<div class='grid_970'>
				<div class='grid_block alpha omega user_section_side_menu'>
					<div class='about_user'>
						<img src="<?php echo glue::user()->getPic(40, 40); ?>" alt='thumbnail'/>
						<h3><a href='<?php echo glue::http()->createUrl('/user/view', array('id' => strval(glue::user()->_id))) ?>'><?php echo glue::user()->getUsername() ?></a></h3>
					</div>
					<a href='<?php echo glue::http()->createUrl('/video/upload', array(), glue::$params['uploadBase']) ?>' class='green_css_button upload'>UPLOAD</a>
					<div class='clearer'></div>
					<ul class='main_options'>
						<li><a href='<?php echo glue::http()->createUrl('/stream/news') ?>' <?php echo $this->tab == "news_feed" ? "class='selected'" : "" ?>>News Feed</a></li>
						<li class='wl_item'><a href='<?php echo glue::http()->createUrl('/user/watch_later') ?>' <?php echo $this->tab == "watch_later" ? "class='selected'" : "" ?>>Watch Later</a></li>
						<li><a href='<?php echo glue::http()->createUrl('/user/videos') ?>' <?php echo $this->tab == "videos" ? "class='selected'" : "" ?>>Videos</a></li>
						<li><a href='<?php echo glue::http()->createUrl('/user/playlists') ?>' <?php echo $this->tab == "playlists" ? "class='selected'" : "" ?>>Playlists</a></li>
						<li><a href='<?php echo glue::http()->createUrl('/history/watched') ?>' <?php echo $this->tab == "watched" ? "class='selected'" : "" ?>>Watched</a></li>
						<li><a href='<?php echo glue::http()->createUrl('/history/rated_videos') ?>' <?php echo $this->tab == "likes" ? "class='selected'" : "" ?>>Likes</a></li>
						<?php if($this->tab == "likes"){ ?>
							<li class='indented_row'><a href='<?php echo glue::http()->createUrl('/history/rated_videos') ?>' <?php echo $this->subtab == "liked_videos" ? "class='selected'" : "" ?>>Videos</a></li>
							<li class='indented_row'><a href='<?php echo glue::http()->createUrl('/history/rated_playlists') ?>' <?php echo $this->subtab == "liked_playlists" ? "class='selected'" : "" ?>>Playlists</a></li>
						<?php } ?>
						<li><a href='<?php echo glue::http()->createUrl('/user/subscriptions') ?>' <?php echo $this->tab == "subscriptions" ? "class='selected'" : "" ?>>Subscriptions</a></li>
						<li><a href='<?php echo glue::http()->createUrl('/stream') ?>' <?php echo $this->tab == "stream" ? "class='selected'" : "" ?>>Stream</a></li>
						<li><a href='<?php echo glue::http()->createUrl('/stream/notifications') ?>' <?php echo $this->tab == "notifications" ? "class='selected'" : "" ?>>Notifications</a></li>
					</ul>
					<h3 class='head_divider'>Settings</h3>
					<ul class='account_settings'>
						<li><a href='<?php echo glue::http()->createUrl('/user/settings') ?>' <?php echo $this->tab == "settings" ? "class='selected'" : "" ?>>Account Overview</a></li>
						<li><a href='<?php echo glue::http()->createUrl('/user/profile') ?>' <?php echo $this->tab == "profile" ? "class='selected'" : "" ?>>Profile Settings</a></li>
						<li><a href='<?php echo glue::http()->createUrl('/user/autoshare') ?>' <?php echo $this->tab == "sharing" ? "class='selected'" : "" ?>>Auto-sharing</a></li>
						<li><a href='<?php echo glue::http()->createUrl('/user/uploadpref') ?>' <?php echo $this->tab == "uploadpref" ? "class='selected'" : "" ?>>Upload Preferences</a></li>
						<li><a href='<?php echo glue::http()->createUrl('/user/activity') ?>' <?php echo $this->tab == "activity" ? "class='selected'" : "" ?>>Account Activity</a></li>
					</ul>
					<ul class='main_options'>
						<li><a href='<?php echo glue::http()->createUrl('/user/logout') ?>'>Logout</a></li>
					</ul>
				</div>
				<div class='grid_block alpha omega user_section_main_content'>
					<?php echo $content ?>
				</div>
			</div>
			<div id="mainSearch_results"></div>

		    <div class="playlistBottomBar_outer" id="playlist-root"></div>
			<div id="mainSearch_results"></div>
			<div id="user_video_results"></div>
		<?php $this->endBody() ?>
	</body>
</html>
<?php $this->endPage() ?>