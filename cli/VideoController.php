<?php

//use glue;
use app\models\Video;
use glue\Controller;

include_once glue::getPath('@glue').'/components/phpthumb/ThumbLib.inc.php';

class VideoController extends Controller
{
	public function action_submitEncodingJob()
	{
		$jobs = iterator_to_array(glue::db()->encoding_jobs->find(array('state' => 'pending'))->sort(array('ts' => -1))->limit(100));
		
		$ids = array();
		foreach($jobs as $_id => $job){
			$ids[] = $job['_id'];
		}
		
		glue::db()->encoding_jobs->update(array('_id' => array('$in' => $ids)), array('$set' => array('state' => 'submitting')), array('multiple' => true));

		foreach($jobs as $job){
			
			$this->logEvent('Loaded Job: '.$job['_id']);
		
			$s3_submit = isset($job['s3_submit']) ? $job['s3_submit'] : false;
			$img_submit = isset($job['img_submit']) ? $job['img_submit'] : false;
			$mp4_submit = isset($job['mp4_submit']) ? $job['mp4_submit'] : false;
			$ogv_submit = isset($job['ogv_submit']) ? $job['ogv_submit'] : false;
			
			$updateObj = array();
			
			$file_path = rtrim(sys_get_temp_dir(), '/') . '/' . $job['file_name'];
			
			if(!$s3_submit){
				if(glue::aws()->S3Upload($job['file_name'], array('Body' => fopen($file_path, 'r+')))){
					unlink($file_path); // Free up space in our temp dir
					$updateObj['original'] = glue::aws()->getS3ObjectURL($job['file_name']);
					$s3_submit = true;
				}else{
					$this->logEvent('Failed to upload to S3: '.$job['_id']);
				}
			}
		
			// So lets send the command to SQS now
			if($s3_submit){
				if(!$img_submit){
					$img_submit = glue::aws()->sendEncodingMessage($job['file_name'], $job['_id'], 'img');
				}
				if(!$mp4_submit){
					$mp4_submit = glue::aws()->sendEncodingMessage($job['file_name'], $job['_id'], 'mp4');
				}
				if(!$ogv_submit){
					$ogv_submit = glue::aws()->sendEncodingMessage($job['file_name'], $job['_id'], 'ogv');
				}
			}
		
			if($s3_submit && $img_submit && $mp4_submit && $ogv_submit){
				$state = 'transcoding';
			}else{
				$state = 'pending';
			}
		
			Video::updateAll(
				array('jobId' => $job['_id']), 
				array('$set' => array_merge($updateObj, array('state' => $state)))
			);
		
			glue::db()->encoding_jobs->update(
				array('_id' => $job['_id']), 
				array('$set' => array('s3_submit' => $s3_submit, 'img_submit' => $img_submit, 'mp4_submit' => $mp4_submit,
						'ogv_submit' => $ogv_submit, 'state' => $state))
			);
			
			$this->logEvent('Done Job: '.$job['_id']);
		}		
	}
	
