<?php

namespace glue;

use glue,
	\glue\Exception;

class Model extends \glue\Component{

	private $scenario;

	private $behaviours = array();
	private $rules = array();

	private $validator;
	private $valid = false;
	private $validated = false;

	private $error_codes = array();
	private $error_messages = array();

	private static $_meta;

	public function behaviours(){ return array(); }

	public function rules(){ return array(); }

	function init($scenario = 'insert'){
		foreach($this->behaviours() as $name => $attr){
			$this->attachBehaviour($name, $attr);
		}

		static $reflections=array();
		if(!isset($reflections[get_class($this)])&&get_class($this)!='\\glue\\Model'){

			$_meta = array();

			$reflect = new \ReflectionClass(get_class($o));
			$class_vars = $reflect->getProperties(ReflectionProperty::IS_PUBLIC); // Pre-defined doc attributes

			foreach ($class_vars as $prop) {

				if($prop->isStatic())
					continue;

				$docBlock = $prop->getDocComment();
				$field_meta = array(
					'name' => $prop->getName(),
					'virtual' => $prop->isProtected() || preg_match('/@virtual/i', $docBlock) <= 0 ? false : true 
					// If the field is virtual its value will not saved  
				);
				$_meta[$prop->getName()] = $field_meta;
			}
		}
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


	public function getScenario(){
		return $this->scenario;
	}

	public function setScenario($scenario){
		$this->scenario = $scenario;
	}

	public function getValidated(){
		return $this->validated;
	}

	public function setValidated($validated){
		$this->validated = $validated;
	}

	function getValid(){
		return $this->valid;
	}

	function setValid($valid){
		$this->valid=$valid;
	}

	public function setRule(){
		$this->rules[] = $rule;
	}

	public function getRules(){
		return array_merge($this->rules(), $this->rules);
	}

	public function attributeNames($safeOnly=true){
		$names=array();
		$i=0;
		foreach($this->getRules() as $rule){
			if($safeOnly && isset($rule['safe']) && $rule['safe']===false)
				continue;

			$fields=preg_split('/[\s]*[,][\s]*/', $rule[0]);
			foreach($fields as $field)
				$names[$field]=$i;
			$i++;
		}
		return array_values(array_flip($names));
	}

	/**
	 * If you want to set the full object from scratch use this
	 * @param $a
	 */
	function setAttributes($a,$safeOnly=true){
		$scenario = $this->getScenario();

		$attributes = array_flip($safeOnly ? $this->attributeNames() : $this->attributeNames(false));
		foreach($a as $name=>$value){
			if($safeOnly&&isset($attributes[$name])){
				$this->$name=!is_array($value) && preg_match('/^([0-9]|[1-9]{1}\d+)$/' /* Will only match real integers, unsigned */, $value) > 0 ? (int)$value : $value;
			}else{
				$this->$name=!is_array($value) && preg_match('/^([0-9]|[1-9]{1}\d+)$/' /* Will only match real integers, unsigned */, $value) > 0 ? (int)$value : $value;
			}
		}
	}

	public function getAttributes($db_only = false) {

		// Redo using the reflection cache above

		$attributes = array();
		$reflect = new ReflectionClass(get_class($this));
		$class_vars = $reflect->getProperties($db_only ? ReflectionProperty::IS_PROTECTED : ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PUBLIC);

		foreach ($class_vars as $prop) {
			$attributes[$prop->getName()] = $this->{$prop->getName()};
		}
		return $attributes;
	}

	/**
	 * Override this function is you want more complex handling of getting back
	 * attributes from the model for things like Html helpers and what not
	 * @param string $k
	 */
	function getAttribute($k){
		return $this->$k;
	}

	/**
	 * This function decides if the form has a summary waiting to be used
	 */
	function hasSummary(){
		if($this->getSuccess() || $this->hasErrors())
			return true;
		return false;
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

	public function validate($data = null, $rules = array(), $runEvents = true){

		if(!$data) $data = $this->getAttributes();

		if($runEvents && !$this->onBeforeValidate()){
			$this->setValidated(true); return false; // NOT VALID
		}
		if(($validator=$this->getValidator())!==null)
			$valid=$validator->run();

		$this->setValidated(true);
		$this->setValid($valid);

		if($runEvents)
			$this->onAfterValidate();
		return $valid;
	}

	function getValidator(){
		if($this->validator===null){
			return $this->validator=new \glue\Validation(array(
				'model' => $this,
				'scenario' => $this->getScenario(),
				'rules' => !empty($rules) ? $rules : array_merge($this->rules(),$this->rules)
			));
		}
		return $this->validator;
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

	function attachBehaviour($name, $options = array()){

		if(!isset($options['class']))
			throw new Exception("There is no class set for {$name} behaviour");

		if(!isset($this->behaviours[$name])){
			$behaviour = new $name;
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

/**
 * The behaviour class. All models behaviour extend from this one.
 */
class Behaviour extends \glue\Component{

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
			if(method_exists($this,$handler))
				$this->owner->attachEventHandler($event, array($this,$handler));
		}
	}

	public function detach(){
		foreach($this->events() as $event => $handler){
			$this->owner->detachEventHandler($event, array($this,$handler));
		}
		$this->owner = null;
	}
}