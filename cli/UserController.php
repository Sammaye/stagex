<?php

use \glue\Controller;
use app\models\AutoPublishQueue;
use app\models\User;
use app\models\Video;
use app\models\Playlist;

class UserController extends Controller
{
	function action_publishStream()
	{
		// Get all jobs which are in progress or done
		$cursor=AutoPublishQueue::model()->findAll(array('processing' => 0, 'done' => 0))->sort(array('ts' => -1))->limit(100);
		
		// Get a list of _ids of these docs to be able to easily update them
		$_ids = array();
		foreach($cursor as $k => $v)
			$_ids[] = new MongoId($k);
		$cursor = iterator_to_array($cursor);
		
		//print count($cursor); // DEBUG
		
		// Set them to processing so future crons don't try and share twice
		// The idea for this is that the cronjob runs once every 3 mins but it will take long than 3 mins for the cronjob to finish
		// so I reserve the docs are are being processed by one thread so another thread won't try and take them and create duplicates or an infinite loop.
		$c = AutoPublishQueue::model()->updateAll(array('_id' => array('$in' => $_ids)), array('$set' => array('processing' => 1)), array('multiple' => true));
		
		//print count($cursor); // DEBUG
		
		// Now lets iterate through the documents finding out what kind of action needs auto publishing to networks
		// The list of possible values can be found in AutoPublishQueue model
		foreach($cursor as $k => $v){
		
			// I have used different fields for each entity to be able to
			// query them like this and then understand below if I have the required information to complete a task.
			// Saves me having to copy and paste the query for each case
			$user = User::model()->findOne(array('_id' => $v['userId']));
			$video = Video::model()->findOne(array('_id' => $v['videoId']));
			$playlist = Playlist::model()->findOne(array('_id' => $v['playlistId']));
		
			// Now since we have no Cookies here we need to set the facebook API to use a pre-defined access token from the DB
			$facebook = glue::facebook();
			if($user){ $facebook->facebook->setAccessToken($user->autoshareFb); }
		
			// Lets use a copy of the twitter object to save potential memory writing problems of variables here
			$twitter = glue::twitter();
			$twitter->connect(array("access_token"=>$user->autoshareTwitter));
		
			// Now lets do the meaty stuff
			switch(true){
				case ($v['type']==AutoPublishQueue::UPLOAD):
					if($user && $video){
						$facebook->update_status( // This liks to a function I made to update the users status, it does not assume the user has given permission
							$video->title,
							'Uploaded a new video to StageX',
							glue::http()->url('/video/watch', array('id' => $video->_id)),
							strlen($video->description) > 0 ? $video->description : '',
							str_replace('http://', '', $video->getImage(124,69))
						);
						$twitter->update_status('uploaded '.glue::http()->url('/video/watch', array('id' =>  $video->_id)).' to #StageX');
					}
					break;
				case ($v['type']==AutoPublishQueue::V_RES):
					if($user && $video && isset($v['text'])){
						$facebook->update_status(
							$video->title,
							'Posted a new video response on StageX',
							glue::http()->url('/video/watch', array('id' => $video->_id)),
							$v['text'],
							str_replace('http://', '', $video->getImage(124,69))
						);
						$twitter->update_status('Posted a new response on a video '.glue::http()->url('/video/watch', array('id' => $video->_id)).' #StageX');
					}
					break;
				case ($v['type']==AutoPublishQueue::LK_V):
					if($video && $user){
						$facebook->update_status(
							$video->title,
							'Liked a video on StageX',
							glue::http()->url('/video/watch', array('id' => $video->_id)),
							strlen($video->description) > 0 ? $video->description : '',
							str_replace('http://', '', $video->getImage(124,69))
						);
						$twitter->update_status('Liked '.glue::http()->url('/video/watch', array('id' =>  $video->_id)).' on #StageX');
					}
					break;
				case ($v['type']==AutoPublishQueue::DL_V):
					if($user && $video){
						$facebook->update_status(
							$video->title,
							'Disliked a video on StageX',
							glue::http()->url('/video/watch', array('id' => $video->_id)),
							strlen($video->description) > 0 ? $video->description : '',
							str_replace('http://', '', $video->getImage(124,69))
						);
						$twitter->update_status('Disliked '.glue::http()->url('/video/watch', array('id' =>  $video->_id)).' on #StageX');
					}
					break;
				case ($v['type']==AutoPublishQueue::LK_PL):
					if($user && $playlist){
						//print 'www.stagex.co.uk'.$playlist->getRandomVideoPic(); exit();
						$facebook->update_status(
							$playlist->title,
							'Liked a playlist on StageX',
							glue::http()->url('/playlist/view', array('id' => $playlist->_id)),
							'There are '.count($playlist->videos).' videos in this playlist',
							str_replace('http://', '', $playlist->getRandomVideoPic())
						);
						$twitter->update_status('Liked '.glue::http()->url('/playlist/view', array('id' =>  $playlist->_id)).' on #StageX');
					}
					break;
				case ($v['type']==AutoPublishQueue::PL_V_ADDED):
					if($user && $playlist && $video){
						$facebook->update_status(
							$playlist->title,
							'Added '.$video->title.' to '.$playlist->title,
							glue::http()->url('/playlist/view', array('id' => $playlist->_id)),
							'There are '.count($playlist->videos).' videos in this playlist',
							str_replace('http://', '', $video->getImage(124,69))
						);
						$twitter->update_status('Added a video to '.glue::http()->url('/playlist/view', array('id' =>  $playlist->_id)).' on #StageX');
					}
					break;
			}
		}
		
		// Tell the db they are done now since removabls are expensive so I can do those some other time. Plus good to house them
		// for duplicate testing
		//glue::db()->auto_publish_stream->update(array('_id' => array('$in' => $_ids)), array('$set' => array('done' => 1)));
		AutoPublishQueue::model()->deleteAll(array('_id' => array('$in' => $_ids)));		
	}
	
	function action_resetUploadBandwith()
	{
		$user=app\models\User::model()->find(array('nextBandwidthTopup' => array('$lt' => time())));
		foreach($users as $k => $v)
			$user->reset_upload_bandwidth();		
	}
}