	public function action_consumeEncodingOutput()
	{
		// This function will be run every minute
		
		// If there are no videos awaiting transcoding then don't ping SQS this minute
		//var_dump(glue::db()->video->findOne(array('state' => 'transcoding')));
		if(!glue::db()->video->findOne(array('state' => 'transcoding')))
			glue::end();
		
		$sqs = glue::aws()->get('sqs');
		
		// Otherwise lets do 10 SQS messages
		for($i = 0; $i < 10; $i++){ // Lets do ten messages a minute
			try{
				$resp = glue::aws()->receiveEncodingMessage();
				$messages=$resp->getpath('Messages');
				
				if(!$messages||empty($messages))
					continue;
			}catch(Aws\Sqs\Exception $e){
				continue;
			}

			foreach($messages  as $sqsMessage){
				
				if(!$message=json_decode($sqsMessage['Body']))
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
						echo "Cleaning old Job ".$job['jobId']."\n";
						var_dump($sqsMessage['ReceiptHandle']);
						var_dump(array(
							array('Id' => 1, 'ReceiptHandle' => $job['img_receipt_handle']),
							array('Id' => 2, 'ReceiptHandle' => $job['mp4_receipt_handle']),
							array('Id' => 3, 'ReceiptHandle' => $job['ogg_receipt_handle']),
						));
						$response=$sqs->deleteMessageBatch(array('QueueUrl' => glue::aws()->output_queue, 'Entries' => array(
							array('Id' => 1, 'ReceiptHandle' => $job['img_receipt_handle']),
							array('Id' => 2, 'ReceiptHandle' => $job['mp4_receipt_handle']),
							array('Id' => 3, 'ReceiptHandle' => $job['ogg_receipt_handle']),
						)));
						//var_dump($response->getpath('Successful'));
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
							$thumb = PhpThumbFactory::create((string)$obj->getpath('Body'), array(), true); // This will need some on spot caching soon
							$thumb->adaptiveResize(800, 600);
							$job['image_src'] = new MongoBinData($thumb->getImageAsString(),2);
							$job['img_state'] = 'finished';
						} // Don't we have a problem if it is null???
					}else{
						$job['img_state'] = 'failed';
						$job['errors'][] = 'img: '.$message->reason;
					}
					$job['img_receipt_handle'] = (string)$sqsMessage['ReceiptHandle'];
					echo "Done IMG for ".$job['jobId']."\n";
				}elseif($message->output_format == 'mp4'){
					if($message->success){
						$job['mp4_url'] = $message->url;
						$job['mp4_state'] = 'finished';
					}else{
						$job['mp4_state'] = 'failed';
						$job['errors'][] = 'mp4: '.$message->reason;
					}
					$job['mp4_receipt_handle'] = (string)$sqsMessage['ReceiptHandle'];
					echo "Done mp4 for ".$job['jobId']."\n";
				}elseif($message->output_format == 'ogv'){
					if($message->success){
						$job['ogg_url'] = $message->url;
						$job['ogg_state'] = 'finished';
					}else{
						$job['ogg_state'] = 'failed';
						$job['errors'][] = 'ogg: '.$message->reason;
					}
					$job['ogg_receipt_handle'] = (string)$sqsMessage['ReceiptHandle'];
					
					echo "Done OGV for ".$job['jobId']."\n";
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
					app\models\Video::updateAll(array('jobId' => $message->id), array('$set' => array('state' => 'failed', 'encoding_state_reason' => $job['errors'])));
	
					$response=$sqs->deleteMessageBatch(array('QueueUrl' => glue::aws()->output_queue, 'Entries' => array(
							array('Id' => 1, 'ReceiptHandle' => $job['img_receipt_handle']),
							array('Id' => 2, 'ReceiptHandle' => $job['mp4_receipt_handle']),
							array('Id' => 3, 'ReceiptHandle' => $job['ogg_receipt_handle']),
					)));
					
					$videos = app\models\Video::find(array('jobId' => $message->id));
					foreach($videos as $k => $video){
						if($video->author->emailEncodingResult){
							glue::mailer()->mail(
								$video->author->email, 
								array('no-reply@stagex.co.uk', 'StageX'), 'Your video has failed to encode!', "videos/failed_encoding.php", array(
									"username"=>$video->author->username, "video"=>$video ));
						}
					}
				}elseif($job['ogg_state'] == 'finished' && $job['mp4_state'] == 'finished' && $job['img_state'] == 'finished'){
					$videos = app\models\Video::find(array('jobId' => $message->id));
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
						if($video->author->autoshareUploads)
							app\models\AutoPublishQueue::queue(app\models\AutoPublishQueue::UPLOAD, $video->author->_id, $video->_id);					
						if($video->isPublic())
							glue::sitemap()->addUrl(glue::http()->url('/video/watch', array('id' => $video->_id)), 'hourly', '1.0');						
						if($video->author->emailEncodingResult)
							glue::mailer()->mail($video->author->email, array('no-reply@stagex.co.uk', 'StageX'), 'Your video has finished encoding and is ready for viewing!',
							"videos/finished_encoding.php", array( "username"=>$video->author->username, "video"=>$video ));
					}
					
					$response=$sqs->deleteMessageBatch(array('QueueUrl' => glue::aws()->output_queue, 'Entries' => array(
							array('Id' => 1, 'ReceiptHandle' => $job['img_receipt_handle']),
							array('Id' => 2, 'ReceiptHandle' => $job['mp4_receipt_handle']),
							array('Id' => 3, 'ReceiptHandle' => $job['ogg_receipt_handle']),
					)));					
				}
			}
		}		
	}
}