<?php

namespace glue\db;

use Glue;
use \glue\Component;

class Collection extends Component
{
    public $mongoCollection;
    
    public function init()
    {
    	$indexes = glue::db()->indexes;
    	if(isset($indexes[$this->mongoCollection->getName()])){
    		foreach($indexes[$this->mongoCollection->getName()] as $index){
    			$this->ensureIndex($index);
    		}
    	}
    }

    public function __call($name, $parameters = array())
    {
        return call_user_func_array(array($this->mongoCollection, $name), $parameters);
    }
    
    public function ensureIndex($index)
    {
		if(isset($index[0])){
    		return $this->mongoCollection->ensureIndex(
    			$index[0], 
    			isset($index[1]) ? $index[1] : array()
    		);
    	}else{
    		return $this->mongoCollection->ensureIndex($index);
    	}
    }
}