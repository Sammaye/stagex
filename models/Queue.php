<?php

namespace app\models;

use glue;
use glue\db\Document;

class Queue extends Document
{
	const DELETE = 1;
	
	public $ref;
	public $type;
	
	function behaviours()
	{
		return array(
			'timestampBehaviour' => array(
				'class' => 'glue\\behaviours\\Timestamp'
			)
		);
	}
	
	public static function collectionName()
	{
		return "queue";
	}	
	
	public static function addMessage($collection, $id, $const)
	{
		$m = new static;
		$m->ref = \MongoDBRef::create($collection,$id);
		$m->type = $const;
		return $m->save();
	}
}