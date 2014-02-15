<?php

namespace glue;

use Glue;
use \glue\Component;
use \glue\Collection;

class Model extends Component
{
	private $_scenario;

	private $_rules = array();

	private $_valid = false;
	private $_validated = false;

	private $_codes = array();
	private $_messages = array();

	private static $_meta=array();

	public function rules()
	{
		return array();
	}

	/**
	 * Will either look for a getter function or will just get
	 * @param string $name
	 */
	public function __get($name)
	{
		if(parent::__get($name) === null && property_exists($this, $name)){
			return $this->$name;
		}else{
			return null;
		}
	}

	/**
	 * Will either look for a setter function or will just set init
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		if(parent::__set($name, $value) === null){
			return $this->$name = $value;
		}
	}


	public function init()
	{
		$this->onAfterConstruct();
		parent::init();
	}
	
	public static function create($attributes, $rules, $runValidation = true)
	{
		$model = new static;
		$model->setRules($rules);
		$model->setAttributes($attributes);
		
		if($runValidation){
			$model->validate(false);
		}
		
		return $model;		
	}

	/**
	 * Gets the models scenario
	 */
	public function getScenario()
	{
		return $this->_scenario;
	}

	/**
	 * Sets the models Scenario
	 * @param string $scenario
	 */
	public function setScenario($scenario)
	{
		$this->_scenario = $scenario;
	}

	/**
	 * Gets a boolean value representing whether or not this modle has been validated once
	 */
	public function getValidated()
	{
		return $this->_validated;
	}

	/**
	 * Sets whether or not this model has been validated
	 * @param boolean $validated
	 */
	public function setValidated($validated)
	{
		$this->_validated = $validated;
	}

	public function getValid()
	{
		return $this->_valid;
	}

	public function setValid($valid)
	{
		$this->_valid = $valid;
	}
	
	public function setRule($rule)
	{
		$this->_rules[] = $rule;
	}
	
