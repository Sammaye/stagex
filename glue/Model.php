<?php

namespace glue;

use \glue\Exception;

class Model{

	private $scenario;

	private $behaviours = array();
	private $rules = array();

	private $validator;
	private $valid = false;
	private $validated = false;

	private $error_codes = array();
	private $error_messages = array();

	private static $_meta=array();

	public function behaviours(){ return array(); }

	public function rules(){ return array(); }

	/**
	 * Will either look for a getter function or will just get
	 * @param string $name
	 */
	public function __get($name){
		if(method_exists($this,'get'.$name)){
			return $this->{'get'.$name}();
		}elseif(property_exists($this,$name))
			return $this->$name;
		else
			return null;
	}

	/**
	 * Will either look for a setter function or will just set init
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value){
		if(method_exists($this,'set'.$name)){
			return $this->{'set'.$name}($value);
		}
		return $this->$name=$value;
	}

	/**
	 * The constructor, all it does is attach behaviours and fires onAfterConstruct
	 * @param string $scenario
	 */
	function __construct($scenario = 'insert'){
		foreach($this->behaviours() as $name => $attr){
			$this->attachBehaviour($name, $attr);
		}
		$this->onAfterConstruct();
	}

	/**
	 * Magically you can call any function within a behaviour as though they are part of the
	 * parent model
	 * @param string $name
	 * @param array $parameters
	 */
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

	/**
	 * Checks to see if a method exists. This will search all behaviours as well to see if a method exists
	 * @param string $f
	 * @return boolean
	 */
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

	/**
	 * Gets the models scenario
	 */
	public function getScenario(){
		return $this->scenario;
	}

	/**
	 * Sets the models Scenario
	 * @param string $scenario
	 */
	public function setScenario($scenario){
		$this->scenario = $scenario;
	}

	/**
	 * Gets a boolean value representing whether or not this modle has been validated once
	 */
	public function getValidated(){
		return $this->validated;
	}

	/**
	 * Sets whether or not this model has been validated
	 * @param boolean $validated
	 */
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

	public function setRules($rules){
		$this->rules=$rules;
	}

	/**
	 * Gets the rules of the model. if not scenario is implied within the parameter it will just get all
	 * the rules of the model, however, if a scenario is implied within the parameter then it will return only
	 * rules that shuld run on that scenario
	 */
	public function getRules($scenario=null){
		$rules = array_merge($this->rules(), $this->rules);
		if($scenario===null)
			return $rules;
		else{
			$srules=array();
			foreach($rules as $rule){
				if(isset($rule['on'])){
					$scenarios=preg_split('/[\s]*[,][\s]*/', $rule['on']);
					if(array_key_exists($scenario, array_flip($scenarios))){
						$srules[] = $rule;
					}
				}else{
					$srules[] = $rule;
				}
			}
			return $srules;
		}
	}

