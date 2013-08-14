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
			<?php app\widgets\Menu::widget(); ?>
			<div class='userbody'>
				<div class='side_menu' style='float:left;'>
					<ul>
						<li><a href='<?php echo glue::http()->url('/stream/news') ?>' <?php echo $this->tab == "news_feed" ? "class='selected'" : "" ?>>News Feed</a></li>
						<li class='wl_item'><a href='<?php echo glue::http()->url('/user/watchLater') ?>' <?php echo $this->tab == "watch_later" ? "class='selected'" : "" ?>>Watch Later</a></li>
						<li><a href='<?php echo glue::http()->url('/user/videos') ?>' <?php echo $this->tab == "videos" ? "class='selected'" : "" ?>>Videos
						<span class="badge"><?php echo glue::user()->totalUploads ?></span></a></li>
						<li><a href='<?php echo glue::http()->url('/user/playlists') ?>' <?php echo $this->tab == "playlists" ? "class='selected'" : "" ?>>Playlists
						<span class="badge"><?php echo glue::user()->totalPlaylists ?></span></a></li>
						<!-- 
						<li><a href='<?php //echo glue::http()->url('/history/watched') ?>' <?php //echo $this->tab == "watched" ? "class='selected'" : "" ?>>Watched</a></li>
						<li><a href='<?php //echo glue::http()->url('/history/ratedVideos') ?>' <?php //echo $this->tab == "likes" ? "class='selected'" : "" ?>>Likes</a></li>
						-->
						<li><a href='<?php echo glue::http()->url('/user/follwoing') ?>' <?php echo $this->tab == "subscriptions" ? "class='selected'" : "" ?>>Following
						<span class="badge"><?php echo glue::user()->totalFollowing ?></span></a></li>
						<li><a href='<?php echo glue::http()->url('/stream/notifications') ?>' <?php echo $this->tab == "notifications" ? "class='selected'" : "" ?>>Notifications
						<span class="badge"><?php echo \app\models\Notification::getNewCount_Notifications() ?></span></a></li>
						<li><a href='<?php echo glue::http()->url('/stream') ?>' <?php echo $this->tab == "news_feed" ? "class='selected'" : "" ?>>Stream</a></li>
					</ul>
					<h3 class='head_divider'>Settings</h3>
					<ul>
						<li><a href='<?php echo glue::http()->url('/user/settings') ?>' <?php echo $this->tab == "settings" ? "class='selected'" : "" ?>>Account Settings</a></li>
						<li><a href='<?php echo glue::http()->url('/user/profile') ?>' <?php echo $this->tab == "profile" ? "class='selected'" : "" ?>>Profile Settings</a></li>
						<li><a href='<?php echo glue::http()->url('/user/activity') ?>' <?php echo $this->tab == "activity" ? "class='selected'" : "" ?>>Account Activity</a></li>
					</ul>
					<ul class='end_list'>
						<li><a href='<?php echo glue::http()->url('/user/logout') ?>'>Logout</a></li>
					</ul>
				</div>
				<div class='grid_block alpha omega user_section_main_content' style='float:left; width:820px;'>
					<?php echo $content ?>
				</div>
				<div class="clear"></div>
			</div>
			<div id="mainSearch_results"></div>

		    <div class="playlistBottomBar_outer" id="playlist-root"></div>
			<div id="mainSearch_results"></div>
			<div id="user_video_results"></div>
		<?php $this->endBody() ?>
	</body>
</html>
<?php $this->endPage() ?>