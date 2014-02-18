<?php
namespace glue\components\elasticsearch;

class Cursor implements \Iterator, \Countable{
	
	public $className;
	
	public $maxPage;
	public $matches;
	public $term;
	public $totalFound = 0;

	public $iteratorCallback;
	
	/** These are for client side skip and limit */
	public $skip=0;
	public $limit=0;
	
	public $run=false;
	
	public function __construct($result,$className=null) {
	    if(isset($result['hits']) && isset($result['hits']['hits'])){
	        $this->totalFound=$result['hits']['total'];
	        
	        $this->matches = $result['hits']['hits'];
	        reset($this->matches);
	    }
		$this->className=$className;
	}
	
	public function setIteratorCallback($callback){
		$this->iteratorCallback=$callback;
	}	
	
	function matches(){
		return $this->matches;
	}
	
	function currentMatch(){
		return current($this->matches);
	}
	
	function current() {
		if(($c=current($this->matches)) !== false){
			//var_dump($c);
			$fn=$this->iteratorCallback;
			$className=$this->className;
			if((is_string($fn) && function_exists($fn)) || (is_object($fn) && $fn instanceof \Closure))
				return $fn($c,$this->className);
			elseif($this->className)
				return $className::findOne(array('_id' => new \MongoId($c['_id'])));
			else 
				return (Object)$c;
		}else
			return  false;
	}
	
	public function count(){
		return count($this->matches);
	}
	
	public function key() {
		return key($this->matches);
	}
	
	public function next() {
		return next($this->matches);
	}
	
	public function valid() {
		return $this->currentMatch() !== false;
	}
	
    public function rewind() {
        reset($this->matches);
    }
    
    public function skip($n){
    	return $this;
    }
    
    public function limit($n){
    	return $this;
    }
}