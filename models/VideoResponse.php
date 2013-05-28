<?php
namespace app\models;

use glue;

class VideoResponse extends \glue\db\Document{

	public $userId;
	public $videoId;
	public $type;
	public $content;
	public $replyVideoId;

	public $threadParentId;
	public $path;

	public $threadParentUsername; // This is used for when the parent comment or user account is deleted to avoid awkward convos

	public $approved;
	public $likes = 0;
	public $dislikes = 0;

	public $deleted = 0;

	function collectionName(){
		return "videoresponse";
	}

	function behaviours(){
		return array(
			'timestampBehaviour' => array(
				'class' => 'glue\\behaviours\\Timestamp'
			)
		);
	}

	function defaultScope(){
		return array(
			'condition' => array('deleted' => 0),
			'sort' => array('created'=>-1)
		);
	}

	function scopes(){
		return array(
			'public' => array(
				'condition' => array('$or' => array(
					array('approved' => true),
					array('user_id' => glue::user()->_id)
				))
			),
			'moderated' => array(
				'condition' => array('approved'=>true)
			),
		);
	}

	function relations(){
		return array(
			"author" => array('one', 'app\\models\\User', "_id", 'on' => 'userId'),
			"video" => array('one', 'app\\models\\Video', "_id", 'on' => 'videoId'),
			"reply_video" => array('one', 'app\\models\\Video', "_id", 'on' => 'replyVideoId'),
			'thread_parent' => array('one', 'app\\models\\VideoResponse', '_id', 'on' => 'threadParentId')
		);
	}

	public static function model($className = __CLASS__){
		return parent::model($className);
	}

	public static function findAllComments($video, $ts_query = array()){
		return self::model()->find(array('vid' => $video->_id, 'ts' => $ts_query));
	}

	public static function findPublicComments($video,  $ts_query = array(), $showOnlyModerated = false){
		if($showOnlyModerated){
			return self::model()->moderated()->find(array('vid' => $video->_id));
		}else{
			return self::model()->public()->find(array('vid' => $video->id, 'ts' => $ts_query));
		}
	}

	function beforeValidate(){
		// Custom error handling which makes sure we are actually allowed to post comments to this video before we do.
		if($this->getIsNewRecord()){

			if($this->getScenario() == 'video_comment'){
				if(!$this->video->vid_coms_allowed){
					$this->addError('Video responses are currently disabled for this video');
					return false;
				}
			}elseif($this->getScenario() == 'text_comment'){
				if(!$this->video->txt_coms_allowed){
					$this->addError('Text responses are currently disabled for this video');
					return false;
				}
			}
		}
		return true;
	}

	function rules(){
		return array(
			array('videoId', 'required', 'message' => 'An unknown error occured. Try refreshing the page to fix this.'),
			array('videoId', 'objExist',
				'class'=>'Video',
				'field'=>'_id',
				'allowNull' => true, 'message' => 'The video you are replying to might no longer exist. Either way we cannot seem to find it now'
			),
			array('content', 'required', 'on' => 'text_comment', 'message' => 'You must enter at least something into the comment field to post a text response.'),
			array('content', 'string', 'max' => '1500', 'message' => 'You can only write 1500 characters for a comment.'),

			array('replyVideoId', 'required', 'on' => 'video_comment', 'message' => 'You must specify a video to repond with'),
			array('replyVideoId', 'objExist',
				'class'=>'Video',
				'field'=>'_id',
				'allowNull' => true,
				'on' => 'video_comment', 'message' => 'The video you selected cannot be validated. Please choose a different video.'
			),
			array('replyVideoId', 'check_already_reply', 'message' => 'This video has already been used as a reply on this one.'),
			array('replyVideoId', 'check_same_video', 'message' => 'The same video being watched cannot be added as a reply.'),

			array('threadParentUsername', 'safe', 'on' => 'text_comment'),
			array('threadParentUsername', 'objExist',
				'class'=>'VideoResponse',
				'field'=>'_id',
				'allowNull' => true,
				'on' => 'text_comment', 'message' => 'The comment you were replying to might no longer exist. Either way we cannot seem to find it now.'
			)
		);
	}

	function check_already_reply($field, $value, $params = array()){
		return !self::model()->findOne(array('replyVideoId' => $value, 'videoId' => $this->videoId));
	}

	function check_same_video($field, $value, $params = array()){
		return $this->replyVideoId != $this->videoId;
	}

	function getThread(){
		/** $secondLevel = $this->Db()->find(array("path"=>new MongoRegex("/^".$path.",[^,]*,[^,]*$/")))->sort(array("seq"=>1)); // Second Level **/
		return self::model()->find(array("path"=>new MongoRegex("/^".$this->path.",[^,]*$/")))->sort(array("ts"=>1)); // First Level
	}