	/**
	 * Gets a list of attribute names of the model.
	 * Attributes are considered to be any class variable which is public and not static.
	 *
	 * It will cache reflections into the meta of the model class.
	 * @return array
	 */
	public function attributeNames(){
		if(isset(self::$_meta[get_class($this)])){
			return self::$_meta[get_class($this)];
		}

		$class = new \ReflectionClass($this);
		$names = array();
		foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
			$name = $property->getName();
			if (!$property->isStatic()) {
				$names[] = $name;
			}
		}
		return self::$_meta[get_class($this)]=$names;
	}

	/**
	 * Gets a list of the attribute names currently stored within the rules of the model.
	 * This method can be overriden to just return an array of names as the values of each element
	 */
	public function scenarioAttributeNames(){
		$names=array();
		foreach($this->getRules($this->getScenario()) as $rule){
			$fields=preg_split('/[\s]*[,][\s]*/', $rule[0]);
			foreach($fields as $field)
				$names[$field]=true;
		}
		return array_keys($names);
	}

	/**
	 * If you want to set the full object from scratch use this
	 * @param $a
	 */
	function setAttributes($a,$safeOnly=true){
		$scenario = $this->getScenario();
		$attributes = array_flip($safeOnly ? $this->scenarioAttributeNames() : $this->attributeNames());
		foreach($a as $name=>$value){
			if($safeOnly){
				if(isset($attributes[$name])){
					$this->$name=!is_array($value) && preg_match('/^([0-9]|[1-9]{1}\d+)$/' /* Will only match real integers, unsigned */, $value) > 0
						&& (string)$value < '9223372036854775807' ? (int)$value : $value;
				}
			}else{
				$this->$name=!is_array($value) && preg_match('/^([0-9]|[1-9]{1}\d+)$/' /* Will only match real integers, unsigned */, $value) > 0
					&& (string)$value < '9223372036854775807' ? (int)$value : $value;
			}
		}
		return $this;
	}

	/**
	 * Gets the models attributes
	 */
	public function getAttributes($names=null,$scenario=true) {

		$values = array();
		if ($names === null) {
			if($scenario)
				$names=$this->scenarioAttributeNames();
			else
				$names = $this->attributeNames();
		}
		foreach ($names as $name) {
			$values[$name] = $this->$name;
		}
		//foreach ($except as $name) {
			//unset($values[$name]);
		//}

		return $values;
	}

	/**
	 * Gets the errors for the model. If a field parameter is provided it will get the errors only for that field
	 * and if the getFirst parameter is set to true it will only get the first error of that field.
	 * @param string $field
	 * @param boolean $getFirst
	 * @return array
	 */
	function getErrors($field = null, $getFirst = false){
		if($field){
			if(isset($this->error_messages[$field]))
				return $getFirst ? $this->error_messages[$field][0] : $this->error_messages[$field];
		}else{
			return $this->error_messages;
		}
		return array();
	}

	/**
	 * Sets an error on the model
	 * @param string $field
	 * @param string $message
	 */
	function setError($field, $message = null){
		if(!$message){
			$this->error_messages[] = $field;
		}else{
			$this->error_messages[$field][] = $message;
		}
	}

	function setAttributeErrors($attribute,$errors){
		$this->error_messages[$attribute]=$errors;
	}

	/**
	 * Clears all the models errors. If a field name is provided in the parameters
	 * then it will clear the errors for only one field.
	 * @param string $field
	 */
	function clearErrors($field=null){
		if($field===null){
			$this->error_messages=array();
			$this->error_codes=array();
		}elseif(isset($this->error_messages[$field])){
			unset($this->error_messages[$field]);
		}elseif(isset($this->error_codes[$field]))
			unset($this->error_codes[$field]);
	}

	/**
	 * Validates the model while running the beforeValidate and afterValidate events of the model.
	 * @param boolean $runEvents Whether or not to run the events of the model
	 */
	public function validate($runEvents = true){

		$data = $this->getAttributes();
		$this->clearErrors();
		$this->setValidated(false);

		if($runEvents && !$this->onBeforeValidate()){
			$this->setValidated(true); return false; // NOT VALID
		}
		if(($validator=$this->getValidator())!==null){
			$validator->model=$this;
			$validator->scenario=$this->getScenario();
			$validator->rules=$this->getRules($validator->scenario);
			$valid=$validator->run();
		}

		$this->error_codes=\glue\Collection::mergeArray($this->error_codes,$validator->error_codes);
		$this->error_messages=\glue\Collection::mergeArray($this->error_messages,$validator->error_messages);

		$this->setValidated(true);
		$this->setValid($valid);

		if($runEvents)
			$this->onAfterValidate();
		return $valid;
	}

	/**
	 * Either creates or returns the models validator
	 * @param array $rules
	 * @return \glue\Validation
	 */
	function getValidator(){
		if($this->validator===null){
			return $this->validator=new \glue\Validation();
		}
		return $this->validator;
	}

	/**
	 * Cleans the model
	 */
	public function clean(){
		$names=$this->attributeNames();
		foreach($names as $name)
			unset($this->$name);
		$nnames=$this->attributeNames(null,true);
		foreach($nnames as $name)
			unset($this->$name);
		return true;
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

	function attachBehaviours($behaviours){
		if(is_array($behaviours)){
			foreach($behaviours as $name => $behaviour)
				$this->attachBehaviour($name, $behaviour);
		}
	}

	function attachBehaviour($name, $options = array()){

		if(!isset($options['class']))
			throw new Exception("There is no class set for {$name} behaviour");

		if(!isset($this->behaviours[$name])){
			$cname=$options['class'];
			$behaviour = new $cname;
			$behaviour->setAttributes($options);

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