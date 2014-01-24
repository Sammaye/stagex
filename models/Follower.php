<?php

namespace app\models;

use glue;
use \glue\db\Document;

class Follower extends Document
{
	public $fromId;
	public $toId;
	
	function behaviours(){
		return array(
			'timestampBehaviour' => array(
				'class' => 'glue\\behaviours\\Timestamp'
			)
		);
	}	

	public static function collectionName(){
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
			$_ids[] = $row->toId;
		}
		return $_ids;
	}

	public static function isSubscribed($user_id){
		return self::findOne(array('fromId' => glue::user()->_id, 'toId' => $user_id)) != null;
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
	
	public static function search($user_id,$term,$limit=1000){
		// We need to do a JOIN here...
		$idRange=array();
		if($term){
			$users=iterator_to_array(\app\models\User::find(array('username'=>new \MongoRegex("/^$term/")))->sort(array('username'=>1))->limit($limit));
			$mongoIds=array();
			foreach($users as $_id=>$user)
				$mongoIds[]=new \MongoId($_id);
			$idRange=array('toId'=>array('$in'=>$mongoIds));
			
			$following=static::find(array_merge(array('fromId'=>$user_id),$idRange));
			
			$followedUsers=array();
			foreach($following as $_id=>$follower){
				if($user=$users[strval($follower->toId)])
					$followedUsers[strval($user->_id)]=$user;
			}
			return $followedUsers;			
			
		}else{
			$following=static::find(array('fromId'=>$user_id))->limit(20);
			$mongoIds = array();
			foreach($following as $_id=>$follower)
				$mongoIds[]=new \MongoId($follower->toId);			
			$users=\app\models\User::find(array('_id'=>array('$in'=>$mongoIds)))->sort(array('username'=>1));
			return $users;
		}
	}
}