<?php

namespace glue\components\Sphinx;

require_once ( "sphinxapi.php" );

class Sphinx extends \glue\Component{

	public $host;
	public $port;
	public $indexes = array();
	
	public $resultsPerPage=20;
	public $maxMatches = 10000; // 10K Is the full search limit
	public $cutoff = 1000000; // Million row cutoff
	
	private $condition;
	private $maxPage;
	private $page = 1;
	private $limit;	
	private $sphinx;

	public function init(){
		$this->sphinx = new SphinxClient();
		$this->sphinx->SetServer ( $this->host, $this->port );
		
		// Lets set our defaults
		$this->sphinx->SetConnectTimeout ( 1 );
		$this->sphinx->SetArrayResult ( true );
		$this->sphinx->SetFilter('deleted', array(1), true);
		$this->sphinx->SetMatchMode(SPH_MATCH_EXTENDED2);
		return $this;
	}
	
	public function __call($method,$params){
		if(method_exists($this->sphinx,$method)){
			return call_user_func_array(array($this->sphinx, $name), $parameters);
		}
	}
	
	public function setIteratorCallback($func){
		$this->iteratorCallback=$func;
		return $this;
	}
	
	public function select($select){
		$this->sphinx->SetSelect($select);
	}
	
	public function match($field,$keywords){
		if(strlen($query) > 0)
			$this->condition .= (is_array($field)?'@('.implode(',',$field).')':'@'.$field) . 
				(is_array($keywords)?explode(' ',$keywords):$keywords);
	}
	
	public function filter($attribute, $values = array(), $exclude = false){
		$this->sphinx->SetFilter($attribute, $values, $exclude);
	}
	
	public function filterRange ( $attribute, $min, $max ) {
		$this->sphinx->SetFilterRange($attribute, $min, $max);
	}	

	public function matchMode($mode = SPH_MATCH_ALL){
		$this->sphinx->SetMatchMode($mode);
	}	
	
	public function sort($mode = SPH_SORT_RELEVANCE, $sortby = ''){
		$this->sphinx->SetSortMode($mode, $sortby);
	}

	public function rank($mode = SPH_RANK_PROXIMITY_BM25){
		$this->sphinx->SetRankingMode($mode);
	}
	
	public function limit($limit,$offset=0){
		$this->limit=array($offset,$limit);
		$this->sphinx->setLimits($offset,$limit,$this->maxMatches,$this->cutoff);
	}
	
	public function page($num){
		$this->page=$num;
	}
	
	public function resultsPerPage($num){
		$this->resultsPerPage=$num;	
	}

	public function setGroupBy(){}
	
	public function resetMatch(){
		$this->condition=null;
		return $this;
	}
	
	public function resetFilters(){
		$this->sphinx->ResetFilters();
		return $this;
	}	

	public function resetLimit(){
		$this->limit=null;
		return $this;
	}
	
	public function resetPage(){
		$this->page=1;
		return $this;
	}
	
	public function resetResultsPerPage(){
		$this->resultsPerPage=20;
		return $this;
	}
	
	public function resetGroupBy(){
		$this->sphinx->ResetGroupBy();
		return $this;
	}
	
	public function resetOverrides(){
		$this->sphinx->ResetOverrides();
		return $this;
	}

	public function resetAll(){
		$this->resetMatch();
		$this->resetFilters();
		$this->resetLimit();
		$this->resetPage();
		$this->resetResultsPerPage();
		$this->resetGroupBy();
		$this->resetOverrides();
	}

	public function query($index,$className=''){

		// Lets get the indexes information
		$index_attr = $this->indexes[$index];

		// Does it have a delta?
		$indexName = $index.(isset($index_attr['type'])&&$index_attr['type']=='delta'?$index_attr['delta']:'');

		// If no limit is set we assume to use paging
		// currently you MUST put a limit in if you do not wish to use paging,
		// this MAY change		
		
		if($this->limit!==null){
			$result = $this->sphinx->Query($this->condition, $indexName);
			$this->resetAll();
			if($error = $this->sphinx->GetLastError())
				throw new \Exception($error); // Throwing an exception should exit
			return new Cursor($result,$className);	
		}
		
		// Just like in SQL I need to do two queries to figure out ouor paging properly
		$this->sphinx->setLimits(0,$this->resultsPerPage,$this->maxMatches,$this->cutoff);
		$firstPage = $this->sphinx->Query($this->condition, $indexName);
		
		if($error = $this->sphinx->GetLastError())
			throw new \Exception($error);
		
		if($firstPage['total_found'] > 0){
			$this->maxPage = $first_page['total_found'] < $this->maxMatches ? ceil($first_page['total_found']/20) : ceil($this->maxMatches/20);
			if($this->maxPage <= 0) $this->maxPage = 1;
			if($this->page > $this->maxPage) $this->page = $this->maxPage;
			if($this->page <= 0) $this->page = 1;
		}			
			
		if($this->page===1){
			$c = new Cursor($firstPage,$className);
		}else{
			$this->sphinx->SetLimits((int)(($this->page-1)*$this->resultsPerPage),$this->resultsPerPage,$this->maxLimit,$this->cutoff);
			$result = $this->sphinx->Query($query_string, $index_string);
			
			if($error = $this->sphinx->GetLastError())
				throw new \Exception($error);
			$c=new Cursor($result,$className);	
		}
		$c->maxPage=$this->maxPage;
		return $c;
	}
	
	public function UpdateAttributes($index, $attrs, $values){
		return $this->sphinx->UpdateAttributes($index, $attrs, $values);
	}	
}