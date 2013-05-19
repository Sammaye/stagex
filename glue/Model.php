<?php

namespace glue;

class Model{

	private $doc = array();

	private $scenario;

	private $behaviours = array();
	private $rules = array();

	private $valid = false;
	private $success = false;
	private $validated = false;
	private $success_message;

	private $error_codes = array();
	private $error_messages = array();

	function __get($k){
		return $this->$k;
	}

	function __set($k, $v){
		$this->$k = $v;
	}

	function __construct($scenario = 'insert'){
		foreach($this->behaviours() as $name => $attr){
			$this->attachBehaviour($name, $attr);
		}

		$reflect = new ReflectionClass(get_class($this));
		$class_vars = $reflect->getProperties(ReflectionProperty::IS_PROTECTED);

		foreach ($class_vars as $prop) {
			$this->doc[$prop->getName()] = $this->{$prop->getName()};
		}

		$this->rules = $this->rules();
		$this->onAfterConstruct();
	}

	function __call($name, $parameters){
		foreach($this->behaviours as $k => $attr){
			if(isset($attr['obj'])){
				if(method_exists($attr['obj'], $name)){
					return call_user_func_array(array($attr['obj'],$name),$parameters); // Call behaviour methods
				}
			}
		}
		return false;
	}

	function method_exists($f){
		if(method_exists($this, $f)){
			return true;
		}else{
			foreach($this->behaviours as $k => $attr){
				if(isset($attr['obj'])){
					if(method_exists($attr['obj'], $f)){
						return true;
					}
				}
			}
		}
		return false;
	}

	function behaviours(){ return array(); }

	public function rules(){ return array(); }

	public function getScenario(){
		return $this->scenario;
	}

	public function setScenario($scenario){
		$this->scenario = $scenario;
	}

	function getErrors($field = null, $getFirst = false){
		if($field){
			if(isset($this->error_messages[$field]))
				return $getFirst ? $this->error_messages[$field][0] : $this->error_messages[$field];
		}else{
			return $this->error_messages;
		}
		return array();
	}

	function getErrorMessages(){
		return $this->getErrors();
	}

	function addError($field, $message = null){
		if(!$message){
			$this->error_messages[] = $field;
		}else{
			$this->error_messages[$field][] = $message;
		}
	}

	public function setSuccess($bool){
		$this->success = $bool;
	}

	public function getSuccess(){
		return $this->success;
	}

	public function setSuccessMessage($message){
		$this->success = true;
		$this->success_message = $message;
	}

	public function getSuccessMessage(){
		return $this->success_message;
	}

	public function getHasBeenValidated(){
		return $this->validated;
	}

	public function setHasBeenValidated($validated){
		$this->validated = $validated;
	}

	public function isValid(){
		return $this->valid;
	}

	/**
	 * If you want to set the full object from scratch use this
	 * @param $a
	 */
	function setAttributes($a){
		if($a){
			foreach($a as $k=>$v){
				if(!array_key_exists($k, $this->relations())){
					if(!is_array($v) && preg_match('/^[0-9]+$/', $v) > 0): $this->$k = (int)$v; else: $this->$k = $v; endif;
				}
			}
		}
	}

	/**
	 * If you want to assign $_POST or $_GET to the model use this
	 * @param $a
	 */
	function _attributes($a){
		$scenario = $this->getScenario();

		// Set main model fields
		foreach($this->rules as $rule){

			$scenarios = preg_split("/[\s]*[,][\s]*/", isset($rule['on']) ? $rule['on'] : '');
			if(array_key_exists($scenario, array_flip($scenarios)) || !isset($rule['on'])){
				$fields = preg_split('/[\s]*[,][\s]*/', $rule[0]);
				foreach($fields as $field){
					if(isset($a[$field])){
						if(!is_array($a[$field]) && preg_match('/^[0-9]+$/', $a[$field]) > 0): $this->$field = (int)$a[$field]; else: $this->$field = $a[$field]; endif;
					}
				}
			}
		}
	}

	public function getAttributes($db_only = false) {
		$attributes = array();
		$reflect = new ReflectionClass(get_class($this));
		$class_vars = $reflect->getProperties($db_only ? ReflectionProperty::IS_PROTECTED : ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PUBLIC);

		foreach ($class_vars as $prop) {
			$attributes[$prop->getName()] = $this->{$prop->getName()};
		}
		return $attributes;
	}

	function getAttribute($k){
		return $this->$k;
	}

	function files($a){
		$scenario = $this->getScenario();

		// Set main model fields
		foreach($this->rules() as $rule){

			$scenarios = preg_split('/[\s]*[,][\s]*/', isset($rule['on']) ? $rule['on'] : '');

			if(array_key_exists($scenario, array_flip($scenarios)) || !isset($rule['on'])){
				if($rule[1] == 'file' || $rule[1] == 'multifile'){
					$fields = preg_split('/[\s]*[,][\s]*/', $rule[0]);

					foreach($fields as $field){
						if($rule[1] == "file"){
							$this->$field = array(
								"name" => $a['name'][$field],
								"type" => $a['type'][$field],
								"tmp_name" => $a['tmp_name'][$field],
								"error" => $a['error'][$field],
								"size" => $a['size'][$field]
							);
						}elseif($rule[1] == "multifile"){
							$c = count($a['name'][$field]);
							$files = array();

							for($i=0; $i < $c; $i++){
								foreach($_FILES as $fileAttribute => $details){
									$files[$i][$fileAttribute] = $details[$field][$i];
								}
							}
							$this->$field = $files;
						}
					}
				}
			}
		}
	}

