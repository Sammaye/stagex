<?php
class GModelBehaviour{

	public $owner;

	public function events(){
		return array(
			'onBeforeFind' => 'beforeFind',
			'onAfterFind' => 'afterFind',
			'onBeforeValidate' => 'beforeValidate',
			'onAfterValidate' => 'afterValidate',
			'onBeforeSave' => 'beforeSave',
			'onAfterSave' => 'afterSave',
			'onBeforeDelete' => 'beforeDelete',
			'onAfterDelete' => 'afterDelete'
		);
	}

	public function attach($owner){
		$this->owner = $owner;
		foreach($this->events() as $event => $handler){
			$this->owner->attachEventHandler($event, array($this,$handler));
		}
	}

	public function detach(){
		foreach($this->events() as $event => $handler){
			$this->owner->detachEventHandler($event, array($this,$handler));
		}
		$this->owner = null;
	}

	public function attributes($a){
		if(is_array($a)){
			foreach($a as $k => $v){
				$this->$k = $v;
			}
		}
	}

	public function beforeValidate(){}

	public function afterValidate(){}

	public function beforeSave(){}

	public function afterSave(){}

	public function beforeDelete(){}

	public function afterDelete(){}

	public function beforeFind(){}

	public function afterFind(){}
}