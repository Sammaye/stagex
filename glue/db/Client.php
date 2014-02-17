<?php

namespace glue\db;

use Glue;
use \glue\Component;
use \glue\Exception;
use \glue\db\Collection;
use \glue\db\Database;

class Client extends Component
{
    public $dsn;

    public $options = array();

    public $defaultDatabaseName;

    public $mongoClient;
    
    public $indexes = array();

    private $_databases = array();

    public function __call($name,$parameters = array())
    {
        $this->connect();
        return call_user_func_array(array($this->mongoClient, $name), $parameters);
    }
    
    public function __get($k)
    {
    	if(parent::__get($k) === null){
    		return $this->selectCollection($k);
    	}
    }

    public function selectCollection($name)
    {
        $dbname = is_array($name) ? $name[0] : null;
        $collection = is_array($name) ? $name[1] : $name;

        return $this->selectDB($dbname)->selectCollection($collection);
    }

    public function selectDB($name = null, $refresh = false)
    {
        $this->connect();
        
        if($name === null)
            $name=$this->getDefaultDatabaseName();

        if($refresh || !array_key_exists($name, $this->_databases)){
            $this->_databases[$name] = new Database(array(
                'mongoDb' => $this->mongoClient->selectDB($name)
            ));
        }
        return $this->_databases[$name];
    }

    public function connect()
    {
        if($this->mongoClient === null){
            
            if($this->defaultDatabaseName !== null){
                $this->options['db'] = $this->defaultDatabaseName;
            }
            
            $this->mongoClient = new \MongoClient($this->dsn, $this->options);
            $this->mongoClient->connect();
        }
        return true;
    }

    protected function getDefaultDatabaseName()
    {
        if($this->defaultDatabaseName === null){
            if(isset($this->options['db'])){
                $this->defaultDatabaseName = $this->options['db'];
            }elseif(preg_match('/^mongodb:\\/\\/.+\\/(.+)$/s', $this->dsn, $matches)){
                $this->defaultDatabaseName = $matches[1];
            }else{
                throw new Exception("Unable to determine default database name from dsn.");
            }
        }
        return $this->defaultDatabaseName;
    }
}