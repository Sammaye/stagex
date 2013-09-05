<?php

namespace app\models;

use glue,
	glue\Model,
	app\models\Playlist,
	glue\util\Crypt,
	glue\Collection,
	glue\Validation;

class User extends \glue\User{

	/* ATM these are not being used
	const AUTOPUBLISH_UPLOAD=1;
	const AUTOPUBLISH_VRESPONSE=2;
	const AUTOPUBLISH_LKVIDEO=4;
	const AUTOPUBLISH_DLVIDEO=8;
	const AUTOPUBLISH_LKPLAYLIST=16;
	const AUTOPUBLISH_PLVADDED=32;
	*/
	
	/** @virtual */
	public $newEmail;
	/** @virtual */
	public $oldPassword;
	/** @virtual */
	public $newPassword;
	/** @virtual */
	public $confirmPassword;
	/** @virtual */
	public $textPassword;
	/** @virtual */
	public $avatar;
	/** @virtual */
	public $watchLater;
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

	public $defaultVideoSettings = array('listing' => 0, 'voteable' => true, 'embeddable' => true, 'moderated' => 0,
			'voteableComments' => true, 'allowVideoComments' => true, 'allowTextComments' => true, 'privateStatistics' => false, 'licence' => 1);

	public $emailVideoResponses = 0;
	//public $emailVideoResponseReplies = 0;
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
	public $allowedBandwidth;
	public $bandwidthLeft;
	public $nextBandwidthTopup;

	public $fbUid;
	public $googleUid;
	public $clickyUid;

