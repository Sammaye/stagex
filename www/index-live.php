<?php
/** Include the main point of entry */
include_once "../glue/glue.php";

/** Run the framework */
$config=require dirname(__DIR__).'/config/config.php';
$configLive=require dirname(__DIR__).'/config/config-live.php';
$config=glue::mergeConfiguration($config,$configLive);
glue::run(isset($_GET['url']) ? $_GET['url'] : null,$config);
/** EOF **/