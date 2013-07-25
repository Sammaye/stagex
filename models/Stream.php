<?php
namespace app\models;

use glue;

class Stream extends \glue\db\Document{

	const VIDEO_UPLOAD = 1;
	const VIDEO_RATE = 2;
	const LIKE_PL = 3;
	const VIDEO_WATCHED = 4;
	const COMMENTED_ON = 5;
	const ADD_TO_PL = 6;
	const ITEM_SHARED = 7;
	const SUBSCRIBED_TO = 8;
	const WALL_POST = 10;

	public $userId;
	public $from_users = array();
	public $items = array();
	public $type;

	public $postedById;
	public $message;
	public $subscribedUserId;
	public $videoId;
	public $itemId;
	public $itemType;
	public $like;

	function collectionName(){
		return "stream";
	}

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

	function relations(){
		return array(
			"parent_video" => array('one', 'app\\models\\Video', "_id", 'on' => 'video_id'),
			"original_comment" => array('one', 'app\\models\\VideoResponse', "_id", 'on' => 'comment_id'),
			'parent_playlist' => array('one', 'app\\models\\Playlist', '_id', 'on' => 'playlist_id'),
			"status_sender" => array('one', 'app\\models\\User', '_id', 'on' => 'user_id'),
			"subscribed_user" => array('one', 'app\\models\\User', '_id', 'on' => 'subscribed_user_id'),
			"commenting_user" => array('one', 'app\\models\\User', '_id', 'on' => 'posted_by_id'),
		);
	}

	function beforeSave(){
		//$this->ts = new MongoDate();
		return true;
	}

	function getDateTime(){
		$today_start = mktime(0, 0, 0, date("n"), date("j")-1, date("Y"));
		if($today_start < $this->created->sec){ // Older than a day
			return date('g:i a', $this->created->sec);
		}else{
			return date('j M Y', $this->created->sec);
		}
	}

	function addUser($id){

		if($this->read){
			$this->from_users = array();
		}

		if(is_array($this->from_users)){
			foreach($this->from_users as $k => $user_id){
				if(strval($user_id) == strval($id)){
					unset($this->from_users[$k]);
				}
			}
			$c = $this->from_users;
			array_unshift($c, $id);
			$this->from_users = $c;
		}else{
			$this->from_users = array($id);
		}

		return true;
	}

	function addItemBy_id($_id, $unique = true){
		if(is_array($this->items)){
			if($unique){
				foreach($this->items as $k => $f_id){
					if(strval($f_id) == strval($_id)){
						unset($this->items[$k]);
					}
				}
				$c = $this->items;
				array_unshift($c, $_id);
				$this->items = $c;
			}else{
				$c = $this->items;
				array_unshift($c, $_id);
				$this->items = $c;
			}
		}else{
			$this->items = array($_id);
		}
		return true;
	}

	function removeAll_byid($id_array = array()){
		$this->Db()->remove(array('_id' => array('$in' => $id_array), 'user_id' => glue::session()->user->_id), array('safe' => true));
	}

	function removeAllByType($type){
		$this->Db()->remove(array('type' => $type, 'user_id' => glue::session()->user->_id));
	}

	function removeAll(){
		$this->Db()->remove(array('stream_type' => 'stream', 'user_id' => glue::session()->user->_id));
	}

	static function newWallPost_on_OtherUserWall($user_id, $to_user, $text){
		$status = Stream::model()->findOne(array('user_id' => $to_user, 'posted_by_id' => $user_id, 'message' => $text, 'type' => Stream::WALL_POST));

		if($status){
			$status->save(); // Just resave so it resets the ts on the object
		}else{
			$status = new Stream();
			$status->user_id = $to_user;
			$status->posted_by_id = $user_id;
			$status->message = $text;
			$status->type = Stream::WALL_POST;
			$status->save();
		}
		return $status;
	}

