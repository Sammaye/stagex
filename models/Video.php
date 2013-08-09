<?php
namespace app\models;

use glue;

class Video extends \glue\Db\Document{
	
	/** @virtual */
	public $response = array(); // I am unsure about this var. I don't like the way it stares at me
	/** @virtual */
	public $stringTags; // To hold the non-persistent representation of the tags field

	/**
	 * This var denotes the public view of the video
	 *
	 * - 0 = public
	 * - 1 = hidden
	 * - 2 = private
	 *
	 * @var int $listing
	 */
	public $listing = 0;
	public $title;
	public $description;
	public $licence = 1;
	public $category = 11;
	public $tags;

	public $voteable = 1;
	public $embeddable = 1;
	public $moderated = 0;
	public $voteableComments = 1;
	public $allowVideoComments = 1;
	public $allowTextComments = 1;
	public $privateStatistics = 0;
	public $mature = 0;

	public $duration;
	public $fileSize;

	public $uniqueViews = 0;
	public $views = 0;
	public $likes = 0;
	public $dislikes = 0;
	public $totalResponses = 0;
	public $totalVideoResponses = 0;
	public $totalTextReponses = 0;

	public $md5;
	public $jobId;
	public $uploadId;
	public $userId;

	/**
	 * This denotes the state of the video (as to whether it is failed or done etc)
	 *
	 * It has 4 states:
	 *
	 *  - uploading (This is used for S3)
	 *  - pending (Waiting to be submitted)
	 *  - submitting (Actively being sent)
	 *  - transcoding (Sent to Encoder)
	 *  - finished (Whoop)
	 *  - failed ( :( )
	 *
	 * @var $state
	 */
	public $state;
	public $stateReason;

	public $original;
	public $mp4;
	public $ogg;
	public $image;
	public $imageSrc;

	public $deleted = 0;

	function behaviours(){
		return array(
			'timestampBehaviour' => array(
				'class' => 'glue\\behaviours\\Timestamp'
			)
		);
	}

	function collectionName(){
		return "video";
	}

	function categories($pivot = 'all'){
		$cats = array(
			'cars' => array('Cars and Vehicles', 0),
			'comedy' => array('Comedy', 1),
			'edu' => array('Education', 2),
			'ent' => array('Entertainment', 3),
			'film' => array('Film and Animation', 4),
			'gaming' => array('Gaming', 5),
			'howto' => array('Howto and Tutorials', 6),
			'style' => array('Style and Fashion', 7),
			'music' => array('Music', 8),
			'news' => array('News and Politics', 9),
			'nonprofit' => array('Non-profit and Activism', 10),
			'people' => array('People and Blogs', 11),
			'pets' => array('Pets and Animals', 12),
			'science' => array('Science and Technology', 13),
			'sport' => array('Sport', 14),
			'travel' => array('Travel and Events', 15)
		);

		if($pivot == 'selectBox'){
			$dropdownList = array();
			foreach($cats as $k => $v){
				$dropdownList[$v[1]] = $v[0];
			}
			return $dropdownList;
		}elseif($pivot == 'values'){
			$list = array();
			foreach($cats as $k => $v){
				$list[] = $v[1];
			}
			return $list;
		}else{
			return $cats;
		}
	}

	function licences($index=null){
		
		$d=array(
			1 => "StageX Licence",
			2 => "Creative Commons Licence (Re-use Allowed)"
		);
		if($index===null)
			return $d;
		else
			return isset($d[$index]) ? $d[$index] : null;
	}

	function response($k = null, $v = null){

		if(!$this->response) $this->response = array();

		if($k && $v){
			$this->response[$k] = $v;
		}elseif($k && !$v){
			return isset($this->response[$k]) ? $this->response[$k] : null;
		}
		return $this->response;
	}

	public static function model($className = __CLASS__){
		return parent::model($className);
	}

	function isPublic(){
		return $this->listing == 0;
	}

	function isUnlisted(){
		return $this->listing == 1;
	}

	function isPrivate(){
		return $this->listing == 2;
	}

	function isProcessing(){
		return $this->state == 'pending' || $this->state == 'submitting' || $this->state == 'transcoding';
	}

	function relations(){
		return array(
			"author" => array('one', 'app\models\User', "_id", 'on' => 'userId'),
			"responses" => array('many', 'app\models\VideoResponse', "videoId", 'on' => '_id'),
		);
	}

