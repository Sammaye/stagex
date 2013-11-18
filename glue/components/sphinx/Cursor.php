<?php
namespace glue\components\Sphinx;

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
		$this->matches = isset($result['matches'])?$result['matches']:array();
		reset($this->matches);
		
		$this->totalFound=isset($result['total_found'])?$result['total_found']:0;
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
				return $fn($c['attrs'],$this->className);
			elseif($this->className)
				return $className::model()->findOne(array('_id' => new \MongoId($c['attrs']['_id'])));
			else 
				return (Object)$c['attrs'];
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
        if(!$this->run){
        	
        	if($this->limit<=0)
        		$limit=null;
        	else
        		$limit=$this->limit;
        	
        	$this->matches=array_slice($this->matches, $this->skip, $limit, true);
        	$this->run=true;
        }
    }
    
    public function skip($n){
    	$this->skip=$n;
    	return $this;
    }
    
    public function limit($n){
    	$this->limit=$n;
    	return $this;
    }
}