	public $totalFollowers = 0;
	public $totalFollowing = 0;
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
			"following" => array('many', 'app\\models\\Follower', "fromId"),
			"followers" => array('many', 'app\\models\\Follower', "toId"),
			"videos" => array('many', 'Video', "user_id"),
			"playlists" => array('many', 'Playlist', "userId"),
			'notifications' => array('many', 'Notification', 'user_id'),
		);
	}

	function beforeValidate(){
		if($this->getScenario() == "updatePassword"){
			if(Crypt::verify($this->oldPassword, $this->password)){
				return true;
			}else{
				$this->setError('The old password did not match the one we have on record for you');
				return false;
			}
		}
		return true;
	}

	function rules(){
		return array(
		array('username, password, email', 'required'),

		array('autoshareUploads, autoshareResponses, autoshareLikes, autoshareAddToPlaylist, birthdayPrivacy, genderPrivacy, countryPrivacy,
				singleSignOn, emailLogins, autoplayVideos, useDivx, canUpload, banned', 'boolean', 'allowNull'=>true),

		array('username', 'string', 'max'=>20, 'message' => 'Please enter a max of 20 characters for your username'),
		array('name', 'string', 'max' => 150, 'message' => 'You can only write 150 characters for your name.'),
		array('about', 'string', 'max' => 1500, 'message' => 'You can only write 1500 characters for your bio.'),
		array('gender', 'in', 'range'=>array("m", "f"), 'message' => 'You must enter a valid gender'),

		array('birthDay', 'validateBirthday', 'on' => 'updateProfile'),
		array('birthDay', 'number', 'min'=>1, 'max'=>32, 'message' => 'Birth day was a invalid value'),
		array('birthMonth', 'number', 'min'=>1, 'max'=>12, 'message' => 'Birth month was a invalid value'),
		array('birthYear', 'number', 'min'=>date('Y') - 100, 'max'=>date('Y'), 'message' => 'Birth year was a invalid value'),

		array('country', 'in', 'range' => new Collection('countries', 'code'), 'message' => 'You supplied an invalid country.'),

		array('username', 'objExist', 'class'=>'app\\models\\User', 'field'=>'username', 'notExist' => true, 'on'=>'insert, updateUsername',
				'message' => 'That username already exists please try another.'),

		array('email,newEmail', 'email', 'message' => 'You must enter a valid Email Address'),
		array('email', 'objExist', 'class'=>'app\\models\\User', 'field'=>'email', 'notExist' => true, 'on'=>'insert', 'message' =>
				'That email address already exists please try and login with it, or if you have forgotten your password try to recover your account.'),
		array('newEmail', 'objExist', 'class'=>'app\\models\\User', 'field'=>'email', 'notExist' => true, 'message' =>
				'That email address already exists please try and login with it, or if you have forgotten your password try to recover your account.'),				

		array('safeSearch', 'in', 'range'=>array(0, 1, 2), 'message' => 'You enterd an invalid value for safe search'),

		array('oldPassword, newPassword, confirmPassword', 'required', 'on' => 'updatePassword', 'message' => 'Please fill in all fields to change your password'),
		array('confirmPassword', 'compare', 'with' => 'newPassword', 'field' => true, 'on' => 'updatePassword', 'message' => 'You did not confirm your new password correctly.'),

		array('avatar', 'file', 'size' => array('lt' => 2097152), 'on' => 'updatePic',
				'message' => 'The picture you provided was too large. Please upload 2MB and smaller pictures'),
		array('avatar', 'file', 'ext' => array('png', 'jpg', 'jpeg', 'bmp'), 'type' => 'image', 'on' => 'updatePic',
				'message' => 'You supplied an invalid file. Please upload an image file only.'),

		array('clickyUid', 'string', 'max' => 20, 'message' => 'Please enter a valid Clicky User Id'),

		array('emailVideoResponses, emailVideoResponseReplies, emailWallComments, emailEncodingResult', 'boolean', 'allowNull'=>true),

		array('externalLinks', 'validateExternalLinks'),
		array('defaultVideoSettings', 'glue\\db\\Subdocument', 'type' => 'one', 'rules' => array(
			array('listing', 'in', 'range' => array(0, 1, 2), 'message' => 'Please enter a valid value for listing'),
			array('moderated', 'in', 'range' => array(0, 1), 'message' => 'Please enter a valid value for all comment options'),
			array('voteable, embeddable, voteableComments, allowVideoComments, allowTextComments, privateStatistics', 'boolean', 'allowNull' => true),
			array('licence', 'in', 'range' => array(1, 2), 'message' => 'Please enter a valid value for licence')
		))
		);
	}

	function validateBirthday($field, $params = array()){
		$filled_size = count(array_filter(array(
			\glue\Validation::isEmpty($this->birthDay) ? null : $this->birthDay,
			\glue\Validation::isEmpty($this->birthMonth) ? null : $this->birthMonth,
			\glue\Validation::isEmpty($this->birthYear) ? null : $this->birthYear,
		)));

		if($filled_size != 3 && $filled_size > 0){
			$this->setError('You must fill in all parts of your bithday to set one');
			return false;
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

	function beforeSave(){

		if($this->getIsNewRecord()){
			$this->lastNotificationPull = new \MongoDate();
			$this->nextBandwidthTopup = strtotime('+1 week', mktime(0, 0, 0, date('m'), date('d'), date('Y')));
			$this->bandwidthLeft = glue::$params['defaultAllowedBandwidth'];
		}

		if($this->getScenario() == "updatePassword" || $this->getScenario() == "recoverPassword" || $this->getIsNewRecord()){
			if($this->getScenario() == "updatePassword")
				$this->password = $this->newPassword;
			$this->textPassword=$this->password;
			$this->password = Crypt::blowfish_hash($this->password);
		}

		if(!\glue\Validation::isEmpty($this->newEmail) && $this->newEmail!==$this->email){
			$this->setScenario('updateEmail'); // This is so we get some response in the controller about what happened
			$hash = hash("sha256", Crypt::generate_new_pass().(substr(md5(uniqid(rand(), true)), 0, 32)));

			$this->accessToken = array(
				"to" => time()+60*60*24, // 24 Hours
				"hash" => $hash,
				"email" => $this->newEmail,
				"y" => "E_CHANGE",
				"url" => glue::http()->url("/user/confirminbox", array('e' => $this->newEmail, 'h' => $hash, 'uid' => strval($this->_id)))
			);
		}
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

			glue::sitemap()->addUrl(glue::http()->url('/user/view', array('id' => $this->_id)), 'hourly', '1.0');
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
	      		"username"=>$this->username, "email"=>$this->email, "password"=>$this->textPassword ));
		}

		if($this->getScenario() == "updateEmail"){
			glue::mailer()->mail($this->email, array('no-reply@stagex.co.uk', 'StageX'), 'Verify your email change', 'user/verify_email_inbox.php',
				array( "username" => $this->username, "verify_url" => $this->accessToken['url'], 'new_email' => $this->newEmail ));
		}

		return true;
	}

	function setAvatar(){

		$ref=\MongoDBRef::create('user',$this->_id);
		$bytes=file_get_contents($this->avatar->tmp_name);

		if(
			strlen($this->avatar->tmp_name) &&
			Image::saveAsSize($ref, $bytes, 800, 600, true) &&
			Image::saveAsSize($ref, $bytes, 48, 48) &&
			Image::saveAsSize($ref, $bytes, 55, 55) &&
			Image::saveAsSize($ref, $bytes, 125, 125)
		){
			unlink($this->avatar->tmp_name); // Delete the file now
			$this->save();
		}
		return true;
	}

	function getAvatar($width, $height){
		if(isset(glue::$params['imagesUrl'])){
			return 'http://images.stagex.co.uk/user/'.strval($this->_id).'_w_'.$width.'_h_'.$height.'.png';
		}else{
			return Glue::http()->url("/image/user", array('file' => strval($this->_id), "w"=>$width, "h"=>$height));
		}
	}

	function getGroup(){
		$groups = array_flip($this->groups());
		if(array_key_exists((int)$this->group, $groups))
			return $groups[$this->group];
		return false;
	}

	function getUsername(){
		return \glue\Html::encode($this->username);
	}

	function getAbout(){
		return nl2br(\glue\Html::encode($this->about));
	}
	
	function getBirthdayTime(){
		if(isset($this->birthDay,$this->birthMonth,$this->birthYear)&&$this->birthMonth&&$this->birthDay&&$this->birthYear)
			return mktime(0, 0, 0, $this->birthMonth, $this->birthDay, $this->birthYear);
		return 0;
	}

	function get_allowed_bandwidth(){
		return $this->allowedBandwidth > 0 ? $this->allowedBandwidth : glue::$params['defaultAllowedBandwidth'];
	}

	function reset_upload_bandwidth(){
		if($this->nextBandwidthTopup < time()){
			$this->nextBandwidthTopup = strtotime('+1 week', mktime(0, 0, 0, date('m'), date('d'), date('Y')));
			$this->bandwidthLeft = $this->allowedBandwidth > 0 ? $this->allowedBandwidth : glue::$params['defaultAllowedBandwidth'];
			$this->save();
		}
	}

	function get_max_video_upload_size(){
		if(isset($this->maxFileSize) && $this->maxFileSize != null){
			return $this->maxFileSize;
		}
		return glue::$params['maxFileSize'];
	}

	function create_username_from_social_signup($username){
		if($this->getCollection()->findOne(array('username' => $username))){
			for($i=0;$i<5;$i++){
				if($i == 3 || $i == 4){ // Lets go for even more unique and nuke the username
					$new_username = substr(substr($username, 0, 10).(md5( uniqid( rand(1,255).rand(45,80).rand(112,350), true ))), 0, 20);
				}else{
					$new_username = substr($username.uniqid(), 0, 20);
				}

				if(!$this->getCollection()->findOne(array('username' => $new_username))){
					$username = $new_username;
					break;
				}
			}
		}
		return $this->username=$username;
	}
	
	function deactivate(){
		$this->deleted=1;
		$this->save();
		//$this->getCollection()->save(array('_id' => $this->_id, 'date_deleted' => new \MongoDate(), 'deleted' => 1, 'username' => '[User Deleted]')); // Empty the document
		glue::db()->qeue->insert(array('id' => $this->_id, 'type' => 'user', 'ts' => new \MongoDate()));		
	}
	
	function autoPublishStreamItem(){
		if(!glue::db()->auto_publish_queue->findOne(array('type'=>$type,'userId'=>$user_id,'videoId'=>$video_id,'playlistId'=>$playlist_id,'text'=>$text))){
			glue::db()->auto_publish_queue->insert(array(
				'type' => $type,
				'userId' => $user_id,
				'videoId' => $video_id,
				'playlistId' => $playlist_id,
				'text' => $text
			));
		}		
	}
	
	function watchLaterPlaylist(){
		if(
			$this->watchLater===null && 
			($playlist=Playlist::model()->findOne(array('title' => 'Watch Later', 'userId' => glue::user()->_id)))!==null
		)
			$this->watchLater=$playlist;
		elseif($this->watchLater===null)
			$this->watchLater=new Playlist();
		return $this->watchLater;
	}
	
	function recordWatched($video){
		glue::db()->watched_history->update(array(
			'user_id' => $this->_id, 'item' => $video->_id), array('$set' => array('ts' => new \MongoDate())), array('upsert' => true));
	}
}