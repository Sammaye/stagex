<?php
/** Include the main point of entry */
include_once "../glue/glue.php";

/** Run the framework */
$config=require ROOT.'/config/config.php';
glue::run(isset($_GET['url']) ? $_GET['url'] : null,$config);
/** EOF **/