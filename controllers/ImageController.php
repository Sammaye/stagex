<?php
include_once glue::getPath('@glue').'/components/phpthumb/ThumbLib.inc.php';

use \glue\Controller;
use app\models\User;
use app\models\Video;
use app\models\Image;

class ImageController extends Controller
{
	public function behaviours()
	{
		return array(
			'auth' => array(
				'class' => 'glue\Auth',
				'rules' => array(
					array('allow', 'users' => array('*')),
				)				
			)
		);
	}
	
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

	function action_index()
	{
		glue::trigger('404');
	}

	function action_video()
	{
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

		if(strlen($file_name) > 0 && ($video = Video::findOne(array('_id' => new MongoId($file_name))))){
			$file = Image::findOne(array('ref.type' => 'video', 'ref._id' => new MongoId($file_name), 'width' => $width, 'height' => $height));
			if($file){
				$bytes = $file->bytes->bin; // The file is in the video row let's get EIT!!
			}elseif(($original_image = Image::findOne(array('ref.type' => 'video', 'ref._id' => new MongoId($file_name), 'original' => 1)))!==null){
				$bytes = $original_image->bytes->bin; // The file is in the video row let's get EIT!!
				$insert_cache = true;
				$resize = true;
			}elseif($original_image = $video->image){ // last resort
			    $obj = glue::aws()->S3GetObject(pathinfo($original_image, PATHINFO_BASENAME));
			    if($obj != null){
			        $thumb = PhpThumbFactory::create($obj->getpath('Body'), array(), true); // This will need some on spot caching soon
			        $thumb->adaptiveResize(800, 600);
			        $video->setImage($thumb->getImageAsString());
			    }
			}
		}
		
		$thumb = PhpThumbFactory::create($bytes, array(), true); // This will need some on spot caching soon
		if($resize) $thumb->adaptiveResize($width, $height);
		
		if($insert_cache){
			Image::saveAsSize(array('type' => 'video', '_id' => new MongoId($file_name)), $thumb->getImageAsString(), $width, $height);
		}
		$thumb->show();
	}

	function action_user()
	{
		$file_name = glue::http()->param('file',null);
		$width = (int)glue::http()->param('w',45);
		$height = (int)glue::http()->param('h',45);

		$resize=  false;
		$insert_cache = false;

		$found = false;
		foreach($this->avatar_sizes as $size){
			if($size[0] == $width && $size[1] == $height){
				$found = true;
			}
		}

		if(!$found){
			$width = 55;
			$height = 55;
		}

		$bytes = file_get_contents(glue::getPath('@app')."/www/images/null_images/nullavatar_".$width."_".$height.".png"); // get bytes of null img

		if(strlen($file_name) > 0 && User::findOne(array('_id' => new MongoId($file_name)))){ // We have to do a user lookup to make sure they are not spamming us with ids
			$file=Image::findOne(array('ref.type' => 'user', 'ref._id' => new MongoId($file_name), 'width' => $width, 'height' => $height));
			if($file){
				$bytes = $file->bytes->bin; // The file is in the video row let's get EIT!!
			}elseif(($original_image = Image::findOne(array('ref.type' => 'user', 'ref._id' => new MongoId($file_name), 'original' => 1))) !== null){
				$bytes = $original_image->bytes->bin; // The file is in the video row let's get EIT!!
				$insert_cache = true;
				$resize = true;
			}
		}

		$thumb = PhpThumbFactory::create($bytes, array(), true); // This will need some on spot caching soon
		if($resize) $thumb->adaptiveResize($width, $height);

		if($insert_cache){
			Image::saveAsSize(array('type' => 'user', '_id' => new MongoId($file_name)), $thumb->getImageAsString(), $width, $height);
		}
		$thumb->show();
	}
}