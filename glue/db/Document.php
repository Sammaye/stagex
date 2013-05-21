<?php 

namespace \glue\db;

use \glue\Exception;

class Document extends \glue\Model{
	
	private $_related;
	private $_partial;
	
	private $_new;
	private $_criteria;
	private $_projected_fields;
	
	private $_db;
	
	private static $_meta=array();
	
	function attributeNames(){
		
		if(!isset(self::$_meta[get_class($this)])&&get_class($this)!='\\glue\\Model'){
		
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
			self::$_meta[get_class($this)]=$_meta;
		}		
		
	}
	
}

