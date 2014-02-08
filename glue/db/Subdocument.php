<?php

namespace glue\db;

use \glue\Validator;
use \glue\Model;
use \glue\Exception;

class Subdocument extends Validator
{
	public $class;
	public $type;
	public $rules;
	public $scenario;
	public $preserveKeys = true;
	
	public $allowEmpty = true;

	public function validateAttribute($object, $attribute, $value)
	{
		if(!$this->type){
			throw new Exception('You must supply a subdocument type of either "many" or "one" in order to validate subdocuments');
		}
		if(!$this->class && !$this->rules){
			throw new Exception('You must supply either some rules to validate by or a class name to use');
		}
		
		if(!$object->$attribute && $this->allowEmpty){
			return true;
		}elseif(!$object->$attribute){
			return false;
		}

		// Lets judge what class we are using
		// If we are using a pre-defined class then lets just get on with it otherwise
		// lets instantiate a EMongoModel and fill its rules with what we want
		if($this->class){
			$c = new $this->class;
		}else{
			$c = new Model();
			$c->setRules($this->rules);
		}
		
		$valid = true;

		if(is_object($this->scenario) && ($this->scenario instanceof Closure)){
			$c->setScenario($this->scenario($object));
		}else{
			$c->setScenario($this->scenario);
		}

		if($this->type == 'many'){
			if(is_array($object->$attribute)){

				$fieldErrors = array();
				$fieldValue = array();

				foreach($object->$attribute as $index=>$row){
					$c->clean();
					if($this->preserveKeys){
						$val = $fieldValue[$index] = $row instanceof $c ? $row->getAttributes(null,true) : $row;
					}else{
						$val = $fieldValue[] = $row instanceof $c ? $row->getAttributes(null,true) : $row;
					}
					$c->setAttributes($val);
					if(!$c->validate()){
						if($this->preserveKeys){
							$fieldErrors[$index] = $c->getErrors();
						}else{
							$fieldErrors[] = $c->getErrors();
						}
					}
					
					// Lets get the field value again to apply filters etc
					if($this->preserveKeys){
						$newFieldValue[$index] = $c->getAttributes(null, true);
					}else{
						$newFieldValue[] = $c->getAttributes(null, true);
					}
				}

				if(sizeof($fieldErrors) > 0){
					$valid=false;
					if($this->message !== null){
						$object->setError($attribute, $this->message);
					}else{
						$object->setAttributeErrors($attribute, $fieldErrors);
					}
				}

				// Strip the models etc from the field value
				$object->$attribute = $newFieldValue;
			}
		}else{
			$c->clean();
			$fieldValue = $object->$attribute instanceof $c ? $object->$attribute->getAttributes() : $object->$attribute;
			$c->setAttributes($fieldValue);
			
			if(!$c->validate()){
				$valid = false;
				if($this->message !== null){
					$object->setError($attribute, $this->message);
				}elseif(sizeof($c->getErrors()) > 0){
					$object->setAttributeErrors($attribute, $c->getErrors());
				}
			}
			// Lets get the field value again to apply filters etc
			$object->$attribute = $c->getAttributes(null, true);			
		}
		
		if($valid){
			return true;
		}else{
			return false;
		}
	}
}