	/**
	 * Gets the rules of the model. if not scenario is implied within the parameter it will just get all
	 * the rules of the model, however, if a scenario is implied within the parameter then it will return only
	 * rules that shuld run on that scenario
	 */
	public function getRules($scenario=null)
	{
		$rules = array_merge($this->rules(), $this->_rules);
		if($scenario === null){
			return $rules;
		}else{
			$srules = array();
			foreach($rules as $rule){
				if(isset($rule['on'])){
					$scenarios = preg_split('/[\s]*[,][\s]*/', $rule['on']);
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

	public function setRules($rules)
	{
		$this->_rules = $rules;
	}
	
	public function resetRules()
	{
		$this->_rules = array();
	}

	/**
	 * Gets a list of attribute names of the model.
	 * Attributes are considered to be any class variable which is public and not static.
	 *
	 * It will cache reflections into the meta of the model class.
	 * @return array
	 */
	public function attributeNames()
	{
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
	public function scenarioAttributeNames()
	{
		$names=array();
		foreach($this->getRules($this->getScenario()) as $rule){
			$fields=preg_split('/[\s]*[,][\s]*/', $rule[0]);
			foreach($fields as $field){
				$names[$field]=true;
			}
		}
		return array_keys($names);
	}

	/**
	 * Gets the models attributes
	 */
	public function getAttributes($names = null, $scenario = true)
	{
		$values = array();
		if($names === null){
			if($scenario){
				$names=$this->scenarioAttributeNames();
			}else{
				$names = $this->attributeNames();
			}
		}
		foreach ($names as $name) {
			$values[$name] = $this->$name;
		}
		return $values;
	}	
	
	/**
	 * If you want to set the full object from scratch use this
	 * @param $a
	 */
	public function setAttributes($a, $safeOnly = true)
	{
		$scenario = $this->getScenario();
		$attributes = array_flip($safeOnly ? $this->scenarioAttributeNames() : $this->attributeNames());
		foreach($a as $name=>$value){
			if($safeOnly){
				if(isset($attributes[$name])){
					$this->$name = !is_array($value) && preg_match('/^([0-9]|[1-9]{1}\d+)$/' /* Will only match real integers, unsigned */, $value) > 0
						&& (string)$value < '9223372036854775807' ? (int)$value : $value;
				}
			}else{
				$this->$name = !is_array($value) && preg_match('/^([0-9]|[1-9]{1}\d+)$/' /* Will only match real integers, unsigned */, $value) > 0
					&& (string)$value < '9223372036854775807' ? (int)$value : $value;
			}
		}
		return $this;
	}

	/**
	 * Gets the errors for the model. If a field parameter is provided it will get the errors only for that field
	 * and if the getFirst parameter is set to true it will only get the first error of that field.
	 * @param string $field
	 * @param boolean $getFirst
	 * @return array
	 */
	public function getErrors($field = null, $getFirst = false)
	{
		if($field){
			if(isset($this->_messages[$field])){
				return $getFirst ? $this->_messages[$field][0] : $this->_messages[$field];
			}
		}else{
			return $this->_messages;
		}
		return array();
	}
	
	/**
	 * Gets the first global error if $field is not set or the first error for that field
	 * if it is set
	 * @param $field
	 */
	public function getFirstError($field = null)
	{
		$errors = $this->getErrors();
		if(!is_array($errors)){
			return null;
		}
	
		// If $field is not set it will take first global error
		if(!$field && isset($errors['global'])){
			return $errors['global'][0];
		}elseif(isset($errors[$field])){
			return $errors[$field][0];
		}else{
			foreach($errors as $field){
				return $field[0];
			}
		}
		return null;
	}	

	/**
	 * Sets an error on the model
	 * @param string $field
	 * @param string $message
	 */
	public function setError($field, $message = null)
	{
		if(!$message){
			$this->_messages[] = $field;
		}else{
			$this->_messages[$field][] = $message;
		}
	}
	
	public function getErrorCodes($map)
	{
		if($map !== array()){
	
			$mapped_errors = array();
				
			foreach($map as $k => $v){
				if(($pos = strpos($k,'||')) !== false){
						
					// $or condition
					$match = false;
					foreach(preg_split('/\|\|/',$k) as $f){
						list($fd, $vr) = preg_split('/_/',$f);
						if(isset($this->_codes[$fd]) && array_key_exists($vr, array_flip($this->_codes[$fd]))){
							$match = true;
						}
					}
					if($match){
						$mapped_errors['global'] = $v;
					}
				}elseif(($pos=strpos($k,'&&')) !== false){
						
					// $and condition
					$match = true;
					foreach(preg_split('/&&/',$k) as $f){
						list($fd, $vr) = preg_split('/_/',$f);
						if(isset($this->_codes[$fd]) && array_key_exists($vr, array_flip($this->_codes[$fd])))
							$match = false && $match;
					}
					if($match){
						$mapped_errors['global'] = $v;
					}
				}else{
					list($field, $validator) = preg_split('/_/',$k);
					if(isset($this->_codes[$field]) && array_key_exists($validator, array_flip($this->_codes[$field]))){
						$mapped_errors[$field] = $v;
					}
				}
			}
			return $mapped_errors;
		}
		return $this->_codes;
	}	
	
	public function setErrorCode($field, $validator)
	{
		$this->_codes[$field][] = $validator;
	}

	public function setAttributeErrors($attribute, $errors)
	{
		$this->_messages[$attribute] = $errors;
	}
	
	public function setAttributeCodes()
	{
		$this->_codes[$field] = $codes;
	}

	/**
	 * Clears all the models errors. If a field name is provided in the parameters
	 * then it will clear the errors for only one field.
	 * @param string $field
	 */
	public function clearErrors($field = null)
	{
		if($field===null){
			$this->_messages=array();
			$this->_codes=array();
		}elseif(isset($this->_messages[$field])){
			unset($this->_messages[$field]);
		}elseif(isset($this->_codes[$field])){
			unset($this->_codes[$field]);
		}
	}

	/**
	 * Validates the model while running the beforeValidate and afterValidate events of the model.
	 * @param boolean $runEvents Whether or not to run the events of the model
	 */
	public function validate($runEvents = true)
	{
		$data = $this->getAttributes();
		$this->clearErrors();
		$this->setValidated(false);
		$this->setValid(false);

		if($runEvents && !$this->onBeforeValidate()){
			$this->setValidated(true); 
			return false; // NOT VALID
		}
		
		$valid = true;
		foreach($this->getRules() as $k => $rule){
			$valid = $this->validateRule($rule) && $valid;
		}
		
		$this->setValidated(true);
		$this->setValid($valid);

		if($runEvents){
			$this->onAfterValidate();
		}
		return $valid;
	}
	
	/**
	 * Validates a single rule to an inputted document
	 *
	 * @param $rule The rule in array form
	 * @param $document The document in array form
	 */
	public function validateRule($rule)
	{
		// Now lets get the pieces of this rule
		$scope = isset($rule[0]) ? preg_split('/[\s]*[,][\s]*/', $rule[0]) : null;
		$validator = isset($rule[1]) ? $rule[1] : null;
	
		$scenario = isset($rule['on']) ? array_flip(preg_split('/[\s]*[,][\s]*/', $rule['on'])) : null;
		$message = isset($rule['message']) ? $rule['message'] : null;
	
		$params = $rule;
		unset($params[0], $params[1], $params['message'], $params['on'], $params['label']);
	
		$valid = true;
		$validator_caption = basename($validator);
	
		if(isset($scenario[$this->getScenario()]) || !$scenario){ // If the scenario key exists in the flipped $rule['on']
			foreach($scope as $k => $field){ // Foreach of the field lets check it out
	
				$field_value = isset($this->$field) ? $this->$field : null;
	
				if(method_exists($this, $validator)){
					$valid = $this->$validator($field, $field_value, $params) && $valid;
				}elseif($this->method_exists($validator)){
					$valid = $this->$validator($field, $field_value, $params) && $valid;
				}elseif($validator instanceof \Closure||(is_string($validator) && function_exists($validator))){
					$valid = $validator($field,$field_value,$params,$this) && $valid;
				}else{
					$o = new $validator($params);
					$o->owner = $this;
					$valid = $o->validateAttribute($this, $field, $field_value) && $valid;
				}
			}
		}
		//if(!$valid)
		//var_dump($rule);
		//var_dump($message);
		// If there is only one field to this rule then we can actually apply it to that field
		if(!$valid && count($scope) <= 1){
			if($message){
				$this->_messages[$field][] = $message;
			}
			$this->_codes[$field][]=$validator_caption;
		}elseif(!$valid){
			if($message){
				$this->_messages['global'][] = $message;
			}
			foreach($scope as $k => $field){ // if there are multiple fields apply the error code to every field
				$this->_codes[$field][]=$validator_caption;
			}
		}
		return $valid;
	}	

	/**
	 * Cleans the model
	 */
	public function clean()
	{
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
	public function raise($event)
	{
		if(method_exists($this,$event)){
			$f = $this->$event();
		}
		return isset($f) ? $this->trigger($event) && $f : $this->trigger($event);
	}
	
	public function onAfterConstruct()
	{
		$this->raise('afterConstruct');
	}
	
	public function onBeforeValidate()
	{
		return $this->raise('beforeValidate');
	}
	
	public function onAfterValidate()
	{
		return $this->raise('afterValidate');
	}

	/**
	 * VALIDATORS
	 */
	public function isEmpty($value, $trim = false)
	{
		return $value===null || $value===array() || $value==='' || $trim && is_scalar($value) && trim($value)==='';
	}
	
	/**
	 * Field is required
	 */
	public function required($field, $value)
	{
		if(self::isEmpty($value)){
			return false;
		}
		return true;
	}
	
	/**
	 * Checks if value entered is equal to 1 or 0, it also allows null values
	 *
	 * @param string $field The field to be tested
	 * @param mixed $value The field value to be tested
	 * @param array $params The parameters for the validator
	 */
	public function boolean($field, $value, $params)
	{
		$params = array_merge(array(
				'allowNull' => false,
				'falseValue' => 0,
				'trueValue' => 1
		), $params);
	
		if($params['allowNull'] || self::isEmpty($value))
			return true;
	
		if($value == $params['trueValue'] || ($value == $params['falseValue'] || !$value)){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * Detects the character length of a certain fields value
	 *
	 * @param $field
	 * @param $value
	 * @param $params
	 */
	public function string($field, $value, $params)
	{
		$params = array_merge(array(
				'allowEmpty' => true,
				'min' => null,
				'max' => null,
				'is' => null,
				'encoding' => null,
		), $params);
	
		if($params['allowEmpty'] && self::isEmpty($value)){
			return true;
		}
	
		if(function_exists('mb_strlen') && $params['encoding'])
			$str_length=mb_strlen($value, $params['encoding'] ? $params['encoding'] : 'UTF-8');
		else
			$str_length=strlen($value);
	
		if($params['min']){
			if($params['min'] > $str_length){ // Lower than min required
				return false;
			}
		}
	
		if($params['max']){
			if($params['max'] < $str_length){
				return false;
			}
		}
		return true;
	}
	
	public function exists($field, $value, $params)
	{
		$params = array_merge(array(
				'allowEmpty' => true,
				'class' => null,
				'condition' => null,
				'field' => null,
				'notExist' => false
		), $params);
	
		if($params['allowEmpty'] && self::isEmpty($value))
			return true;
	
		$cName = $params['class'];
		$condition = isset($params['condition']) ? $params['condition'] : array();
		$object = $cName::findOne(array_merge(array($params['field']=>$value), $condition));
	
		if($params['notExist']){
			if($object){
				return false;
			}else{
				return true;
			}
		}else{
			if($object){
				return true;
			}else{
				return false;
			}
		}
	}
	
	public function in($field, $value, $params)
	{
		$params = array_merge(array(
				'allowEmpty' => true,
				'range' => array(),
		), $params);
	
		if($params['allowEmpty'] && self::isEmpty($value)){
			return true;
		}
	
		$found = false;
		foreach($params['range'] as $match){
			if($match == $value){
				$found = true;
			}
		}
	
		if(!$found){
			return false;
		}
		return true;
	}
	
	public function nin($field, $value, $params)
	{
		$params = array_merge(array(
				'allowEmpty' => true,
				'range' => array(),
		), $params);
	
		if($params['allowEmpty'] && self::isEmpty($value)){
			return true;
		}
	
		$found = false;
		foreach($params['range'] as $match){
			if($match == $value){
				$found = true;
			}
		}
	
		if($found){
			return false;
		}
		return true;
	}
	
	public function regex($field, $value, $params)
	{
		$params = array_merge(array(
				'allowEmpty' => true,
				'pattern' => null,
				'nin' => false
		), $params);
	
		if($params['allowEmpty'] && self::isEmpty($value)){
			return true;
		}
	
		if($params['nin']){
			if(preg_match($params['pattern'], $value) > 0){
				return false;
			}
		}else{
			if(preg_match($params['pattern'], $value) <= 0 || preg_match($params['pattern'], $value) === false){
				return false;
			}
		}
		return true;
	}
	
	public function compare($field, $value, $params)
	{
		$params = array_merge(array(
				'allowEmpty' => true,
				'with' => true,
				'field' => null,
				'operator' => '=',
		), $params);
	
		if($params['allowEmpty'] && self::isEmpty($value)){
			return true;
		}
	
		$with_val = $params['with'];
		if($params['field']){
			$with_val = $this->model->{$params['with']};
		}
	
		switch($params['operator']){
			case '=':
			case '==':
				if($value == $with_val){
					return true;
				}
				break;
			case '!=':
				if($value != $with_val){
					return true;
				}
				break;
			case ">=":
				if($value >= $with_val){
					return true;
				}
				break;
			case ">":
				if($value > $with_val){
					return true;
				}
				break;
			case "<=":
				if($value <= $with_val){
					return true;
				}
				break;
			case "<":
				if($value < $with_val){
					return true;
				}
				break;
		}
		return false;
	}
	
	public function number($field, $value, $params)
	{
		$params = array_merge(array(
				'allowEmpty' => true,
				'integerOnly' => true,
				'max' => null,
				'min' => null,
				'intPattern' => '/^\s*[+-]?\d+\s*$/',
				'numPattern' => '/^\s*[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?\s*$/'
		), $params);
	
		//var_dump($vlaue); exit();
		if($params['allowEmpty'] && self::isEmpty($value)){
			return true;
		}
	
		if($params['integerOnly']){
			if(preg_match($params['intPattern'], $value) > 0){
			}else{
				return false;
			}
		}elseif(preg_match($params['numPattern'], $value) < 0 || !preg_match($params['numPattern'], $value)){
			return false;
		}
	
		if($params['min']){
			if($value < $params['min']){
				return false;
			}
		}
	
		if($params['max']){
			if($value > $params['max']){
				return false;
			}
		}
		return true;
	}
	
	public function url($field, $value, $params)
	{
		$params = array_merge(array(
				'allowEmpty' => true,
		), $params);
	
		if($params['allowEmpty'] && self::isEmpty($value)){
			return true;
		}elseif(self::isEmpty($value))
		return false;
	
		$parsed_url = parse_url($value);
	
		if(!$parsed_url){
			return false;
		}
	
		if(isset($parsed_url['scheme'])){
			if(!isset($parsed_url['host'])){
				return false;
			}else{
				return true;
			}
		}
		return false;
	}
	
	public function file($field, $value, $params)
	{
		$params = array_merge(array(
				'allowEmpty' => true,
				'ext' => null,
				'size' => null,
				'type' => null
		), $params);
	
		if($params['allowEmpty'] && self::isEmpty($value)){
			return true;
		}
	
		$fieldValue = $value;
	
		if($fieldValue->error === UPLOAD_ERR_OK){
			if(isset($params['ext'])){
				$path = pathinfo($fieldValue->name);
	
				$found = false;
				foreach($params['ext'] as $ext){
					if($ext == $path['extension'])
						$found = true;
				}
	
				if(!$found){
					return false;
				}
			}
	
			if(isset($params['size'])){
				if(isset($params['size']['gt'])){
					if($fieldValue->size < $params['size']['gt']){
						return false;
					}
				}elseif(isset($params['size']['lt'])){
					if($fieldValue->size > $params['size']['lt']){
						return false;
					}
				}
			}
	
			if(isset($params['type'])){
				if(preg_match("/".$params['type']."/i", $fieldValue->type) === false || preg_match("/".$params['type']."/i", $fieldValue->type) < 0){
					return false;
				}
			}
		}else{
			switch ($fieldValue->error) {
				case UPLOAD_ERR_INI_SIZE:
					return false;
				case UPLOAD_ERR_FORM_SIZE:
					return false;
				case UPLOAD_ERR_PARTIAL:
					return false;
				case UPLOAD_ERR_NO_FILE:
					return false;
				case UPLOAD_ERR_NO_TMP_DIR:
					return false;
				case UPLOAD_ERR_CANT_WRITE:
					return false;
				case UPLOAD_ERR_EXTENSION:
					return false;
				default:
					return false;
			}
		}
		return true;
	}
	
	public function tokenized($field, $value, $params)
	{
		$params = array_merge(array(
				'allowEmpty' => true,
				'del' => '/[\s]*[,][\s]*/',
				'max' => null
		), $params);
	
		if($params['allowEmpty'] && self::isEmpty($value)){
			return true;
		}
	
		$ex_val = preg_split($params['del'], $value);
	
		if(isset($params['max'])){
			if(count($ex_val) > $params['max']){
				return false;
			}
		}
		return true;
	}
	
	public function email($field, $value, $params = array())
	{
		$params = array_merge(array(
				'allowEmpty' => true,
		), $params);
	
		if($params['allowEmpty'] && self::isEmpty($value)){
			return true;
		}elseif(self::isEmpty($value))
		return false;
	
		if(filter_var($value, FILTER_VALIDATE_EMAIL)){
			return true;
		}
		return false;
	}
	
	public function csrf($field, $value, $params = array())
	{
		if(glue::http()->validateCsrfToken($value)){
			return true;
		}
		return false;
	}
	
	public function safe($field, $value, $params = array())
	{
		return true; // Just do this so the field gets sent through
	}
	
	public function date($field, $value, $params = array())
	{
		$params = array_merge(array(
				'format' => 'd/m/yyyy'
		), $params);
	
		// Lets tokenize the date field
		$date_parts = preg_split('/[-\/\s]+/', $value); // Accepted deliminators are -, / and space
	
		switch($params['format']){
			case 'd/m/yyyy':
				if(count($date_parts) != 3){
					return false;
				}
	
				if(preg_match('/[1-32]/', $date_parts[0]) > 0 && preg_match('/[1-12]/', $date_parts[1]) > 0 && preg_match('/[0-9]{4}/', $date_parts[2]) && $date_parts[2] <= date('Y')){
					// If date matches formation and is not in the future in this case
					return true;
				}
				break;
		}
		return false;
	}	
}