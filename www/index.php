<?php

if(strncmp('@fgfgfhf', '@', 1)){
	echo "poop";
}else{
	echo "whoop";
}
exit();













/** Include the main point of entry */
include_once "../glue/glue.php";

/** Run the framework */
$config=require dirname(__DIR__).'/config/config.php';
glue::run(isset($_GET['url']) ? $_GET['url'] : null,$config);
/** EOF **/