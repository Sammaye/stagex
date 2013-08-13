<?php

namespace app\models;

use glue;

class Follower extends \glue\db\Document{

	public $fromId;
	public $toId;
	
	function behaviours(){
		return array(
			'timestampBehaviour' => array(
				'class' => 'glue\\behaviours\\Timestamp'
			)
		);
	}	

	function collectionName(){
		return "follower";
	}

	function relations(){
		return array(
			"follower" => array('one', 'app\\models\\User', "_id", 'on' => 'fromId'),
			"following" => array('one', 'app\\models\\User', "_id", 'on' => 'toId'),
		);
	}

	function getAll_ids(){
		$rows = $this->find(array('fromId' => glue::user()->_id));
		$_ids = array();

		foreach($rows as $k => $row){
			$_ids[] = $row['to_id'];
		}
		return $_ids;
	}

	public static function isSubscribed($user_id){
		return self::model()->findOne(array('fromId' => glue::user()->_id, 'toId' => $user_id)) != null;
	}

	public static function model($className = __CLASS__){
		return parent::model($className);
	}

	function afterSave(){
		if($this->getIsNewRecord()){
			$this->following->saveCounters(array('totalFollowers'=>1));
			$this->follower->saveCounters(array('totalFollowing'=>1));
		}
	}

	function afterDelete(){
		$this->following->saveCounters(array('totalFollowers'=>-1),0);
		$this->follower->saveCounters(array('totalFollowing'=>-1),0);
	}
}