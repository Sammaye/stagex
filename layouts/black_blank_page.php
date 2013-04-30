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
		?>
	</head>

	<body class='all_black'><?php echo $page ?></body>

</html>