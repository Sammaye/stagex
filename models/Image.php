<?php
namespace app\models;

use glue;
use glue\db\Document;

include_once glue::getPath('@glue').'/components/phpthumb/ThumbLib.inc.php';

class Image extends Document
{
	public static function collectionName()
	{
		return 'image';
	}

	public static function saveAsSize($ref, $bytes, $width, $height, $original=false)
	{
		$thumb = \PhpThumbFactory::create($bytes, array(), true); // This will need some on spot caching soon
		$thumb->adaptiveResize($width, $height);

		return static::updateAll(array(
			'ref.type' => $ref['type'], 
			'ref._id' => $ref['_id'], 
			'width' => $width, 
			'height' => $height
		), array('$set' => array(
			'ref' => $ref,
			'bytes' => new \MongoBinData($thumb->getImageAsString(),2),
			'width' => $width,
			'height' => $height,
			'original' => $original,
			'created' => new \MongoDate()
		)), array('upsert' => true));
	}
}