<?php

ini_set('error_reporting', E_ALL & ~E_NOTICE);

/**
 * This script will run every 1 hour picking up new things to be deleted from the deleted job queue
 */
$obj_rows = glue::db()->delete_queue->find()->sort(array('ts' => -1))->limit(10); // Find The oldest put on and get 10 of them

/**
 * If we are deleting a user :'(
 */
foreach($obj_rows as $k => $obj){
	if($obj['type'] == 'user'){

		/**
		 * Lets reove the users subscribers so they no longer recieve notifications
		 */
		glue::db()->subscription->remove(array('to_id' => $obj['object_id']), array('safe' => true));

		/**
		 * Now lets remove all the users subscriptions and de-$inc the subscribed users so everything stays counting nicely.
		 */
		$subscriptions = glue::db()->subscription->find(array('from_id' => $obj['object_id']));

		$user_ids = array();
		foreach($subscriptions as $k => $v){
			$user_ids[] = new MongoId($k);
		}

		glue::db()->users->update(array('_id' => array('$in' => $user_ids)), array('$inc' => array('subscribers' => -1)), array('safe' => true, 'multiple' => true));
		glue::db()->subscription->remove(array('from_id' => $obj['object_id']), array('safe' => true));

		/**
		 * Run a general SQL query deleting all the items from the db.
		 * Run this before everything else to stop problems in Sphinx.
		 */
		glue::mysql()->query('UPDATE documents SET deleted=1 WHERE uid = :_id OR _id = :_id', array(':_id' => strval($obj['object_id'])));

		/**
		 * Now lets go through the users videos and replaylists
		 *
		 * Videos are still not completely removed. I need to come up with a way of doing this...
		 */
		glue::db()->videos->update(array('user_id' => $obj['object_id']), array('$set' => array('deleted' => 1)), array('safe' => true, 'multiple' => true));
		glue::db()->playlists->remove(array('user_id' => $obj['object_id']), array('safe' => true));

		/**
		 * Now lets delete the responses to these videos
		 * @var unknown_type
		 */
		$videos = glue::db()->videos->find(array('user_id' => $obj['object_id']));
		foreach($videos as $k => $vid){
			glue::db()->image_cache->remove(array('object_id' => $vid['_id']), array('safe' => true));
			glue::db()->videoresponse->remove(array('vid' => $vid['_id']), array('safe' => true));
		}

		/**
		 * Lastely lets delete the stream and notifications for the user
		 */
		glue::db()->notification->remove(array('user_id' => $obj['object_id']), array('safe' => true));
		glue::db()->stream->remove(array('$or'=> array(array('user_id' => $obj['object_id']), array('posted_by_id' => $obj['object_id']))), array('safe' => true));

		glue::db()->image_cache->remove(array('object_id' => $obj['object_id']), array('safe' => true));
	}
	glue::db()->delete_queue->remove(array('_id' => $obj['_id'])); // Now lets clear up the deletion queue!
}