<?php
namespace glue\components\elasticsearch;

class Query{

	public $index;
	public $type;
	
	public $filtered = false;
	
	private $query = array();
	private $filter = array();
	private $sort = array();

	private $params = array();
	
	private $previousPiece;
	
    public function __call($name = null, $params = array())
    {
        if ($this->previousPiece === null) {
        	throw new \Exception('There was no root defined for the Elastic Search query');
        } else {
        	if (empty($params)) {
        		if (!array_key_exists($name, $this->previousPiece)) {
	        		$this->previousPiece[$name] = array();
        		}
	        	$this->previousPiece =& $this->previousPiece[$name];
        	} else {
        		$this->previousPiece[$name][] = array($params[0] => $params[1]);
        	}
        }
    	return $this;
    }
    
    public function __get($k)
    {
    	if (array_key_exists($k, $this->params)) {
    		return $this->params[$k];
    	}
    	return null;
    }
    
    public function __set($k, $v)
    {
    	$this->params[$k] = $v;
    }
    
    public function __construct()
    {    
    }
    
    public function query()
    {
        $this->previousPiece =& $this->query;
        return $this;
    }
    
    public function filter()
    {
    	$this->previousPiece =& $this->filter;
    	return $this;
    }
    
    public function sort($key, $value = null)
    {
    	if ($value === null)
    		$this->sort[] = $key;
    	else
    		$this->sort[] = array($key => $value);
    	return $this;
    }
    
    public function multiPrefix($fields, $keywords, $tokenizePattern = '/\s+/')
    {
    	$this->query()->bool()->should('multi_match', array(
    		'query' => $keywords,
    		'fields' => $fields
    	));
    	
    	$tokenized = preg_split($tokenizePattern, trim($keywords));
    	foreach ($tokenized as $keyword) {
    		for ($i = 0, $size = count($fields); $i < $size; $i++) {
    			$this->query()->bool()->should('prefix', array($fields[$i] => $keyword));
    		}
    	}    	
    }
    
    public function page($page, $pageSize = 20)
    {
    	$this->from = ($page - 1) * $pageSize;
    	$this->size = $pageSize;
    }
    
    public function get()
    {
    	$query['index'] = $this->index;
    	$query['type'] = $this->type;    	
    	
    	if ($this->filtered) {
    		$query['body'] = array('query' => array(
    			'filtered' => array(
    				'query' => $this->query,
    				'filter' => $this->filter		
    			)));
    	} else {
    		$query['body'] = array('query' => array(
    			'query' => $this->query,
    			'filter' => $this->filter
    		));    		
    	}
    	$query['body']['sort'] = $this->sort;
    	
    	foreach ($this->params as $k => $v) {
    		$query['body'][$k] = $v;
    	}
    	return $query;
    }
}