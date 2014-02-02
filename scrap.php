<?php

if(!$matched_video||$matched_video->state!='finished'){
	// Lets transfer to S3
	$file_name = new \MongoId().'.'.pathinfo($file->name, PATHINFO_EXTENSION);
	//if(move_uploaded_file($file->tmp_name, $file_name)){

	//unlink($file->tmp_name); // Free up space in our temp dir

	/*
	 * I create and insert a new job here into Mongo. This is the easiest way by far
	* to keep track of encoding over possibly many videos and many outputs and also to keep track of which
	* videos received an AWS cURL error while trying to send messages
	*/
	$job = array('jobId' => md5( uniqid( rand(1,255).rand(45,80).rand(112,350), true ) )); // Pre-pop the job with an id
	$this->updateAll(array('_id' => $this->_id), array('$set' => array(/*'state' => $state, */'jobId' => $job['jobId'])));
	glue::db()->encoding_jobs->insert(array_merge(
	$job,
	array('file_name' => $file_name, /*'img_submit' => $img_submit, 'mp4_submit' => $mp4_submit,
	'ogv_submit' => $ogv_submit,*/ 'state' => $state, 'ts'=>new \MongoDate())
	));

	// technically the video is now there so lets inc the total uploads.
	// glue::user()->saveCounters(array('totalUploads'=>1));
	//}else{

	// FAIL
	//$this->updateAll(array('_id' => $this->_id), array('$set' => array('state' => 'failed')));
	//$this->response("ERROR", "UNKNOWN");
	//return false;
	//}
}