	function rules(){
		return array(
			array('title', 'required', 'message' => 'You must provide a title'),
			array('title', 'string', 'max' => '75', 'message' => 'You can only write 75 characters for the title'),

			array('description', 'string', 'max' => '1500', 'message' => 'You can only write 1500 characters for the description'),
				
			array('category', 'in', 'range' => $this->categories('values'), 'message' => 'You must provide a valid category'),
			array('licence', 'in', 'range' => array(1, 2), 'message' => 'You must provide a licence type'),
				
			array('listing', 'in', 'range' => array(0, 1, 2), 'message' => 'You must select a valid listing of either public, unlisted or private'),
			array('voteable, embeddable, privateStatistics, voteableComments, allowVideoComments, allowTextComments, mature, moderated', 'boolean', 'allowNull' => true),
			
			array('stringTags', 'tokenized', 'max' => 10, 'message' => 'You can add upto 10 tags, no more')
		);
	}
	
	public function init(){
		$this->populateDefaults();
	}

	function afterFind(){
		if(is_array($this->tags)){
			$this->stringTags = implode(',', $this->tags);
		}
		return true;
	}
	
	public function populateDefaults(){
		if(glue::session()->authed){
			$defaults=glue::user()->defaultVideoSettings;
			foreach($defaults as $k => $v)
				$this->$k=$v;
		}
	}
	
	function setImage($bytes){
		$ref=\MongoDBRef::create('video',$this->_id);
		if(
				$bytes &&
				Image::saveAsSize($ref, $bytes, 800, 600, true) &&
				Image::saveAsSize($ref, $bytes, 33, 18) &&
				Image::saveAsSize($ref, $bytes, 44, 26) &&
				Image::saveAsSize($ref, $bytes, 124, 69) &&
				Image::saveAsSize($ref, $bytes, 138, 77) &&
				Image::saveAsSize($ref, $bytes, 234, 130)
		){
			return true;
		}
		return false;
	}	

	function getImage($width, $height){
		if(isset(glue::$params['thumbnailBase'])){
			return 'http://'.glue::$params['thumbnailBase'].strval($this->_id).'_w_'.$width.'_h_'.$height.'.png';
		}else{
			return Glue::http()->url("/image/video", array(
				'file' => strval($this->_id),
				"w"=>$width,
				"h"=>$height
			));
		}
	}

