<?php

namespace glue\db;

use Glue;
use \glue\Component;
use \glue\db\Collection;

class Database extends Component
{
    public $mongoDb;
    
    private $_collections = array();
    
    public function __get($k)
    {
    	return $this->selectCollection($k);
    }
    
    public function selectCollection($name, $refresh = null)
    {
        if($refresh || !array_key_exists($name, $this->_collections)){
            $this->_collections[$name] = new Collection(array(
                'mongoCollection' => $this->mongoDb->selectCollection($name)
            ));
        }
        return $this->_collections[$name];
    }
}