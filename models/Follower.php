<?php

namespace app\models;

use glue;
use \glue\db\Document;
use app\models\User;

class Follower extends Document
{
	public $fromId;
	public $toId;
	
	public function behaviours()
	{
		return array(
			'timestampBehaviour' => array(
				'class' => 'glue\\behaviours\\Timestamp'
			)
		);
	}	

	public static function collectionName()
	{
		return "follower";
	}

	public function relations()
	{
		return array(
			"follower" => array('one', 'app\\models\\User', "_id", 'on' => 'fromId'),
			"following" => array('one', 'app\\models\\User', "_id", 'on' => 'toId'),
		);
	}

	public static function getAllIds()
	{
		$rows = static::find(array('fromId' => glue::user()->_id));
		$_ids = array();

		foreach($rows as $k => $row){
			$_ids[] = $row->toId;
		}
		return $_ids;
	}

	public static function isSubscribed($user_id)
	{
		return self::findOne(array('fromId' => glue::user()->_id, 'toId' => $user_id)) != null;
	}

	public function afterSave()
	{
		if($this->getIsNewRecord()){
			$this->following->saveCounters(array('totalFollowers'=>1));
			$this->follower->saveCounters(array('totalFollowing'=>1));
		}
	}

	public function afterDelete()
	{
		$this->following->saveCounters(array('totalFollowers'=>-1),0);
		$this->follower->saveCounters(array('totalFollowing'=>-1),0);
	}
	
	public static function search($userId, $term, $limit = 1000, $searchFollowers = false)
	{
		// We need to do a JOIN here...
		$idRange = array();
		if($term){
			$users = iterator_to_array(User::find(array('username'=>new \MongoRegex("/^$term/")))->sort(array('username'=>1))->limit($limit));
			$mongoIds = array();
			foreach($users as $_id => $user){
				$mongoIds[]=new \MongoId($_id);
			}
			
			if($searchFollowers){
				$idRange = array('fromId' => array('$in' => $mongoIds));
				$relations = static::find(array_merge(array('toId' => $userId), $idRange));				
			}else{
				$idRange = array('toId' => array('$in' => $mongoIds));
				$relations = static::find(array_merge(array('fromId' => $userId), $idRange));
			}
			
			$rUsers = array();
			foreach($relations as $_id => $follower){
				if($searchFollowers){
					if($user = $users[strval($follower->fromId)]){
						$rUsers[strval($user->_id)] = $user;
					}
				}else{
					if($user = $users[strval($follower->toId)]){
						$rUsers[strval($user->_id)] = $user;
					}
				}
			}
			return $rUsers;
			
		}else{
			
			if($searchFollowers){
				$relations = static::find(array('toId' => $userId))->limit(20);
			}else{
				$relations = static::find(array('fromId' => $userId))->limit(20);
			}
			$mongoIds = array();
			foreach($relations as $_id => $follower){
				if($searchFollowers){
					$mongoIds[] = new \MongoId($follower->fromId);
				}else{
					$mongoIds[] = new \MongoId($follower->toId);
				}
			}
			$users = User::find(array('_id' => array('$in' => $mongoIds)))->sort(array('username' => 1));
			return $users;
		}
	}
	
	public static function searchFollowers($userId, $term, $limit = 1000)
	{
		return static::search($userId, $term, $limit, true);
	}
}