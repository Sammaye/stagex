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

			$ca_js = "var clicky_site_ids = clicky_site_ids || [];
				clicky_site_ids.push(0);";

			if(strlen($model->author->clicky_uid) > 0){
				$ca_js .= "clicky_site_ids.push(".$model->author->clicky_uid.");";
			}

			$ca_js .= "(function() {
				  var s = document.createElement('script');
				  s.type = 'text/javascript';
				  s.async = true;
				  s.src = '//innovectra.re.getclicky.com/js';
				  ( document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0] ).appendChild( s );
			})();";

			glue::clientScript()->addJsScript('ca_script', $ca_js, GClientScript::HEAD);
		?>
	</head>

	<body>
		<?php $this->widget('application/widgets/presenceBar.php') ?>
		<?php echo $page ?>
		<div id="mainSearch_results"></div>
	</body>
</html>