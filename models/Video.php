<?php
class Video extends MongoDocument{

	public $response = array(); // I am unsure about this var. I don't like the way it stares at me
	public $string_tags; // To hold the non-persistent representation of the tags field

	/**
	 * This var denotes the public view of the video
	 *
	 * - 1 = public
	 * - 2 = hidden
	 * - 3 = private
	 *
	 * @var int $listing
	 */
	protected $listing = 1;
	protected $title;
	protected $description;
	protected $licence = 1;
	protected $category = 11;
	protected $tags;

	protected $voteable = 1;
	protected $embeddable = 1;
	protected $mod_comments = 0;
	protected $voteable_comments = 1;
	protected $vid_coms_allowed = 1;
	protected $txt_coms_allowed = 1;
	protected $private_stats = 0;
	protected $adult_content = 0;

	protected $duration;
	protected $file_size;

	protected $unique_views = 0;
	protected $views = 0;
	protected $likes = 0;
	protected $dislikes = 0;
	protected $total_responses = 0;
	protected $vid_responses = 0;
	protected $txt_responses = 0;

	protected $md5;
	protected $job_id;
	protected $upload_id;
	protected $user_id;

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
	protected $state;

	protected $original;
	protected $mp4;
	protected $ogg;
	protected $image;
	protected $image_src;

	protected $deleted = 0;

	protected $updated;
	protected $created;

