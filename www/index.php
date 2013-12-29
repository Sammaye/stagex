<?php
/** Include the main point of entry */
include_once "../glue/Glue.php";

/** Run the framework */
$config=require dirname(__DIR__).'/config/config.php';
glue::run(isset($_GET['url']) ? $_GET['url'] : null,$config);
/** EOF **/