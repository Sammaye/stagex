<?php

namespace glue\db;

use Glue;
use \Iterator;
use \Countable;
use glue\Component;

class Cursor extends Component implements Iterator, Countable
{
	public $select;
	
	public $model;
	
	public $from;
	
	public $with = array();
	
	public $where;
	
	public $sort = array();
	
	public $skip;
	
	public $limit;
	
	private $_mongoCursor;
	
	private $_run = false;

	/**
	 * If we call a function that is not implemented here we try and pass the method onto
	 * the MongoCursor class, otherwise we produce the error that normally appears
	 *
	 * @param $method
	 * @param $params
	 */
	public function __call($method, $params = array())
	{
		if($this->cursor() instanceof \MongoCursor && method_exists($this->cursor(), $method)){
			return call_user_func_array(array($this->cursor(), $method), $params);
		}
		
		if($this->model !== null && method_exists($this->model, $method)){
			$class = $this->model;
			array_unshift($params, $this);
			call_user_func_array(array($class, $method), $params);
		}
		
		parent::__call($method, $params);
	}

	/**
	 * Holds the MongoCursor
	 */
	public function cursor()
	{
		return $this->_mongoCursor;
	}
	
	public function getCollection($db = null)
	{
		$modelClass = $this->model;
		if($db === null && $modelClass !== null){
			$db = $modelClass::getDb();
		}elseif($db === null){
			$db = glue::db();
		}
		
		if($this->from === null){
			$this->from = $modelClass::collectionName();
		}
		return $db->{$this->from};
	}	
	
	public function one()
	{
		return $this->current($this->getCollection()->findOne($this->where, $this->select));
	}
	
	public function all()
	{
		$cursor = $this->getCollection()
			->find($this->where, $this->select)
			->sort($this->sort)
			->skip($this->skip)
			->limit($this->limit);
		$this->_mongoCursor = $cursor;
		return $this;
	}
		
	public function sort($fields)
	{
		$this->sort = $fields;
		return $this;
	}
		
	public function skip($num = 0)
	{
		$this->skip = $num;
		return $this;
	}
		
	public function limit($num = 0)
	{
		$this->limit = $num;
		return $this;
	}
	
	public function slaveOkay($val = true){
		$this->cursor()->slaveOkay($val);
		return $this;
	}	

	/**
	 * Gets the active record for the current row
	 */
	public function current($current = null)
	{
		if($current === null && $this->cursor() instanceof \MongoCursor){
			$current = $this->cursor()->current();
		}elseif($current === null){
			return null;
		}
		
		if($this->model === null){
			return $current;
		}else{
			$class = $this->model;
			return $class::populate($current, true);
		}
	}
	
	public function getNext()
	{
		if($next = $this->cursor()->getNext()){
			return $this->current($next);
		}
	}	

	public function count($takeSkip = false)
	{
		$this->rewind();
		return $this->cursor()->count($takeSkip);
	}
	
	public function rewind()
	{
		if($this->_run === false){
			$this->all();
			$this->_run = true;
		}		
		
		$this->cursor()->rewind();
		return $this;
	}

	public function key()
	{
		return $this->cursor()->key();
	}

	public function next()
	{
		return $this->cursor()->next();
	}

	public function valid()
	{
		return $this->cursor()->valid();
	}
}