<?php

use \glue\Html;

$this->beginPage() ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta http-equiv="content-language" content="en"/>

		<link rel="shortcut icon" href="/images/favicon.ico" />

		<title><?php echo Html::encode($this->title) ?></title>

		<?php
		
		$this->jsFile(array(
			'/js/jquery.js',
			'/js/jquery-ui.js',
			'/js/bootstrap.js',
			'/js/common.js',
		), self::HEAD);
			
		$this->cssFile(array(
			'/css/springhare.css',
			'/css/jquery-ui/jquery-ui.css',
			'/css/main.css'
		));
		
			$this->js('ga_script', "var _gaq = _gaq || [];
			  _gaq.push(['_setAccount', 'UA-31049834-1']);
			  _gaq.push(['_trackPageview']);

			  (function() {
			    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		  	})();", self::HEAD);
			$this->head();
		?>
	</head>
	<body>
		<?php $this->beginBody() ?>
			<?php echo app\widgets\Menu::run(); ?>
			<div class='grid-container userbody'>
				<?php echo app\widgets\UserMenu::run(array('tab'=>$this->tab)) ?>
				<div class='user_section_main_content grid-col-41' >
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