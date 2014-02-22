<?php
namespace app\models;

use glue;
use \glue\db\Document;
use \app\models\Notification;
use \app\models\Stream;

class VideoResponse extends Document
{
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
	public $replies = 0;

	public $deleted = 0;

	public static function collectionName()
	{
		return "videoresponse";
	}

	public static function defaultScope($cursor)
	{
		$cursor->andWhere(array('deleted' => 0));
		$cursor->sort(array('created' => -1));
	}
	
	public static function visible($cursor, $video = null)
	{
		if($video === null || !Glue::auth()->check(array('^' => $video))){
			$cursor->andWhere(array('$or' => array(
				array('approved' => true),
				array('userId' => glue::user()->_id)
			)));
		}
	}
	
	public static function moderated($cursor)
	{
		$cursor->andWhere(array('approved' => true));
	}
	
	public static function pending($cursor)
	{
		$cursor->andWhere(array('approved' => false));
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
				"author" => array('one', 'app\\models\\User', "_id", 'on' => 'userId'),
				"video" => array('one', 'app\\models\\Video', "_id", 'on' => 'videoId'),
				"reply_video" => array('one', 'app\\models\\Video', "_id", 'on' => 'replyVideoId'),
				'thread_parent' => array('one', 'app\\models\\VideoResponse', '_id', 'on' => 'threadParentId')
		);
	}

	public static function findAllComments($video, $ts_query = array())
	{
		return self::find(array('videoId' => $video->_id, 'created' => $ts_query));
	}

	public static function findPublicComments($video,  $ts_query = array(), $showOnlyModerated = false)
	{
		if($showOnlyModerated){
			return self::moderated()->find(array('videoId' => $video->_id));
		}else{
			return self::find(array('videoId' => $video->_id, 'created' => $ts_query))->visible();
		}
	}

	public function rules()
	{
		return array(
				array('videoId', 'required', 'message' => 'An unknown error occured. Try refreshing the page to fix this.'),
				array('videoId', 'exists',
						'class'=>'app\\models\\Video',
						'field'=>'_id',
						'allowNull' => true, 'message' => 'The video you are replying to might no longer exist. Either way we cannot seem to find it now'
				),
				array('content', 'required', 'on' => 'text_comment', 'message' => 'You must enter at least something into the comment field to post a text response.'),
				array('content', 'string', 'max' => '1500', 'message' => 'You can only write 1500 characters for a comment.'),

				array('replyVideoId', 'required', 'on' => 'video_comment', 'message' => 'You must specify a video to repond with'),
				array('replyVideoId', 'exists',
						'class'=>'app\\models\\Video',
						'field'=>'_id',
						'allowNull' => true,
						'on' => 'video_comment', 'message' => 'The video you selected cannot be validated. Please choose a different video.'
				),
				array('replyVideoId', 'checkAlreadyReply', 'message' => 'This video has already been used as a reply on this one.', 'on' => 'video_comment'),
				array('replyVideoId', 'checkSameVideo', 'message' => 'The same video being watched cannot be added as a reply.', 'on' => 'video_comment'),

				array('threadParentUsername', 'safe', 'on' => 'text_comment'),
				array('threadParentId', 'exists',
						'class'=>'app\\models\\VideoResponse',
						'field'=>'_id',
						'allowNull' => true,
						'on' => 'text_comment', 'message' => 'The comment you were replying to might no longer exist. Either way we cannot seem to find it now.'
				)
		);
	}

	public function checkAlreadyReply($field, $value, $params = array())
	{
		return !self::findOne(array('replyVideoId' => $value, 'videoId' => $this->videoId));
	}

	public function checkSameVideo($field, $value, $params = array())
	{
		return $this->replyVideoId != $this->videoId;
	}

	public function getThread()
	{
		/** $secondLevel = $this->Db()->find(array("path"=>new MongoRegex("/^".$path.",[^,]*,[^,]*$/")))->sort(array("seq"=>1)); // Second Level **/
		return self::find(array("path"=>new \MongoRegex("/^".$this->path.",[^,]*$/")))->sort(array("created"=>1)); // First Level
	}

	public function beforeSave()
	{
		if($this->getIsNewRecord()){
			$this->userId = $this->userId?:glue::user()->_id;

			if($this->video->moderated){
				$this->approved = !glue::auth()->check(array('^' => $this->video)) ? false : true;
			}else{
				$this->approved = true;
			}
			$this->type = 'text';
			$this->content = trim($this->content);

			// Build a path. There are some bugs in my active record stopping this from working in a better way
			$this->_id = new \MongoId(); // Set the id here since we don't actually have it yet, we'll send it down with the rest of the record
			
			if($this->thread_parent instanceof \app\models\VideoResponse){
				$this->path = rtrim($this->thread_parent->path.','.strval($this->_id),',');
			}else{
				$this->path = rtrim(strval($this->_id),',');
			}
		}
		return true;
	}

	public function afterSave()
	{
		if($this->getIsNewRecord()){
			$this->video->saveCounters(array('totalResponses' => 1, 'totalTextResponses' => 1));
			$this->video->recordStatistic('text_comments');
			
			if($this->thread_parent instanceof \app\models\VideoResponse){
				$parentIds = preg_split('/,/', $this->path);
				array_pop($parentIds); // Remove this comment
				array_walk($parentIds, function(&$n){
					$n = new \MongoId($n);
				});
				static::updateAll(array('_id' => array('$in' => $parentIds)), array('$inc' => array('replies' => 1)));
			}
			
			if($this->video->listing != 1 && $this->video->listing != 2){
				\app\models\Stream::commentedOn($this->userId, $this->videoId, $this->_id);
			}			

			if(!glue::auth()->check(array('^' => $this->video))){
				if($this->video->author->emailVideoResponses){
					glue::mailer()->mail($this->video->author->email, array('no-reply@stagex.co.uk', 'StageX'), 'Someone replied to one of you videos on StageX',
					"videos/new_comment.php", array( 'username' => $this->video->author->username, 'approved' => $this->approved,
					'comment' => $this, 'from' => $this->author, 'video' => $this->video ));
				}
				\app\models\Notification::newVideoResponse($this->video->userId, $this->video->_id, $this->approved);
			}
			if($this->threadParentId && $this->approved){
				if($this->thread_parent->author->emailVideoResponses){
					glue::mailer()->mail($this->thread_parent->author->email, array('no-reply@stagex.co.uk', 'StageX'), 'Someone replied to one of you comments on StageX',
					"videos/new_comment_reply.php", array( 'username' => $this->thread_parent->author->username,
					'comment' => $this, 'from' => $this->author, 'video' => $this->video ));
				}
				\app\models\Notification::newVideoResponseReply($this->thread_parent->_id, $this->thread_parent->userId, $this->_id, $this->video->_id);
			}
		}
		return true;
	}

	public function approve()
	{
		if(!$this->approved){
			$this->approved = true;
			$this->save();

			if($this->threadParentId){

				if(!glue::auth()->check(array('^' => $this->video)) && $this->thread_parent->author->emailVideoResponses){
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

	public function currentUserLikes()
	{
		return glue::db()->videoresponse_likes->findOne(array('userId' => glue::user()->_id, 'responseId' => $this->_id));
	}

	public function like()
	{
		glue::db()->videoresponse_likes->update(
		array("userId"=>glue::user()->_id, "responseId"=>$this->_id),
		array("userId"=>glue::user()->_id, "responseId"=>$this->_id, "weight"=>"+1", 'videoId' => $this->videoId, "ts" => new \MongoDate()),
		array("upsert"=>true)
		);

		$this->likes = $this->likes+1;
		$this->save();
		return true;
	}

	public function unlike()
	{
		glue::db()->videoresponse_likes->remove(array("userId"=>glue::user()->_id, "responseId"=>$this->_id));
		$this->likes = $this->likes-1;
		$this->save();
		return true;
	}

	public function delete()
	{
		$this->video->saveCounters(array('totalResponses'=>-1),0);
		glue::db()->videoresponse_likes->remove(array("responseId"=>$this->_id));
		parent::delete();
	}
}