<?php

namespace glue;

use glue,
	\glue\Exception,
	\glue\Collection;

class Validation extends \glue\Component{

	public $model;
	public $scenario;
	public $rules;

	public $valid;

	public $error_codes = array();
	public $error_messages=array();

//	public function __construct($config){
//		$d=new \glue\util\Crypt();
//		var_dump($d); exit();
//		trigger_error('f');
//	}

	public function run(){
		$valid = true;
		$errors = array();

		if(!$this->model)
			throw new Exception("No model or map was provided to validate against");
		if(!is_array($this->rules))
			throw new Exception("A valid set of rules must be applied");

		foreach($this->rules as $k => $rule)
			$valid=$this->validateRule($rule)&&$valid;
		return $this->valid=$valid; // Return whether valid or not
	}

	/**
	 * Validates a single rule to an inputted document
	 *
	 * @param $rule The rule in array form
	 * @param $document The document in array form
	 */
	private function validateRule($rule){

		// Now lets get the pieces of this rule
		$scope = isset($rule[0]) ? preg_split('/[\s]*[,][\s]*/', $rule[0]) : null;
		$validator = isset($rule[1]) ? $rule[1] : null;

		$scenario = isset($rule['on']) ? array_flip(preg_split('/[\s]*[,][\s]*/', $rule['on'])) : null;
		$message = isset($rule['message']) ? $rule['message'] : null;

		$params = $rule;
		unset($params[0], $params[1], $params['message'], $params['on'], $params['label']);

		$valid = true;
		$validator_caption=basename($validator);

		if(isset($scenario[$this->scenario]) || !$scenario){ // If the scenario key exists in the flipped $rule['on']
			foreach($scope as $k => $field){ // Foreach of the field lets check it out

				if(is_object($this->model)){
					$field_value = isset($this->model->$field) ? $this->model->$field : null;
				}else{
					$field_value = isset($this->model[$field]) ? $this->model[$field] : null;
				}

				if(method_exists($this, $validator)){
					$valid=self::$validator($field, $field_value, $params)&&$valid;
				}elseif($this->model && $this->model->method_exists($validator)){
					$valid = $this->model->$validator($field, $field_value, $params) && $valid;
				}elseif($validator instanceof \Closure||(is_string($validator) && function_exists($validator))){
					$valid = $validator($field,$field_value,$params,&$this->model) && $valid;
				}else{//if(glue::canImport($validator)){
					$o = new $validator($params);
					$o->owner = $this;
					$valid = $o->validate($field, $field_value) && $valid;
				//}else{
					//trigger_error("The validator $validator could not be found in the ".get_class($this)." model");
				}
			}
		}

		// If there is only one field to this rule then we can actually apply it to that field
		if(!$valid && count($scope) <= 1){
			if($message)
				$this->error_messages[$field][] = $message;
			$this->error_codes[$field][]=$validator_caption;
		}elseif(!$valid){
			if($message)
				$this->error_messages['global'][] = $message;
			foreach($scope as $k => $field) // if there are multiple fields apply the error code to every field
				$this->error_codes[$field][]=$validator_caption;
		}
		return $valid;
	}

	/**
	 * Adds an error message to the model
	 * @param $message
	 * @param $field
	 */
	function addErrorMessage($message, $field = 'global' /* Global denotes where the error should apply to the form rather than a field */){
		$this->error_messages[$field][] = $message;
	}

	function addErrorCode($field,$validator){
		$this->error_codes[$field][]=$validator;
	}

	/**
	 * Gets all errors for this model or if $field is set
	 * gets only those fields errors
	 * @param string $field
	 */
	function getErrors($field = null){
		if($field){
			if(isset($this->error_messages[$field])){
				return $this->error_messages[$field];
			}
			return null;
		}else{
			return $this->error_messages;
		}
	}

	function getErrorCodes(){
		return $this->error_codes;
	}

	/**
	 * Gets the first global error if $field is not set or the first error for that field
	 * if it is set
	 * @param $field
	 */
	function getFirstError($field = null){
		$errors = $this->getErrors();

		if(!is_array($errors))
			return null;

		// If $field is not set it will take first global error
		if(!$field && isset($errors['global'])){
			return $errors['global'][0];
		}elseif(isset($errors[$field])){
			return $errors[$field][0];
		}
		return null;
	}

	public function setFieldMessages($messages){
		$this->error_messages[$field]=$messages;
	}

	public function setFieldCodes($codes){
		$this->error_codes[$field]=$codes;
	}

	// START OF VALIDATORS

	public static function isEmpty($value, $trim = false){
		return $value===null || $value===array() || $value==='' || $trim && is_scalar($value) && trim($value)==='';
	}

	/**
	 * Field is required
	 */
	public static function required($field, $value){
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
	public static function boolean($field, $value, $params){

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
	public static function string($field, $value, $params){

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

	public static function objExist($field, $value, $params){

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
		$object = $cName::model()->findOne(array_merge(array($params['field']=>$value), $condition));

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

	public static function in($field, $value, $params){

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

	public static function nin($field, $value, $params){

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

	public static function regex($field, $value, $params){

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

	public static function compare($field, $value, $params){

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
			$with_val = $this->{$params['with']};
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

	public static function number($field, $value, $params){

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

	public static function url($field, $value, $params){

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

	public static function file($field, $value, $params){

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

		if($fieldValue['error'] === UPLOAD_ERR_OK){
			if(isset($params['ext'])){
				$path = pathinfo($fieldValue['name']);

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
					if($fieldValue['size'] < $params['size']['gt']){
						return false;
					}
				}elseif(isset($params['size']['lt'])){
					if($fieldValue['size'] > $params['size']['lt']){
						return false;
					}
				}
			}

			if(isset($params['type'])){
				if(preg_match("/".$params['type']."/i", $fieldValue['type']) === false || preg_match("/".$params['type']."/i", $fieldValue['type']) < 0){
					return false;
				}
			}
		}else{
			switch ($fieldValue['error']) {
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

	public static function tokenized($field, $value, $params){

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

	public static function email($field, $value, $params = array()){

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

	public static function hash($field, $value, $params = array()){
		if(glue::http()->validateCsrfToken($value)){
			return true;
		}
		return false;
	}

	public static function safe($field, $value, $params = array()){
		return true; // Just do this so the field gets sent through
	}

	public static function date($field, $value, $params = array()){

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