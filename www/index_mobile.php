<?php
/** Include the main point of entry */
include_once "../glue/Glue.php";

/** Run the framework */
$mainConfig = require dirname(__DIR__) . '/config/config.php';
$config = require dirname(__DIR__) . '/config/mobile.php';

glue::run(isset($_GET['url']) ? $_GET['url'] : null, $mainConfig, $config);
/** EOF **/