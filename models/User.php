<?php

namespace app\models;

use glue,
	glue\Model,
	app\models\Playlist,
	glue\util\Crypt,
	glue\Collection,
	glue\Validation;

glue::import('@glue/components/phpthumb/ThumbLib.inc.php');

class User extends \glue\User{

	/** @virtual */
	public $newEmail;
	/** @virtual */
	public $avatar;
	/** @virtual */
	public $hash;

	public $username;
	public $password;

	public $name;
	public $email;

	public $birthDay;
	public $birthMonth;
	public $birthYear;

	public $about;
	public $gender;
	public $country;

	public $externalLinks;

	public $birthdayPrivacy=0;
	public $genderPrivacy=0;
	public $countryPrivacy=0;

	public $group=1;

	/**
	 * 0 - Off
	 * 1 - Children
	 * 2 - Mature
	 */
	public $safeSearch = 1;

	/**
	 * This is used for the search.
	 * It decides wether or not the users profile is searchable
	 *
	 * 0 - Public
	 * 1 - Unlisted
	 * 2 - Private
	 */
	public $listing = 0;

	public $defaultVideoSettings = array('listing' => 1, 'voteable' => true, 'embeddable' => true, 'mod_comments' => 0,
			'voteable_comments' => true, 'vid_coms_allowed' => true, 'txt_coms_allowed' => true, 'private_stats' => false, 'licence' => 1);

	public $emailVideoResponses = 0;
	public $emailVideoResponseReplies = 0;
	public $emailWallComments = 0;
	public $emailEncodingResult = 0;

	public $autoplayVideos = 1;
	public $useDivx = 0;

	public $autoshareUploads=0;
	public $autoshareResponses=0;
	public $autoshareLikes=0;
	public $autoshareAddToPlaylist=0;
	public $autoshareFb;
	public $autoshareTwitter;

	public $maxFileSize;
	public $bandwidthLeft;
	public $nextBandwidthTopup;

	public $fbUid;
	public $googleUid;
	public $clickyUid;

	public $totalSubscribers = 0;
	public $totalSubscriptions = 0;
	public $totalPlaylists = 0;
	public $totalUploads = 0;

	public $lastNotificationPull;

	public $canUpload = 1;
	public $deleted = 0;
	public $banned = 0;

	public $sessions;

	public $singleSignOn=0;
	public $emailLogins=0;

	public $accessToken;

	function groups(){
		return array(
			1=>'user',
			2=>'VIP',
			3=>'Liked enough to be given this role but not enough to be given something of use',
			4=>'IRMOD',
			5=>'King of StageX',
			6=>'Queen of StageX'
		);
	}

	function collectionName(){
		return "user";
	}

	function behaviours(){
		return array(
			'timestampBehaviour' => array(
				'class' => 'glue\\behaviours\\Timestamp'
			)
		);
	}

	public static function model($className = __CLASS__){
		return parent::model($className);
	}

	function relations(){
		return array(
			"subscriptions" => array('many', 'Subscription', "from_id"),
			"subscribers" => array('many', 'Subscription', "to_id"),
			"videos" => array('many', 'Video', "user_id"),
			"playlists" => array('many', 'Playlist', "user_id"),
			'notifications' => array('many', 'Notification', 'user_id'),
		);
	}

