<?php
include_once ROOT.'/glue/plugins/phpthumb/ThumbLib.inc.php';

class User extends MongoDocument{

	public $new_email;
	public $profile_image;
	public $hash;

	protected $name;
	protected $username;
	protected $password;
	protected $email;

	protected $birth_day;
	protected $birth_month;
	protected $birth_year;

	protected $about;
	protected $gender;
	protected $country;

	protected $external_links;

	protected $profile_privacy;

	protected $ins;

	protected $remember;
	protected $rem_m;

	protected $temp_access_token;

	protected $single_sign;
	protected $email_notify;

	protected $group;

	protected $safe_srch = "S";

	/**
	 * This is used for the search.
	 * It decides wether or not the users profile is searchable
	 *
	 * 1- is Searchable
	 * 0- is Private
	 */
	protected $listing = 1;

	protected $max_upload;
	protected $upload_left;

	protected $next_bandwidth_up;

	protected $image_src; // I do really wanna take this out

	protected $max_video_file_size;

	protected $default_video_settings = array('listing' => 1, 'voteable' => true, 'embeddable' => true, 'mod_comments' => 0,
			'voteable_comments' => true, 'vid_coms_allowed' => true, 'txt_coms_allowed' => true, 'private_stats' => false, 'licence' => 1);

	protected $email_vid_responses = 0;
	protected $email_vid_response_replies = 0;
	protected $email_wall_comments = 0;
	protected $email_encoding_result = 0;

	protected $auto_play_vids = 1;
	protected $use_divx_player = 0;

	protected $autoshare_opts;
	protected $fb_autoshare_token;
	protected $twt_autoshare_token;

	protected $total_subscribers = 0;
	protected $total_subscriptions = 0;
	protected $total_playlists = 0;
	protected $total_uploads = 0;

	protected $fb_uid;
	protected $google_uid;
	protected $clicky_uid;

	protected $last_notification_pull;

	protected $upload_enabled = 1;
	protected $deleted = 0;
	protected $banned;

	protected $updated;
	protected $ts;

	function groups(){
		return array(
		  	"a normal user"=>1,
		  	"a VIP"=>4,
		  	"Liked enough to be given this role but not enough to be given something of use"=>6,
		  	"IRMOD"=>8,
		  	"the King of StageX"=>9,
			"the Queen of StageX"=>10
		);
	}

	function getCollectionName(){
		return "users";
	}

	function behaviours(){
		return array(
			'timestampBehaviour' => array(
				'class' => 'glue/extended/behaviours/timestampBehaviour.php'
			)
		);
	}

	public static function model($className = __CLASS__){
		return parent::model($className);
	}

	function relations(){
		return array(
			"subscriptions" => array(self::HAS_MANY, 'Subscription', "from_id"),
			"subscribers" => array(self::HAS_MANY, 'Subscription', "to_id"),
			"videos" => array(self::HAS_MANY, 'Video', "user_id"),
			"playlists" => array(self::HAS_MANY, 'Playlist', "user_id"),
			'notifications' => array(self::HAS_MANY, 'Notification', 'user_id'),
		);
	}

	function beforeValidate(){
		if($this->getScenario() == "updatePassword"){
			if(GCrypt::verify($this->o_password, $this->password)){
				return true;
			}else{
				$this->addError('The old password did not match the one we have on record for you');
				return false;
			}
		}
		return true;
	}

