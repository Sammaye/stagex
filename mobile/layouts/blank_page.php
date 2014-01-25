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
<?php $this->head(); ?>
</head>
<body>
<?php $this->beginBody() ?>
	<?php echo app\widgets\userMobileMenu::run(array('tab' => $this->tab)) ?>
	<div class="container">
	<?php echo $content ?>
	</div>
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