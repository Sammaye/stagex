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
		<?php echo app\widgets\userMobileMenu::run() ?>

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