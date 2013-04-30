<?php
class GMongoCursor implements Iterator, Countable{

	private $className;
	private $cursor;

	private $isMr;
	private $current;

    public function __construct($cursor, $className = __CLASS__, $isMR = false) {
    	$this->cursor = $cursor;
    	$this->className = $className;
    	$this->isMr = $isMR;

    	if($this->cursor)
        	$this->cursor->reset();
    }

    function cursor(){
    	return $this->cursor;
    }

    function count(){
    	if($this->cursor())
    		return $this->cursor()->count();
    }

    function sort(array $fields){
		$this->cursor()->sort($fields);
		return $this;
    }

    function skip($num){
		$this->cursor()->skip($num);
		return $this;
    }

    function limit($num){
		$this->cursor()->limit($num);
		return $this;
    }

    function rewind() {
        $this->cursor()->rewind();
        return $this;
    }

    function current() {
        $this->current = new $this->className();

        $this->current->setIsNewRecord(false);
        $this->current->setScenario('update');

        if(!$this->current->onBeforeFind()) return null;
        if($this->isMr){
			$doc = $this->cursor()->current();
			$this->current->setAttributes(array_merge(array('_id'=>$doc['_id']), $doc['value']));
        }else{
        	$this->current->setAttributes($this->cursor()->current());
        }
        $this->current->onAfterFind();
        return $this->current;
    }

    function key() {
        return $this->cursor()->key();
    }

    function next() {
        return $this->cursor()->next();
    }

    function valid() {
        return $this->cursor()->valid();
    }
}