	function rules(){
		return array(

		array('username, password, email, hash', 'required', 'on'=>'insert', 'message' => 'You must fill in all of the fields to register for this site.'),

		array('single_sign, email_notify', 'required', 'on'=>'updateSecurity'),
		array('username', 'required', 'on'=>'updateUsername', 'message' => 'You must provide a username'),

		//array('auto_play_vids, use_divx_player', 'safe', 'on' => 'updatePlayback'),
		//array('name, country, about, gender, birth_day, birth_month, birth_year', 'safe', 'on' => 'updateProfile'),
		array('single_sign, email_notify, remember, auto_play_vids, use_divx_player', 'boolean', 'allowNull'=>true),

		array('username', 'string', 'max'=>20, 'message' => 'Please enter a max of 20 characters for your username'),
		array('name', 'string', 'max' => 150, 'message' => 'You can only write 150 characters for your name.'),
		array('about', 'string', 'max' => 1500, 'message' => 'You can only write 1500 characters for your bio.'),

		array('hash', 'hash', 'on'=>'insert'),
		array('username', 'objExist', 'class'=>'User', 'field'=>'username', 'notExist' => true, 'on'=>'insert, updateUsername',
				'message' => 'That username already exists please try another.'),

		array('email', 'email', 'message' => 'You must enter a valid Email Address'),

		array('email', 'objExist', 'class'=>'User', 'field'=>'email', 'notExist' => true, 'on'=>'insert', 'message' =>
				'That email address already exists please try and login with it, or if you have forgotten your password try to recover your account.'),

		array('gender', 'in', 'range'=>array("m", "f"), 'message' => 'You must enter a valid gender'),

		array('birth_day', 'validate_birthday', 'on' => 'updateProfile'),
		array('birth_day', 'number', 'min'=>1, 'max'=>32, 'message' => 'Birth day was a invalid value'),
		array('birth_month', 'number', 'min'=>1, 'max'=>12, 'message' => 'Birth month was a invalid value'),
		array('birth_year', 'number', 'min'=>date('Y') - 100, 'max'=>date('Y'), 'message' => 'Birth year was a invalid value'),

		array('country', 'in', 'range' => new GListProvider('countries', 'code'), 'on' => 'updateProfile', 'message' => 'You supplied an invalid country.'), // We only wanna do laggy functions on scenarios

		array('new_email', 'required', 'on' => 'updateEmail', 'message' => 'You did not enter a valid Email Address for this account'),
		array('new_email', 'email', 'on' => 'updateEmail', 'message' => 'You must enter a valid Email Address'),
		array('new_email', 'objExist', 'class'=>'User', 'field'=>'email', 'notExist' => true, 'on'=>'updateEmail', 'message' =>
				'That email address already exists please try and login with it, or if you have forgotten your password try to recover your account.'),

		array('safe_srch', 'required', 'on'=>'updateSafeSearch', 'message' => 'You enterd an invalid value for safe search'),
		array('safe_srch', 'in', 'range'=>array('S', 'T', '0'), 'message' => 'You enterd an invalid value for safe search'),

		array('o_password, new_password, cn_password', 'required', 'on' => 'updatePassword', 'message' => 'Please fill in all fields to change your password'),
		array('cn_password', 'compare', 'with' => 'new_password', 'field' => true, 'on' => 'updatePassword', 'message' => 'You did not confirm your new password correctly.'),

		array('profile_image', 'file', 'size' => array('lt' => 2097152), 'on' => 'updatePic',
				'message' => 'The picture you provided was too large. Please upload 2MB and smaller pictures'),
		array('profile_image', 'file', 'ext' => array('png', 'jpg', 'jpeg', 'bmp'), 'type' => 'image', 'on' => 'updatePic',
				'message' => 'You supplied an invalid file. Please upload an image file only.'),

		array('clicky_uid', 'string', 'max' => 20, 'message' => 'Those are not valid anayltics accounts.'),
		array('email_vid_responses, email_vid_response_replies, email_wall_comments, email_encoding_result', 'boolean', 'allowNull'=>true),
		);
	}

	function validate_birthday($field, $params = array()){
		$filled_size = sizeof(array_filter(array(
			GValidators::isEmpty($this->birth_day) ? null : $this->birth_day,
			GValidators::isEmpty($this->birth_month) ? null : $this->birth_month,
			GValidators::isEmpty($this->birth_year) ? null : $this->birth_year,
		)));

		if($filled_size != 3 && $filled_size > 0){
			$this->addError('You must fill in all parts of your bithday to set one');
			return false;
		}
		return true;
	}

