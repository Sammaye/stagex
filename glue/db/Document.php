<?php

namespace glue\db;

use Glue;
use \glue\Model;
use \glue\db\Cursor;

class Document extends Model
{
	private $_related = array();
	private $_partial = false;

	private $_attributes = array();

	private $_new = false;
	private $_projected_fields = array();

	private static $_meta = array();
	
	public static function primaryKey()
	{
		return '_id';
	}	
	
	public static function collectionName()
	{
		return strtolower(preg_replace('/\B([A-Z])/', '_$1', get_called_class()));
	}

	public function __get($name)
	{
		if(isset($this->_attributes[$name])){
			return $this->_attributes[$name];
		}elseif(isset($this->_related[$name])){
			return $this->_related[$name];
		}elseif(array_key_exists($name, $this->relations())){
			return $this->_related[$name] = $this->getRelated($name);
		}else{
			return parent::__get($name);
		}
	}

	public function __set($name, $value)
	{
		if(isset($this->_related[$name]) || array_key_exists($name, $this->relations()))
			return $this->_related[$name] = $value;
		elseif(method_exists($this,'set'.$name))
			return $this->{'set'.$name}($value);
		else{
			return $this->setAttribute($name,$value);
		}
	}

	public function __isset($name)
	{
		if(isset($this->_attributes[$name])){
			return true;
		}elseif(isset($this->_related[$name])){
			return true;
		}elseif(array_key_exists($name, $this->relations())){
			return $this->getRelated($name) !== null;
		}elseif(property_exists($this, $name)){
			return true;
		}
	}

	public function __unset($name)
	{
		if(isset($this->_attributes[$name])){
			unset($this->_attributes[$name]);
		}elseif(isset($this->_related[$name])){
			unset($this->_related[$name]);
		}elseif(property_exists($this, $name)){
			unset($this->$name);
		}
	}

	public function __construct($scenario = 'insert')
	{
		// will set the cache of our fields if needed
		$this->attributeNames();

		if($scenario !== null){ // internally used by populateRecord() and model()
			$this->setScenario($scenario);
			$this->setIsNewRecord(true);
		}
		
		parent::__construct();
		$this->onAfterConstruct();
	}
	
	public static function instantiate($attributes)
	{
		return new static;
	}
	
	public static function populate($attributes, $callAfterFind = true, $partial = false)
	{
		if($attributes!==false){
			$record = static::instantiate($attributes);
			$record->setScenario('update');
			$record->setIsNewRecord(false);
	
			foreach($attributes as $name => $value){
				$record->$name = $value;
			}
	
			if($partial){
				$record->setIsPartial(true);
				$record->setProjectedFields(array_keys($attributes));
			}
			if($callAfterFind){
				$record->afterFind();
			}
			return $record;
		}else{
			return null;
		}
	}	

	public function __call($name,$parameters)
	{
		if(array_key_exists($name, $this->relations())){
			if(empty($parameters)){
				return $this->getRelated($name, false);
			}else{
				return $this->getRelated($name, true, $parameters[0]);
			}
		}
		return parent::__call($name, $parameters);
	}
	
	public static function getDb()
	{
		return Glue::getComponent('db');
	}
	
	public static function getCollection()
	{
		return static::getDb()->selectCollection(static::collectionName());
	}	
	
	/**
	 * Returns the value of the primary key
	 */
	public function getPrimaryKey()
	{
		return static::formatPrimaryKey($this->{$this->primaryKey()});
	}
	
	public static function formatPrimaryKey($value)
	{
		return $value instanceof \MongoId ? $value : new \MongoId($value);
	}

	public function getIsNewRecord()
	{
		return $this->_new;
	}

	public function setIsNewRecord($value)
	{
		$this->_new = $value;
	}

	public function getProjectedFields()
	{
		return $this->_projected_fields;
	}

	public function setProjectedFields($a)
	{
		$this->_projected_fields = $a;
	}

	public function setAttribute($name, $value)
	{
		if(property_exists($this, $name)){
			$this->$name = $value;
		}else{
			$this->_attributes[$name] = $value;
		}
		return true;
	}

	function attributeNames($db_only = false)
	{
		if(!isset(self::$_meta[get_class($this)]) && get_class($this) != 'glue\Model'){

			$_meta = array();

			$reflect = new \ReflectionClass(get_class($this));
			$class_vars = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC); // Pre-defined doc attributes

			foreach ($class_vars as $prop) {

				if($prop->isStatic()){
					continue;
				}

				$docBlock = $prop->getDocComment();
				$field_meta = array(
					'name' => $prop->getName(),
					'virtual' => $prop->isProtected() || preg_match('/@virtual/i', $docBlock) <= 0 ? false : true
					// If the field is virtual its value will not saved
				);
				$_meta[$prop->getName()] = $field_meta;
			}
			self::$_meta[get_class($this)] = $_meta;
		}

