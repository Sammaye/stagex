<?php
require_once 'sdk.class.php';

class aws_bootstrap extends GApplicationComponent{

	public $key;
	public $secret;
	public $bucket;

	public $input_queue;
	public $output_queue;

	public $aws = array();

	function init(){
		glue::registerAutoloader(array('CFLoader', 'autoloader'));
	}

	function get($obj){
    	if(!isset($this->aws[$obj])){ // This if will cache the aws response
			$this->aws[$obj] = new $obj(array(
				'key' => $this->key,
				'secret' => $this->secret
			));
    	}
		return $this->aws[$obj];
	}

	function s3_upload($file_name, $opt = array(), $bucket = null){

		$s3 = $this->get('AmazonS3');

		$to_bucket = $this->bucket;
		if($bucket){
			$to_bucket = $this->bucket;
		}

		$response = $s3->create_object($to_bucket, $file_name, array_merge($opt, array(
			'acl' => AmazonS3::ACL_PUBLIC,
			'storage' => AmazonS3::STORAGE_REDUCED
		)));

		if($response->isOK()){
			return true;
		}else{
			return false;
		}
	}

	function s3_get_file($file_name){
		$s3 = $this->get('AmazonS3');

		$real_name = trim($file_name);
		if(strlen($real_name) <= 0) $real_name = 'sjdnjflsdkfjklsdfjsdflksdfjdslkfdsklnfsdlvnjnvbsdvnsjvgnsdkljnfvdljsnfd23543534243456534576y4563.penis'; // Something I would never call it

		$response = '';
		if($s3->if_object_exists($this->bucket, $real_name)){
			return $response = $s3->get_object($this->bucket, $file_name);
		}else{
			return null;
		}
	}

	function send_video_encoding_message($file_name, $job_id, $output){
		$sqs = glue::aws()->get('AmazonSQS');
		$response = $sqs->send_message($this->input_queue, json_encode(array(
			'input_file' => $file_name,
			'bucket' => $this->bucket,
			'output_format' => $output,
			'output_queue' => $this->output_queue,
			'job_id' => $job_id
		)));

		if($response->isOK()){
			return true;
		}
		return false;
	}

	function receive_encoding_message(){
		$sqs = glue::aws()->get('AmazonSQS');
		return $sqs->receive_message($this->output_queue, array(
		    'VisibilityTimeout' => 1
		));
	}

	/**
	 * This gets the S3 url without pinging AWS
	 */
	function get_s3_obj_url($filename){
		return 'http://s3.amazonaws.com/'.$this->bucket.'/'.$filename;
	}
}