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
	public $allowLike = 1;

	public $videos = array(); // For each video there is a _id, position and description

	public $likes = 0;
	public $totalVideos = 0;

	public $deleted = 0;

	public function collectionName(){
		return 'playlists';
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

	function get4Pics(){
		$pics = array(); $i = 0;

		if(count($this->videos) > 0){
			for($i, $size = count($this->videos) >= 4 ? 4 : count($this->videos); $i < $size; $i++){
				$video = Video::model()->findOne(array('_id' => $this->videos[$i]['_id']));

				if(!$video)
					$video = new Video;

				if($i==0){
					$pics[] = $video->getImage(138, 77);
				}else{
					$pics[] = $video->getImage(44, 26);
				}
			}
		}

		for($i; $i < 4; $i++){
			if($i==0)
				$pics[] = '/image/video?id=&w=138&h=77';
			else
				$pics[] = '/image/video?id=&w=44&h=26';
		}

		return $pics;
	}

	function add_video($_id){
		foreach($this->videos as $k=>$v){
			if($v['_id'] == $_id)
				return; // Bail if the video exists
		}
		$this->videos[] = array('_id' => $_id, 'pos' => count($this->videos));
	}

	function add_video_at_pos($_id, $pos = 0){
		$this->videos[] = array('_id' => $_id, 'pos' => $pos);
	}

	function video_already_added($_id){
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
			array('listing', 'in', 'range' => array(1, 2, 3), 'on' => 'update', 'message' => 'You must enter a valid listing'),
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
		if($this->listing==0){
			if($this->getIsNewRecord()){

				//$this->author->total_playlists = $this->author->total_playlists+1;
				//$this->author->save();

				glue::mysql()->query("INSERT INTO documents (_id, uid, listing, title, description, tags, author_name, type, videos, date_uploaded)
					VALUES (:_id, :uid, :listing, :title, :description, null, :author_name, :type, :videos, now())", array(
					":_id" => strval($this->_id),
					":uid" => strval($this->user_id),
					":listing" => $this->listing,
					":title" => $this->title,
					":description" => $this->description,
					":type" => "playlist",
					":videos" => count($this->videos),
					":author_name" => glue::user()->username,
				));

				glue::sitemap()->addUrl(glue::url()->create('/playlist/view', array('id' => $this->_id)), 'hourly', '1.0');

			}else{
				glue::mysql()->query("UPDATE documents SET uid = :uid, deleted = :deleted, listing = :listing, title = :title, description = :description,
						author_name = :author_name, videos = :videos WHERE _id = :_id", array(
					":_id" => strval($this->_id),
					":uid" => strval($this->userId),
					":deleted" => $this->deleted,
					":listing" => $this->listing,
					":title" => $this->title,
					":description" => $this->description,
					":author_name" => glue::user()->username,
					":videos" => count($this->videos)
				));
			}
		}
		return true;
	}

	function delete(){
		glue::mysql()->findOne("UPDATE documents SET deleted = 1 WHERE _id = :_id");
		$this->remove(array('_id' => $this->_id));

		//$this->author->total_playlists = $this->author->total_playlists-1;
		return true;
	}
}