		if($db_only){
			$fields=array();
			foreach(self::$_meta[get_class($this)] as $field){
				if(!$field['virtual']){
					$fields[$field['name']] = true;
				}
			}
			return array_keys($fields);
		}else{
			return array_keys(self::$_meta[get_class($this)]);
		}
	}

	/**
	 * Saves this record
	 *
	 * If an attributes specification is sent in it will only validate and save those attributes
	 *
	 * @param boolean $runValidation
	 * @param array $attributes
	 */
	public function save($runValidation = true, $attributes = null)
	{
		return $this->getIsNewRecord() ? 
			$this->insert($runValidation, $attributes) : 
			$this->update($runValidation, $attributes);
	}

	/**
	 * Saves only a specific subset of attributes as defined by the param
	 * @param array $attributes
	 */
	public function saveAttributes($attributes)
	{
		if($this->getIsNewRecord()){
			throw new Exception('The active record cannot be updated because it is new.');
		}

		$this->trace(__FUNCTION__);
		$values=array();
		foreach($attributes as $name=>$value){
			if(is_integer($name)){
				$v = $this->$value;
				if(is_array($this->$value)){
					$v = $this->filterRawDocument($this->$value);
				}
				$values[$value]=$v;
			}else
				$values[$name]=$this->$name=$value;
		}
		return $this->update(false, $values);
	}

	/**
	 * Inserts this record
	 * @param array $attributes
	 */
	public function insert($runValidation = true, $attributes = null)
	{
		if(!$this->getIsNewRecord()){
			throw new Exception('The active record cannot be inserted because it is not new.');
		}
		
		if($runValidation && !$this->validate($attributes)){
			return false;
		}
		
		if($this->onBeforeSave()){
			$this->trace(__FUNCTION__);
			
			$this->{$this->primaryKey()} = $this->getPrimaryKey();
			$attributes = $attributes ? $this->filterRawDocument($attributes) : $this->getRawDocument();
			
			if($response = $this->getCollection()->insert($attributes)){
				$this->onAfterSave();
				$this->setIsNewRecord(false);
				$this->setScenario('update');
				return $response;
			}
		}
		return false;
	}

	/**
	 * Updates this record
	 * @param array $attributes
	 */
	public function update($runValidation = true, $attributes = null)
	{
		if($this->getIsNewRecord()){
			throw new Exception('The active record cannot be updated because it is new.');
		}
		
		if($this->getPrimaryKey() === null){
			throw new Exception('The active record cannot be updated because it has no primary key.');
		}		
		
		if($runValidation && !$this->validate($attributes)){
			return false;
		}		
		
		$this->trace(__FUNCTION__);
		
		if(!$this->onBeforeSave()){
			return false;
		}

		if($attributes !== null){
			$attributes = $this->filterRawDocument($attributes);
		}elseif($this->getIsPartial()){
			foreach($this->_projected_fields as $field){
				$attributes[$field] = $this->$field;
			}
			$attributes=$this->filterRawDocument($attributes);
		}else{
			$attributes=$this->getRawDocument();
		}
		
		unset($attributes['_id']); // Unset the _id before update

		$response = static::updateAll($this->{$this->primaryKey()}, array('$set' => $attributes));
		$this->onAfterSave();
		return $response;
	}
	
	public function delete(){
		if($this->getIsNewRecord()){
			throw new Exception('The active record cannot be deleted because it is new.');
		}
		
		$this->trace(__FUNCTION__);
		if($this->onBeforeDelete()){
			$response = static::deleteAll($this->{$this->primaryKey()});
			$this->onAfterDelete();
			return $response;
		}else{
			return false;
		}
	}

	public function saveCounters(array $counters,$lower=null)
	{
		$this->trace(__FUNCTION__);
	
		if($this->getIsNewRecord()){
			throw new Exception('The active record cannot be updated because it is new.');
		}
	
		if(sizeof($counters) > 0){
			foreach($counters as $k => $v){
				if(($lower !== null && (($this->$k + $v) >= $lower)) || $lower === null){
					$this->$k = $this->$k + $v;
				}else
					unset($counters[$k]);
			}
			if(count($counters) > 0){
				return static::saveAllCounters($this->getPrimaryKey(), $counters);
			}
		}
		return true; // Assume true since the action did run it just had nothing to update...
	}	
	
	public function equals($record)
	{
		return $this->collectionName()===$record->collectionName() && (string)$this->getPrimaryKey()===(string)$record->getPrimaryKey();
	}

	/**
	 * Alias for getRelated
	 */
	public function with($name, $params=array(), $refresh = false)
	{
		return $this->getRelated($name, !empty($params) ? true : $refresh, $params);
	}

	public function getRelated($name, $refresh=false, $params=array())
	{
		if(!$refresh && $params===array() && (isset($this->_related[$name]) || array_key_exists($name,$this->_related)))
			return $this->_related[$name];

		$relations = $this->relations();

		if(!isset($relations[$name]))
			throw new Exception(get_class($this)." does not have relation".$name);

		$cursor = array();
		$relation = $relations[$name];

		// Let's get the parts of the relation to understand it entirety of its context
		$cname = $relation[1];
		$fkey = $relation[2];
		$pk = isset($relation['on']) ? $this->{$relation['on']} : $this->{$this->primaryKey()};

		// Form the where clause
		$where = $params;
		if(isset($relation['where'])&&!$params) $where = array_merge($relation['where'], $params);
		
		// Find out what the pk is and what kind of condition I should apply to it
		if (is_array($pk)) {
			//It is an array of references
			if (MongoDBRef::isRef(reset($pk))) {
				$result = array();
				foreach ($pk as $singleReference) {
					$row = $this->populateReference($singleReference, $cname);
					if ($row) array_push($result, $row);
				}
				return $this->_related[$name]=$result;
			}
			// It is an array of _ids
			$clause = array_merge($where, array($fkey=>array('$in' => $pk)));
		}elseif($pk instanceof MongoDBRef){
			
			// I should probably just return it here
			// otherwise I will continue on
			return $this->_related[$name]=$this->populateReference($pk, $cname);
		}else{
			// It is just one _id
			$clause = array_merge($where, array($fkey=>$pk));
		}
		
		$o = new $cname;
		if($relation[0] === 'one'){
			return $this->_related[$name] = $o->findOne($clause);
		}elseif($relation[0] === 'many'){
		
			// Lets find them and return them
			$cursor = $o->find($clause)
			->sort(isset($relation['sort']) ? $relation['sort'] : array())
			->skip(isset($relation['skip']) ? $relation['skip'] : null)
			->limit(isset($relation['limit']) ? $relation['limit'] : null);
			
			return $this->_related[$name] = iterator_to_array($cursor);
		}
		return $cursor;
	}

	/**
	 * @param mixed $reference Reference to populate
	 * @param null|string $cname Class of model to populate. If not specified, populates data on current model
	 * @return EMongoModel
	 */
	public function populateReference($reference, $cname = null)
	{
		$row = \MongoDBRef::get(self::$db->getDB(), $reference);
		$o=(is_null($cname)) ? $this : new $cname;
		return $o->populateRecord($row);
	}

	/**
	 * Returns a value indicating whether the named related object(s) has been loaded.
	 * @param string $name the relation name
	 * @return boolean a value indicating whether the named related object(s) has been loaded.
	 */
	public function hasRelated($name)
	{
		return isset($this->_related[$name]) || array_key_exists($name,$this->_related);
	}
	
	/**
	 * Cleans or rather resets the document
	 */
	public function clean()
	{
		$this->_attributes=array();
		$this->_related=array();
	
		// blank class properties
		$cache = $this->attributeNames();
		foreach($cache as $k => $v){
			$this->$v = null;
		}
		return true;
	}	

	/**
	 * Refreshes the data from the database
	 */
	public function refresh()
	{
		$this->trace(__FUNCTION__);
		if(
			!$this->getIsNewRecord() && 
			($record = $this->getCollection()->findOne(array($this->primaryKey() => $this->getPrimaryKey()))) !== null
		){
			$this->clean();
			foreach($record as $name => $column){
				$this->$name = $record[$name];
			}
			return true;
		}else{
			return false;
		}
	}

	/**
	 * Gets the formed document with MongoYii objects included
	 */
	public function getDocument()
	{
		$attributes = $this->attributeNames(true);
		$doc = array();

		if(is_array($attributes)){
			foreach($attributes as $field) $doc[$field] = $this->$field;
		}
		return array_merge($doc, $this->_attributes);
	}

	/**
	 * Gets the raw document with MongoYii objects taken out
	 */
	public function getRawDocument()
	{
		return $this->filterRawDocument($this->getDocument());
	}

	/**
	 * Filters a provided document to take out MongoYii objects.
	 * @param array $doc
	 */
	public function filterRawDocument($doc)
	{
		if(is_array($doc)){
			foreach($doc as $k => $v){
				if(is_array($v)){
					$doc[$k] = $this->{__FUNCTION__}($doc[$k]);
				}elseif($v instanceof \glue\Model || $v instanceof \glue\db\Document){
					$doc[$k] = $doc[$k]->getRawDocument();
				}
			}
		}
		return $doc;
	}

    public function onBeforeSave()
    {
    	return $this->raise('beforeSave');
    }
    
    public function onAfterSave()
    {
    	return $this->raise('afterSave');
    }
    
    public function onBeforeDelete()
    {
    	return $this->raise('beforeDelete');
    }
    
    public function onAfterDelete()
    {
    	return $this->raise('afterDelete');
    }
    
    public static function findOne($query = array(), $fields = array())
    {
    	$cursor = new Cursor(array(
    		'select' => $fields,
    		'model' => get_called_class(),
    		'where' => $query
    	));
    	return $cursor->one();
    }
    
    public static function find($query = array(), $fields = array())
    {
    	if(!is_array($query)){
    		$query = array(static::primaryKey() => new \MongoId($query));
    	}
    	$cursor = new Cursor(array(
    		'select' => $fields,
    		'model' => get_called_class(),
    		'where' => $query
    	));
    	return $cursor;
    }
    
    public static function updateAll($query, $attributes, $options = array())
    {
    	return static::getCollection()->update(
    		is_array($query) ? $query : array(static::primaryKey() => static::formatPrimaryKey($query)), 
    		$attributes, 
    		array_merge(array('multiple' => true), $options)
    	);
    }
    
    public static function saveAllCounters($query, $counters, $options = array())
    {
    	return static::getCollection()->update(
    		is_array($query) ? $query : array(static::primaryKey() => static::formatPrimaryKey($query)), 
    		array('$set' => $counters), 
    		array_merge(array('multiple' => true), $options)
    	);
    }
    
    public static function deleteAll($query, $options = array())
    {
    	return static::getCollection()->remove(
    		is_array($query) ? $query : array(static::primaryKey() => static::formatPrimaryKey($query)), 
    		$options
    	);
    }    
    
    /**
     * This is an aggregate helper on the model
     * Note: This does not return the model but instead the result array directly from MongoDB.
     * @param array $pipeline
     */
    public static function aggregate($pipeline){
    	return $this->getDb()->aggregate(static::collectionName(), $pipeline);
    }
    
    /**
     * Allows for searching a subset of model fields; should not be used without a prior constraint.
     *
     * Will return an active cursor containing the search results.
     *
     * @param array $fields
     * @param string|int $term
     * @param array $extra
     * @param string $class
     */
    public static function fts($fields = array(), $term = '', $extra = array()){
    
    	$query = array();
    
    	$working_term = trim(preg_replace('/(?:\s\s+|\n|\t)/', '', $term)); // Strip all whitespace to understand if there is actually characters in the string
    
    	if(strlen($working_term) <= 0 || empty($fields)){ // I dont want to run the search if there is no term
    		$result = static::find($extra); // If no term is supplied just run the extra query placed in
    		return $result;
    	}
    
    	$broken_term = explode(' ', $term);
    
    	// Strip whitespace from query
    	foreach($broken_term as $k => $term){
    		$broken_term[$k] = trim(preg_replace('/(?:\s\s+|\n|\t)/', '', $term));
    	}
    
    	// Now lets build a regex query
    	$sub_query = array();
    	foreach($broken_term as $k => $term){
    
    		// Foreach of the terms we wish to add a regex to the field.
    		// All terms must exist in the document but they can exist across any and all fields
    		$field_regexes = array();
    		foreach($fields as $k => $field){
    			$field_regexes[] = array($field => new \MongoRegex('/'.$term.'/i'));
    		}
    		$sub_query[] = array('$or' => $field_regexes);
    	}
    	$query['$and'] = $sub_query; // Lets make the $and part so as to make sure all terms must exist
    	$query = array_merge($query, $extra); // Lets add on the additional query to ensure we find only what we want to.
    
    	// TODO Add relevancy sorting
    	$result = static::find($query);
    	return $result;
    }    
    
    public function trace($func)
    {
    	return true;
    }    
}