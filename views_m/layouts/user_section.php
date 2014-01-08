<?php

use \glue\Html;

$this->beginPage() ?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo Html::encode($this->title) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
<link href="css/bootstrap.min.css" rel="stylesheet">

<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
<script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
<![endif]-->

<link type="text/css" rel="stylesheet" href="css/mmenu.css" />
<link type="text/css" rel="stylesheet" href="css/main.css" />
<?php 
	$this->head();
?>
</head>
<body>
	<div id="page">
	<?php $this->beginBody() ?>
		<?php //echo app\widgets\Menu::run() ?>
			<div id="header">
				<a href="#menu"></a>
				<?php echo Html::encode($this->title) ?>
			</div>

			<nav id="menu">
				<ul>
					<li class="Selected"><a href="index.html">News Feed</a></li>
					<li><a href="horizontal-submenus.html">Watch Later</a></li>
					<li>
						<a href="vertical-submenus.html">Videos</a>
						<ul>
							<li><a href="#">First sub-item</a></li>
							<li><a href="#">Second sub-item</a></li>
							<li><a href="#">Third sub-item</a></li>
						</ul>
					</li>
					<li><a href="photos.html">Playlists</a></li>
					<li><a href="positions.html">Following</a></li>
					<li><a href="colors.html">Notifications</a></li>
					<li><a href="advanced.html">Profile</a></li>
					<li><a href="onepage.html">Settings</a></li>
					<li><a href="onepage.html">Logout</a></li>
				</ul>
			</nav>

		<?php echo $content ?>
		<div id="mainSearch_results"></div>
	<?php $this->endBody() ?>
	</div>
	<script src="https://code.jquery.com/jquery.js"></script>
	<script type="text/javascript" src="js/mmenu.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script type="text/javascript">
	$(function() {
		$('nav#menu').mmenu();
	});
	</script>	
</body>
</html>
<?php $this->endPage() ?>