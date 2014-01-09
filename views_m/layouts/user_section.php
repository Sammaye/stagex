<?php

use \glue\Html;

$this->beginPage() ?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo Html::encode($this->title) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
<link href="/css/bootstrap.min.css" rel="stylesheet">

<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
<script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
<![endif]-->

<link type="text/css" rel="stylesheet" href="/css/mmenu.css" />
<link type="text/css" rel="stylesheet" href="/css/main.css" />
<?php 
	$this->head();
?>
</head>
<body>
	<div id="page">
	<?php $this->beginBody() ?>
		<div id="header">
			<a href="#menu"></a>
			<?php echo Html::encode($this->title) ?>
		</div>

		<nav id="menu">
			<ul>
				<li class="Selected"><a href="<?php echo glue::http()->url('/stream/news') ?>">News Feed</a></li>
				<li><a href="<?php echo glue::http()->url('/user/watchLater') ?>">Watch Later</a></li>
				<li>
					<a href="<?php echo glue::http()->url('/user/videos') ?>">Videos</a>
					<ul>
						<li><a href="<?php echo glue::http()->url('/user/videos') ?>">Uploads</a></li>
						<li><a href="<?php echo glue::http()->url('/user/watched') ?>">Watched</a></li>
						<li><a href="<?php echo glue::http()->url('/user/rated') ?>">Liked</a></li>
						<li><a href="<?php echo glue::http()->url('/user/rated?tab=dislikes') ?>">Disliked</a></li>
					</ul>
				</li>
				<li><a href="<?php echo glue::http()->url('/user/playlists') ?>">Playlists</a>
					<ul>
						<li><a href="<?php echo glue::http()->url('/user/playlists') ?>">My Playlists</a></li>
						<li><a href="<?php echo glue::http()->url('/user/playlistSubscriptions') ?>">Subscribed</a></li>
					</ul>				
				</li>
				<li><a href="<?php echo glue::http()->url('/user/following') ?>">Following</a></li>
				<li><a href="<?php echo glue::http()->url('/stream/notifications') ?>">Notifications</a></li>
				<li><a href="<?php echo glue::http()->url('/user/view') ?>">Profile</a></li>
				<li><a href="<?php echo glue::http()->url('/user/settings') ?>">Settings</a>
					<ul>
						<li><a href="<?php echo glue::http()->url('/user/settings') ?>">Account Settings</a></li>
						<li><a href="<?php echo glue::http()->url('/user/profile') ?>">Profile Settings</a></li>
						<li><a href="<?php echo glue::http()->url('/user/activity') ?>">Account Acivity</a></li>
					</ul>				
				</li>
				<li><a href="<?php echo glue::http()->url('/help') ?>">Help</a></li>
				<li><a href="<?php echo glue::http()->url('/user/logout') ?>">Logout</a></li>
			</ul>
		</nav>

		<div class="row" style='padding:20px;'>
		<div class="col-md-12">
		<?php echo $content ?>
		</div>
		</div>
		<div id="mainSearch_results"></div>
		<script src="https://code.jquery.com/jquery.js"></script>
		<script type="text/javascript" src="/js/mmenu.js"></script>
		<script src="/js/bootstrap.min.js"></script>
		<script type="text/javascript">
		$(function() {
			$('nav#menu').mmenu();
		});
		</script>			
	<?php $this->endBody() ?>
	</div>

</body>
</html>
<?php $this->endPage() ?>