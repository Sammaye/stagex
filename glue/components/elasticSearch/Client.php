<?php

namespace glue\components\Elasticsearch;

use glue;
use \glue\components\Elasticsearch\Cursor;

require glue::getPath('@glue').'/components/vendor/autoload.php';

class Client extends \glue\Component{
    
    public $index;
    public $params=array();
    
    private $client;
    
    public function __call($name, $parameters = array())
    {
        return call_user_func_array(array($this->getClient(), $name), $parameters);
    }
    
    public function init()
    {
        if($this->client === null)
        {
            $this->connect();
        }
    }
    
    public function connect()
    {
        return $this->client = new \Elasticsearch\Client($this->params);
    }
    
    public function getClient()
    {
        if($this->client == null)
        {
            $this->connect();
        }
        return $this->client;
    }
    
    public function getIndex()
    {
        return $this->index;
    }
    
    public function search($body, $className = null)
    {
    	if ($body instanceof Query)
    		$body = $body->get();
        return new Cursor($this->client->search(array_merge(array( 'index' =>  $this->getIndex()), $body)), $className);
    }
    
    public function index($body)
    {
        return $this->client->index(array_merge(array( 'index' =>  $this->getIndex()), $body));
    }
    
    public function update($body)
    {
        return $this->client->update(array_merge(array( 'index' =>  $this->getIndex()), $body));
    }
    
    public function delete($body)
    {
        return $this->client->delete(array_merge(array( 'index' =>  $this->getIndex()), $body));
    }
    
    public function deleteByQuery($body)
    {
        return $this->client->deleteByQuery(array_merge(array( 'index' =>  $this->getIndex()), $body));
    }
}