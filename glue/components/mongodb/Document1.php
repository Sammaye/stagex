<?php
class MongoDocument extends GModel{

	const HAS_ONE = 4;
	const HAS_MANY = 8;

	private $_meta = array();

	private $relations = array();
	private $new = false;
	private $oldRecord;

	public function &__get($k){
		if(array_key_exists($k, $this->relations())){
			if(!isset($this->relations[$k])){
				$rel = $this->with($k); // Since the user is hitting directly on the relation lets cache it
				$this->relations[$k] = $rel;
			}
			return $this->relations[$k];
		}elseif(isset($this->doc[$k])){
			return $this->doc[$k];
		}else{
			return $this->$k;
		}
	}

	public function __set($k, $v){
		if(property_exists($this, $k)){
			$this->$k = $v;
		}elseif(isset($this->relations[$k])){
			$this->relations[$k] = $v; // Allow relations to be settable but not savable
		}else{
			if(!isset($this->_meta[$k])) $this->_meta[$k] = null;
			$this->doc[$k] = $v;
		}
	}

	public function __unset($k){
		if(property_exists($this, $k)){
			unset($this->_meta[$k]);
			unset($this->$k);
		}elseif(isset($this->relations[$k])){
			unset($this->relations[$k]);
		}else{
			unset($this->_meta[$k]);
			unset($this->doc[$k]);
		}
	}

	public function __isset($name){
		if(property_exists($this, $name))
			return isset($this->$name);
		elseif(isset($this->relations[$name])){
			return isset($this->relations[$name]);
		}else{
			return isset($this->doc[$name]);
		}
	}

	function __construct($scenario ='insert'){
		$this->setScenario($scenario);
		$this->setIsNewRecord(true);

		$reflect = new ReflectionClass(get_class($this));
		$class_vars = $reflect->getProperties(ReflectionProperty::IS_PROTECTED); // Pre-defined doc attributes

		foreach ($class_vars as $prop) {
			$this->_meta[$prop->getName()] = null;
		}
		parent::__construct();
	}

	public function primaryKey(){
		return '_id';
	}

	public function getCollectionName(){}

	public function setIsNewRecord($bool){
		$this->new = $bool;
	}

	public function getIsNewRecord(){
		return $this->new;
	}

	public function oldRecord(){
		return $this->oldRecord;
	}

	public static function model($className = __CLASS__){
		return new $className;
	}

	function relations(){
		return array();
	}

	function with($k, $where = array()){
		$relations = $this->relations();
		if(array_key_exists($k, $relations)){
			$relation = $relations[$k];

			$c_name = $relation[1];
			$f_key = $relation[2];

			$o = new $c_name();

			$f_key_val = isset($relation['on']) ? $this->{$relation['on']} : $this->{$this->primaryKey()};

			$clause = array_merge(array($f_key=>$f_key_val), $where);

			if($relation[0]&self::HAS_ONE){
				$o = $o::model()->findOne($clause);
				return $o;
			}elseif($relation[0]&self::HAS_MANY){
				return glue::db()->getActiveCursor(Glue::db()->{$o->getCollectionName()}->find($clause), $c_name);
			}
		}else{
			return false;
		}
	}

	function getRelated($k, $where = array()){
		return $this->with($k, $where);
	}

	function getAttributes($db_only = false){
		$virtualAttributes = array();
		$reflect = new ReflectionClass(get_class($this));
		$class_vars = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

		foreach ($class_vars as $prop) {
			$virtualAttributes[$prop->getName()] = $this->{$prop->getName()};
		}
		return $db_only ? $this->getDocument() : array_merge($virtualAttributes, $this->getDocument());
	}