	function beforeSave(){
		if($this->getIsNewRecord()){
			$this->user_id = glue::user()->_id;

			if($this->video->mod_comments == 1)
				$this->approved = !glue::auth()->checkRoles(array('^' => $this->video)) ? false : true;
			else
				$this->approved = true;

			if($this->getScenario() == 'video_comment'){
				$this->type = 'video';
			}elseif($this->getScenario() == 'text_comment'){
				$this->type = 'text';
			}

			$this->content = trim($this->content);

			// Build a path. There are some bugs in my active record stopping this from working in a better way
			$this->_id = new MongoId(); // Set the id here since we don't actually have it yet, we'll send it down with the rest of the record

			if($this->video->listing != 2 && $this->video->listing != 3){
				\app\models\Stream::commentedOn($this->user_id, $this->vid, $this->_id);
			}

			$this->path = rtrim($this->thread_parent->path.','.strval($this->_id),',');
		}
		return true;
	}

	function afterSave(){
		if($this->getIsNewRecord()){

			$video = $this->video;
			$video->total_responses = $video->total_responses+1;

			if($this->getScenario() == 'video_comment')
				$video->vid_responses = $video->vid_responses+1;

			if($this->getScenario() == 'text_comment')
				$video->txt_responses = $video->txt_responses+1;

			$video->record_statistic($this->getScenario() == 'video_comment' ? 'video_comment' : 'text_comment');
			$video->save();

			if(!glue::roles()->checkRoles(array('^' => $this->video))){

				if($this->video->author->email_vid_responses){
					glue::mailer()->mail($this->video->author->email, array('no-reply@stagex.co.uk', 'StageX'), 'Someone replied to one of you videos on StageX',
						"videos/new_comment.php", array( 'username' => $this->video->author->username, 'approved' => $this->approved,
						'comment' => $this, 'from' => $this->author, 'video' => $this->video ));
				}

				Notification::newVideoResponse($this->video->user_id, $this->video->_id, $this->approved);
			}
			if($this->parent_comment && $this->approved){

				if($this->thread_parent->author->email_vid_response_replies){
					glue::mailer()->mail($this->thread_parent->author->email, array('no-reply@stagex.co.uk', 'StageX'), 'Someone replied to one of you comments on StageX',
						"videos/new_comment_reply.php", array( 'username' => $this->thread_parent->author->username,
						'comment' => $this, 'from' => $this->author, 'video' => $this->video ));
				}

				Notification::newVideoResponseReply($this->thread_parent->_id, $this->thread_parent->user_id, $this->_id, $this->video->_id);
			}
		}
		return true;
	}

	function approve(){
		if(!$this->approved){
			$this->approved = true;
			$this->save();

			if($this->threadParentId){

				if(!glue::auth()->checkRoles(array('^' => $this->video)) && $this->thread_parent->author->email_vid_response_replies){
					glue::mailer()->mail($this->thread_parent->author->email, array('no-reply@stagex.co.uk', 'StageX'), 'Someone replied to one of you comments on StageX',
						"videos/new_comment_reply.php", array( 'username' => $this->thread_parent->author->username,
						'comment' => $this, 'from' => $this->author, 'video' => $this->video ));
				}
				\app\models\Notification::newVideoResponseReply($this->thread_parent->_id, $this->thread_parent->userId, $this->_id, $this->video->_id, $this->userId);
			}
			\app\models\Notification::commentApproved($this->video->userId, $this->video->_id, $this->userId);
			return true;
		}
		return false;
	}

	function currentUserLikes(){
		return $this->Db('videoresponse_likes')->findOne(array('userId' => glue::session()->user->_id, 'responseId' => $this->_id));
	}

	function like(){
		glue::db()->videoresponse_likes->update(
			array("userId"=>glue::user()->_id, "responseId"=>$this->_id),
			array("userId"=>glue::user()->_id, "responseId"=>$this->_id, "weight"=>"+1", 'videoId' => $this->videoId, "ts" => new MongoDate()),
			array("upsert"=>true)
		);

		$this->likes = $this->likes+1;
		$this->save();
		return true;
	}

	function unlike(){
		glue::db()->videoresponse_likes->remove(array("userId"=>glue::user()->_id, "responseId"=>$this->_id));
		$this->likes = $this->likes-1;
		$this->save();
		return true;
	}

	function delete(){
		$video = $this->video;
		$video->total_responses = $video->total_responses-1;
		$video->save();

		glue::db()->videoresponse_likes->remove(array("responseId"=>$this->_id));
		parent::delete();
	}
}