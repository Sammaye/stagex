<?php

include_once glue::getPath('@glue').'/components/phpthumb/ThumbLib.inc.php';

class VideoController extends \glue\Controller{
	
	function action_submitEncodingJob(){
		$jobs = iterator_to_array(glue::db()->encoding_jobs->find(array('state' => 'pending'))->sort(array('ts' => -1))->limit(100));
		
		$ids=array();
		foreach($jobs as $_id => $job)
			$ids=$job['_id'];
		glue::db()->encoding_jobs->update(array('_id' => array('$in'=>$ids)),array('$set' => array('state' => 'submitting')));
		
		foreach($jobs as $job){
		
			$img_submit = true;
			$mp4_submit = true;
			$ogv_submit = true;
		
			// So lets send the command to SQS now
			if(!$job['img_submit'])
				$img_submit = glue::aws()->sendEncodingMessage($job['file_name'], $job['jobId'], 'img');
			if(!$job['mp4_submit'])
				$mp4_submit = glue::aws()->sendEncodingMessage($job['file_name'], $job['jobId'], 'mp4');
			if(!$job['ogv_submit'])
				$ogv_submit = glue::aws()->sendEncodingMessage($job['file_name'], $job['jobId'], 'ogv');
		
			if($img_submit && $mp4_submit && $ogv_submit){
				$state = 'transcoding';
			}else{
				$state = 'pending';
			}
		
			app\models\Video::model()->updateAll(array('jobId' => $job['jobId']), array('$set' => array('state' => $state)));
		
			glue::db()->encoding_jobs->update(array('_id' => $job['_id']), array('$set' => array('img_submit' => $img_submit, 'mp4_submit' => $mp4_submit,
			'ogv_submit' => $ogv_submit, 'state' => $state)));
		}		
	}
	
