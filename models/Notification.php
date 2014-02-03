<?php
namespace app\models;

use glue;
use glue\db\Document;

class Notification extends Document
{
	const VIDEO_COMMENT = 1;
	const VIDEO_COMMENT_REPLY = 2;
	const VIDEO_RESPONSE_APPROVE = 3;
	const WALL_POST = 4;

	public $userId;
	public $fromUsers = array();
	public $items = array();
	public $type;
	public $ts;

	public $totalResponses;
	public $approved;
	public $videoId;
	public $responseId;

	public static function collectionName()
	{
		return "notification";
	}

	public function behaviours()
	{
		return array(
			'timestampBehaviour' => array(
				'class' => 'glue\\behaviours\\Timestamp'
			)
		);
	}

	public function relations()
	{
		return array(
			"video" => array('one', 'app\\models\\Video', "_id", 'on' => 'videoId'),
			"response" => array('one', 'app\\models\\VideoResponse', "_id", 'on' => 'responseId'),
			"sender" => array('one', 'app\\models\\User', '_id', 'on' => 'userId'),
		);
	}

	public static function getNewCountNotifications()
	{
		//var_dump(glue::user()->lastNotificationPull);
		return Notification::find(array('userId' => glue::user()->_id,
				'created' => array('$gt' => glue::user()->lastNotificationPull)))->count();
	}

	public function beforeSave()
	{
		$this->read = false;
		return true;
	}

	public function getDateTime()
	{
		$today_start = mktime(0, 0, 0, date("n"), date("j")-1, date("Y"));
		if($today_start < $this->created->sec){ // Older than a day
			return date('g:i a', $this->created->sec);
		}else{
			return date('j M Y', $this->created->sec);
		}
	}

	public function addUser($id)
	{
		if($this->read){
			$this->fromUsers = array();
		}

		if(is_array($this->fromUsers)){
			foreach($this->fromUsers as $k => $userId){
				if(strval($userId) == strval($id)){
					unset($this->fromUsers[$k]);
				}
			}
			$c = $this->fromUsers;
			array_unshift($c, $id);
			$this->fromUsers = $c;
		}else{
			$this->fromUsers = array($id);
		}

		return true;
	}

	public function addItemById($_id, $unique = true)
	{
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

	public static function directlyMessageUser($userId, $to_user)
	{
		$notification = Notification::findOne(array('userId' => $to_user, 'type' => Notification::WALL_POST));

		if($notification){
			$notification->addUser($userId);
			$notification->response_count = $notification->response_count+1;
			$notification->save();
		}else{
			// make a new status
			$notification = new Notification();
			$notification->userId = $to_user;
			$notification->addUser($userId);
			$notification->totalResponses = 1;
			$notification->type = Notification::WALL_POST;
			$notification->save();
		}
	}

	public static function newVideoResponse($to_user, $video_id, $approved)
	{
		$status = Notification::findOne(array( 'type' => Notification::VIDEO_COMMENT, 'userId' => $to_user, 'videoId' => $video_id ));

		if($status){
			$status->addUser(glue::user()->_id);
			$status->approved = $approved;
			$status->totalResponses = $status->totalResponses+1;
			$status->save();
		}else{
			// make a new status
			$status = new Notification();
			$status->userId = $to_user;
			$status->videoId = $video_id;
			$status->addUser(glue::user()->_id);
			$status->approved = $approved;
			$status->totalResponses = $status->totalResponses+1;
			$status->type = Notification::VIDEO_COMMENT;
			$status->save();
		}
	}

	public static function newVideoResponseReply($comment_id, $to_user, $reply_id, $video_id, $from_user = null)
	{
		$status = Notification::findOne(array( 'type' => Notification::VIDEO_COMMENT_REPLY, 'userId' => $to_user, 'responseId' => $comment_id));

		if($status){
			$status->addUser($from_user ? $from_user : glue::user()->_id);
			$status->addItemById($reply_id);
			$status->totalResponses = $status->totalResponses+1;
			$status->save();
		}else{
			// make a new status
			$status = new Notification();
			$status->userId = $to_user;
			$status->videoId = $video_id;
			$status->responseId = $comment_id;
			$status->addUser($from_user ? $from_user : glue::user()->_id);
			$status->addItemById($reply_id);
			$status->totalResponses = $status->totalResponses+1;
			$status->type = Notification::VIDEO_COMMENT_REPLY;
			$status->save();
		}
	}

	public static function commentApproved($from_user, $video_id, $to_user)
	{
		$status = Notification::findOne(array( 'type' => Notification::VIDEO_RESPONSE_APPROVE, 'userId' => $to_user, 'videoId' => $video_id));

		if($status){
			$status->addUser($from_user ? $from_user : glue::user()->_id);
			$status->totalResponses = $status->totalResponses+1;
			$status->save();
		}else{
			// make a new status
			$status = new Notification();
			$status->userId = $to_user;
			$status->videoId = $video_id;
			$status->addUser($from_user ? $from_user : glue::user()->_id);
			$status->totalResponses = $status->totalResponses+1;
			$status->type = Notification::VIDEO_RESPONSE_APPROVE;
			$status->save();
		}
	}

	public function getUsernamesCaption($getOnlyFirst = false)
	{
		$users_count = count($this->fromUsers);
		$caption = '';

		$i = 0;
		foreach($this->fromUsers as $user){

			$user = User::findOne(array('_id' => $this->fromUsers[$i]));
			if($users_count > 1 && $i == $users_count-1){
				$caption .=  " and <a href='".glue::http()->url('/user/view', array('id' => strval($user->_id)))."'>@{$user->getUsername()}</a>";
			}elseif($users_count > 1 && $i != 0){
				$caption .=  ", <a href='".glue::http()->url('/user/view', array('id' => strval($user->_id)))."'>@{$user->getUsername()}</a>";
			}else{
				$caption .=  "<a href='".glue::http()->url('/user/view', array('id' => strval($user->_id)))."'>@{$user->getUsername()}</a>";
			}
			if($i == 2 || $getOnlyFirst){
				break;
			}
			$i++;
		}

		if($users_count > 4 && !$getOnlyFirst){
			$caption .= ' including '.($users_count - 3).' others';
		}
		return $caption;
	}
}