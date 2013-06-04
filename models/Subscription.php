<?php

namespace app\models;

use glue;

class Subscription extends \glue\db\Document{

	protected $toId;
	protected $fromId;
	
	function behaviours(){
		return array(
				'timestampBehaviour' => array(
						'class' => 'glue\\behaviours\\Timestamp'
				)
		);
	}	

	function collectionName(){
		return "subscription";
	}

	function relations(){
		return array(
			"user_subscribed" => array('one', 'User', "_id", 'on' => 'toId'),
			"subscription_user" => array('one', 'User', "_id", 'on' => 'fromId'),
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

	function beforeSave(){
		return true;
	}

	function afterSave(){
		if($this->getIsNewRecord()){
			$this->user_subscribed->totalSubscribers = $this->user_subscribed->totalSubscribers+1;
			$this->subscription_user->totalSubscriptions = $this->user_subscribed->totalSubscriptions+1;
			$this->user_subscribed->save();
			$this->subscription_user->save();
		}
	}

	function afterDelete(){
		$this->user_subscribed->totalSubscribers = $this->user_subscribed->totalSubscribers > 1 ? $this->user_subscribed->totalSubscribers-1 : 0;
		$this->subscription_user->totalSubscriptions = $this->user_subscribed->totalSubscriptions > 1 ? $this->user_subscribed->totalSubscriptions-1 : 0;
		$this->user_subscribed->save();
		$this->subscription_user->save();
	}
}