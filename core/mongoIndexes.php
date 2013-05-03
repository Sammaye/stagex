<?php

/**
 * This file is not designed to really be used in huge production envos incase of sudden index change
 */
return array(
	'users' => array(
		array(array('email' => 1)),
		array(array('_id' => 1, 'username' => 1)),
		array(array('fb_uid' => 1)),
		array(array('username' => 1))
	),

	'subscription' => array(
		array(array('from_id' => 1, 'to_id' => 1)),
		array(array('to_id' => 1)),
		array(array('from_id' => 1))
	),

	'videos' => array(
		array(array('title' => 1, 'user_id' => 1)),
		array(array('user_id' => 1)),
		array(array('file' => 1, 'user_id' => 1)),
		array(array('state' => 1))
	),

	'videoresponse' => array(
		array(array('vid' => 1)),
		array(array('vid' => 1, 'ts' => 1)),
		array(array('path' => 1)),
		array(array('vid' => 1, 'path' => 1))
	),

	'videoresponse_likes' => array(
		array(array('user_id' => 1, 'response_id' => 1)),
		array(array('video_id' => 1))
	),

	'stream' => array(
		array(array('stream_type' => 1, 'user_id' => 1, 'type' => 1)),
		array(array('stream_type' => 1, 'user_id' => 1, 'type' => 1, 'ts' => 1)),
		array(array('_id' => 1, 'user_id' => 1)),
		array(array('user_id' => 1))
	),

	'help' => array(
		array(array('t_normalised' => 1)),
		array(array('title' => 1, 'path' => 1, 'type' => 1)),
		array(array('title' => 1, 'path' => 1, 'type' => 1))
	),

	'video_likes' => array(
		array(array('user_id' => 1, 'item' => 1))
	),

	'report.video' => array(
		array(array('vid' => 1, 'uid' => 1))
	),

	'playlists' => array(
		array(array('_id' => 1, 'title' => 1)),
		array(array('_id' => 1, 'user_id' => 1, 'title' => 1)),
		array(array('title' => 1)),
		array(array('user_id' => 1)),
	),

	'playlist_likes' => array(
		array(array('user_id' => 1, 'item' => 1))
	),

	'watched_history' => array(
		array(array('user_id' => 1)),
		array(array('user_id' => 1, 'item' => 1))
	),

	'image_cache' => array(
		array(array('object_id' => 1, 'width' => 1, 'height' => 1, 'type' => 1))
	)
);
