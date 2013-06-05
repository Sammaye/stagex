<?php
namespace app\models;

use glue;

include_once glue::getPath('@glue').'/components/phpthumb/ThumbLib.inc.php';

class Image extends \glue\db\Document{

	function collectionName(){
		return 'image';
	}

	public static function model($className = __CLASS__){
		return parent::model($className);
	}

	public static function saveAsSize(MongoDBRef $ref, $bytes, $width, $height, $original=false){
		$thumb = PhpThumbFactory::create($bytes, array(), true); // This will need some on spot caching soon
		$thumb->adaptiveResize($width, $height);

		return self::model()->insert(array(
			'ref'=>$ref,
			'bytes'=>new MongoBinData($thumb->getImageAsString()),
			'width'=>$width,
			'height'=>$height,
			'original'=>$original,
			'created'=>new MongoDate()
		));
	}

}