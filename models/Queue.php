<?php

namespace app\models;

use glue;

class Queue extends \glue\db\Document{
	
	const DELETE=1;
	
	public $ref;
	public $type;
	
	function behaviours(){
		return array(
			'timestampBehaviour' => array(
				'class' => 'glue\\behaviours\\Timestamp'
			)
		);
	}
	
	function collectionName(){
		return "queue";
	}	
	
	public static function model($className = __CLASS__){
		return parent::model($className);
	}	
	
	static function addMessage($collection,$id,$const){
		$m=new static;
		$m->ref=\MongoDBRef::create($collection,$id);
		$m->type=$const;
		return $m->save();
	}
}