	function upload($id){

		$file=new \glue\File(array('model'=>null,'id'=>$id));
		if(strlen($file->tmp_name)<=0){
			$this->response("ERROR", "NOT_VALID");
			return false;
		}
	
		if(glue::user()->bandwidthLeft < $_FILES[$id]['size']){
			unlink($file->tmp_name); // Free up space in our temp dir
			$this->response("ERROR", "NOT_ENOUGH_SP");
			return false;
		}

		// Does ffmpeg think this is a real file??
		exec(sprintf('ffmpeg -i "'.$file->tmp_name.'" 2>&1'), $output);
		$ffmpeg_details = implode("\r", $output);
		if(!preg_match('!Duration: ([0-9:.]*)[, ]!', $ffmpeg_details) || preg_match('!Duration: ([0-9:.]*)[, ]!', $ffmpeg_details) <= 0){
			unlink($file->tmp_name); // Free up space in our temp dir
			// FAIL the file might not be a video
			$this->response("ERROR", "NOT_VALID");
			return false;
		}

		glue::user()->saveCounters(array('bandwidthLeft'=>-$file->size));
		
		// Now lets make a new video
		$this->populateDefaults();
		/*
		 *	This takes the form from the upload page and places the data in
		 */
		if(isset($_SESSION['_upload'][$id]) && is_array($_SESSION['_upload'][$id])){
			foreach($_SESSION['_upload'][$id] as $k=>$v){
				$this->$k = $v;
			}
		}		
		$this->userId = glue::user()->_id;
		$this->fileSize = $file->size;
		$this->title = substr(substr($file->name, 0, strrpos($file->name, '.')), 0, 75);
		$this->md5 = md5_file($file->tmp_name);
		$this->uploadId = $id;
		$this->created = new \MongoDate();
		$this->state = 'uploading';

		if(
			($matched_video=self::model()->findOne(array('md5' => $this->md5))) 
			&& $matched_video->state == 'finished'
		){
			$this->duration = $matched_video->duration;
			$this->state = $matched_video->state;
			$this->original = $matched_video->original;
			$this->mp4 = $matched_video->mp4;
			$this->ogg = $matched_video->ogg;
			$this->image = $matched_video->image;
			$this->imageSrc = $matched_video->imageSrc;
			$this->jobId = $matched_video->jobId;
		}else{
			//$this->deleted = 1; // Mark this as deleted to stop it from showing
		}
		$this->save(); // We save now to stop race conditions

		if(!$matched_video||$matched_video->state!='finished'){
			// Lets transfer to S3
			$file_name = new \MongoId().'.'.pathinfo($file->name, PATHINFO_EXTENSION);
			if(glue::aws()->S3Upload($file_name, array('Body' => fopen($file->tmp_name, 'r+')))){
				$this->original = glue::aws()->getS3ObjectURL($file_name);
				unlink($file->tmp_name); // Free up space in our temp dir

				/*
				 * I create and insert a new job here into Mongo. This is the easiest way by far
				 * to keep track of encoding over possibly many videos and many outputs and also to keep track of which
				 * videos received an AWS cURL error while trying to send messages
				 */
				$job = array('jobId' => md5( uniqid( rand(1,255).rand(45,80).rand(112,350), true ) )); // Pre-pop the job with an id

				// So lets send the command to SQS now
				$img_submit = glue::aws()->sendEncodingMessage($file_name, $job['jobId'], 'img');
				$mp4_submit = glue::aws()->sendEncodingMessage($file_name, $job['jobId'], 'mp4');
				$ogv_submit = glue::aws()->sendEncodingMessage($file_name, $job['jobId'], 'ogv');

				if($img_submit && $mp4_submit && $ogv_submit){
					$state = 'transcoding';
				}else{
					$state = 'pending';
				}

				$this->updateAll(array('_id' => $this->_id), array('$set' => array('state' => $state, 'jobId' => $job['jobId'])));
				glue::db()->encoding_jobs->insert(array_merge(
					$job, 
					array('file_name' => $file_name, 'img_submit' => $img_submit, 'mp4_submit' => $mp4_submit,
					'ogv_submit' => $ogv_submit, 'state' => $state, 'ts'=>new \MongoDate())
				));
				
				// technically the video is now there so lets inc the total uploads.
				glue::user()->saveCounters(array('totalUploads'=>1));
			}else{
				
				// FAIL
				$this->updateAll(array('_id' => $this->_id), array('$set' => array('state' => 'failed')));
				$this->response("ERROR", "UNKNOWN");
				return false;
			}
		}
	}

	function beforeSave(){
		if(strlen(strip_whitespace($this->stringTags)) > 0){
			$this->tags = preg_split("/[\s]*[,][\s]*/", $this->stringTags);

			for($i=0;$i<count($this->tags);$i++){
				$this->tags[$i] = strip_whitespace($this->tags[$i]);
			}
		}else{
			unset($this->tags);
		}
		return true;
	}

	function afterSave(){

		if($this->state == 'finished'){ // Only put it into the search index if it's done

			if($this->getIsNewRecord()){
				$query = "INSERT INTO documents (_id,uid,deleted,listing,title,description,category,tags,author_name,duration,views,rating,type,adult,date_uploaded)
							VALUES(:_id,:uid,:deleted,:listing,:title,:description,:cat,:tags,:author_name,:duration,:views,:rating,:type,:adult,now())";
			}else{
				$query = "UPDATE documents SET _id = :_id, uid = :uid, deleted = :deleted, listing = :listing, title = :title, description = :description, category = :cat,
						tags = :tags, author_name = :author_name, duration = :duration, views = :views, rating = :rating, type = :type, adult = :adult WHERE _id = :_id";
			}

			glue::mysql()->query($query, array(
				":_id" => strval($this->_id),
				":uid" => strval($this->user_id),
				":deleted" => $this->deleted,
				":listing" => $this->listing,
				":title" => $this->title,
				":description" => $this->description,
				":cat" => $this->category,
				":tags" => $this->stringTags,
				":duration" => $this->duration,
				":rating" => $this->likes - $this->dislikes,
				":views" => $this->views,
				":type" => "video",
				":adult" => $this->mature,
				":author_name" => $this->author->username,
			));
		}
		return true;
	}
	
