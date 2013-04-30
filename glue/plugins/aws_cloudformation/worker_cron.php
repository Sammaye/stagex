<?php
/**
 * This is the cronjob file that will be run on every instance I create.
 *
 * It should be placed within the /home/ec2-user or /home/ubuntu user of every instance you make. The AWS library will be downloaded or installed via
 * cloud init config.
 */
define('ROOT', dirname(__FILE__)); // Should be either /home/ec2-user or /home/ubuntu

const AWS_KEY = ''; // Add this to cloud formation as a app part bringing down your config or add a config file
const AWS_SECRET = ''; // Same as above
const QUEUE = ''; // Same as above above

echo '[ NEW CRON STARTED ]\n';

function logEvent($message){
	echo '[ '.date('d-m-Y H:i:s').' '.microtime(true).' ] '.$message.'\n';
}

exec('git clone https://github.com/Sammaye/aws_worker.git '.ROOT.'/worker');

if(!file_exists(ROOT.'/worker/worker_despatcher.php')){
	logEvent('Could not download GIT');
	exit();
}

logEvent('GIT Downloaded');

include_once ROOT.'/worker/worker_despatcher.php';