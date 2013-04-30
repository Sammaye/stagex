<?php
class AutoPublishStream extends MongoDocument{

	// When a user uploads a video
	const UPLOAD = 'UPLOAD';

	// When a user responds to a video
	const V_RES = 'V_RES';

	// When a user likes a video
	const LK_V = 'LK_V';

	// When a user dislikes a video
	const DL_V = 'DL_V';

	// When a user likes a playlist
	const LK_PL = 'LK_PL';

	// When a user adds a video to a playlist
	const PL_V_ADDED = 'PL_V_ADDED';

	protected $type;

	protected $user_id;
	protected $pl_id;
	protected $v_id;

	protected $text;

	protected $processing = 0;
	protected $done = 0;

	protected $ts;

	public static function model($className = __CLASS__){
		return parent::model($className);
	}

	function getCollectionName(){
		return "auto_publish_stream";
	}

	public function beforeSave(){
		if($this->getIsNewRecord()){
			$this->ts = new MongoDate();
		}
		return true;
	}

	static function add_to_qeue($type, $user_id, $video_id = null, $playlist_id = null, $text = null){
		$oldDoc = AutoPublishStream::model()->findOne(array('type' => $type, 'user_id' => $user_id, 'v_id' => $video_id, 'pl_id' => $playlist_id, 'text' => $text));

		// If oldDoc is filled then we dont do shit else lets add this as something that needs syncing
		// This will help prevent duplicate items on the users feed which, let's face it, is a pain in the ass
		if(!$oldDoc){
			$item = new AutoPublishStream();
			$item->type = $type;
			$item->user_id = $user_id;
			$item->v_id = $video_id;
			$item->pl_id = $playlist_id;
			$item->text = $text;

			$item->save();
		}
	}
}