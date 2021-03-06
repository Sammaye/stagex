<?php

namespace glue;

use glue,
	\glue\Exception;

class Collection implements \Iterator,\ArrayAccess,\Countable{

	public $limit;
	public $skip;
	
	private $queried=false;
	
	private $_container;
	private $_class;

	public function __construct($list, $fields = array(), $class = null){

		if($class)
			$this->_class = $class;

		$return_array = array();
		$list = is_string($list) ? include glue::getPath('@app').'/lists/'.$list.'.php' : $list;

		if(count($fields) <= 0)
			$this->_container = $list;
		elseif(is_array($fields)){
			foreach($list as $row){
				$return_array[$row[$fields[0]]] = $row[$fields[1]];
			}
			$this->_container = $return_array;
		}elseif(is_string($fields)){
			foreach($list as $row){
				$return_array[] = $row[$fields];
			}
			$this->_container = $return_array;
		}
		return $this;
	}
	
	public function o($o){
		return $this->_container;
	}

	public function sort(){}

	public function count(){
		return count($this->_container);
	}
	
	public function skip($n){
		$this->skip=$n;
		return $this;
	}
	
	public function limit($n){
		$this->limit=$n;
		return $this;
	}

 	public function rewind() {
        reset($this->_container);
    }

    public function current() {
    	if(current($this->_container) !== false){
    		if($this->_class){
	        	$o = new $this->_class();
	        	$o->setAttributes(current($this->_container));
	        	return $o;
    		}else{
    			return current($this->_container);
    		}
    	}else{
    		return false;
    	}
    }

    public function key() {
        return key($this->_container);
    }

    public function next() {
        return next($this->_container);
    }

    public function valid() {
    	// If this is the first time we have run this iterator then let us do in memory aggregation operations now
    	if(!$this->queried){
    		if($this->skip > 0)
    			$this->_container = array_values(array_slice($this->_container, $this->skip, $this->limit));
    		else
    			$this->_container = array_slice($this->_container, $this->skip, $this->limit);
    	}
    	$this->queried = true;
        return $this->current() !== false;
    }
    
    public function offsetSet($offset, $value) {
    	if (is_null($offset)) {
    		$this->_container[] = $value;
    	} else
    		$this->_container[$offset] = $value;
    }
    
    public function offsetExists($offset) {
    	return isset($this->_container[$offset]);
    }
    
    public function offsetUnset($offset) {
    	unset($this->_container[$offset]);
    }
    
    public function offsetGet($offset) {
    	if(isset($this->_container[$offset])){
    		if($this->_class){
    			$o = new $this->_class;
    			$o->setAttributes($this->_container[$offset]);
    			return $o;
    		}else
    			return $this->_container[$offset];
    	}
    	return null; //Else lets just return normal
    }
    

	function filter_array_fields($ar, $fields = array()){
		$new = null;
		foreach($ar as $k => $v){
			if(array_search($k, $fields) !== null){
				$new[$k] = !is_array($v) && preg_match('/^[0-9]+$/', $v) > 0 ? (int)$v : $v;
			}
		}
		return $new;
	}

	static function mergeArray() {

	    if (func_num_args() < 2) {
	    	throw new Exception(__FUNCTION__ .' needs two or more array arguments');
	        return;
	    }
	    $arrays = func_get_args();
	    $merged = array();
	    
	    while ($arrays) {
	        $array = array_shift($arrays);
	        if (!is_array($array)) {
	        	throw new Exception(__FUNCTION__ .' encountered a non array argument');
	            return;
	        }
	        if (!$array)
	            continue;
	        foreach ($array as $key => $value)
	            if (is_string($key))
	                if (is_array($value) && array_key_exists($key, $merged) && is_array($merged[$key]))
	                    $merged[$key] = call_user_func(__METHOD__, $merged[$key], $value);
	                else
	                    $merged[$key] = $value;
	            else
	                $merged[] = $value;
	    }
	    return $merged;
	}

	static function aggregate($new_array, $old_array){
		$ret = array();
		foreach($old_array as $k=>$v){
			if(isset($new_array[$k])){
				$ret[$k] = $v+$new_array[$k];
			}else{
				$ret[$k] = 0;
			}
			unset($new_array[$k]);
		}

		if(!is_array($new_array))
			$new_array = array();

		$ret = array_merge($ret, $new_array);
		return $ret;
	}
}