	static function subscribedTo($user_id, $subscribed_user){
		$status = Stream::model()->findOne(array('userId' => $user_id, 'subscribed_user_id' => $subscribed_user, 'type' => Stream::SUBSCRIBED_TO));

		if($status){
			$status->save();
		}else{
			$status = new Stream();
			$status->user_id = $user_id;
			$status->subscribed_user_id = $subscribed_user;
			$status->type = Stream::SUBSCRIBED_TO;
			$status->save();
		}
	}

	static function videoWatch($user_id, $video_id){
		$status = Stream::model()->findOne(array('user_id' => $user_id, 'video_id' => $video_id, 'type' => Stream::VIDEO_WATCHED));

		if($status){
			$status->save(); // Just resave so it resets the ts on the object
		}else{
			$status = new Stream();
			$status->user_id = $user_id;
			$status->video_id = $video_id;
			$status->type = Stream::VIDEO_WATCHED;
			$status->save();
		}
	}

	static function videoUpload($user_id, $video_id){
		$status = new static;
		$status->userId = $user_id;
		$status->videoId = $video_id;
		$status->type = Stream::VIDEO_UPLOAD;
		$status->save();
	}

	static function shareItem($user_id, $item_id, $item_type, $custom_text = null){
		$status = Stream::model()->findOne(array('user_id' => $user_id, 'item_id' => $item_id, 'type' => Stream::ITEM_SHARED));

		if($status){
			$status->message = $custom_text;
			$status->save();
		}else{
			$status = new Stream();
			$status->user_id = $user_id;
			$status->item_id = $item_id;
			$status->item_type = $item_type;
			$status->message = $custom_text;
			$status->type = Stream::ITEM_SHARED;
			$status->save();
		}
	}

	static function videoLike($video_id, $user_id){
		$status = Stream::model()->findOne(array('user_id' => $user_id, 'video_id' => $video_id, 'type' => Stream::VIDEO_RATE));

		if($status){
			$status->like = 1;
			$status->save();
		}else{
			$status = new Stream();
			$status->user_id = $user_id;
			$status->video_id = $video_id;
			$status->type = Stream::VIDEO_RATE;
			$status->like = 1;
			$status->save();
		}
	}

	static function videoDislike($video_id, $user_id){
		$status = Stream::model()->findOne(array('user_id' => $user_id, 'video_id' => $video_id, 'type' => Stream::VIDEO_RATE));

		if($status){
			$status->like = 0;
			$status->save();
		}else{
			$status = new Stream();
			$status->user_id = $user_id;
			$status->video_id = $video_id;
			$status->type = Stream::VIDEO_RATE;
			$status->like = 0;
			$status->save();
		}
	}

	static function commentedOn($user_id, $video_id, $comment_id){
		$status = Stream::model()->findOne(array('user_id' => $user_id, 'video_id' => $video_id, 'type' => Stream::COMMENTED_ON));

		if($status){
			$status->addItemBy_id($comment_id);
			$status->save();
		}else{
			$status = new Stream();
			$status->user_id = $user_id;
			$status->video_id = $video_id;
			$status->type = Stream::COMMENTED_ON;
			$status->addItemBy_id($comment_id);
			$status->save();
		}
	}

	public static function add_video_2_playlist($user_id, $playlist_id, $video_id){
		$status = Stream::model()->findOne(array('user_id' => $user_id, 'playlist_id' => $playlist_id, 'type' => Stream::ADD_TO_PL));

		if($status){
			$status->addItemBy_id($video_id);
			$status->save();
		}else{
			$status = new Stream();
			$status->user_id = $user_id;
			$status->playlist_id = $playlist_id;
			$status->type = Stream::ADD_TO_PL;
			$status->addItemBy_id($video_id);
			$status->save();
		}
	}

	public static function like_playlist($user_id, $playlist_id){
		$status = Stream::model()->findOne(array('user_id' => $user_id, 'playlist_id' => $playlist_id, 'type' => Stream::LIKE_PL));

		if($status){
			$status->save();
		}else{
			$status = new Stream();
			$status->user_id = $user_id;
			$status->playlist_id = $playlist_id;
			$status->type = Stream::LIKE_PL;
			$status->save();
		}
	}
}