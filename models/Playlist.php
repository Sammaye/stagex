<?php
namespace app\models;

use glue;
use glue\db\Document;

class Playlist extends Document
{
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

	public static function collectionName()
	{
		return 'playlist';
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
		);
	}
	
	public function defaultScope()
	{
		return array(
			'condition' => array('deleted' => array('$in' => 0, null))
		);
	}
	
	public static function advancedSearchRules()
	{
		return array(
			'title' => array('string', '\s+'),
			'description' => array('string', '\s+'),
			'listing' => array('int', ','),
			'created' => array('date', '\s+(TO|to)\s+')
		);
	}	

	public function getRandomVideoPic()
	{
		if(count($this->videos) <= 0){
			if(isset(glue::$params['thumbnailBase'])){
				return 'images.stagex.co.uk/videos/_w_138_h_77.png';
			}else{
				return '/image/video?w=138&h=77';
			}
		}

		$video = Video::findOne(array('_id' => $this->videos[0]['_id']));
		return $video->getImage(138, 77);
	}

	public function get4Pics($large_width=124,$large_height=69,$small_width=44,$small_height=26)
	{
		$pics = array(); $i = 0;

		if(count($this->videos) > 0){
			for($i, $size = count($this->videos) >= 4 ? 4 : count($this->videos); $i < $size; $i++){
				$video = Video::findOne(array('_id' => $this->videos[$i]['_id']));

				if(!$video){
					$video = new Video;
				}

				if($i == 0){
					$pics[] = $video->getImage($large_width, $large_height);
				}else{
					$pics[] = $video->getImage($small_width, $small_height);
				}
			}
		}

		for($i; $i < 4; $i++){
			if($i==0){
				$pics[] = "/image/video?id=&w=$large_width&h=$large_height";
			}else{
				$pics[] = "/image/video?id=&w=$small_width&h=$small_height";
			}
		}
		return $pics;
	}

	public function addVideo($_id)
	{
		foreach($this->videos as $k=>$v){
			if($v['_id'] == $_id){
				return; // Bail if the video exists
			}
		}
		$this->videos[] = array('_id' => $_id, 'pos' => count($this->videos));
		$this->totalVideos++;
	}

	public function addVideoAtPos($_id, $pos = 0)
	{
		$this->videos[] = array('_id' => $_id, 'pos' => $pos);
	}

	public function videoAlreadyAdded($_id)
	{
		foreach($this->videos as $k=>$v){
			if($v['_id'] == $_id){
				return true; // Bail if the video exists
			}
		}
		return false;
	}

	public function getSortedVideos()
	{
		$videos = $this->videos;

		foreach($videos as $k => $v){
			$video = Video::findOne(array('_id' => $v['_id']));
			if($video){
				$videos[$k] = $video;
			}
		}
		return $videos;
	}

	public function like()
	{
		$like_row = glue::db()->playlist_likes->findOne(array('userId' => glue::user()->_id, 'item' => $this->_id));

		if(!$like_row){
			glue::db()->playlist_likes->insert(array('userId' => glue::user()->_id, 'item' => $this->_id, 'like' => 1, 'ts' => new MongoDate()));
			$this->likes = $this->likes+1;
			$this->save();
		}
		return true;
	}

	public function unlike()
	{
		$like_row = glue::db()->playlist_likes->findOne(array('userId' => glue::user()->_id, 'item' => $this->_id));

		if($like_row){
			glue::db()->playlist_likes->remove(array('userId' => glue::user()->_id, 'item' => $this->_id));
			$this->likes = $this->likes-1;
			$this->save();
		}
		return true;
	}

	public function rules()
	{
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

	public function beforeSave()
	{
		if($this->getIsNewRecord() && !$this->userId){
			$this->userId = glue::user()->_id;
		}
		return true;
	}

	public function afterSave()
	{
		//var_dump(strval($this->userId));
		glue::elasticSearch()->index(array(
    		'id' => strval($this->_id),
    		'type' => 'playlist',
    		'body' => array(
        		'title' => $this->title,
        		'blurb' => $this->description,
        		'deleted' => $this->deleted,
        		'listing' => $this->listing,
        		'videos' => count($this->videos),
        		'userId' => strval($this->userId),
        		'username' => $this->author->getUsername(),
        		'mature' => 0,
        		'created' => date('c',$this->created->sec)
    		)
		));		
		return true;
	}

	public function delete()
	{
		glue::elasticSearch()->delete(array(
			'id' => $this->_id,
			'type' => 'playlist'
		));		
		parent::delete();
		
		// @todo fix this
		//glue::db()->playlist_likes->remove(array('item' => $playlist->_id));		
		
		$this->author->saveCounters(array('totalPlaylists'=>-1),0);
		return true;
	}
	
	public function getSubscription($user)
	{
		return glue::db()->playlist_subscription->findOne(array('user_id' => $user->_id, 'playlist_id' => $this->_id));
	}
}