	function search($keyword='',$query=false){
		
		$sphinx=glue::sphinx()
				->index('main')
				->match(array('title', 'description', 'tags', 'author_name'),glue::http()->param('q',$keyword))
				->match('type','video')
				->filter(array(
						array('deleted', array(1), true), 
						array('listing', array(0, 1), true),
				));
		
		// Since this will be used for public sorting I think this always applies		
		if(glue::user()->safeSearch || !glue::session()->authed){
			$sphinx->filter('adult', array(1), true);
		}				
				
		if($query)
			return $cursor=$sphinx->query();
		else
			return $sphinx;
	}

	/**
	 * Document structure:
	 *
	 * {
	 *   _id: {},
	 *   sid: {},
	 *   vid: {},
	 *   hits: 0,
	 *   u_hits: 0,
	 *   hours: [
	 *   	0:{v:5,u:9},
	 *   	1:{v:3,u:0},
	 *   ],
	 *   browser: {
	 *   	chrome: 1,
	 *   	ie: 2
	 *   },
	 *   age: {
	 *   	13_16: 1,
	 *   	17_25: 2
	 *   },
	 *   v_comments: 0,
	 *   t_comments: 0,
	 *   likes: 0,
	 *   dislikes: 0,
	 *   age_total: 0,
	 *   browser_total: 0,
	 *   male: 0,
	 *   female: 0,
	 *   day: 0
	 * }
	 */
	function recordHit(){

		if(!glue::http()->is_search_bot($_SERVER['HTTP_USER_AGENT'])){ // Is the user a search bot? is so we don't want to add them

			$user = glue::session()->user;
			$bname=glue::http()->getMajorBrowserName();

	        $age_key = 'u';
	        if(glue::session()->authed){
		        ///Other, 13-16, 17-25, 26-35, 36-50, 50+
				$age_diff = (time() - glue::user()->getBirthdayTime())/(60*60*24*365);
				if($age_diff > 12 && $age_diff < 17)
					$age_key = '13_16';
				if($age_diff > 17 && $age_diff < 26)
					$age_key = '17_25';
				if($age_diff > 25 && $age_diff < 36)
					$age_key = '26_35';
				if($age_diff > 35 && $age_diff < 51)
					$age_key = '36_50';
				if($age_diff > 50)
					$age_key = '50_plus';
	        }

			$resp = !glue::db()->video_statistics->update(array(
				"sid" => glue::user()->_id instanceof \MongoId ? glue::user()->_id : session_id(),
				"vid" => $this->_id
			), array('$setOnInsert'=>array('ts'=>new \MongoDate())), array('upsert'=>true));
			$is_unique=!$resp['updatedExisting'];

			$doc = array( '$inc' => array( 'hits' => 1 ) );
			if($is_unique){
				$doc['$inc']['u_hits'] = 1;
				$doc['$inc']['age.'.$age_key] = 1;
				$doc['$inc']['browser.'.$bname] = 1;

				// These are used to make my life a little easier
				$doc['$inc']['age_total'] = 1;
				$doc['$inc']['browser_total'] = 1;

				if(glue::user()->gender == 'm'){
					$doc['$inc']['male'] = 1;
				}elseif(glue::user()->gender == 'f'){
					$doc['$inc']['female'] = 1;
				}
			}

			$doc['$inc']['hours.'.date('G').'.v'] = 1;
			if($is_unique) $doc['$inc']['hours.'.date('G').'.u'] = 1;

			glue::db()->video_statistics_day->update(array("day"=>new \MongoDate(mktime(0, 0, 0, date("m"), date("d"), date("Y"))), "vid"=>$this->_id), 
				$doc, array("upsert"=>true));

			if($is_unique) $this->uniqueViews = $this->uniqueViews+1;
			$this->views = $this->views+1;
			$this->save();

			// Now lets do some referers
			$referer = glue::http()->getNormalisedReferer();
			if($referer)
				glue::db()->video_referers->update(array('video_id' => $this->_id, 'referer' => $referer), array('$inc' => array('c' => 1),
					'$setOnInsert' => array('ts' => new \MongoDate())), array('upsert' => true));
		}
	}

