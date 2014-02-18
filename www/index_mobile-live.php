<?php
/** Include the main point of entry */
include_once "../glue/Glue.php";

/** Run the framework */
$config = require dirname(__DIR__) . '/config/config.php';
$configLive = require dirname(__DIR__) . '/config/config-live.php';
$configMobile = require dirname(__DIR__) . '/config/mobile.php';
$configMobileLive = require dirname(__DIR__) . '/config/mobile-live.php';

glue::run(
	isset($_GET['url']) ? $_GET['url'] : null, 
	$config, 
	$configLive, 
	$configMobile, 
	$configMobileLive
);
/** EOF **/