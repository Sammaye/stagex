<?php

use \glue\Html;

$this->beginPage() ?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo Html::encode($this->title) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
<link href="/css/bootstrap.min.css" rel="stylesheet">
<link type="text/css" rel="stylesheet" href="/css/mmenu.css" />
<link type="text/css" rel="stylesheet" href="/css/mobile.css" />
<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
<script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
<![endif]-->
<?php 
$this->js('ga_script', "var _gaq = _gaq || [];
	_gaq.push(['_setAccount', 'UA-31049834-1']);
	_gaq.push(['_trackPageview']);

	(function() {
	var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
})();",self::HEAD);

$ca_js = "var clicky_site_ids = clicky_site_ids || []; clicky_site_ids.push(0);";
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
	<?php echo app\widgets\userMobileMenu::run(array('tab' => $this->tab)) ?>
	<?php echo $content ?>
	<div id="mainSearch_results"></div>
	<script src="/js/jquery.js"></script>
	<script type="text/javascript" src="/js/jquery-ui.min.js"></script>
	<script type="text/javascript" src="/js/mmenu.js"></script>
	<script src="/js/mobile/bootstrap.min.js"></script>
	<script type="text/javascript" src="/js/common.js"></script>
	<script type="text/javascript">
	$(function() {
		$('nav#menu').mmenu();
	});
	</script>			
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>