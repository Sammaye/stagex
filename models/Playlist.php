<?php
namespace app\models;

use glue;

class Playlist extends \glue\db\Document{

	public $userId;

	public $title;
	public $description;

	/**
	 * 0 - Public
	 * 1 - Unlisted
	 * 2 - Private
	 */
	public $listing = 0; // public, unlisted or private
	public $allowEmbedding = 1;
	public $allowFollowers = 1;

	public $videos = array(); // For each video there is a _id, position and description

	public $followers = 0;
	public $totalVideos = 0;

	public $deleted = 0;

	public function collectionName(){
		return 'playlist';
	}

	function behaviours(){
		return array(
			'timestampBehaviour' => array(
				'class' => 'glue\\behaviours\\Timestamp'
			)
		);
	}

	function relations(){
		return array(
			"author" => array('one', 'app\\models\\User', "_id", 'on' => 'userId'),
		);
	}
	
	function defaultScope(){
		return array(
			'condition' => array('deleted'=>array('$in'=>0,null))		
		);
	}

	public static function model($class = __CLASS__){
		return parent::model($class);
	}

	function getRandomVideoPic(){

		if(count($this->videos) <= 0){
			if(isset(glue::$params['thumbnailBase'])){
				return 'images.stagex.co.uk/videos/_w_138_h_77.png';
			}else{
				return '/image/video?w=138&h=77';
			}
		}

		$video = Video::model()->findOne(array('_id' => $this->videos[0]['_id']));
		return $video->getImage(138, 77);
	}

	function get4Pics($large_width=124,$large_height=69,$small_width=44,$small_height=26){
		$pics = array(); $i = 0;

		if(count($this->videos) > 0){
			for($i, $size = count($this->videos) >= 4 ? 4 : count($this->videos); $i < $size; $i++){
				$video = Video::model()->findOne(array('_id' => $this->videos[$i]['_id']));

				if(!$video)
					$video = new Video;

				if($i==0){
					$pics[] = $video->getImage($large_width, $large_height);
				}else{
					$pics[] = $video->getImage($small_width, $small_height);
				}
			}
		}

		for($i; $i < 4; $i++){
			if($i==0)
				$pics[] = "/image/video?id=&w=$large_width&h=$large_height";
			else
				$pics[] = "/image/video?id=&w=$small_width&h=$small_height";
		}

		return $pics;
	}

	function addVideo($_id){
		foreach($this->videos as $k=>$v){
			if($v['_id'] == $_id)
				return; // Bail if the video exists
		}
		$this->videos[] = array('_id' => $_id, 'pos' => count($this->videos));
		$this->totalVideos++;
	}

	function add_video_at_pos($_id, $pos = 0){
		$this->videos[] = array('_id' => $_id, 'pos' => $pos);
	}

	function videoAlreadyAdded($_id){
		foreach($this->videos as $k=>$v){
			if($v['_id'] == $_id)
				return true; // Bail if the video exists
		}
		return false;
	}

	function get_sorted_videos(){
		$videos = $this->videos;

		foreach($videos as $k => $v){
			$video = Video::model()->findOne(array('_id' => $v['_id']));
			if($video){
				$videos[$k] = $video;
			}
		}
		return $videos;
	}

	function like(){
		$like_row = glue::db()->playlist_likes->findOne(array('userId' => glue::user()->_id, 'item' => $this->_id));

		if(!$like_row){
			glue::db()->playlist_likes->insert(array('userId' => glue::user()->_id, 'item' => $this->_id, 'like' => 1, 'ts' => new MongoDate()));
			$this->likes = $this->likes+1;
			$this->save();
		}
		return true;
	}

	function unlike(){
		$like_row = glue::db()->playlist_likes->findOne(array('userId' => glue::user()->_id, 'item' => $this->_id));

		if($like_row){
			glue::db()->playlist_likes->remove(array('userId' => glue::user()->_id, 'item' => $this->_id));
			$this->likes = $this->likes-1;
			$this->save();
		}
		return true;
	}

	function current_user_likes(){
		$like_row = glue::db()->playlist_likes->findOne(array('userId' => glue::session()->user->_id, 'item' => $this->_id));
		if($like_row)
			return true;

		return false;
	}

	function rules(){
		return array(
			array('title', 'required', 'message' => 'You must enter a title for your playlist.', 'on' => 'insert'),
			//array('description', 'safe'),

			array('title', 'string', 'max' => '80', 'message' => 'You can only write 80 characters for the title.'),
			array('description', 'string', 'max' => '1500', 'message' => 'You can only write 1500 characters for the description.'),

			//array('listing, allow_embedding, allow_like', 'safe', 'on' => 'update'),
			array('listing', 'in', 'range' => array(0, 1, 2), 'on' => 'update', 'message' => 'You must enter a valid listing'),
			array('allowEmbedding, allowLike', 'boolean', 'allowNull' => true, 'on' => 'update'),
		);
	}

	function beforeSave(){
		if($this->getIsNewRecord() && !$this->userId){
			$this->userId = glue::user()->_id;
		}
		return true;
	}

	function afterSave(){
		if($this->getIsNewRecord()){
			$this->author->saveCounters(array('totalPlaylists'=>1));
			glue::sitemap()->addUrl(glue::http()->url('/playlist/view', array('id' => $this->_id)), 'hourly', '1.0');
		}
		
		glue::mysql()->query("INSERT INTO documents (_id, uid, listing, title, description, tags, author_name, type, videos, date_uploaded)
			VALUES (:_id, :uid, :listing, :title, :description, null, :author_name, :type, :videos, now()) ON DUPLICATE KEY UPDATE uid = :uid,
			deleted = :deleted, listing = :listing, title = :title, description = :description, author_name = :author_name, videos = :videos", array(
			":_id" => strval($this->_id),
			":uid" => strval($this->userId),
			":deleted" => $this->deleted,			
			":listing" => $this->listing,
			":title" => $this->title,
			":description" => $this->description,
			":type" => "playlist",
			":videos" => count($this->videos),
			":author_name" => glue::user()->username,
		));		
		return true;
	}

	function delete(){
		glue::mysql()->query("UPDATE documents SET deleted = 1 WHERE _id = :id",array(':id'=>strval($this->_id)));
		parent::delete();
		
		// @todo fix this
		//glue::db()->playlist_likes->remove(array('item' => $playlist->_id));		
		
		$this->author->saveCounters(array('totalPlaylists'=>-1),0);
		return true;
	}
	
	function user_is_subscribed($user){
		return glue::db()->playlist_subscription->findOne(array('user_id' => $user->_id, 'playlist_id' => $this->_id))!==null;
	}
}