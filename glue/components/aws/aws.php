<?php

namespace glue\components\aws;

require_once 'aws.phar';

use Aws\Common\Aws,
	Aws\Common\Enum\Region, 
	Aws\S3\S3Client;

class aws extends \glue\Component{

	public $key;
	public $secret;
	public $bucket;

	public $input_queue;
	public $output_queue;

	public $aws;

	function init(){
		//glue::registerAutoloader(array('CFLoader', 'autoloader'));
	}

	function get($obj){
		if($this->aws===null){
			$this->aws=Aws::factory(array(
				'key' => $this->key,
				'secret' => $this->secret						
			));
		}
		return $this->aws->get($obj);
//     	if(!isset($this->aws[$obj])){ // This if will cache the aws response
// 			$this->aws[$obj] = $obj::factory(array(
// 				'key' => $this->key,
// 				'secret' => $this->secret
// 			));
//     	}
// 		return $this->aws[$obj];
	}

	function S3Upload($filename,$opt = array()){

		$s3 = $this->get('S3Client');
		$response=$s3->putObject(array_merge(array(
			'Bucket' => $this->bucket,
			'Key' => $filename,
			'Body' => '',
			'ACL' => 'public-read',
			'StorageClass' => 'REDUCED_REDUNDANCY'		
		), $opt));

		if($response instanceof \Guzzle\Service\Resource\Model && strlen($response['ObjectURL'])>0)
			return true;
		}else{
			return false;
		}
	}

	function S3GetObject($file_name){
		$s3 = $this->get('AmazonS3');

		$real_name = trim($file_name);
		if(strlen($real_name) <= 0) 
			$real_name = new \MongoId() . md5('something_really_random') . rand() . microtime() . '.penis'; // Something I would never call it

		$response = '';
		if($s3->doesObjectExist($this->bucket, $real_name)){
			return $response = $s3->getObject(array(
				'Bucket' => $this->bucket,
				'key' => $real_name	
			));
		}else{
			return null;
		}
	}

	function sendEncodingMessage($file_name, $job_id, $output){
		$sqs = $this->get('sqs');
		$response = $sqs->sendMessage(array(
			'QueueUrl' => $this->input_queue, 
			'MessageBody' => json_encode(array(
				'input_file' => $file_name,
				'bucket' => $this->bucket,
				'output_format' => $output,	
				'output_queue' => $this->output_queue,
				'job_id' => $job_id
			)
		));

		if($response instanceof \Guzzle\Service\Resource\Model && strlen($response['MessageId'])>0)
			return true;
		}
		return false;
	}

	function receiveEncodingMessage(){
		$sqs = $this->get('sqs');
		return $sqs->receiveMessage(array(
			'QueueUrl' => $this->output_queue
		    'VisibilityTimeout' => 1
		));
	}

	/**
	 * This gets the S3 url without pinging AWS
	 */
	function getS3ObjectURL($filename){
		return 'http://s3.amazonaws.com/'.$this->bucket.'/'.$filename;
	}
}