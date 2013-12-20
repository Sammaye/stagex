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

	public static function saveAsSize($ref, $bytes, $width, $height, $original=false){
		$thumb = \PhpThumbFactory::create($bytes, array(), true); // This will need some on spot caching soon
		$thumb->adaptiveResize($width, $height);

		$m=new Image;
		return $m->setAttributes(array(
			'ref'=>$ref,
			'bytes'=>new \MongoBinData($thumb->getImageAsString(),2),
			'width'=>$width,
			'height'=>$height,
			'original'=>$original,
			'created'=>new \MongoDate()
		),false)->upsert(array('ref.type' => $ref['type'], 'ref._id' => $ref['_id'], 'width' => $width, 'height' => $height));
	}
}