	function beforeSave(){

		if($this->getIsNewRecord()){
			$this->last_notification_pull = new MongoDate();
			$this->ts = new MongoDate();
			$this->next_bandwidth_up = strtotime('+1 week', mktime(0, 0, 0, date('m'), date('d'), date('Y')));
			$this->upload_left = glue::$params['maxUpload'];
		}else{
			$this->updated = new MongoDate();
		}

		if($this->getScenario() == "updatePassword" || $this->getScenario() == "recoverPassword" || $this->getIsNewRecord()){

			if($this->getScenario() == "updatePassword")
			$this->password = $this->new_password;

			$this->password = GCrypt::blowfish_hash($this->password);
		}

		if($this->getScenario() == "updateEmail"){

			$hash = hash("sha256", generate_new_pass().(substr(md5(uniqid(rand(), true)), 0, 32)));

			$this->temp_access_token = array(
				"to" => time()+60*60*24, // 24 Hours
				"hash" => $hash,
				"email" => $this->new_email,
				"y" => "E_CHANGE",
				"url" => glue::url()->create("/user/confirminbox", array('e' => $this->new_email, 'h' => $hash, 'uid' => strval($this->_id)))
			);
		}

		//$this->upload_left = (string)$this->upload_left;
		//var_dump($this);
		return true;
	}

	function afterSave(){

		if($this->getIsNewRecord()){
			$watch_later = new Playlist();
			$watch_later->title = "Watch Later";
			$watch_later->description = "All videos you mark for watching later are saved here";
			$watch_later->listing = 3;
			$watch_later->user_id = $this->_id;
			$watch_later->save();

			if($this->getScenario() != 'social_signup'){
				glue::mailer()->mail($this->email, array('no-reply@stagex.co.uk', 'StageX'), 'Welcome to StageX',
					"user/register.php", array( "username"=>$this->username, "email"=>$this->email ));
			}

			glue::mysql()->query("INSERT INTO documents (_id, uid, listing, title, description, tags, author_name, type, date_uploaded)
								VALUES (:_id, null, :listing, :title, null, null, null, :type, now())", array(
				":_id" => strval($this->_id),
				":listing" => $this->listing,
				":title" => $this->username,
				":type" => "user",
			));

			glue::sitemap()->addUrl(glue::url()->create('/user/view', array('id' => $this->_id)), 'hourly', '1.0');
		}else{
			glue::mysql()->query("UPDATE documents SET _id=:_id, deleted=:deleted, listing=:listing, title=:title, type=:type WHERE _id=:_id", array(
				":_id" => strval($this->_id),
				":deleted" => $this->deleted,
				":listing" => $this->listing,
				":title" => $this->username,
				":type" => "user",
			));
		}

		if($this->deleted){
			glue::mysql()->query("UPDATE documents SET deleted=1 WHERE _id=:_id", array(
				":_id" => strval($this->_id),
			));
		}


		if($this->getScenario() == "updatePassword"){
			glue::mailer()->mail($this->email, array('no-reply@stagex.co.uk', 'StageX'), 'StageX Password Changed',
				"user/passwordChange.php", array( "username"=>$this->username ));
		}

		if($this->getScenario() == "recoverPassword"){
			glue::mailer()->mail($this->email, array('no-reply@stagex.co.uk', 'StageX'), 'StageX Password Recovery', "user/forgotPassword.php", array(
	      		"username"=>$this->username, "email"=>$this->email, "password"=>$this->oldRecord()->password ));
		}

		if($this->getScenario() == "updateEmail"){
			glue::mailer()->mail($this->email, array('no-reply@stagex.co.uk', 'StageX'), 'Verify your email change', 'user/verify_email_inbox.php',
				array( "username" => $this->username, "verify_url" => $this->temp_access_token['url'], 'new_email' => $this->new_email ));
		}