	function getStatistics_dateRange($fromTs /* d-m-Y */, $toTs /* d-m-Y */){
//var_dump($toTs);
		$unique_views_range = array();
		$non_unique_views_range = array();

		// These totals make my percentage calcing life easier
		$total_browsers = 0;
		$total_ages = 0;

		$sum_browser = array();
		$sum_ages = array();
		$sum_video_comments = 0;
		$sum_text_comments = 0;
		$sum_video_likes = 0;
		$sum_video_dislikes = 0;

		$sum_males = 0;
		$sum_females = 0;

		if($fromTs > $toTs){
			$dateFrom = mktime(0, 0, 0, date("m"), date("d")-7, date("Y"));
			$dateTo = time();
		}
//var_dump($toTs-strtotime('+5 days'));

		if($fromTs < strtotime('-4 days', $toTs)){ // Else I am doing days if there is more than one
//echo "here";
		  	$newts = $fromTs;
			while ($newts <= $toTs) {
				//$newts += 86400;
				$unique_views_range[$newts] = 0;
				$non_unique_views_range[$newts] = 0;
				$newts = mktime(0,0,0, date('m', $newts), date('d', $newts)+1, date('Y', $newts));
			}

			//$unique_views_range[mktime(0,0,0, date('m', $newts), date('d', $newts)+1, date('Y', $newts))] = 0;
			//$non_unique_views_range[mktime(0,0,0, date('m', $newts), date('d', $newts)+1, date('Y', $newts))] = 0;

			foreach(glue::db()->video_statistics_day->find(array(
				"vid"=>$this->_id,
				"day"=> array("\$gte" => new \MongoDate($fromTs), "\$lte" => new \MongoDate($toTs) ),
			)) as $day){
				//var_dump($day);
				$non_unique_views_range[$day['day']->sec] = !empty($day['hits']) ? $day['hits'] : 0;
				$unique_views_range[$day['day']->sec] = !empty($day['u_hits']) ? $day['u_hits'] : 0;

				$sum_browser = \Collection::aggregate(isset($day['browser']) ? $day['browser'] : array(), $sum_browser);
				$sum_ages = \Collection::aggregate(isset($day['age']) ? $day['age'] : array(), $sum_ages);

				$total_browsers += isset($day['browser_total']) ? (int)$day['browser_total'] : 0;
				$total_ages += isset($day['age_total']) ? (int)$day['age_total'] : 0;

				$sum_video_comments += isset($day['video_comments']) ? (int)$day['video_comments'] : 0;
				$sum_text_comments += isset($day['text_comments']) ? (int)$day['text_comments'] : 0;
				$sum_video_likes += isset($day['video_likes']) ? (int)$day['video_likes'] : 0;
				$sum_video_dislikes += isset($day['video_dislikes']) ? (int)$day['video_dislikes'] : 0;

				$sum_males += isset($day['male']) ? (int)$day['male'] : 0;
				$sum_females += isset($day['female']) ? (int)$day['female'] : 0;
			}

		}else{ // else obviously I am doing over a single day
			//echo "here";
			$newts = $fromTs;
			while($newts < $toTs){
				$newts = $newts+(60*60);
				$unique_views_range[$newts] = 0;
				$non_unique_views_range[$newts] = 0;
			}
			//var_dump($unique_views_range);
//var_dump($toTs);
			foreach(glue::db()->video_statistics_day->find(array(
				"vid"=>$this->_id,
				"day"=> array("\$gte" => new \MongoDate($fromTs), "\$lte" => new MongoDate($toTs) ),
			)) as $day){
				//var_dump($day);
				foreach($day['hours'] as $k => $v){
					$k = $k+1;
					$non_unique_views_range[mktime($k, 0, 0, date('m', $day['day']->sec), date('d', $day['day']->sec), date('Y', $day['day']->sec))] = !empty($v['v']) ? $v['v'] : 0;
					$unique_views_range[mktime($k, 0, 0, date('m', $day['day']->sec), date('d', $day['day']->sec), date('Y', $day['day']->sec))] = !empty($v['u']) ? $v['u'] : 0;
				}

				$sum_browser = \Collection::aggregate(isset($day['browser']) ? $day['browser'] : array(), $sum_browser);
				$sum_ages = \Collection::aggregate(isset($day['age']) ? $day['age'] : array(), $sum_ages);

				$total_browsers += isset($day['browser_total']) ? (int)$day['browser_total'] : 0;
				$total_ages += isset($day['age_total']) ? (int)$day['age_total'] : 0;

				$sum_video_comments += isset($day['video_comments']) ? (int)$day['video_comments'] : 0;
				$sum_text_comments += isset($day['text_comments']) ? (int)$day['text_comments'] : 0;
				$sum_video_likes += isset($day['video_likes']) ? (int)$day['video_likes'] : 0;
				$sum_video_dislikes += isset($day['video_dislikes']) ? (int)$day['video_dislikes'] : 0;

				$sum_males += isset($day['male']) ? (int)$day['male'] : 0;
				$sum_females += isset($day['female']) ? (int)$day['female'] : 0;
			}
		}
//var_dump($sum_males);
		// Now lets get the browser crap
		$browsers_highCharts_array = array();
		$u_brows_capt = 'Other';
		foreach($sum_browser as $k => $sum){
			if($k =='ie'){
				$u_brows_capt = "IE";
			}elseif($k == 'ff'){
				$u_brows_capt = "Firefox";
			}elseif($k == 'chrome'){
				$u_brows_capt = "Chrome";
			}elseif($k == 'safari'){
				$u_brows_capt = "Safari";
			}elseif($k == 'opera'){
				$u_brows_capt = "Opera";
			}elseif($k == 'netscape'){
				$u_brows_capt = "Netscape";
			}
			$browsers_highCharts_array[] = array($u_brows_capt, ($sum/$total_browsers)*100);
		}

		$ages_highCharts_array = array();
		$u_age_capt = 'Unknown';
		foreach($sum_ages as $k => $sum){
			if($k == '13_16'){
				$u_age_capt = '13-16';
			}elseif($k == '17_25'){
				$u_age_capt = '17-25';
			}elseif($k == '26_35'){
				$u_age_capt = '26-35';
			}elseif($k == '36_50'){
				$u_age_capt = '36-50';
			}elseif($k == '50_plus'){
				$u_age_capt = '50+';
			}
			$ages_highCharts_array[] = array($u_age_capt, ($sum/$total_ages)*100);
		}

		if(count($ages_highCharts_array) <= 0){
			$ages_highCharts_array = array(array('None', 100));
		}

		if(count($browsers_highCharts_array) <= 0){
			$browsers_highCharts_array = array(array('None', 100));
		}

		$total_males_females = $sum_males+$sum_females;

		return array(
			'hits' => $this->formatHighChart(array(
				"Views"=>$non_unique_views_range,
				"Unique Views"=>$unique_views_range
			)),
			'browsers' => $browsers_highCharts_array,
			'ages' => $ages_highCharts_array,
			'video_comments' => $sum_video_comments,
			'text_comments' => $sum_text_comments,
			'video_likes' => $sum_video_likes,
			'video_dislikes' => $sum_video_dislikes,
			'males' => $sum_males > 0 ? number_format(($total_males_females/$sum_males)*100, 0) : 0,
			'females' => $sum_females > 0 ? number_format(($total_males_females/$sum_females)*100, 0) : 0
		);
	}