	function getCollectionName(){
		return "videos";
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

	function licences(){
		return array(
			1 => "StageX Licence",
			2 => "Creative Commons Licence (Re-use Allowed)"
		);
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

	function if_is_public(){
		return $this->listing == 1;
	}

	function if_is_unlisted(){
		return $this->listing == 2;
	}

	function if_is_private(){
		return $this->listing == 3;
	}

	function is_processing(){
		return $this->state == 'pending' || $this->state == 'submitting' || $this->state == 'transcoding';
	}

	function relations(){
		return array(
			"author" => array(self::HAS_ONE, 'User', "_id", 'on' => 'user_id'),
			"responses" => array(self::HAS_MANY, 'VideoResponse', "vid", 'on' => '_id'),
		);
	}

	function rules(){
		return array(
			array('listing, category, licence', 'required', 'message' => 'Listing, category and licence have invalid values. Please enter a correct value'),
			array('title, description, string_tags', 'safe', 'on' => 'upload'),

			array('listing', 'in', 'range' => array(1, 2, 3), 'message' => 'You must select a valid listing of either public, unlisted or private'),
			array('voteable, embeddable, private_stats, voteable_comments, vid_coms_allowed, txt_coms_allowed, adult_content', 'boolean', 'allowNull' => true),

			array('mod_comments', 'in', 'range' => array(0, 1), 'allowEmpty' => false, 'on' => 'update', 'message' => 'Incorrect value provided for comment settings'),

			array('title', 'required', 'on' => 'update', 'message' => 'You must provide a title'), // Licence
			array('licence', 'required', 'on' => 'update', 'message' => 'You must provide a licence type'),
			array('category', 'in', 'range' => $this->categories('values'), 'message' => 'You must provide a valid category'),
			array('licence', 'in', 'range' => array(1, 2), 'message' => 'You must provide a licence type'),

			array('title', 'string', 'max' => '75', 'message' => 'You can only write 75 characters for the title'),
			array('description', 'string', 'max' => '1500', 'message' => 'You can only write 1500 characters for the description'),
			array('string_tags', 'tokenized', 'max' => 10, 'message' => 'You can add upto 10 tags, no more')
		);
	}

	function afterFind(){
		if(is_array($this->tags)){
			$this->string_tags = implode(',', $this->tags);
		}
		return true;
	}

	function getImage($width, $height){
		if(isset(glue::$params['thumbnailBase'])){
			return 'http://'.glue::$params['thumbnailBase'].strval($this->_id).'_w_'.$width.'_h_'.$height.'.png';
		}else{
			return Glue::url()->create("/image/video", array(
				'file' => strval($this->_id),
				"w"=>$width,
				"h"=>$height
			));
		}
	}

	function upload($id){

		if (empty($_FILES) && strtolower($_SERVER['REQUEST_METHOD']) == 'post' && !isset($_FILES[$id])) {
			$this->response("ERROR", "FILE_A_EMPTY");
			return false;
		}

		if($_FILES[$id]['size'] > glue::session()->user->get_max_video_upload_size() || !($_FILES[$id]['error'] == "0" || $_FILES[$id]['error'] == 0)){
			// video did not pass last minute checks
			$this->response("ERROR", "NOT_VALID");
			return false;
		}

		if(glue::session()->user->get_upload_bandwidth_left() < $_FILES[$id]['size']){
			unlink($_FILES[$id]['tmp_name']); // Free up space in our temp dir
			$this->response("ERROR", "NOT_ENOUGH_SP");
			return false;
		}
		glue::session()->user->change_upload_bandwidth_left_by($_FILES[$id]['size']);

		// Does ffmpeg think this is a real file??
		exec(sprintf('ffmpeg -i "'.$_FILES[$id]['tmp_name'].'" 2>&1'), $output);
		$ffmpeg_details = implode("\r", $output);
		if(!preg_match('!Duration: ([0-9:.]*)[, ]!', $ffmpeg_details) || preg_match('!Duration: ([0-9:.]*)[, ]!', $ffmpeg_details) <= 0){
			unlink($_FILES[$id]['tmp_name']); // Free up space in our temp dir
			// FAIL the file might not be a video
			$this->response("ERROR", "NOT_VALID");
			return false;
		}

		$file_hash = md5_file($_FILES[$id]['tmp_name']);
		$matched_video = self::model()->findOne(array('md5' => $file_hash));

		// Now lets make a new video
		$this->user_id = glue::session()->user->_id;
		$this->file_size = $_FILES[$id]['size'];
		$this->title = substr(substr($_FILES[$id]['name'], 0, strrpos($_FILES[$id]['name'], '.')), 0, 75);
		$this->md5 = $file_hash;
		$this->upload_id = $id;
		$this->created = new MongoDate();
		$this->state = 'uploading';

		if($matched_video && $matched_video->state == 'finished'){
			$this->duration = $matched_video->duration;
			$this->state = $matched_video->state;
			$this->original = $matched_video->original;
			$this->mp4 = $matched_video->mp4;
			$this->ogg = $matched_video->ogg;
			$this->image = $matched_video->image;
			$this->image_src = $matched_video->image_src;
			$this->job_id = $matched_video->job_id;
		}else{
			$this->deleted = 1; // Mark this as deleted to stop it from showing
		}

		// Do video defaults
		$defaults = isset(glue::session()->user->default_video_settings) ? glue::session()->user->default_video_settings : array();
		if(is_array($defaults)){
			foreach($defaults as $item => $val){
				$this->$item = $val;
			}
		}

		/*
		 *	This takes the form from the upload page and places the data in
		 */
		if(isset($_SESSION['_upload_save'][$id]) && is_array($_SESSION['_upload_save'][$id])){
			foreach($_SESSION['_upload_save'][$id] as $k=>$v){
				$this->$k = $v;
			}
		}

		$this->save();

		if(!$matched_video){
			// Lets transfer to S3
			$file_name = new MongoId().'.'.pathinfo($_FILES[$id]['name'], PATHINFO_EXTENSION);
			if(glue::aws()->s3_upload($file_name, array('fileUpload' => $_FILES[$id]['tmp_name']))){
				$this->original = glue::aws()->get_s3_obj_url($file_name);
				unlink($_FILES[$id]['tmp_name']); // Free up space in our temp dir

				/*
				 * I create and insert a new job here into Mongo. This is the easiest way by far
				 * to keep track of encoding over possibly many videos and many outputs and also to keep track of which
				 * videos received an AWS cURL error while trying to send messages
				 */
				$job = array('job_id' => md5( uniqid( rand(1,255).rand(45,80).rand(112,350), true ) )); // Pre-pop the job with an id

				// So lets send the command to SQS now
				$img_submit = glue::aws()->send_video_encoding_message($file_name, $job['job_id'], 'img');
				$mp4_submit = glue::aws()->send_video_encoding_message($file_name, $job['job_id'], 'mp4');
				$ogv_submit = glue::aws()->send_video_encoding_message($file_name, $job['job_id'], 'ogv');

				if($img_submit && $mp4_submit && $ogv_submit){
					$state = 'transcoding';
				}else{
					$state = 'pending';
				}

				glue::db()->videos->update(array('_id' => $this->_id), array('$set' => array('state' => $state, 'deleted' => 0, 'job_id' => $job['job_id'])));
				glue::db()->encoding_jobs->insert(array_merge($job, array('file_name' => $file_name, 'img_submit' => $img_submit, 'mp4_submit' => $mp4_submit,
					'ogv_submit' => $ogv_submit, 'state' => $state)));
			}else{
				// If it won't upload to S3 don't bother, mark video as deleted and carry on
				glue::db()->videos->update(array('_id' => $this->_id), array('$set' => array('deleted' => 1, 'state' => 'failed')));
				$this->response("ERROR", "UNKNOWN");
				return false;
			}
		}
	}

	function beforeSave(){
		if(strlen(strip_whitespace($this->string_tags)) > 0){
			$this->tags = preg_split("/[\s]*[,][\s]*/", $this->string_tags);

			for($i=0;$i<count($this->tags);$i++){
				$this->tags[$i] = strip_whitespace($this->tags[$i]);
			}
		}else{
			unset($this->tags);
		}

		if(!$this->getIsNewRecord())
			$this->updated = new MongoDate();
		return true;
	}

	function afterSave(){

		if($this->state == 'finished' && $this->getScenario() != 'process_encoding'){ // Only put it into the search index if it's done

			if($this->getIsNewRecord()){

				glue::session()->user->total_uploads = glue::session()->user->total_uploads+1;
				glue::session()->user->save();

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
				":tags" => $this->string_tags,
				":duration" => $this->duration,
				":rating" => $this->likes - $this->dislikes,
				":views" => $this->views,
				":type" => "video",
				":adult" => $this->adult_content,
				":author_name" => $this->author->username,
			));
		}
		return true;
	}

	/**
	 * Document structure:
	 *
	 * {
	 *   _id: {},
	 *   hits: 0,
	 *   u_hits: 0,
	 *   hours: {
	 *   	1: {v: 5, u: 0},
	 *   	2: {v: 3, u: 9},
	 *   },
	 *   browser: {
	 *   	chrome: 1,
	 *   	ie: 2
	 *   },
	 *   age: {
	 *   	13_16: 0,
	 *   	17_25: 0
	 *   },
	 *   video_comments: 0,
	 *   text_comments: 0,
	 *   video_likes: 0,
	 *   video_dislikes: 0,
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
			$u_brows_key = glue::http()->get_major_ua_browser();

	        $u_age_key = 'u';
	        if($_SESSION['logged']){
		        ///Other, 13-16, 17-25, 26-35, 36-50, 50+
		        //var_dump($user->birth_month); exit();
		        $u_age = !empty($user->birth_day) && !empty($user->birth_month) && !empty($user->birth_year) ?
		        	mktime(0, 0, 0, $user->birth_month, $user->birth_day, $user->birth_year) : 0;
				$u_age_diff = (time() - $u_age)/(60*60*24*365);

				switch(true){
					case $u_age_diff > 12 && $u_age_diff < 17:
						$u_age_key = '13_16';
						break;
					case $u_age_diff > 17 && $u_age_diff < 26:
						$u_age_key = '17_25';
						break;
					case $u_age_diff > 25 && $u_age_diff < 36:
						$u_age_key = '26_35';
						break;
					case $u_age_diff > 35 && $u_age_diff < 51:
						$u_age_key = '36_50';
						break;
					case $u_age_diff > 50:
						$u_age_key = '50_plus';
						break;
				}
	        }

			$is_unique = glue::db()->video_stats_all->find(array(
				"sid" => glue::session()->user->_id instanceof MongoId ? glue::session()->user->_id : session_id(),
				"vid" => $this->_id
			))->count() <= 0;

			$update_doc = array( '$inc' => array( 'hits' => 1 ) );
			if($is_unique){
				$update_doc['$inc']['u_hits'] = 1;
				$update_doc['$inc']['age.'.$u_age_key] = 1;
				$update_doc['$inc']['browser.'.$u_brows_key] = 1;

				// These are used to make my life a little easier
				$update_doc['$inc']['age_total'] = 1;
				$update_doc['$inc']['browser_total'] = 1;

				if(glue::session()->user->gender == 'm'){
					$update_doc['$inc']['male'] = 1;
				}elseif(glue::session()->user->gender == 'f'){
					$update_doc['$inc']['female'] = 1;
				}
			}

			$day_update_doc = $update_doc;
			$day_update_doc['$inc']['hours.'.date('G').'.v'] = 1;
			if($is_unique): $day_update_doc['$inc']['hours.'.date('G').'.u'] = 1; endif;

			glue::db()->video_stats_day->update(array("day"=>new MongoDate(mktime(0, 0, 0, date("m"), date("d"), date("Y"))), "vid"=>$this->_id), $day_update_doc, array("upsert"=>true));
			//glue::db()->{video_stats_year}->update(array("year"=>new MongoDate(mktime(0, 0, 0, 1, 1, date("Y"))), "vid"=>$this->_id), $update_doc, array("upsert"=>true));

			if($is_unique){
				$this->unique_views = $this->unique_views+1;

				glue::db()->video_stats_all->insert(array(
					'sid' => glue::session()->user->_id instanceof MongoId ? glue::session()->user->_id : session_id(),
					'vid' => $this->_id,
					'ts' => new MongoDate()
				));
			}
			$this->views = $this->views+1;
			$this->save();

			// Now lets do some referers
			$referer = glue::http()->getNormalisedReferer();
			if($referer){
				glue::db()->video_referers->update(array('video_id' => $this->_id, 'referer' => $referer), array('$inc' => array('c' => 1),
					'$set' => array('ts' => new MongoDate())), array('upsert' => true));
			}
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

			foreach(glue::db()->video_stats_day->find(array(
				"vid"=>$this->_id,
				"day"=> array("\$gte" => new MongoDate($fromTs), "\$lte" => new MongoDate($toTs) ),
			)) as $day){
				//var_dump($day);
				$non_unique_views_range[$day['day']->sec] = !empty($day['hits']) ? $day['hits'] : 0;
				$unique_views_range[$day['day']->sec] = !empty($day['u_hits']) ? $day['u_hits'] : 0;

				$sum_browser = summarise_array_row(isset($day['browser']) ? $day['browser'] : array(), $sum_browser);
				$sum_ages = summarise_array_row(isset($day['age']) ? $day['age'] : array(), $sum_ages);

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
			foreach(glue::db()->video_stats_day->find(array(
				"vid"=>$this->_id,
				"day"=> array("\$gte" => new MongoDate($fromTs), "\$lte" => new MongoDate($toTs) ),
			)) as $day){
				//var_dump($day);
				foreach($day['hours'] as $k => $v){
					$k = $k+1;
					$non_unique_views_range[mktime($k, 0, 0, date('m', $day['day']->sec), date('d', $day['day']->sec), date('Y', $day['day']->sec))] = !empty($v['v']) ? $v['v'] : 0;
					$unique_views_range[mktime($k, 0, 0, date('m', $day['day']->sec), date('d', $day['day']->sec), date('Y', $day['day']->sec))] = !empty($v['u']) ? $v['u'] : 0;
				}

				$sum_browser = summarise_array_row(isset($day['browser']) ? $day['browser'] : array(), $sum_browser);
				$sum_ages = summarise_array_row(isset($day['age']) ? $day['age'] : array(), $sum_ages);

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

		if(sizeof($ages_highCharts_array) <= 0){
			$ages_highCharts_array = array(array('None', 100));
		}

		if(sizeof($browsers_highCharts_array) <= 0){
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

	function get_licence_text(){
		$licence = $this->licences();
		return $licence[$this->licence];
	}

	function get_category_text(){
		$categories = $this->categories('selectBox');
		return $categories[$this->category];
	}

	function userHasWatched(){
		$r = glue::db()->watched_history->findOne(array('user_id' => glue::session()->user->_id, 'item' => $this->_id));
		if($r) return true;
		return false;
	}

	function currentUserLikes(){
		$r = glue::db()->video_likes->findOne(array('user_id' => glue::session()->user->_id, 'item' => $this->_id, 'like' => 1));
		if($r) return true;
		return false;
	}

	function currentUserDislikes(){
		$r = glue::db()->video_likes->findOne(array('user_id' => glue::session()->user->_id, 'item' => $this->_id, 'like' => 0));
		if($r) return true;
		return false;
	}

	function like(){
		glue::db()->video_likes->update(
			array("user_id" => Glue::session()->user->_id, "item" => $this->_id),
			array(
				"user_id" => Glue::session()->user->_id,
				"item" => $this->_id,
				"like" => 1,
				"ts" => new MongoDate()
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
			array("user_id" => Glue::session()->user->_id, "item" => $this->_id),
			array(
				"user_id" => Glue::session()->user->_id,
				"item" => $this->_id,
				"like" => 0,
				"ts" => new MongoDate()
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
		glue::db()->getCollection("report.video")->update(array('vid' => $this->_id, 'uid' => glue::session()->user->_id),
			array('vid' => $this->_id, 'uid' => glue::session()->user->_id, 'reason' => $reason, 'ts' => new MongoDate()), array('upsert' => true));
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
		glue::db()->video_stats_day->update(array("day"=>new MongoDate(mktime(0, 0, 0, date("m"), date("d"), date("Y"))), "vid"=>$this->_id),
			array('$inc' => array($field => 1)), array("upsert"=>true));
	}
}