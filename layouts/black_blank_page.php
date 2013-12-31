<?php

use \glue\Html;

$this->beginPage() ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta http-equiv="content-language" content="en"/>

		<link rel="shortcut icon" href="/images/favicon.ico" />
		<title><?php echo $this->pageTitle ?></title>

		<?php
		$this->jsFile(array(
			'/js/jquery.js',
			'/js/jquery-ui.js',
			'/js/bootstrap.js',
			'/js/common.js',
		), self::HEAD);
			
		$this->cssFile(array(
			'/css/bootstrap.css',
			'/css/jquery-ui/jquery-ui.css',
			'/css/main.css'
		));

		$this->head();
		?>
	</head>

	<body class='all_black'>
	<?php $this->beginBody();
		echo $content;
	$this->endBody() ?>
	</body>
</html>
<?php $this->endPage() ?>