	function save($params = array(), $runValidation = false){

		$this->oldRecord = (Object)$this->getDocument(); // This can be DEPRECATED with some mods

		$validated = true;
		if($runValidation)
			$validated = $this->validate();

		if($validated){

			if($this->getIsNewRecord() && !isset($this->_id))
				$this->_id = new MongoId();

			if($this->onBeforeSave()){
				$attributes = $this->getDocument();

				// Get the fields we are saving
				if(isset($params['fields'])){
					foreach($attributes as $field=>$value){
						if(array_key_exists($field, array_flip($fields))){
							$doc[$field] = $value;
						}
					}
				}else{
					$doc = $attributes;
				}

				$queryOptions = array(
					'safe' => isset($params['safe']) ? $params['safe'] : true,
					'upsert' => isset($params['upsert']) ? $params['upsert'] : false
				);

				if($this->getIsNewRecord()){ // If is new record insert
					Glue::db()->{$this->getCollectionName()}->insert($doc, $queryOptions);
				}else{
					// TODO add $set for changed fields
					Glue::db()->{$this->getCollectionName()}->update(array($this->primaryKey()=>$this->{$this->primaryKey()}), $doc, $queryOptions);
				}
				$this->onAfterSave();
				$this->setIsNewRecord(false);

				return true;
			}else{
				return false;
			}
		}
	}

	function delete(){
		if($this->onBeforeDelete()){
			Glue::db()->{$this->getCollectionName()}->remove(array("_id" => $this->_id));
		}

		$this->onAfterDelete();
		return true;
	}

	/**
	 * DB functions
	 */
	function find($query){
		$cursor = glue::db()->{$this->getCollectionName()}->find($query);
		return new GMongoCursor($cursor, get_class($this));
	}

	function findOne($query){
		$doc = glue::db()->{$this->getCollectionName()}->findOne($query);

		if(!$doc){
			return null;
		}

		if(!$this->onBeforeFind()) return false;
		$this->setIsNewRecord(false);
		$this->setScenario('update');
		$this->setAttributes($doc);
		$this->onAfterFind();
		return $this;
	}

	/**
	 * NONE ACTIVE RECORD BOUND FUNCTIONS
	 */
	function remove($query, $queryOptions = array()){
		return Glue::db()->{$this->getCollectionName()}->remove($query, $queryOptions);
	}

	function update($query, $queryOptions = array()){
		return Glue::db()->{$this->getCollectionName()}->update($query, $queryOptions);
	}

	function Db($collection = null){
		if($collection){
			return Glue::db()->{$collection};
		}else{
			return Glue::db()->{$this->getCollectionName()};
		}
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
	 *
	 * @return GMongoCursor of the results
	 */
	function search($fields = array(), $term = '', $extra = array()){

		$query = array();

		$working_term = trim(preg_replace('/(?:\s\s+|\n|\t)/', '', $term)); // Strip all whitespace to understand if there is actually characters in the string

		if(strlen($working_term) <= 0 || empty($fields)){ // I dont want to run the search if there is no term
			$result = $this->find($extra); // If no term is supplied just run the extra query placed in
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
				$field_regexes[] = array($field => new MongoRegex('/'.$term.'/i'));
			}
			$sub_query[] = array('$or' => $field_regexes);
		}
		$query['$and'] = $sub_query; // Lets make the $and part so as to make sure all terms must exist
		$query = array_merge($query, $extra); // Lets add on the additional query to ensure we find only what we want to.

		// TODO Add relevancy sorting
		$result = $this->find($query);
		return $result;
	}

	/**
	 * This just gets the document in normal PHP array format
	 */
	function getDocument(){
		$doc = array();
		foreach($this->_meta as $field => $meta){
			$doc[$field] = isset($this->doc[$field]) ? $this->doc[$field] : $this->$field;
		}
		return $doc;
	}

	function getBSONDocument(){
		return bson_encode($this->getDocument());
	}

	function getJSONDocument(){
		return json_encode($this->getDocument());
	}
}


	/*
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
	*/

	/**
	 * Not sure if I need these
	private $success = false;
	private $success_message;
	 */
	

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