	function beforeValidate(){
		if($this->getScenario() == "updatePassword"){
			if(Crypt::verify($this->o_password, $this->password)){
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

		array('hash', 'hash', 'on'=>'insert', 'message' => 'CSRF not valid'),
		array('username', 'objExist', 'class'=>'app\\models\\User', 'field'=>'username', 'notExist' => true, 'on'=>'insert, updateUsername',
				'message' => 'That username already exists please try another.'),

		array('email', 'email', 'message' => 'You must enter a valid Email Address'),

		array('email', 'objExist', 'class'=>'app\\models\\User', 'field'=>'email', 'notExist' => true, 'on'=>'insert', 'message' =>
				'That email address already exists please try and login with it, or if you have forgotten your password try to recover your account.'),

		array('gender', 'in', 'range'=>array("m", "f"), 'message' => 'You must enter a valid gender'),

		array('birth_day', 'validate_birthday', 'on' => 'updateProfile'),
		array('birth_day', 'number', 'min'=>1, 'max'=>32, 'message' => 'Birth day was a invalid value'),
		array('birth_month', 'number', 'min'=>1, 'max'=>12, 'message' => 'Birth month was a invalid value'),
		array('birth_year', 'number', 'min'=>date('Y') - 100, 'max'=>date('Y'), 'message' => 'Birth year was a invalid value'),

		array('country', 'in', 'range' => new Collection('countries', 'code'), 'on' => 'updateProfile', 'message' => 'You supplied an invalid country.'), // We only wanna do laggy functions on scenarios

		array('new_email', 'required', 'on' => 'updateEmail', 'message' => 'You did not enter a valid Email Address for this account'),
		array('new_email', 'email', 'on' => 'updateEmail', 'message' => 'You must enter a valid Email Address'),
		array('new_email', 'objExist', 'class'=>'User', 'field'=>'email', 'notExist' => true, 'on'=>'updateEmail', 'message' =>
				'That email address already exists please try and login with it, or if you have forgotten your password try to recover your account.'),

		array('safe_srch', 'required', 'on'=>'updateSafeSearch', 'message' => 'You enterd an invalid value for safe search'),
		array('safe_srch', 'in', 'range'=>array('S', 'T', '0'), 'message' => 'You enterd an invalid value for safe search'),

		array('o_password, new_password, cn_password', 'required', 'on' => 'updatePassword', 'message' => 'Please fill in all fields to change your password'),
		array('cn_password', 'compare', 'with' => 'new_password', 'field' => true, 'on' => 'updatePassword', 'message' => 'You did not confirm your new password correctly.'),

		array('avatar', 'file', 'size' => array('lt' => 2097152), 'on' => 'updatePic',
				'message' => 'The picture you provided was too large. Please upload 2MB and smaller pictures'),
		array('avatar', 'file', 'ext' => array('png', 'jpg', 'jpeg', 'bmp'), 'type' => 'image', 'on' => 'updatePic',
				'message' => 'You supplied an invalid file. Please upload an image file only.'),

		array('clicky_uid', 'string', 'max' => 20, 'message' => 'Those are not valid anayltics accounts.'),
		array('email_vid_responses, email_vid_response_replies, email_wall_comments, email_encoding_result', 'boolean', 'allowNull'=>true),

		array('externalLinks', 'validateExternalLinks'),
		array('defaultVideoSettings', 'glue\\db\\Subdocument', 'type' => 'one', 'rules' => array(			
			array('listing', 'in', 'range' => array(1, 2, 3), 'message' => 'Please enter a valid value for listing'),
			array('mod_comments', 'in', 'range' => array(0, 1), 'message' => 'Please enter a valid value for all comment options'),
			array('voteable, embeddable, voteable_comments, vid_coms_allowed, txt_coms_allowed, private_stats', 'boolean', 'allowNull' => true),
			array('licence', 'in', 'range' => array(1, 2), 'message' => 'Please enter a valid value for licence')
		))
		);
	}

	function validate_birthday($field, $params = array()){
		$filled_size = count(array_filter(array(
			\glue\Validation::isEmpty($this->birth_day) ? null : $this->birth_day,
			\glue\Validation::isEmpty($this->birth_month) ? null : $this->birth_month,
			\glue\Validation::isEmpty($this->birth_year) ? null : $this->birth_year,
		)));

		if($filled_size != 3 && $filled_size > 0){
			$this->addError('You must fill in all parts of your bithday to set one');
			return false;
		}
		return true;
	}

	function beforeSave(){

		if($this->getIsNewRecord()){
			$this->lastNotificationPull = new \MongoDate();
			//$this->ts = new MongoDate();
			$this->nextBandwidthTopup = strtotime('+1 week', mktime(0, 0, 0, date('m'), date('d'), date('Y')));
			$this->bandwidthLeft = glue::$params['maxUpload'];
		}else{
			//$this->updated = new MongoDate();
		}

		if($this->getScenario() == "updatePassword" || $this->getScenario() == "recoverPassword" || $this->getIsNewRecord()){
			if($this->getScenario() == "updatePassword")
				$this->password = $this->new_password;
			$this->password = Crypt::blowfish_hash($this->password);
		}

		if($this->getScenario() == "updateEmail"){

			$hash = hash("sha256", Crypt::generate_new_pass().(substr(md5(uniqid(rand(), true)), 0, 32)));

			$this->accessToken = array(
				"to" => time()+60*60*24, // 24 Hours
				"hash" => $hash,
				"email" => $this->new_email,
				"y" => "E_CHANGE",
				"url" => glue::http()->createUrl("/user/confirminbox", array('e' => $this->new_email, 'h' => $hash, 'uid' => strval($this->_id)))
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
			$watch_later->listing = 2;
			$watch_later->userId = $this->_id;
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

			glue::sitemap()->addUrl(glue::http()->createUrl('/user/view', array('id' => $this->_id)), 'hourly', '1.0');
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

	function validateExternalLinks(){

		if(count($this->externalLinks) > 6){
			$this->setError('externalLinks', 'You can only add 6 external links for the time being. Please make sure you have entered no more and try again.');
			return false;
		}

		$valid=true;
		if(is_array($this->externalLinks)){
			foreach($this->externalLinks as $k=>$v){
				$m=new Model();
				$m->setRules(array(
					array('url', 'required', 'message' => 'One or more of the external links you entered were invalid URLs.'),
					array('url', 'url', 'message' => 'One or more of the external links you entered were invalid URLs.'),
					array('url', 'string', 'max' => 200, 'message' => 'External URLs can only be 200 characters in length'),
					array('title', 'string', 'max' => 20, 'message' => 'The optional external URL caption field can only be 200 characters in length')
				));
				$m->setAttributes($v);
				$valid=$m->validate()&&$valid;
				$this->externalLinks[$k]=$m->getAttributes(null,true);
			}
			$this->externalLinks=array_values($this->externalLinks);
		}

		if(!$valid){
			$this->setError('externalLinks', 'One or more of the external links you entered were invalid.');
			return false;
		}
		return true;
	}

	function setAvatar(){

		$ref=\MongoDBRef::create('user',$this->_id);
		$bytes=file_get_contents($this->avatar->tmp_name);

		if(
			strlen($this->avatar['tmp_name']) &&
			Image::saveAsSize($ref, $bytes, 800, 600, true) &&
			Image::saveAsSize($ref, $bytes, 48, 48) &&
			Image::saveAsSize($ref, $bytes, 55, 55) &&
			Image::saveAsSize($ref, $bytes, 125, 125)
		){
			unlink($this->avatar['tmp_name']); // Delte the file now
			$this->save();
		}
		return true;
	}

	function getAvatar($width, $height){
		if(isset(glue::$params['imagesUrl'])){
			return 'http://images.stagex.co.uk/user/'.strval($this->_id).'_w_'.$width.'_h_'.$height.'.png';
		}else{
			return Glue::http()->createUrl("/image/user", array('file' => strval($this->_id), "w"=>$width, "h"=>$height));
		}
	}

	function getGroup(){
		$groups = array_flip($this->groups());

		if(array_key_exists((int)$this->group, $groups)){
			return $groups[$this->group];
		}
		return false;
	}

	function getUsername(){
		return glue\Html::encode($this->username);
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
