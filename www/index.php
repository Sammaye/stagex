<?php
/** Include the main point of entry */
include_once "../glue/App.php";

use \glue\app as app;

/** Run the framework */
$config=require ROOT.'/config/config.php';
app::setConfig($config);
app::run(isset($_GET['url']) ? $_GET['url'] : null);
/** EOF **/