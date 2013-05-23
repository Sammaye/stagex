<?php

namespace glue\db;

use \glue\Exception;

class Document extends \glue\Model{

	private $_related=array();
	private $_partial=false;

	private $_attributes=array();

	private $_new=false;
	private $_criteria;
	private $_projected_fields=array();

	private static $_db;
	private static $_models=array();
	private static $_meta=array();

	/**
	 * The scope attached to this model
	 *
	 * It is very much like how Yii normally uses scopes except the params are slightly different.
	 *
	 * @example
	 *
	 * array(
	 * 	'10_recently_published' => array(
	 * 		'condition' => array('published' => 1),
	 * 		'sort' => array('date_published' => -1),
	 * 		'skip' => 5,
	 * 		'limit' => 10
	 * 	)
	 * )
	 *
	 * Not all params need to be defined they are all just there above to give an indea of how to use this
	 *
	 * @return An array of scopes
	 */
	public function scopes(){ return array(); }

	/**
	 * Sets the default scope
	 *
	 * @example
	 *
	 * array(
	 * 	'condition' => array('published' => 1),
	 * 	'sort' => array('date_published' => -1),
	 * 	'skip' => 5,
	 * 	'limit' => 10
	 * )
	 *
	 * @return an array which represents a single scope within the scope() function
	 */
	public function defaultScope(){ return array(); }

	public function attributeLabels(){ return array(); }
	
	public function relations(){ return array(); }

