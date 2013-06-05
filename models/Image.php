<?php
namespace app\models;

use glue;

class Image extends \glue\db\Document{

	function collectionName(){
		return 'image';
	}

	public static function model($className = __CLASS__){
		return parent::model($className);
	}
}