	public function getFiles($byScenario = false){
		$files = array();
		$valid = true;

		$scenario = $this->getScenario();

		foreach($this->rules() as $rule){
			if($rule[1] == 'file' || $rule[1] == 'multifile'){
				if($byScenario){
					$scenarios = preg_split('/[\s]*[,][\s]*/', $rule['on']);

					if(array_key_exists($scenario, array_flip($scenarios)) || !isset($rule['on'])){
						$valid = true;
					}else{
						$valid = false;
					}
				}

				if($valid){
					$fields = preg_split('/[\s]*[,][\s]*/', $rule[0]);
					foreach($fields as $field){
						$files[] = $this->$field;
					}
				}
			}
		}
		return $files;
	}

	public function validate($data = null, $rules = array(), $runEvents = true){

		if(!$data) $data = $this->getAttributes();

		if($runEvents && !$this->onBeforeValidate()){
			$this->setHasBeenValidated(true); return false; // NOT VALID
		}
		$this->validator=new \glue\Validation(array(
			'model' => $this,
			'scenario' => $this->getScenario(),
			'rules' => !empty($rules) ? $rules : $this->rules
		));
		$valid=$this->validator->run();

		$this->setHasBeenValidated(true);
		$this->valid = $valid;

		if($runEvents)
			$this->onAfterValidate();

		return $valid;
	}

	function validateRules($rules, $data = null, $runEvents = false){
		return $this->validate($data, $rules, $runEvents);
	}

	/**
	 * This function decides if the form has a summary waiting to be used
	 */
	function hasSummary(){
		if($this->getSuccess() || $this->hasErrors())
			return true;
		return false;
	}

	/**
	 * EVENTS
	 */

	function raiseEvent($event){
		foreach($this->behaviours as $behaviour => $attrs){
			if(isset($attrs[$event])){ // If event exists
				call_user_func_array($attrs[$event], array()); // Lets call its
			}
		}
	}

	function onAfterConstruct(){
		$this->raiseEvent('onAfterConstruct');
		return $this->afterConstruct();
	}

	function onBeforeFind(){
		$this->raiseEvent('onBeforeFind');
		return $this->beforeFind();
	}

	function onAfterFind(){
		$this->raiseEvent('onAfterFind');
		return $this->afterFind();
	}

	function onBeforeValidate(){
		$this->raiseEvent('onBeforeValidate');
		return $this->beforeValidate();
	}

	function onAfterValidate(){
		$this->raiseEvent('onAfterValidate');
		return $this->afterValidate();
	}

	function onBeforeSave(){
		$this->raiseEvent('onBeforeSave');
		return $this->beforeSave();
	}

	function onAfterSave(){
		$this->raiseEvent('onAfterSave');
		return $this->afterSave();
	}

	function onBeforeDelete(){
		$this->raiseEvent('onBeforeDelete');
		return $this->beforeDelete();
	}

	function onAfterDelete(){
		$this->raiseEvent('onAfterDelete');
		return $this->afterDelete();
	}

	function afterConstruct(){ return true; }

	function beforeFind(){ return true; }

	function afterFind(){ return true; }

	function beforeValidate(){ return true; }

	function afterValidate(){ return true; }

	function beforeSave(){ return true; }

	function afterSave(){ return true; }

	function beforeDelete(){ return true; }

	function afterDelete(){ return true; }

	/**
	 * ATTACH / DETACH FUNCTIONS
	 *
	 * These various functions concern themselves with attaching and detaching certain aspects of the model.
	 * This enables us to be able to build models dynamically and even use the std::Model class to give us anon models to play with
	 */

	public function attachValidationRule($rule){
		$this->rules[] = $rule;
	}

	public function clearRules(){
		$this->rules = null;
	}

	function attachBehaviour($name, $options = array()){

		if(!isset($options['class']))
		trigger_error("There is no class set for {$name} behaviour");

		if(!isset($this->behaviours[$name])){
			glue::import($options['class']);
			$behaviour = new $name();
			$behaviour->attributes($options);

			$this->behaviours[$name] = array(
				'obj' => $behaviour
			);
			$behaviour->attach($this);
		}
	}


	function detachBehaviour($name){
		$behaviour = $this->behaviours[$name];
		if(isset($behaviour['obj'])){
			$behaviour['obj']->detach();
		}
	}

	function attachEventHandler($event, $call_array = array()){
		$class_name = get_class($call_array[0]); // Get the name of the behaviour so we can index it
		$this->behaviours[$class_name][$event] = $call_array;
	}

	function detachEventHandler($event, $call_array = array()){
		$class_name = get_class($call_array[0]); // Get the name of the behaviour so we can index it
		unset($this->behaviours[$class_name][$event]);
	}
}

class ModelBehaviour{

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