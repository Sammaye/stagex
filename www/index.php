<?php
/**
 * DOCUMENT ROOT Constant
 *
 * Defines the root of the website.
 * This saves us from having to use insecure header
 * variables to understand where the root is.
 */
define('ROOT', dirname(__FILE__).'/..');

/** Include the main point of entry */
include_once ROOT."/glue/App.php";
include_once ROOT."/glue/Globals.php";

use \glue\app as app;

/** Run the framework */
app::setConfig(ROOT.'application/core/config.php');
app::run(isset($_GET['url']) ? $_GET['url'] : null);
/** EOF **/