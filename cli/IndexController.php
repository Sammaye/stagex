<?php

use \glue\Controller;

class IndexController extends Controller
{
	function action_deleteObject()
	{
		ini_set('error_reporting', E_ALL & ~E_NOTICE);
		
		/**
		 * This script will run every 1 hour picking up new things to be deleted from the deleted job queue
		*/
		$obj_rows = glue::db()->delete_queue->find()->sort(array('ts' => -1))->limit(10); // Find The oldest put on and get 10 of them
		
		/**
		 * If we are deleting a user :'(
		*/
		foreach($obj_rows as $k => $obj){
			if($obj['type'] === 'user'){
		
				/**
				 * Lets reove the users subscribers so they no longer recieve notifications
				 */
				$subscribers = glue::db()->follower->find(array('toId' => $obj['id']))->sort(array('created' => -1));
				$user_ids = array();
				foreach($subscribers as $k => $v){
					$user_ids[] = new MongoId($v['fromId']);
				}
				glue::db()->user->update(array('_id' => array('$in' => $user_ids)), array('$inc' => array('totalFollowing' => -1)), array('multiple' => true));
				glue::db()->follower->remove(array('toId' => $obj['id']));
		
				/**
				 * Now lets remove all the users subscriptions and de-$inc the subscribed users so everything stays counting nicely.
				*/
				$subscriptions = glue::db()->follower->find(array('fromId' => $obj['id']))->sort(array('created' => -1));
				$user_ids = array();
				foreach($subscriptions as $k => $v){
					$user_ids[] = new MongoId($v['toId']);
				}
				glue::db()->user->update(array('_id' => array('$in' => $user_ids)), array('$inc' => array('totalFollowers' => -1)), array('multiple' => true));
				glue::db()->follower->remove(array('fromId' => $obj['id']));
		
				// Clean the search index, we do this first to stop dead results in the index
				glue::elasticSearch()->deleteByQuery(array(
					'type' => 'playlist,video',
					'body' => array("term" => array("userId" => strval($obj['id'])))
				));				
				
				// Clean videos
				
				// Lets set all videos deleted
				glue::db()->video->update(array('userId' => $obj['id']), array('$set' => array('deleted' => 1)), array('multiple' => true));
				
				$videos = glue::db()->videos->find(array('userId' => $obj['id']))->sort(array('created' => -1));
				foreach($videos as $k => $vid){
					glue::db()->image->remove(array('ref._id' => $vid['_id'], 'ref.type' => 'video'));
					glue::db()->videoresponse->remove(array('vid' => $vid['_id']));
					
					glue::db()->videoresponse_likes->remove(array('videoId' => $vid['_id']));
					
					// delete the likes, commented out since I am unsure if the user needs 
					// to remove these themselves, they might think we are being too clever
					// glue::db()->video_likes->remove(array('item' => $vid['_id']));
				}				
				
				// This is where normally I would delete the video responses that this ouser has made on other 
				// videos but I will not. I am not deleting any foreign data in this script so that users do not suddenly 
				// have massive gaping holes in their own content and wonder why they are there, instead it will say that the user is 
				// removed and the video owner will have to decide to delete the content
				
				// Lets remove all playlists
				glue::db()->playlist->remove(array('userId' => $obj['id']));
				
				// Clean the user themselves
				
				// remove their notifications
				glue::db()->notification->remove(array('userId' => $obj['id']));
				
				// remove their stream
				glue::db()->stream->remove(array('user_id' => $obj['id']));
				
				// remove their watched history
				glue::db()->watched_history->remove(array('user_id' => $obj['id']));
				
				// remove their playlist subscriptions
				glue::db()->playlist_subscription->remove(array('user_id' => $obj['id']));
		
				// remove their avatar
				glue::db()->image->remove(array('ref._id' => $obj['id'], 'ref.type' => 'user'));
				
				// remove them
				// glue::db()->user->rmeove(array('_id' => $obj['id']));
				
				// Update their email address instead
				glue::db()->user->update(array('_id' => $obj['id']), array('$set' => array('email' => null, 'username' => '[Deleted]')));
				
				print 'deleted user with _id: ' . $obj['id'] . "\n";
			}
			glue::db()->delete_queue->remove(array('_id' => $obj['_id'])); // Now lets clear up the deletion queue!
		}		
	}
	
	function action_submitSitemap()
	{
		/**
		 * All this script does is cURL Google telling them we have changed our sitemap once every hour
		 */
		$ch = curl_init("www.google.com/webmasters/tools/ping?sitemap=".'http://www.stagex.co.uk/site_map.xml');
		curl_setopt($ch, CURLOPT_HEADER, 0);
		//curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);
		curl_close($ch);
		echo $output;
	}
	
}