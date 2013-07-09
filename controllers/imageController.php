<?php
include_once glue::getPath('@glue').'/components/phpthumb/ThumbLib.inc.php';

use app\models\User,
	app\models\Video,
	app\models\Image;

class imageController extends glue\Controller{

	public $avatar_sizes = array(
		array(30, 30),
		array(40, 40),
		array(48, 48),
		array(55, 55),
		array(125, 125)
	);

	public $thumbnail_sizes = array(
		array(33, 18),
		array(44, 26),
		array(88, 49),
		array(124, 69),
		array(138, 77),
		array(234, 130)
	);

	function action_index(){
		glue::trigger('404');
	}

	function action_video(){
		$this->title = 'Video Image - StageX';

		$file_name = glue::http()->param('file',null);
		$width = (int)glue::http()->param('w',138);
		$height = (int)glue::http()->param('h',77);

		$bytes = file_get_contents(glue::getPath('@app')."/www/images/null_images/nullthumb_".$width."_".$height.".png"); // get bytes of null img
		$resize = false;
		$insert_cache = false;
		//$found_image = true;

		$found = false;
		foreach($this->thumbnail_sizes as $size){
			if($size[0] == $width && $size[1] == $height)
				$found = true;
		}

		if(!$found){
			$width = 138;
			$height = 77;
		}

		if(strlen($file_name)>0&&Video::model()->findOne(array('_id' => new MongoId($file_name)))){
			$file=Image::model()->findOne(array('ref' => MongoDBRef::create('video',new MongoId($file_name)), 'width' => $width, 'height' => $height));
			if($file){
				$bytes = $file->bytes->bin; // The file is in the video row let's get EIT!!
			}elseif(($original_image=Image::model()->findOne(array('ref' => MongoDBRef::create('video',new MongoId($file_name)), 'original' => 1)))!==null){
				$bytes = $original_image->bytes->bin; // The file is in the video row let's get EIT!!
				$insert_cache = true;
				$resize = true;
			}			
		}
		
		$thumb = PhpThumbFactory::create($bytes, array(), true); // This will need some on spot caching soon
		if($resize) $thumb->adaptiveResize($width, $height);
		
		if($insert_cache){
			Image::saveAsSize(MongoDBRef::create('video',new MongoId($file_name)), $thumb->getImageAsString(), $width, $height);
		}
		$thumb->show();		
	}

	function action_user(){
		$this->title = 'User Avatar - StageX';

		$file_name = glue::http()->param('file',null);
		$width = (int)glue::http()->param('w',45);
		$height = (int)glue::http()->param('h',45);

		$resize=  false;
		$insert_cache = false;

		$found = false;
		foreach($this->avatar_sizes as $size){
			if($size[0] == $width && $size[1] == $height)
				$found = true;
		}

		if(!$found){
			$width = 55;
			$height = 55;
		}

		$bytes = file_get_contents(glue::getPath('@app')."/www/images/null_images/nullavatar_".$width."_".$height.".png"); // get bytes of null img

		if(strlen($file_name) > 0 && User::model()->findOne(array('_id' => new MongoId($file_name)))){ // We have to do a user lookup to make sure they are not spamming us with ids
			$file=Image::model()->findOne(array('ref' => MongoDBRef::create('user',new MongoId($file_name)), 'width' => $width, 'height' => $height));
			if($file){
				$bytes = $file->bytes->bin; // The file is in the video row let's get EIT!!
			}elseif(($original_image=Image::model()->findOne(array('ref' => MongoDBRef::create('user',new MongoId($file_name)), 'original' => 1)))!==null){
				$bytes = $original_image->bytes->bin; // The file is in the video row let's get EIT!!
				$insert_cache = true;
				$resize = true;
			}
		}

		$thumb = PhpThumbFactory::create($bytes, array(), true); // This will need some on spot caching soon
		if($resize) $thumb->adaptiveResize($width, $height);

		if($insert_cache){
			Image::saveAsSize(MongoDBRef::create('user',new MongoId($file_name)), $thumb->getImageAsString(), $width, $height);
		}
		$thumb->show();
	}
}