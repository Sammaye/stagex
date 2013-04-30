<?php
/**
 * This is the cronjob file that will be run on every instance I create.
 *
 * It should be placed within the /home/ec2-user or /home/ubuntu user of every instance you make. The AWS library will be downloaded or installed via
 * cloud init config.
 */
define('ROOT', dirname(__FILE__)); // Should be either /home/ec2-user or /home/ubuntu

const AWS_KEY = 'AKIAICYRUYXAXE3MTUXA'; // Add this to cloud formation as a app part bringing down your config or add a config file
const AWS_SECRET = 'TiSFUTOgBioHTUSU4rZf3/3LmK+14gjV7V6EH85r'; // Same as above
const QUEUE = 'https://us-west-2.queue.amazonaws.com/663341881510/stagex-uploadsQueue'; // Same as above above

echo '[ NEW CRON STARTED ]\n';

function logEvent($message){
	echo '[ '.date('d-m-Y H:i:s').' '.microtime(true).' ] '.$message.'\n';
}

//exec('rm -rf '.ROOT.'/worker');
exec('git clone https://github.com/Sammaye/aws_worker.git '.ROOT.'/worker');

if(!file_exists(ROOT.'/worker/worker_despatcher.php')){
	exit();
}

include_once ROOT.'/worker/worker_despatcher.php';


// {"job_id": 1, "input_file": "4fa54b3ccacf54cb250000d8.divx", "bucket": "videos.stagex.co.uk", "output_format": "mp4", "output_queue": "https://us-west-2.queue.amazonaws.com/663341881510/stagex-outputsQueue"}
// {"job_id": 1, "input_file": "4fa54b3ccacf54cb250000d8.divx", "bucket": "videos.stagex.co.uk", "output_format": "ogv", "output_queue": "https://us-west-2.queue.amazonaws.com/663341881510/stagex-outputsQueue"}
// {"job_id": 1, "input_file": "4fa54b3ccacf54cb250000d8.divx", "bucket": "videos.stagex.co.uk", "output_format": "img", "output_queue": "https://us-west-2.queue.amazonaws.com/663341881510/stagex-outputsQueue"}



//{"job_id":"3e19e2606168f26b23673cf7ed1f4e8d","output_format":"img","input_file":"4ffc96476803faed090002c8.divx","bucket": "videos.stagex.co.uk","output_queue": "https://us-west-2.queue.amazonaws.com/663341881510/stagex-outputsQueue"}