<?php
namespace \glue\components\Sphinx;

class Cursor implements \Iterator, \Countable{
	
	public $className;
	
	public $maxPage;
	public $matches;
	public $term;
	public $totalFound = 0;

	public $iteratorCallback;
	
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
			if(
				(is_string($this->iteratorCallback) && function_exists($this->iteratorCallback)) || 
				(is_object($this->iteratorCallback) && $this->iteratorCallback instanceof \Closure)
			)
				return $this->iteratorCallback($c['attrs'],$this->className);
			elseif($this->className)
				$className::model()->findOne(array('_id' => new \MongoId($c['attrs']['_id'])));
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
    }
}