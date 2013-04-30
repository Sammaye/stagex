<?php
class Subscription extends MongoDocument{

	protected $to_id;
	protected $from_id;
	protected $ts;

	function getCollectionName(){
		return "subscription";
	}

	function relations(){
		return array(
			"user_subscribed" => array(self::HAS_ONE, 'User', "_id", 'on' => 'to_id'),
			"subscription_user" => array(self::HAS_ONE, 'User', "_id", 'on' => 'from_id'),
		);
	}

	function getAll_ids(){
		$rows = $this->Db()->find(array('from_id' => glue::session()->user->_id));
		$_ids = array();

		foreach($rows as $k => $row){
			$_ids[] = $row['to_id'];
		}
		return $_ids;
	}

	public static function isSubscribed($user_id){
		return self::model()->findOne(array('from_id' => glue::session()->user->_id, 'to_id' => $user_id)) != null;
	}

	public static function model($className = __CLASS__){
		return parent::model($className);
	}

	function beforeSave(){
		if($this->getIsNewRecord()){
			$this->ts = new MongoDate();
		}
		return true;
	}

	function afterSave(){
		if($this->getIsNewRecord()){
			$this->user_subscribed->total_subscribers = $this->user_subscribed->total_subscribers+1;
			$this->subscription_user->total_subscriptions = $this->user_subscribed->total_subscriptions+1;
			$this->user_subscribed->save();
			$this->subscription_user->save();
		}
	}

	function afterDelete(){
		$this->user_subscribed->total_subscribers = $this->user_subscribed->total_subscribers > 1 ? $this->user_subscribed->total_subscribers-1 : 0;
		$this->subscription_user->total_subscriptions = $this->user_subscribed->total_subscriptions > 1 ? $this->user_subscribed->total_subscriptions-1 : 0;
		$this->user_subscribed->save();
		$this->subscription_user->save();
	}
}