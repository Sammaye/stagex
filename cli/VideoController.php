<?php

include_once ROOT.'/glue/plugins/phpthumb/ThumbLib.inc.php';

class VideoController extends \glue\Controller{
	
	function action_submitEncodingJob(){
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
	}
	
	function action_consumeEncodingOutput(){
		for($i = 0; $i < 10; $i++){ // Lets do ten messages a minute
		
			if(!glue::db()->videos->findOne(array('state' => 'transcoding'))){ // If there are no videos awaiting transcoding then don't ping SQS
				continue;
			}
		
			$sqs_message = glue::aws()->receive_encoding_message();
			if(!isset($sqs_message->body->ReceiveMessageResult->Message)){
				continue; // Nothing to process, loop back around
			}
		
			$message = json_decode($sqs_message->body->ReceiveMessageResult->Message->Body);
			if(!$message || empty($message)){
				continue; // Empty message sent to us, how rude!!
			}
		
			$job = glue::db()->encoding_jobs->findOne(array('job_id' => $message->id));
			if(!$job){
				$job = array(
						'job_id' => $message->id,
						'complete' => false
				); // If there isn't a job lets init one
			}
		
			if(isset($job['complete'])){
				/*
				 * We have a rogue message on the loose so let's double check they are all deleted
				*/
				if($job['complete']){
					$sqs = glue::aws()->get('AmazonSQS');
		
					//var_dump($job['img_receipt_handle']);
					//var_dump($job['mp4_receipt_handle']);
					//var_dump($job['ogg_receipt_handle']);
		
					$response = $sqs->delete_message(glue::aws()->output_queue,  $job['img_receipt_handle']);
					//var_dump($response->isOK());
					$sqs->delete_message(glue::aws()->output_queue,  $job['mp4_receipt_handle']);
					$sqs->delete_message(glue::aws()->output_queue,  $job['ogg_receipt_handle']);
					continue;
				}
			}else{
				$job['complete'] = false;
			}
		
			if($message->output_format == 'img'){
				if($message->success){
					$job['image'] = $message->url;
		
					$obj = glue::aws()->s3_get_file(pathinfo($job['image'], PATHINFO_BASENAME));
					if($obj != null){
						$bytes = $obj->body;
		
						$thumb = PhpThumbFactory::create($bytes, array(), true); // This will need some on spot caching soon
						$thumb->adaptiveResize(800, 600);
						$job['image_src'] = new MongoBinData($thumb->getImageAsString());
						$job['img_state'] = 'finished';
					} // Don't we have a problem if it is null???
				}else{
					$job['img_state'] = 'failed';
					$job['errors'][] = 'img: '.$message->reason;
				}
				$job['img_receipt_handle'] = (string)$sqs_message->body->ReceiveMessageResult->Message->ReceiptHandle;
			}elseif($message->output_format == 'mp4'){
				if($message->success){
					$job['mp4_url'] = $message->url;
					$job['mp4_state'] = 'finished';
				}else{
					$job['mp4_state'] = 'failed';
					$job['errors'][] = 'mp4: '.$message->reason;
				}
				$job['mp4_receipt_handle'] = (string)$sqs_message->body->ReceiveMessageResult->Message->ReceiptHandle;
			}elseif($message->output_format == 'ogv'){
				if($message->success){
					$job['ogg_url'] = $message->url;
					$job['ogg_state'] = 'finished';
				}else{
					$job['ogg_state'] = 'failed';
					$job['errors'][] = 'ogg: '.$message->reason;
				}
				$job['ogg_receipt_handle'] = (string)$sqs_message->body->ReceiveMessageResult->Message->ReceiptHandle;
			}
		
			if($message->success) // Lets add the duration
				$job['duration_ts'] = $message->duration;
		
			if(isset($job['ogg_state']) && isset($job['mp4_state']) && isset($job['img_state'])){
				$job['complete'] = true;
				$job['complete_time'] = time();
			}
		
			glue::db()->encoding_jobs->save($job);
		
			if(isset($job['ogg_state']) && isset($job['mp4_state']) && isset($job['img_state'])){
				if($job['ogg_state'] == 'failed' || $job['mp4_state'] == 'failed' || $job['img_state'] == 'failed'){
					glue::db()->videos->update(array('job_id' => $message->id), array('$set' => array('state' => 'failed', 'encoding_state_reason' => $job['errors'])));
		
					$sqs = glue::aws()->get('AmazonSQS');
					$sqs->delete_message(glue::aws()->output_queue,  $job['img_receipt_handle']);
					$sqs->delete_message(glue::aws()->output_queue,  $job['mp4_receipt_handle']);
					$sqs->delete_message(glue::aws()->output_queue,  $job['ogg_receipt_handle']);
		
					$videos = Video::model()->find(array('job_id' => $message->id));
					foreach($videos as $k => $video){
						if($video->author->email_encoding_result){
							glue::mailer()->mail($video->author->email, array('no-reply@stagex.co.uk', 'StageX'), 'Your video has failed to encode!', "videos/failed_encoding.php", array(
							"username"=>$video->author->username, "video"=>$video ));
						}
					}
				}elseif($job['ogg_state'] == 'finished' && $job['mp4_state'] == 'finished' && $job['img_state'] == 'finished'){
					$videos = Video::model()->find(array('job_id' => $message->id));
					foreach($videos as $k => $video){
		
						$video->setScenario('process_encoding');
		
						$video->duration = $job['duration_ts'];
						$video->ogg = $job['ogg_url'];
						$video->mp4 = $job['mp4_url'];
						$video->image = $job['image'];
						$video->state = 'finished';
						$video->image_src = $job['image_src'];
		
						$thumb = PhpThumbFactory::create($job['image_src']->bin, array(), true); // This will need some on spot caching soon
						$thumb->adaptiveResize(124, 69);
						glue::db()->image_cache->update(array('object_id' => $video->_id, 'width' => 124, 'height' => 69, 'type' => 'video'),
						array('$set' => array('data' => new MongoBinData($thumb->getImageAsString()),
						)), array('upsert' => true));
		
						$thumb = PhpThumbFactory::create($job['image_src']->bin, array(), true); // This will need some on spot caching soon
						$thumb->adaptiveResize(138, 77);
						glue::db()->image_cache->update(array('object_id' => $video->_id, 'width' => 138, 'height' => 77, 'type' => 'video'),
						array('$set' => array('data' => new MongoBinData($thumb->getImageAsString()),
						)), array('upsert' => true));
		
						$thumb = PhpThumbFactory::create($job['image_src']->bin, array(), true); // This will need some on spot caching soon
						$thumb->adaptiveResize(234, 130);
						glue::db()->image_cache->update(array('object_id' => $video->_id, 'width' => 234, 'height' => 130, 'type' => 'video'),
						array('$set' => array('data' => new MongoBinData($thumb->getImageAsString()),
						)), array('upsert' => true));
		
						$user = User::model()->findOne(array('_id' => $video->user_id));
						Stream::videoUpload($user->_id, $video->_id);
						if($user->should_autoshare('upload')){
							AutoPublishStream::add_to_qeue(AutoPublishStream::UPLOAD, $user->_id, $video->_id);
						}
		
						$user->total_uploads = $user->total_uploads+1;
						$user->save();
		
						glue::mysql()->query("INSERT INTO documents (_id, uid, listing, title, description, category, tags, author_name, duration, views, rating, type, adult, date_uploaded)
							VALUES (:_id, :uid, :listing, :title, :description, :cat, :tags, :author_name, :duration_ts, :views, :rating, :type, :adult, now())", array(
									":_id" => strval($video->_id),
									":uid" => strval($video->user_id),
									":listing" => $video->listing,
									":title" => $video->title,
									":description" => $video->description,
									":cat" => $video->category,
									":tags" => $video->string_tags,
									":duration_ts" => $video->duration,
									":rating" => $video->likes - $video->dislikes,
									":views" => $video->views,
									":type" => "video",
									":adult" => $video->adult_content,
									":author_name" => $video->author->username,
						));
		
						if($video->listing == 1){
							glue::sitemap()->addUrl(glue::url()->create('/video/watch', array('id' => $video->_id)), 'hourly', '1.0');
						}
		
						$video->save();
		
						if($video->author->email_encoding_result){
							glue::mailer()->mail($video->author->email, array('no-reply@stagex.co.uk', 'StageX'), 'Your video has finished encoding and is ready for viewing!',
							"videos/finished_encoding.php", array( "username"=>$video->author->username, "video"=>$video ));
						}
					}
		
					$sqs = glue::aws()->get('AmazonSQS');
					//var_dump($job['img_receipt_handle']);
					//var_dump($job['mp4_receipt_handle']);
					//var_dump($job['ogg_receipt_handle']);
		
					$response = $sqs->delete_message(glue::aws()->output_queue,  $job['img_receipt_handle']);
					//var_dump($response->isOK());
		
					$response = $sqs->delete_message(glue::aws()->output_queue,  $job['mp4_receipt_handle']);
					//var_dump($response->isOK());
		
					$response = $sqs->delete_message(glue::aws()->output_queue,  $job['ogg_receipt_handle']);
					//var_dump($response->isOK());
				}
			}
		}		
	}
	
}