	function action_consumeEncodingOutput(){
		
		// This function will be run every minute
		
		// If there are no videos awaiting transcoding then don't ping SQS this minute
		if(!glue::db()->videos->findOne(array('state' => 'transcoding')))
			glue::end();
		
		$sqs = glue::aws()->get('sqs');
		
		// Otherwise lets do 10 SQS messages
		for($i = 0; $i < 10; $i++){ // Lets do ten messages a minute
		
			try{
				$resp = glue::aws()->receiveEncodingMessage();
				if(!$resp['messages']||empty($resp['messages']))
					continue;
			}catch(Aws\Sqs\Exception $e){
				continue;
			}
			
			foreach($resp['messages']  as $sqsMessage){
				
				if(!$message=json_decode($sqsMessage['body']))
					continue; // Empty message sent to us, how rude!!
				
				$job = glue::db()->encoding_jobs->findOne(array('jobId' => $message->id));
				if(!$job)
					$job = array(
						'jobId' => $message->id,
						'complete' => false
					); // If there isn't a job lets init one
				
				if(isset($job['complete'])){
					/*
					 * We have a rogue message on the loose so let's double check they are all deleted
					*/
					if($job['complete']){
						//var_dump($job['img_receipt_handle']);
						//var_dump($job['mp4_receipt_handle']);
						//var_dump($job['ogg_receipt_handle']);
				
						$response = $sqs->deleteMessage(array('QueueUrl' => glue::aws()->output_queue, 'ReceiptHandle'=>$job['img_receipt_handle']));
						$sqs->deleteMessage(array('QueueUrl' => glue::aws()->output_queue, 'ReceiptHandle'=>$job['mp4_receipt_handle']));
						$sqs->deleteMessage(array('QueueUrl' => glue::aws()->output_queue, 'ReceiptHandle'=>$job['ogg_receipt_handle']));
						//var_dump($response->isOK());						
						continue;
					}
				}else{
					$job['complete'] = false;
				}				
				
				if($message->output_format == 'img'){
					if($message->success){
						$job['image'] = $message->url;
			
						$obj = glue::aws()->S3GetObject(pathinfo($job['image'], PATHINFO_BASENAME));
						if($obj != null){
							$thumb = PhpThumbFactory::create($obj->body, array(), true); // This will need some on spot caching soon
							$thumb->adaptiveResize(800, 600);
							$job['image_src'] = new MongoBinData($thumb->getImageAsString());
							$job['img_state'] = 'finished';
						} // Don't we have a problem if it is null???
					}else{
						$job['img_state'] = 'failed';
						$job['errors'][] = 'img: '.$message->reason;
					}
					$job['img_receipt_handle'] = (string)$sqsMessage->ReceiptHandle;
				}elseif($message->output_format == 'mp4'){
					if($message->success){
						$job['mp4_url'] = $message->url;
						$job['mp4_state'] = 'finished';
					}else{
						$job['mp4_state'] = 'failed';
						$job['errors'][] = 'mp4: '.$message->reason;
					}
					$job['mp4_receipt_handle'] = (string)$sqsMessage->ReceiptHandle;
				}elseif($message->output_format == 'ogv'){
					if($message->success){
						$job['ogg_url'] = $message->url;
						$job['ogg_state'] = 'finished';
					}else{
						$job['ogg_state'] = 'failed';
						$job['errors'][] = 'ogg: '.$message->reason;
					}
					$job['ogg_receipt_handle'] = (string)$sqsMessage->ReceiptHandle;
				}
				if($message->success) // Lets add the duration
					$job['duration'] = $message->duration;
			}
		
			if(isset($job['ogg_state']) && isset($job['mp4_state']) && isset($job['img_state'])){
				$job['complete'] = true;
				$job['complete_time'] = time();
			}
			glue::db()->encoding_jobs->save($job);
		
			if(isset($job['ogg_state']) && isset($job['mp4_state']) && isset($job['img_state'])){
				if($job['ogg_state'] == 'failed' || $job['mp4_state'] == 'failed' || $job['img_state'] == 'failed'){
					app\models\Video::model()->updateAll(array('jobId' => $message->id), array('$set' => array('state' => 'failed', 'encoding_state_reason' => $job['errors'])));
		
					$response = $sqs->deleteMessage(array('QueueUrl'=>glue::aws()->output_queue,  'ReceiptHandle'=>$job['img_receipt_handle']));
					$response = $sqs->deleteMessage(array('QueueUrl'=>glue::aws()->output_queue,  'ReceiptHandle'=>$job['mp4_receipt_handle']));
					$response = $sqs->deleteMessage(array('QueueUrl'=>glue::aws()->output_queue,  'ReceiptHandle'=>$job['ogg_receipt_handle']));					
		
					$videos = app\models\Video::model()->find(array('jobId' => $message->id));
					foreach($videos as $k => $video){
						if($video->author->emailEncodingResult){
							glue::mailer()->mail(
								$video->author->email, 
								array('no-reply@stagex.co.uk', 'StageX'), 'Your video has failed to encode!', "videos/failed_encoding.php", array(
									"username"=>$video->author->username, "video"=>$video ));
						}
					}
				}elseif($job['ogg_state'] == 'finished' && $job['mp4_state'] == 'finished' && $job['img_state'] == 'finished'){
					$videos = app\models\Video::model()->find(array('jobId' => $message->id));
					foreach($videos as $k => $video){
		
						$video->duration = $job['duration'];
						$video->ogg = $job['ogg_url'];
						$video->mp4 = $job['mp4_url'];
						$video->image = $job['image'];
						$video->state = 'finished';
						//$video->imageSrc = $job['image_src']; // technically I should not need this but I will leave it commented out atm
						$video->setImage($job['image_src']->bin);
						$video->save();

						// Send it all over the internet!!
						app\models\Stream::videoUpload($video->author->_id, $video->_id);
						if($user->autoshareUploads)
							app\models\AutoPublishQueue::add_to_qeue(app\models\AutoPublishQueue::UPLOAD, $video->author->_id, $video->_id);					
						if($video->isPublic())
							glue::sitemap()->addUrl(glue::http()->url('/video/watch', array('id' => $video->_id)), 'hourly', '1.0');						
						if($video->author->emailEncodingResult)
							glue::mailer()->mail($video->author->email, array('no-reply@stagex.co.uk', 'StageX'), 'Your video has finished encoding and is ready for viewing!',
							"videos/finished_encoding.php", array( "username"=>$video->author->username, "video"=>$video ));
					}
					//var_dump($job['img_receipt_handle']);
					//var_dump($job['mp4_receipt_handle']);
					//var_dump($job['ogg_receipt_handle']);
					$response = $sqs->deleteMessage(array('QueueUrl'=>glue::aws()->output_queue,  'ReceiptHandle'=>$job['img_receipt_handle']));
					$response = $sqs->deleteMessage(array('QueueUrl'=>glue::aws()->output_queue,  'ReceiptHandle'=>$job['mp4_receipt_handle']));
					$response = $sqs->deleteMessage(array('QueueUrl'=>glue::aws()->output_queue,  'ReceiptHandle'=>$job['ogg_receipt_handle']));
					//var_dump($response->isOK());
				}
			}
		}		
	}
}