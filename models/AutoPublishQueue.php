<?php

namespace app\models;

use glue;

class AutoPublishQueue extends \glue\db\Document{

	// When a user uploads a video
	const UPLOAD = 1;

	// When a user responds to a video
	const V_RES = 2;

	// When a user likes a video
	const LK_V = 4;

	// When a user dislikes a video
	const DL_V = 8;

	// When a user likes a playlist
	const LK_PL = 16;

	// When a user adds a video to a playlist
	const PL_V_ADDED = 32;

	protected $type;

	protected $userId;
	protected $playlistId;
	protected $videoId;

	protected $text;

	protected $processing = 0;
	protected $done = 0;

	function behaviours(){
		return array(
			'timestampBehaviour' => array(
				'class' => 'glue\\behaviours\\Timestamp'
			)
		);
	}

	public static function model($className = __CLASS__){
		return parent::model($className);
	}

	function collectionName(){
		return "auto_publish_queue";
	}

	static function add_to_qeue($type, $user_id, $video_id = null, $playlist_id = null, $text = null){
		$oldDoc = AutoPublishQueue::model()->findOne(array('type' => $type, 'userId' => $user_id, 'videoId' => $video_id, 'playlistId' => $playlist_id, 'text' => $text));

		// If oldDoc is filled then we dont do shit else lets add this as something that needs syncing
		// This will help prevent duplicate items on the users feed which, let's face it, is a pain in the ass
		if(!$oldDoc){
			$item = new AutoPublishQueue();
			$item->type = $type;
			$item->userId = $user_id;
			$item->videoId = $video_id;
			$item->playlistId = $playlist_id;
			$item->text = $text;

			$item->save();
		}
	}
}