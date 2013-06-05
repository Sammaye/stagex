<?php
include_once glue::getPath('@glue').'/components/phpthumb/ThumbLib.inc.php';

use app\models\User,
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
		$this->pageTitle = 'Image Error - StageX';
		trigger_error('No image specified. Cannot return nothing');
	}

	function action_video(){
		$this->pageTitle = 'Video Image - StageX';

		$file_name = isset($_GET['file']) ? $_GET['file'] : null;
		$width = isset($_GET['w']) ? $_GET['w'] : 138;
		$height = isset($_GET['h']) ? $_GET['h'] : 77;

		$bytes = null; // Image contents
		$resize = false;
		$insert_cache = false;
		$found_image = true;

		$found = false;
		foreach($this->thumbnail_sizes as $size){
			if($size[0] == $width && $size[1] == $height)
				$found = true;
		}

		if(!$found){
			$width = 138;
			$height = 77;
		}

		$video = glue::db()->videos->findOne(array('_id' => new MongoId($file_name)));
		$mongo_file = glue::db()->image_cache->findOne(array('object_id' => new MongoId($file_name), 'width' => $width, 'height' => $height, 'type' => 'video'));

		if($video){
			if($mongo_file){
				$bytes = $mongo_file['data']->bin; // The file is in the video row let's get EIT!!
			}else{
				// If for some reason the image file was not assigned when the notification for the video came down do it now
				$orig_image = isset($video['image_src']) ? $video['image_src'] : null;
				if($orig_image instanceof MongoBinData){
					$bytes = $orig_image->bin;
					$insert_cache = true;
					$resize = true;
				}else{
					$found_image = false;
				}
			}
		}

		if(!$found_image || !$video)
			$bytes = file_get_contents(ROOT."/images/null_images/nullthumb_".$width."_".$height.".png"); // No Image

		$thumb = PhpThumbFactory::create($bytes, array(), true); // This will need some on spot caching soon
		if($resize) $thumb->adaptiveResize($width, $height);

		if($insert_cache){
			glue::db()->image_cache->update(array('object_id' => $video['_id'], 'width' => $width, 'height' => $height, 'type' => 'video'),
				array('$set' => array('data' => new MongoBinData($thumb->getImageAsString()),
			)), array('upsert' => true));
		}

		$thumb->show();
	}

	function action_user(){
		$this->pageTitle = 'User Avatar - StageX';

		$file_name = glue::http()->param('file',null);
		$width = glue::http()->param('w',45);
		$height = glue::http()->param('h',45);

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
				$bytes = $file->data->bin; // The file is in the video row let's get EIT!!
			}elseif(($original_image=Image::model()->findOne(array('ref' => MongoDBRef::create('user',new MongoId($file_name)), 'original' => true)))!==null){
				$bytes = $original_image->data->bin; // The file is in the video row let's get EIT!!
				$insert_cache = true;
				$resize = true;
			}
		}

		$thumb = PhpThumbFactory::create($bytes, array(), true); // This will need some on spot caching soon
		if($resize) $thumb->adaptiveResize($width, $height);

		if($insert_cache){
			glue::db()->image_cache->update(array('ref' => MongoDBRef::create('user',new MongoId($file_name)), 'width' => $width, 'height' => $height),
				array('$set' => array('data' => new MongoBinData($thumb->getImageAsString()),
			)), array('upsert' => true));
		}
		$thumb->show();
	}
}