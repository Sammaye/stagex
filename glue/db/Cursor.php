<?php

namespace glue\db;

use \glue\Exception;

class Cursor implements \Iterator, \Countable{

	private $modelClass;
	private $model;

	private $cursor = array();
	private $current;

	/**
	 * This denotes a partial cursor which in turn will transpose onto the active record
	 * to state a partial document. If any projection is supplied this will result in true since
	 * I cannot detect if you are projecting the whole document or not...THERE IS NO PRE-DEFINED SCHEMA
	 * @var boolean
	 */
	private $partial = false;

	/**
	 * The cursor constructor
	 * @param array|MongoCursor $condition Either a condition array (without sort,limit and skip) or a MongoCursor Object
	 * @param string $class the class name for the active record
	 */
	public function __construct($modelClass,$cursor,$partial=false) {

		// If $fields has something in it
		if($partial)
			$this->partial=true;

	    if(is_string($modelClass)){
			$this->modelClass=$modelClass;
			$this->model=EMongoDocument::model($this->modelClass);
		}elseif($modelClass instanceof EMongoDocument){
			$this->modelClass=get_class($modelClass);
			$this->model=$modelClass;
		}

		$this->cursor=$cursor;
		return $this; // Maintain chainability
	}

	/**
	 * If we call a function that is not implemented here we try and pass the method onto
	 * the MongoCursor class, otherwise we produce the error that normally appears
	 *
	 * @param $method
	 * @param $params
	 */
	public function __call($method, $params = array()){
		if($this->cursor() instanceof \MongoCursor && method_exists($this->cursor(), $method)){
			return call_user_func_array(array($this->cursor(), $method), $params);
		}
		throw new Exception("Call to undefined function $method on the cursor");
	}

	/**
	 * Holds the MongoCursor
	 */
	public function cursor(){
		return $this->cursor;
	}

    /**
     * Get next doc in cursor
     */
    public function getNext(){
		if($c=$this->cursor()->getNext())
			return $this->current=$this->model->populateRecord($c,true,$this->partial);
    }

	/**
	 * Gets the active record for the current row
	 */
	public function current() {
		if($this->model === null)
			throw new Exception("The MongoCursor must have a model");
		return $this->current=$this->model->populateRecord($this->cursor()->current(),true,$this->partial);
	}

	public function count($takeSkip = false /* Was true originally but it was to change the way the driver worked which seemed wrong */){
		return $this->cursor()->count($takeSkip);
	}

	public function slaveOkay($val = true){
		$this->cursor()->slaveOkay($val);
		return $this;
	}

	public function sort(array $fields){
		$this->cursor()->sort($fields);
		return $this;
	}

	public function skip($num = 0){
		$this->cursor()->skip($num);
		return $this;
	}

	public function limit($num = 0){
		$this->cursor()->limit($num);
		return $this;
	}

	public function rewind() {
		$this->cursor()->rewind();
		return $this;
	}

	public function key() {
		return $this->cursor()->key();
	}

	public function next() {
		return $this->cursor()->next();
	}

	public function valid() {
		return $this->cursor()->valid();
	}
}