		return true;
	}

	function setExternalLinks($ar){

		$rules = array(
			array('$.url', 'required', 'message' => 'One or more of the external links you entered were invalid URLs.'),
			array('$.url', 'url', 'message' => 'One or more of the external links you entered were invalid URLs.'),
			array('$.url', 'string', 'max' => 200, 'message' => 'External URLs can only be 200 characters in length'),
			array('$.title', 'string', 'max' => 20, 'message' => 'The optional external URL caption field can only be 200 characters in length')
		);

		$valid = true;
		foreach($ar as $k => $row){
			$ar[$k] = filter_array_fields($row, array('url', 'title'));
		}
		$this->external_links = $ar;

		$valid = $this->validateRules($rules, $ar) && $valid;

		//if(!$valid)
			//$this->addError('One or more of the external links you entered were incorrect. Please enter the full URL you wish to link to and optionally a title no longer than 20 characters.');

		if(sizeof($this->external_links) > 6){
			$this->addError('You can only add 6 external links for the time being. Please make sure you have entered no more and try again.');
		}

		if(sizeof($this->getErrors()) > 0){
			return false;
		}
		return true;
	}

	function setAutoshareOptions($ar){
		$rules = array(array('upload, create_pl, video_2_pl, lk_dl, c_video, facebook, twitter, linkedin', 'boolean', 'allowNull' => true,
				'message' => 'An unknown error occurred. We are working as fast as possible to fix these errors so please try again later.'));

		$this->autoshare_opts = filter_array_fields($ar, array('upload', 'create_pl', 'video_2_pl', 'lk_dl', 'c_video', 'facebook', 'twitter', 'linkedin'));
		return $this->validateRules($rules, $ar);
	}

	function setProfilePrivacy($ar){
		$rules = array(
			array('gender, birthday, country', 'in', 'range' => array(0, 1), 'message' => 'Some of the values you provided for your privacy settings were invalid.')
		);
		$this->profile_privacy = filter_array_fields($ar, array('gender', 'birthday', 'country'));
		return $this->validateRules($rules, $ar);
	}

	function setDefaultVideoSettings($ar){

		$data = array_merge($this->default_video_settings, $ar);
		$this->default_video_settings = filter_array_fields($data, array('listing', 'mod_comments', 'voteable', 'embeddable',
						'voteable_comments', 'vid_coms_allowed', 'txt_coms_allowed', 'private_stats', 'licence'));

		$rules = array(
			array('listing', 'in', 'range' => array(1, 2, 3), 'message' => 'Please enter a valid value for listing'),
			array('mod_comments', 'in', 'range' => array(0, 1), 'message' => 'Please enter a valid value for all comment options'),
			array('voteable, embeddable, voteable_comments, vid_coms_allowed, txt_coms_allowed, private_stats', 'boolean', 'allowNull' => true),
			array('licence', 'in', 'range' => array(1, 2), 'message' => 'Please enter a valid value for licence')
		);

		return $this->validateRules($rules, $data);
	}

	function setPic(){

		if(strlen($this->profile_image['tmp_name'])){
			$thumb = PhpThumbFactory::create(file_get_contents($this->profile_image['tmp_name']), array(), true); // This will need some on spot caching soon
			$thumb->adaptiveResize(800, 600);
			$this->image_src = new MongoBinData($thumb->getImageAsString());

			glue::db()->image_cache->remove(array('object_id' => $this->_id, 'type' => 'user'), array('safe' => true)); // Clear all cache

			$thumb = PhpThumbFactory::create($this->image_src->bin, array(), true); // This will need some on spot caching soon
			$thumb->adaptiveResize(48, 48);
			glue::db()->image_cache->update(array('object_id' => $this->_id, 'width' => 48, 'height' => 48, 'type' => 'user'),
				array('$set' => array('data' => new MongoBinData($thumb->getImageAsString()),
			)), array('upsert' => true));

			$thumb = PhpThumbFactory::create($this->image_src->bin, array(), true); // This will need some on spot caching soon
			$thumb->adaptiveResize(55, 55);
			glue::db()->image_cache->update(array('object_id' => $this->_id, 'width' => 55, 'height' => 55, 'type' => 'user'),
				array('$set' => array('data' => new MongoBinData($thumb->getImageAsString()),
			)), array('upsert' => true));

			$thumb = PhpThumbFactory::create($this->image_src->bin, array(), true); // This will need some on spot caching soon
			$thumb->adaptiveResize(125, 125);
			glue::db()->image_cache->update(array('object_id' => $this->_id, 'width' => 125, 'height' => 125, 'type' => 'user'),
				array('$set' => array('data' => new MongoBinData($thumb->getImageAsString()),
			)), array('upsert' => true));

			unlink($this->profile_image['tmp_name']); // Delte the file now

			$this->save();
		}
		return true;
	}

	function getPic($width, $height){
		if(isset(glue::$params['imagesUrl'])){
			return 'http://images.stagex.co.uk/user/'.strval($this->_id).'_w_'.$width.'_h_'.$height.'.png';
		}else{
			return Glue::url()->create("/image/user", array('file' => strval($this->_id), "w"=>$width, "h"=>$height));
		}
	}

	function loginNotification_email(){
		glue::mailer()->mail($this->email, array('no-reply@stagex.co.uk', 'StageX'), 'Someone has logged onto your StageX account',	"user/emailLogin.php",
			array_merge($this->ins[session_id()], array("username"=>$this->username)));
    	return true;
	}

	function getGroup(){
		$groups = array_flip($this->groups());

		if(array_key_exists((int)$this->group, $groups)){
			return $groups[$this->group];
		}
		return false;
	}

	function getUsername(){
		return html::encode($this->username);
	}

	function getAbout(){
		return nl2br(html::encode($this->about));
	}

	function should_autoshare($action){

		if(!is_array($this->autoshare_opts)){
			return false;
		}

		if(!isset($this->autoshare_opts[$action])){
			return false;
		}

		if((bool)$this->autoshare_opts[$action]){
			return true;
		}
		return false;
	}

	function get_max_upload_bandwidth(){
		return $this->max_upload > 0 ? $this->max_upload : glue::$params['maxUpload'];
	}

	function change_upload_bandwidth_left_by($number, $percentage = false, $save = true){
		$this->upload_left = $this->upload_left-$number;
		$this->save();
	}

	function reset_upload_bandwidth(){
		if($this->next_bandwidth_up < time()){
			$this->next_bandwidth_up = strtotime('+1 week', mktime(0, 0, 0, date('m'), date('d'), date('Y')));
			$this->upload_left = $this->max_upload > 0 ? $this->max_upload : glue::$params['maxUpload'];
			$this->save();
		}
	}

	function get_max_video_upload_size(){
		if(isset($this->max_video_file_size) && $this->max_video_file_size != null){
			return $this->max_video_file_size;
		}
		return glue::$params['maxVideoFileSize'];
	}

	function get_upload_bandwidth_left(){
		return $this->upload_left;
	}

	function create_username_from_social_signup($username){
		if(glue::db()->users->findOne(array('username' => $username))){
			for($i=0;$i<5;$i++){
				if($i == 3 || $i == 4){ // Lets go for even more unique and nuke the username
					$new_username = substr(substr($username, 0, 10).(md5( uniqid( rand(1,255).rand(45,80).rand(112,350), true ))), 0, 20);
				}else{
					$new_username = substr($username.uniqid(), 0, 20);
				}

				if(!glue::db()->users->findOne(array('username' => $new_username))){
					$username = $new_username;
					break;
				}
			}
		}
		return $this->username=$username;
	}
}