	function formatHighChart($seriesRanges = array()){
		$allSeries = array();
//var_dump($non_unique_views_range);
		if($seriesRanges){
			foreach($seriesRanges as $seriesName => $series){
				$seriesValues = array();

				foreach($series as $key=>$entry){
					$seriesValues[] = array($key*1000, $entry); // HighChart needs the ts in milliseconds
				}

				$allSeries[] = array(
					'name' => $seriesName,
					'data' => $seriesValues
				);
			}
		}
		return $allSeries;
	}

	function renderTags(){
		if(is_array($this->tags)){
			return implode(", ", $this->tags);
		}
	}

	function get_category_text(){
		$categories = $this->categories('selectBox');
		return $categories[$this->category];
	}

	function userHasWatched(){
		$r = glue::db()->watched_history->findOne(array('user_id' => glue::user()->_id, 'item' => $this->_id));
		if($r) return true;
		return false;
	}

	function currentUserLikes(){
		$r = glue::db()->video_likes->findOne(array('user_id' => glue::user()->_id, 'item' => $this->_id, 'like' => 1));
		if($r) return true;
		return false;
	}

	function currentUserDislikes(){
		$r = glue::db()->video_likes->findOne(array('user_id' => glue::user()->_id, 'item' => $this->_id, 'like' => 0));
		if($r) return true;
		return false;
	}

