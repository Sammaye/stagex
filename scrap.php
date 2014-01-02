<?php
	
	public $model;
	public $scenario;
	public $rules;

	public $valid;
	
	public $error_map=array();

	public $error_codes = array();
	public $error_messages=array();

	/**
	 * Validates a single rule to an inputted document
	 *
	 * @param $rule The rule in array form
	 * @param $document The document in array form
	 */
	function validateRule($rule){

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
					$valid=$this->$validator($field, $field_value, $params)&&$valid;
				}elseif($this->model instanceof \glue\Model && $this->model->method_exists($validator)){
					$valid = $this->model->$validator($field, $field_value, $params) && $valid;
				}elseif($validator instanceof \Closure||(is_string($validator) && function_exists($validator))){
					$valid = $validator($field,$field_value,$params,$this) && $valid;
				}else{//if(glue::canImport($validator)){
					$o = new $validator($params);
					$o->owner = $this;
					$valid = $o->validateAttribute($this->model, $field, $field_value) && $valid;
				//}else{
					//trigger_error("The validator $validator could not be found in the ".get_class($this)." model");
				}
			}
		}

		//if(!$valid)
			//var_dump($rule);
		
		//var_dump($message);
		
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

	function getErrorCodes($map){
		if($map!==array()){

			$mapped_errors=array();
			
			foreach($map as $k => $v){
				if(($pos=strpos($k,'||'))!==false){
					
					// $or condition
					$match=false;
					foreach(preg_split('/\|\|/',$k) as $f){
						list($fd, $vr)=preg_split('/_/',$f);
						if(isset($this->error_codes[$fd])&&array_key_exists($vr,array_flip($this->error_codes[$fd])))
							$match=true;
					}
					
					if($match)
						$mapped_errors['global']=$v;
				}elseif(($pos=strpos($k,'&&'))!==false){
					
					// $and condition
					$match=true;
					foreach(preg_split('/&&/',$k) as $f){
						list($fd, $vr)=preg_split('/_/',$f);
						if(isset($this->error_codes[$fd])&&array_key_exists($vr,array_flip($this->error_codes[$fd])))
							$match=false&&$match;
					}
						
					if($match)
						$mapped_errors['global']=$v;					
				}else{
					list($field, $validator)=preg_split('/_/',$k);
					if(isset($this->error_codes[$field])&&array_key_exists($validator,array_flip($this->error_codes[$field])))
						$mapped_errors[$field]=$v;
				}
				
			}
			
			return $mapped_errors;
		}
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