	/**
	 * (non-PHPdoc)
	 * @see yii/framework/CComponent::__get()
	 */
	public function __get($name){

		if(isset($this->_attributes[$name]))
			return $this->_attributes[$name];
		elseif(isset($this->_related[$name]))
			return $this->_related[$name];
		elseif(array_key_exists($name, $this->relations()))
			return $this->_related[$name]=$this->getRelated($name);
		else{
			return parent::__get($name);
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see CComponent::__set()
	 */
	public function __set($name,$value){

		if(isset($this->_related[$name]) || array_key_exists($name, $this->relations()))
			return $this->_related[$name]=$value;
		else{
			return $this->setAttribute($name,$value);
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see CComponent::__isset()
	 */
	public function __isset($name){
		if(isset($this->_attributes[$name]))
			return true;
		elseif(isset($this->_related[$name]))
			return true;
		elseif(array_key_exists($name, $this->relations()))
			return $this->getRelated($name)!==null;
		elseif(property_exists($this,$name))
			return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see CComponent::__unset()
	 */
	public function __unset($name){
		if(isset($this->_attributes[$name]))
			unset($this->_attributes[$name]);
		elseif(isset($this->_related[$name]))
			unset($this->_related[$name]);
		elseif(property_exists($this,$name))
			unset($this->$name);
	}

	/**
	 * Sets up our model and set the field cache just like in EMongoModel
	 *
	 * It will also set the default scope on the model so be aware that if you want the default scope to not be applied you will
	 * need to run resetScope() straight after making this model
	 *
	 * @param string $scenario
	 */
	public function __construct($scenario='insert'){

		// will set the cache of our fields if needed
		$this->attributeNames();

		if($scenario===null) // internally used by populateRecord() and model()
			return;

		$this->setScenario($scenario);
		$this->setIsNewRecord(true);

		$this->init();

		$this->attachBehaviors($this->behaviors());
		$this->afterConstruct();
	}

	/**
	 * This, in addition to EMongoModels edition, will also call scopes on the model
	 * @see protected/extensions/MongoYii/EMongoModel::__call()
	 */
	public function __call($name,$parameters){

		if(array_key_exists($name, $this->relations())){
			if(empty($parameters))
				return $this->getRelated($name,false);
			else
				return $this->getRelated($name,false,$parameters[0]);
		}

		$scopes=$this->scopes();
		if(isset($scopes[$name])){
			$this->setDbCriteria($this->mergeCriteria($this->_criteria, $scopes[$name]));
			return $this;
		}
		return parent::__call($name,$parameters);
	}

	/**
	 * Resets the scopes applied to the model clearing the _criteria variable
	 * @return $this
	 */
	public function resetScope($resetDefault=true){
		if($resetDefault)
			$this->_criteria = array();
		else
			$this->_criteria = null;
		return $this;
	}

	/**
	 * Returns the collection name as a string
	 *
	 * @example
	 *
	 * return 'users';
	 */
	function collectionName(){}

	/**
	 * Returns the value of the primary key
	 */
	public function getPrimaryKey($value=null){
		if($value===null)
			$value=$this->{$this->primaryKey()};
		return $value instanceof \MongoId ? $value : new MongoId($value);
	}

	/**
	 * Returns if the current record is new.
	 * @return boolean whether the record is new and should be inserted when calling {@link save}.
	 * This property is automatically set in constructor and {@link populateRecord}.
	 * Defaults to false, but it will be set to true if the instance is created using
	 * the new operator.
	 */
	public function getIsNewRecord(){
		return $this->_new;
	}

	/**
	 * Sets if the record is new.
	 * @param boolean $value whether the record is new and should be inserted when calling {@link save}.
	 * @see getIsNewRecord
	 */
	public function setIsNewRecord($value){
		$this->_new=$value;
	}

	/**
	 * Gets a list of the projected fields for the model
	 */
	public function getProjectedFields(){
		return $this->_projected_fields;
	}

	/**
	 * Sets the projected fields of the model
	 * @param array $a
	 */
	public function setProjectedFields($a){
		$this->_projected_fields=$a;
	}

	/**
	 * Sets the attribute of the model
	 * @param string $name
	 * @param mixed $value
	 */
	public function setAttribute($name,$value){
		if(property_exists($this,$name))
			$this->$name=$value;
		else//if(isset($this->_attributes[$name]))
			$this->_attributes[$name]=$value;
		//else return false;
		return true;
	}

	/**
	 * Returns the static model of the specified AR class.
	 * The model returned is a static instance of the AR class.
	 * It is provided for invoking class-level methods (something similar to static class methods.)
	 *
	 * EVERY derived AR class must override this method as follows,
	 * <pre>
	 * public static function model($className=__CLASS__)
	 * {
	 *     return parent::model($className);
	 * }
	 * </pre>
	 *
	 * @param string $className active record class name.
	 * @return EMongoDocument active record model instance.
	 */
	public static function model($className=__CLASS__){
		if(isset(self::$_models[$className]))
			return self::$_models[$className];
		else{
			$model=self::$_models[$className]=new $className(null);
			$model->attachBehaviors($model->behaviors());
			return $model;
		}
	}

	/**
	 * Instantiates a model from an array
	 * @param array $document
	 */
	protected function instantiate($document){
		$class=get_class($this);
		$model=new $class(null);
		return $model;
	}

	/**
	 * Returns the text label for the specified attribute.
	 * This method overrides the parent implementation by supporting
	 * returning the label defined in relational object.
	 * In particular, if the attribute name is in the form of "post.author.name",
	 * then this method will derive the label from the "author" relation's "name" attribute.
	 * @param string $attribute the attribute name
	 * @return string the attribute label
	 * @see generateAttributeLabel
	 */
	public function getAttributeLabel($attribute)
	{
		$labels=$this->attributeLabels();
		if(isset($labels[$attribute]))
			return $labels[$attribute];
		elseif(strpos($attribute,'.')!==false)
		{
			$segs=explode('.',$attribute);
			$name=array_pop($segs);
			$model=$this;
			foreach($segs as $seg)
			{
				$relations=$model->relations();
				if(isset($relations[$seg]))
					$model=\glue\db\Document::model($relations[$seg][1]);
				else
					break;
			}
			return $model->getAttributeLabel($name);
		}
		else
			return $this->generateAttributeLabel($attribute);
	}

	/**
	 * Creates an active record with the given attributes.
	 * This method is internally used by the find methods.
	 * @param array $attributes attribute values (column name=>column value)
	 * @param boolean $callAfterFind whether to call {@link afterFind} after the record is populated.
	 * @return CActiveRecord the newly created active record. The class of the object is the same as the model class.
	 * Null is returned if the input data is false.
	 */
	public function populateRecord($attributes,$callAfterFind=true,$partial=false)
	{
		if($attributes!==false)
		{
			$record=$this->instantiate($attributes);
			$record->setScenario('update');
			$record->setIsNewRecord(false);
			$record->init();

			$labels=array();
			foreach($attributes as $name=>$value)
			{
				$labels[$name]=1;
				$record->$name=$value;
			}

			if($partial){
				$record->setIsPartial(true);
				$record->setProjectedFields($labels);
			}
			//$record->_pk=$record->primaryKey();
			$record->attachBehaviors($record->behaviors());
			if($callAfterFind)
				$record->afterFind();
			return $record;
		}
		else
			return null;
	}


	function attributeNames($db_only=false){

		if(!isset(self::$_meta[get_class($this)])&&get_class($this)!='glue\Model'){

			$_meta = array();

			$reflect = new \ReflectionClass(get_class($this));
			$class_vars = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC); // Pre-defined doc attributes

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

		if($db_only){
			$fields=array();
			foreach(self::$_meta[get_class($this)] as $field){
				if(!$field['virtual'])
					$fields[$field['name']] = true;
			}
			return array_keys($fields);
		}else
			return array_keys(self::$_meta[get_class($this)]);
	}

	/**
	 * Saves this record
	 *
	 * If an attributes specification is sent in it will only validate and save those attributes
	 *
	 * @param boolean $runValidation
	 * @param array $attributes
	 */
	public function save($runValidation=true,$attributes=null){
		if(!$runValidation || $this->validate($attributes))
			return $this->getIsNewRecord() ? $this->insert($attributes) : $this->update($attributes);
		else
			return false;
	}

	/**
	 * Saves only a specific subset of attributes as defined by the param
	 * @param array $attributes
	 * @throws CDbException
	 */
	public function saveAttributes($attributes)
	{
		if(!$this->getIsNewRecord())
		{
			$this->trace(__FUNCTION__);
			$values=array();
			foreach($attributes as $name=>$value)
			{
				if(is_integer($name)){
					$v = $this->$value;
					if(is_array($this->$value)){
						$v = $this->filterRawDocument($this->$value);
					}
					$values[$value]=$v;
				}else
					$values[$name]=$this->$name=$value;
			}
			if(!isset($this->{$this->primaryKey()}) || $this->{$this->primaryKey()}===null)
				throw new Exception('The active record cannot be updated because its _id is not set!');

			return $this->updateByPk($this->{$this->primaryKey()},$values);
		}
		else
			throw new Exception('The active record cannot be updated because it is new.');
	}

	/**
	 * Inserts this record
	 * @param array $attributes
	 * @throws CDbException
	 */
	public function insert($attributes=null){
		if(!$this->getIsNewRecord())
			throw new Exception('The active record cannot be inserted to database because it is not new.');
		if($this->beforeSave())
		{
			$this->trace(__FUNCTION__);

			if(!isset($this->{$this->primaryKey()})) $this->{$this->primaryKey()} = new MongoId;
			if($this->getCollection()->insert($this->getRawDocument(), $this->getDb()->getDefaultWriteConcern())){
				$this->afterSave();
				$this->setIsNewRecord(false);
				$this->setScenario('update');
				return true;
			}
		}
		return false;
	}

	/**
	 * Updates this record
	 * @param array $attributes
	 * @throws CDbException
	 */
	public function update($attributes=null){
		if($this->getIsNewRecord())
			throw new Exception('The active record cannot be updated because it is new.');
		if($this->beforeSave())
		{
			$this->trace(__FUNCTION__);
			if($this->{$this->primaryKey()}===null) // An _id is required
				throw new Exception('The active record cannot be updated because it has no _id.');

			if($attributes!==null)
				$attributes=$this->filterRawDocument($attributes);
			elseif($this->getIsPartial()){
				foreach($this->_projected_fields as $field => $v)
					$attributes[$field] = $this->$field;
				$attributes=$this->filterRawDocument($attributes);
			}else
				$attributes=$this->getRawDocument();
			unset($attributes['_id']); // Unset the _id before update

			$this->updateByPk($this->{$this->primaryKey()}, array('$set' => $attributes));
			$this->afterSave();
			return true;
		}
		else
			return false;
	}

	/**
	 * Deletes this record
	 * @throws CDbException
	 */
	public function delete(){
		if(!$this->getIsNewRecord()){
			$this->trace(__FUNCTION__);
			if($this->beforeDelete()){
				$result=$this->deleteByPk($this->{$this->primaryKey()});
				$this->afterDelete();
				return $result;
			}
			else
				return false;
		}
		else
			throw new Exception('The active record cannot be deleted because it is new.');
	}

	/**
	 * Checks if a record exists in the database
	 * @param array $criteria
	 */
	public function exists($criteria=array()){
		$this->trace(__FUNCTION__);
		return $this->getCollection()->findOne($criteria)!==null;
	}

	/**
	 * Compares current active record with another one.
	 * The comparison is made by comparing table name and the primary key values of the two active records.
	 * @param EMongoDocument $record record to compare to
	 * @return boolean whether the two active records refer to the same row in the database table.
	 */
	public function equals($record)
	{
		return $this->collectionName()===$record->collectionName() && (string)$this->getPrimaryKey()===(string)$record->getPrimaryKey();
	}

	/**
	 * Find one record
	 * @param array $criteria
	 */
	public function findOne($criteria=array(),$fields=array()){
		$this->trace(__FUNCTION__);
		if((
				$record=$this->getCollection()->findOne($this->mergeCriteria(isset($c['condition']) ? $c['condition'] : array(), $criteria),
						$this->mergeCriteria(isset($c['project']) ? $c['project'] : array(), $fields))
		)!==null){
			$this->resetScope();
			return $this->populateRecord($record,true,$fields===array()?false:true);
		}else
			return null;
	}

	/**
	 * Find some records
	 * @param array $criteria
	 */
	public function find($criteria=array(),$fields=array()){
		$this->trace(__FUNCTION__);

		$c=$this->getDbCriteria();

		if($c!==array()){
			$cursor = new \glue\db\Cursor($this,
					$this->getCollection()->find($this->mergeCriteria(isset($c['condition']) ? $c['condition'] : array(), $criteria),
					$this->mergeCriteria(isset($c['project']) ? $c['project'] : array(), $fields)));
			if(isset($c['sort'])) $cursor->sort($c['sort']);
			if(isset($c['skip'])) $cursor->skip($c['skip']);
			if(isset($c['limit'])) $cursor->limit($c['limit']);

			$this->resetScope();
			return $cursor;
		}else{
			return new EMongoCursor($this, $this->getCollection()->find($criteria, $fields));
		}
	}

	/**
	 * Finds one by _id
	 * @param $_id
	 */
	public function findBy_id($_id,$fields=array()){
		$this->trace(__FUNCTION__);
		$_id = $this->getPrimaryKey($_id);
		return $this->findOne(array($this->primaryKey() => $_id),$fields);
	}

	/**
	 * An alias for findBy_id() that relates to Yiis own findByPk
	 * @param $pk
	 */
	public function findByPk($pk,$fields=array()){
		$this->trace(__FUNCTION__);
		return $this->findBy_id($pk,$fields);
	}

	/**
	 * Delete record by pk
	 * @param $pk
	 * @param $criteria
	 * @param $options
	 */
	public function deleteByPk($pk,$criteria=array(),$options=array()){
		$this->trace(__FUNCTION__);
		$pk = $this->getPrimaryKey($pk);
		return $this->getCollection()->remove(array_merge(array($this->primaryKey() => $pk), $criteria),
				array_merge($this->getDb()->getDefaultWriteConcern(), $options));
	}

	/**
	 * Update record by PK
	 *
	 * @param string $pk
	 * @param array $updateDoc
	 * @param array $options
	 */
	public function updateByPk($pk, $updateDoc = array(), $criteria = array(), $options = array()){
		$this->trace(__FUNCTION__);

		$pk = $this->getPrimaryKey($pk);
		return $this->getCollection()->update($this->mergeCriteria($criteria, array($this->primaryKey() => $pk)),$updateDoc,
				array_merge($this->getDb()->getDefaultWriteConcern(), $options));
	}

	/**
	 * Update all records matching a criteria
	 * @param array $criteria
	 * @param array $updateDoc
	 * @param array $options
	 */
	public function updateAll($criteria=array(),$updateDoc=array(),$options=array('multiple'=>true)){
		$this->trace(__FUNCTION__);
		return $this->getCollection()->update($criteria, $updateDoc, array_merge($this->getDb()->getDefaultWriteConcern(), $options));
	}

	/**
	 * Delete all records matching a criteria
	 * @param array $criteria
	 * @param array $options
	 */
	public function deleteAll($criteria=array(),$options=array()){
		$this->trace(__FUNCTION__);
		return $this->getCollection()->remove($criteria, array_merge($this->getDb()->getDefaultWriteConcern(), $options));
	}

	/**
	 * (non-PHPdoc)
	 * @see http://www.yiiframework.com/doc/api/1.1/CActiveRecord#saveCounters-detail
	 */
	public function saveCounters(array $counters) {
		$this->trace(__FUNCTION__);

		if ($this->getIsNewRecord())
			throw new Exception('The active record cannot be updated because it is new.');

		if(sizeof($counters)>0){
			foreach($counters as $k => $v) $this->$k=$this->$k+$v;
			return $this->updateByPk($this->{$this->primaryKey()}, array('$inc' => $counters));
		}
		return true; // Assume true since the action did run it just had nothing to update...
	}

	/**
	 * Count() allows you to count all the documents returned by a certain condition, it is analogous
	 * to $db->collection->find()->count() and basically does exactly that...
	 * @param EMongoCriteria|array $criteria
	 */
	public function count($criteria = array()){
		$this->trace(__FUNCTION__);

		// If we provide a manual criteria via EMongoCriteria or an array we do not use the models own DbCriteria
		return $this->getCollection()->find(isset($criteria) ? $criteria : array())->count();
	}

	/**
	 * Alias for getRelated
	 */
	public function with($name,$refresh=false,$params=array()){
		return $this->getRelated($name,$refresh=false,$params=array());
	}

	/**
	 * Returns the related record(s).
	 * This method will return the related record(s) of the current record.
	 * If the relation is 'one' it will return a single object
	 * or null if the object does not exist.
	 * If the relation is 'many' it will return an array of objects
	 * or an empty iterator.
	 * @param string $name the relation name (see {@link relations})
	 * @param boolean $refresh whether to reload the related objects from database. Defaults to false.
	 * @param mixed $params array with additional parameters that customize the query conditions as specified in the relation declaration.
	 * @return mixed the related object(s).
	 * @throws EMongoException if the relation is not specified in {@link relations}.
	 */
	public function getRelated($name,$refresh=false,$params=array())
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
		$where = array();
		if(isset($relation['where'])) $where = array_merge($relation['where'], $params);

		// Find out what the pk is and what kind of condition I should apply to it
		if (is_array($pk)) {
			//It is an array of references
			if (MongoDBRef::isRef(reset($pk))) {
				$result = array();
				foreach ($pk as $singleReference) {
					$row = $this->populateReference($singleReference, $cname);
					if ($row) array_push($result, $row);
				}
				return $result;
			}
			// It is an array of _ids
			$clause = array_merge($where, array($fkey=>array('$in' => $pk)));
		}elseif($pk instanceof MongoDBRef){

			// I should probably just return it here
			// otherwise I will continue on
			return $this->populateReference($pk, $cname);

		}else{

			// It is just one _id
			$clause = array_merge($where, array($fkey=>$pk));
		}

		$o = $cname::model();
		if($relation[0]==='one'){

			// Lets find it and return it
			$cursor = $o->findOne($clause);
		}elseif($relation[0]==='many'){

			// Lets find them and return them
			$cursor = $o->find($clause);
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
		$row = MongoDBRef::get(self::$db->getDB(), $reference);
		$o=(is_null($cname))?$this:$cname::model();
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
	 * Returns the database connection used by active record.
	 * By default, the "mongodb" application component is used as the database connection.
	 * You may override this method if you want to use a different database connection.
	 * @return EMongoClient the database connection used by active record.
	 */
	public function getDb()
	{
		if(self::$db!==null)
			return self::$db;
		else
		{
			self::$db=Yii::app()->db;
			if(self::$db instanceof \glue\db\Client)
				return self::$db;
			else
				throw new Exception('MongoDB Active Record requires a "mongodb" EMongoClient application component.');
		}
	}

	/**
	 * Cleans or rather resets the document
	 */
	public function clean(){
		$this->_attributes=array();
		$this->_related=array();

		// blank class properties
		$cache = $this->attributeNames();
		foreach($cache as $k => $v)
			$this->$k = null;
		return true;
	}

	/**
	 * This is an aggregate helper on the model
	 * Note: This does not return the model but instead the result array directly from MongoDB.
	 * @param array $pipeline
	 */
	public function aggregate($pipeline){
		$this->trace(__FUNCTION__);
		return $this->getDb()->aggregate($this->collectionName(),$pipeline);
	}

	/**
	 * A distinct helper on the model, this is not the same as the aggregation framework
	 * distinct
	 * @link http://docs.mongodb.org/manual/reference/command/distinct/
	 * @param string $key
	 * @param array $query
	 */
	public function distinct($key, $query = array()){
		$this->trace(__FUNCTION__);
		$c=$this->getDbCriteria();
		if(is_array($c) && isset($c['condition']) && !empty($c['condition']))
			$query=\glue\Collection::mergeArray($query, $c['condition']);

		return $this->getDb()->command(array(
			'distinct' => $this->collectionName(),
			'key' => $key,
			'query' => $query
		));
	}

	/**
	 * Refreshes the data from the database
	 */
	public function refresh(){

		$this->trace(__FUNCTION__);
		if(!$this->getIsNewRecord() && ($record=$this->getCollection()->findOne(array($this->primaryKey() => $this->getPrimaryKey())))!==null){
			$this->clean();

			foreach($record as $name=>$column)
				$this->$name=$record[$name];
			return true;
		}
		else
			return false;
	}

	/**
	 * gets and if null sets the db criteria for this model
	 * @param $createIfNull
	 */
	public function getDbCriteria($createIfNull=true)
	{
		if($this->_criteria===null)
		{
			if(($c=$this->defaultScope())!==array() || $createIfNull)
				$this->_criteria=$c;
			else
				return array();
		}
		return $this->_criteria;
	}

	/**
	 * Sets the db criteria for this model
	 * @param array $criteria
	 */
	public function setDbCriteria($criteria){
		return $this->_criteria=$criteria;
	}

	/**
	 * Merges the currrent DB Criteria with the inputted one
	 * @param array $newCriteria
	 */
	public function mergeDbCriteria($newCriteria){
		return $this->_criteria=$this->mergeCriteria($this->getDbCriteria(), $newCriteria);
	}

	/**
	 * Gets the collection for this model
	 */
	public function getCollection(){
		return $this->getDb()->{$this->collectionName()};
	}

	/**
	 * Merges two criteria objects. Best used for scopes
	 * @param $oldCriteria
	 * @param $newCriteria
	 */
	public function mergeCriteria($oldCriteria, $newCriteria){
		return \glue\Collection::mergeArray($oldCriteria, $newCriteria);
	}

	/**
	 * Gets the formed document with MongoYii objects included
	 */
	public function getDocument(){

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
	public function getRawDocument(){
		return $this->filterRawDocument($this->getDocument());
	}

	/**
	 * Filters a provided document to take out MongoYii objects.
	 * @param array $doc
	 */
	public function filterRawDocument($doc){
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

	/**
	 * Gets the JSON encoded document
	 */
	public function getJSONDocument(){
		return json_encode($this->getRawDocument());
	}

	/**
	 * Gets the BSON encoded document (never normally needed)
	 */
	public function getBSONDocument(){
		return bson_encode($this->getRawDocument());
	}

    /**
     * Produces a trace message for functions in this class
     * @param string $func
     */
    public function trace($func){
    	return true;
    	//Yii::trace(get_class($this).'.'.$func.'()','extensions.MongoYii.EMongoDocument');
    }
}