	function like(){
		glue::db()->video_likes->update(
			array("user_id" => Glue::user()->_id, "item" => $this->_id),
			array(
				"user_id" => Glue::user()->_id,
				"item" => $this->_id,
				"like" => 1,
				"ts" => new \MongoDate()
			),
			array("upsert"=>true)
		);

		$this->likes = glue::db()->video_likes->find(array("item"=>$this->_id, "like"=>1))->count();
		$this->dislikes = glue::db()->video_likes->find(array("item"=>$this->_id, "like"=>0))->count();

		$this->record_statistic('video_likes');
		$this->save();
		return true;
	}

	function dislike(){
		glue::db()->video_likes->update(
			array("user_id" => Glue::user()->_id, "item" => $this->_id),
			array(
				"user_id" => Glue::user()->_id,
				"item" => $this->_id,
				"like" => 0,
				"ts" => new \MongoDate()
			),
			array("upsert"=>true)
		);

		$this->likes = glue::db()->video_likes->find(array("item"=>$this->_id, "like"=>1))->count();
		$this->dislikes = glue::db()->video_likes->find(array("item"=>$this->_id, "like"=>0))->count();

		$this->record_statistic('video_dislikes');
		$this->save();
		return true;
	}

	function getLongAbstract(){
		return preg_replace('/[\'"]/', '', truncate_string(htmlspecialchars($this->description), 250));
	}

	function getHTML_safeTitle(){
		return preg_replace('/[\'"]/', '', $this->title);
	}

	function report($reason){
		glue::db()->report->update(array('ref' => \MongoDBRef::create($this->collectionName(), $this->_id), 'userId' => glue::user()->_id),
			array('$setOnInsert' => array('reason' => $reason, 'ts' => new \MongoDate())), array('upsert' => true));
		return true;
	}

	function get_time_string(){

		$time = $this->duration/1000;
		//$time = 3600+(60*32)+(50);
		$time_string = '';

		$hours = (int)($time/(60*60));
		if(strlen($hours) > 1){
			$time_string = $hours.':';
		}else{
			$time_string = '0'.$hours.':';
		}

		$minutes = (int)(($time%(60*60))/(60));
		if($minutes >= 1){
			if(strlen($minutes) > 1){
				$time_string .= $minutes.':';
			}else{
				$time_string .= '0'.$minutes.':';
			}

			$seconds = (int)(($time%(60*60))%(60));
			if(strlen($seconds) > 1){
				$time_string .= $seconds;
			}else{
				$time_string .= '0'.$seconds;
			}
		}else{
			$time = (int)$time;
			if(strlen($time) > 1){
				$time_string .= '00:'.$time;
			}else{
				$time_string .= '00:0'.$time;
			}
		}
		return $time_string;
	}

	function record_statistic($field){
		glue::db()->video_statistics_day->update(array("day"=>new \MongoDate(mktime(0, 0, 0, date("m"), date("d"), date("Y"))), "vid"=>$this->_id),
			array('$inc' => array($field => 1)), array("upsert"=>true));
	}
	
	function delete(){
		foreach($video_rows as $video){
			VideoResponse::model()->Db()->remove(array('$or' => array(
			array('vid' => $video->_id), array('xtn_vid' => $video->_id)
			)), array('safe' => true));
			$video->deleted = 1;
			$video->save();
		}
		glue::db()->videoresponse_likes->remove(array("video_id"=>array('$in' => $video_ids)));
		glue::db()->video_likes->remove(array('item' => array('$in' => $video_ids))); // Same reason as above		
	}
	
	function removeVideoResponses(){
		$count=\app\models\VideoResponse::model()->findAll(array('videoId' => $this->_id, 'type' => 'video'))->count();
		\app\models\VideoResponse::model()->deleteAll(array('videoId' => $this->_id, 'type' => 'video'));
		$this->totalResponses = $this->totalResponses-$count;
		$this->totalVideoResponses = $this->totalVideoResponses-$count;
		$this->save();		
	}
	
	function removeTextResponses(){
		$count = \app\models\VideoResponse::model()->find(array('videoId' => $this->_id, 'type' => 'text'))->count();
		\app\models\VideoResponse::model()->deleteAll(array('videoId' => $this->_id, 'type' => 'text'));
		$this->totalResponses = $this->totalResponses-$count;
		$this->totalTextReponses = $this->totalTextReponses-$count;
		$this->save();		
	}
}