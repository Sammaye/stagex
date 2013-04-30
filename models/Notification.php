<?php

/**
 * Notifications class
 *
 * This class deals with sending notifications between users (not InMail).
 * Notifications are basically actions that concern other users involved.
 * Notifications were created for the same reason as facebook has them! To stop so much crap
 * from entering the users inbox and leave it clean for important stuff.
 *
 * @author Sam Millman
 */
class Notification extends MongoDocument{

	const VIDEO_COMMENT = 1;
	const VIDEO_COMMENT_REPLY = 2;
	const VIDEO_RESPONSE_APPROVE = 3;
	const WALL_POST = 4;

	protected $user_id;
	protected $from_users = array();
	protected $items = array();
	protected $type;
	protected $ts;

	protected $response_count;
	protected $approved;
	protected $comment_id;

	function getCollectionName(){
		return "notification";
	}

	public static function model($className = __CLASS__){
		return parent::model($className);
	}

	function relations(){
		return array(
			"parent_video" => array(self::HAS_ONE, 'Video', "_id", 'on' => 'video_id'),
			"original_comment" => array(self::HAS_ONE, 'VideoResponse', "_id", 'on' => 'comment_id'),
			'parent_playlist' => array(self::HAS_ONE, 'Playlist', '_id', 'on' => 'playlist_id'),
			"status_sender" => array(self::HAS_ONE, 'User', '_id', 'on' => 'user_id'),
			"subscribed_user" => array(self::HAS_ONE, 'User', '_id', 'on' => 'subscribed_user_id'),
			"commenting_user" => array(self::HAS_ONE, 'User', '_id', 'on' => 'posted_by_id'),
		);
	}

	static function getNewCount_Notifications(){
		return Notification::model()->find(array('user_id' => glue::session()->user->_id,
				'ts' => array('$gt' => glue::session()->user->last_notification_pull)))->count();
	}

	function beforeSave(){
		$this->ts = new MongoDate();
		$this->read = false;
		return true;
	}

	function getDateTime(){
		$today_start = mktime(0, 0, 0, date("n"), date("j")-1, date("Y"));
		if($today_start < $this->ts->sec){ // Older than a day
			return date('g:i a', $this->ts->sec);
		}else{
			return date('j M Y', $this->ts->sec);
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

	static function newWallPost_on_OtherUserWall($user_id, $to_user){
		$notification = Notification::model()->findOne(array('user_id' => $to_user, 'type' => Notification::WALL_POST));

		if($notification){
			$notification->addUser($user_id);
			$notification->response_count = $notification->response_count+1;
			$notification->save();
		}else{
			// make a new status
			$notification = new Notification();
			$notification->user_id = $to_user;
			$notification->addUser($user_id);
			$notification->response_count = 1;
			$notification->type = Notification::WALL_POST;
			$notification->save();
		}
	}

	public static function newVideoResponse($to_user, $video_id, $approved){
		$status = Notification::model()->findOne(array( 'type' => Notification::VIDEO_COMMENT, 'user_id' => $to_user, 'video_id' => $video_id ));

		if($status){
			$status->addUser(glue::session()->user->_id);
			$status->approved = $approved;
			$status->response_count = $status->response_count+1;
			$status->save();
		}else{
			// make a new status
			$status = new Notification();
			$status->user_id = $to_user;
			$status->video_id = $video_id;
			$status->addUser(glue::session()->user->_id);
			$status->approved = $approved;
			$status->response_count = $status->response_count+1;
			$status->type = Notification::VIDEO_COMMENT;
			$status->save();
		}
	}

	public static function newVideoResponseReply($comment_id, $to_user, $reply_id, $video_id, $from_user = null){
		$status = Notification::model()->findOne(array( 'type' => Notification::VIDEO_COMMENT_REPLY, 'user_id' => $to_user, 'comment_id' => $comment_id));

		if($status){
			$status->addUser($from_user ? $from_user : glue::session()->user->_id);
			$status->addItemBy_id($reply_id);
			$status->response_count = $status->response_count+1;
			$status->save();
		}else{
			// make a new status
			$status = new Notification();
			$status->user_id = $to_user;
			$status->video_id = $video_id;
			$status->comment_id = $comment_id;
			$status->addUser($from_user ? $from_user : glue::session()->user->_id);
			$status->addItemBy_id($reply_id);
			$status->response_count = $status->response_count+1;
			$status->type = Notification::VIDEO_COMMENT_REPLY;
			$status->save();
		}
	}

	public static function commentApproved($from_user, $video_id, $to_user){
		$status = Notification::model()->findOne(array( 'type' => Notification::VIDEO_RESPONSE_APPROVE, 'user_id' => $to_user, 'video_id' => $video_id));

		if($status){
			$status->addUser($from_user ? $from_user : glue::session()->user->_id);
			$status->response_count = $status->response_count+1;
			$status->save();
		}else{
			// make a new status
			$status = new Notification();
			$status->user_id = $to_user;
			$status->video_id = $video_id;
			$status->addUser($from_user ? $from_user : glue::session()->user->_id);
			$status->response_count = $status->response_count+1;
			$status->type = Notification::VIDEO_RESPONSE_APPROVE;
			$status->save();
		}
	}

	function get_usernames_caption($getOnlyFirst = false){
		$users_count = count($this->from_users);
		$caption = '';

		$i = 0;
		foreach($this->from_users as $user){

			$user = User::model()->findOne(array('_id' => $this->from_users[$i]));
			if($users_count > 1 && $i == $users_count-1){
				$caption .=  " and <a href='".glue::url()->create('/user/view', array('id' => strval($user->_id)))."'>@{$user->getUsername()}</a>";
			}elseif($users_count > 1 && $i != 0){
				$caption .=  ", <a href='".glue::url()->create('/user/view', array('id' => strval($user->_id)))."'>@{$user->getUsername()}</a>";
			}else{
				$caption .=  "<a href='".glue::url()->create('/user/view', array('id' => strval($user->_id)))."'>@{$user->getUsername()}</a>";
			}

			if($i == 2 || $getOnlyFirst)
				break;
			$i++;
		}

		if($users_count > 4 && !$getOnlyFirst){
			$caption .= ' including '.($users_count - 3).' others';
		}
		return $caption;
	}
}