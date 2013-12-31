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
			
			echo Html::jsFile('/js/bootstrap.js')."\n";
			echo Html::jsFile('/js/common.js')."\n";

			echo Html::cssFile("/css/bootstrap.css")."\n";
			echo Html::cssFile("/css/main.css")."\n";
			
			$this->js('ga_script', "var _gaq = _gaq || [];
			  _gaq.push(['_setAccount', 'UA-31049834-1']);
			  _gaq.push(['_trackPageview']);
			
			  (function() {
			    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		  	})();",self::HEAD);
			
			$ca_js = "var clicky_site_ids = clicky_site_ids || [];
				clicky_site_ids.push(0);";
			if(strlen($model->author->clickyUid) > 0)
				$ca_js .= "clicky_site_ids.push(".$model->author->clickyUid.");";
			$this->js('ca_script', $ca_js . "(function() {
				  var s = document.createElement('script');
				  s.type = 'text/javascript';
				  s.async = true;
				  s.src = '//innovectra.re.getclicky.com/js';
				  ( document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0] ).appendChild( s );
			})();", self::HEAD);		
				
			$this->head();
		?>
	</head>
	<body>
		<?php $this->beginBody() ?>
			<?php app\widgets\Menu::run() ?>
			<?php echo $content ?>
			<div id="mainSearch_results"></div>
		<?php $this->endBody() ?>
	</body>
</html>
<?php $this->endPage() ?>