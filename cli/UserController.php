<?php
class UserController extends \glue\Controller{
	
	function action_publishStream(){
		// Get all jobs which are in progress or done
		$stream_cursor = glue::db()->auto_publish_stream->find(array('processing' => 0, 'done' => 0))->sort(array('ts' => -1))->limit(100);
		
		// Get a list of _ids of these docs to be able to easily update them
		$_ids = array();
		foreach($stream_cursor as $k => $v){
			$_ids[] = new MongoId($k);
		}
		
		$stream_cursor = iterator_to_array($stream_cursor);
		
		//print count($stream_cursor); // DEBUG
		
		// Set them to processing so future crons don't try and share twice
		// The idea for this is that the cronjob runs once every 3 mins but it will take long than 3 mins for the cronjob to finish
		// so I reserve the docs are are being processed by one thread so another thread won't try and take them and create duplicates or an infinite loop.
		$c = glue::db()->auto_publish_stream->update(array('_id' => array('$in' => $_ids)), array('$set' => array('processing' => 1)), array('multiple' => true));
		
		//print count($stream_cursor); // DEBUG
		
		// Now lets iterate through the documents finding out what kind of action needs auto publishing to networks
		// The list of possible values can be found in AutoPublishStream model
		foreach($stream_cursor as $k => $v){
		
			// I have used different fields for each entity to be able to
			// query them like this and then understand below if I have the required information to complete a task.
			// Saves me having to copy and paste the query for each case
			$user = User::model()->findOne(array('_id' => $v['user_id']));
			$video = Video::model()->findOne(array('_id' => $v['v_id']));
			$playlist = Playlist::model()->findOne(array('_id' => $v['pl_id']));
		
			// Now since we have no Cookies here we need to set the facebook API to use a pre-defined access token from the DB
			$facebook = glue::facebook();
			if($user){ $facebook->facebook->setAccessToken($user->fb_autoshare_token); }
		
			// Lets use a copy of the twitter object to save potential memory writing problems of variables here
			$twitter = glue::twitter();
			$twitter->connect(array("access_token"=>$user->twt_autoshare_token));
		
			// Now lets do the meaty stuff
			switch($v['type']){
				case 'UPLOAD':
					if($user && $video){
						$facebook->update_status( // This liks to a function I made to update the users status, it does not assume the user has given permission
								$video->title,
								'Uploaded a new video to StageX',
								glue::url()->create('/video/watch', array('id' => $video->_id)),
								strlen($video->description) > 0 ? $video->description : '',
								str_replace('http://', '', $video->getImage(124,69))
						);
						$twitter->update_status('uploaded '.glue::url()->create('/video/watch', array('id' =>  $video->_id)).' to #StageX');
					}
					break;
				case 'V_RES':
					if($user && $video && isset($v['text'])){
						$facebook->update_status(
								$video->title,
								'Posted a new video response on StageX',
								glue::url()->create('/video/watch', array('id' => $video->_id)),
								$v['text'],
								str_replace('http://', '', $video->getImage(124,69))
						);
						$twitter->update_status('Posted a new response on a video '.glue::url()->create('/video/watch', array('id' => $video->_id)).' #StageX');
					}
					break;
				case 'LK_V':
					if($video && $user){
						$facebook->update_status(
								$video->title,
								'Liked a video on StageX',
								glue::url()->create('/video/watch', array('id' => $video->_id)),
								strlen($video->description) > 0 ? $video->description : '',
								str_replace('http://', '', $video->getImage(124,69))
						);
						$twitter->update_status('Liked '.glue::url()->create('/video/watch', array('id' =>  $video->_id)).' on #StageX');
					}
					break;
				case 'DL_V':
					if($user && $video){
						$facebook->update_status(
								$video->title,
								'Disliked a video on StageX',
								glue::url()->create('/video/watch', array('id' => $video->_id)),
								strlen($video->description) > 0 ? $video->description : '',
								str_replace('http://', '', $video->getImage(124,69))
						);
						$twitter->update_status('Disliked '.glue::url()->create('/video/watch', array('id' =>  $video->_id)).' on #StageX');
					}
					break;
				case 'LK_PL':
					if($user && $playlist){
						//print 'www.stagex.co.uk'.$playlist->getRandomVideoPic(); exit();
						$facebook->update_status(
								$playlist->title,
								'Liked a playlist on StageX',
								glue::url()->create('/playlist/view', array('id' => $playlist->_id)),
								'There are '.count($playlist->videos).' videos in this playlist',
								str_replace('http://', '', $playlist->getRandomVideoPic())
						);
						$twitter->update_status('Liked '.glue::url()->create('/playlist/view', array('id' =>  $playlist->_id)).' on #StageX');
					}
					break;
				case 'PL_V_ADDED':
					if($user && $playlist && $video){
						$facebook->update_status(
								$playlist->title,
								'Added '.$video->title.' to '.$playlist->title,
								glue::url()->create('/playlist/view', array('id' => $playlist->_id)),
								'There are '.count($playlist->videos).' videos in this playlist',
								str_replace('http://', '', $video->getImage(124,69))
						);
						$twitter->update_status('Added a video to '.glue::url()->create('/playlist/view', array('id' =>  $playlist->_id)).' on #StageX');
					}
					break;
			}
		}
		
		// Tell the db they are done now since removabls are expensive so I can do those some other time. Plus good to house them
		// for duplicate testing
		//glue::db()->auto_publish_stream->update(array('_id' => array('$in' => $_ids)), array('$set' => array('done' => 1)));
		glue::db()->auto_publish_stream->remove(array('_id' => array('$in' => $_ids)));		
	}
	
	function action_resetUploadBandwith(){
		$users = glue::db()->users->find(array('next_bandwidth_up' => array('$lt' => time())));
		
		foreach($users as $k => $v){
			$v['next_bandwidth_up'] = strtotime('+1 week', mktime(0, 0, 0, date('m'), date('d'), date('Y')));
			$v['upload_left'] = $v['max_upload'] > 0 ? $v['max_upload'] : glue::$params['maxUpload'];
			glue::db()->users->save($v);
		}		
	}
	
}