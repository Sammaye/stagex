<?php

/** Include the main point of entry */
include "glue/Glue.php";

/** Run the framework */
$config=require 'config/config.php';
$url = $_SERVER['argv'][1];
unset($_SERVER['argv'][0],$_SERVER['argv'][1]);
glue::run(isset($url) ? $url : null,$config);
/** EOF **/