<?php

/**
 * Submit to AWS Encoder
 *
 * This script effectively submits any videos which are either failing, been put on weirdly or just dodgy to the
 * AWS encoder for processing. From there it will decide if this video should exist on the site.
 */

$jobs = glue::db()->encoding_jobs->find(array('state' => 'pending'))->sort(array('date_uploaded' => 1))->limit(100); // Do 100 jobs a minute
foreach($jobs as $job){

	$img_submit = true;
	$mp4_submit = true;
	$ogv_submit = true;

	glue::db()->encoding_jobs->update(array('_id' => $job['_id']), array('$set' => array('state' => 'submitting'))); // Optimistic lock to avoid duplication

	// So lets send the command to SQS now
	if(!$job['img_submit']){
		$img_submit = glue::aws()->send_video_encoding_message($job['file_name'], $job['id'], 'img');
	}

	if(!$job['mp4_submit']){
		$mp4_submit = glue::aws()->send_video_encoding_message($job['file_name'], $job['id'], 'mp4');
	}

	if(!$job['ogv_submit']){
		$ogv_submit = glue::aws()->send_video_encoding_message($job['file_name'], $job['id'], 'ogv');
	}

	if($img_submit && $mp4_submit && $ogv_submit){
		$state = 'transcoding';
	}else{
		$state = 'pending';
	}

	glue::db()->videos->update(array('job_id' => $job['id']), array('$set' => array('state' => $state)));

	glue::db()->encoding_jobs->update(array('id' => $job['id']), array('$set' => array('img_submit' => $img_submit, 'mp4_submit' => $mp4_submit,
			'ogv_submit' => $ogv_submit, 'state' => $state)));
}