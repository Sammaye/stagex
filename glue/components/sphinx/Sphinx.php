<?php

namespace glue\components\Sphinx;

require_once ( "sphinxapi.php" );

class Sphinx extends \glue\Component implements ArrayAccess, Iterator, Countable{

	public $host;
	public $port;
	public $indexes = array();

	public $matches;
	public $total_found = 0;
	public $max_page;
	public $page = 1;
	public $limit;

	public $maxLimit = 10000; // 10K Is the full search limit

	public $cutoff = 1000000; // Million row cutoff

	public $term;

	private $condition;
	
	private $iteratorCallback;
	
	private $sphinx;

	function init(){
		$this->sphinx = new SphinxClient();
		$this->sphinx->SetServer ( $this->host, $this->port );
		$this->sphinx->SetConnectTimeout ( 1 );
		$this->sphinx->SetArrayResult ( true );
		$this->sphinx->SetFilter('deleted', array(1), true);

		return $this;
	}
	
	public function __call($method,$params){
		if(method_exists($this->sphinx,$method)){
			return call_user_func_array(array($this->sphinx, $name), $parameters);
		}
	}
	
	function setIteratorCallback($func){
		$this->iteratorCallback=$func;
		return $this;
	}
	
	function select($select){
		$this->sphinx->SetSelect($select);
	}
	
	function match(){
		
	}
	
	function filter($attribute, $values = array(), $exclude = false){
		$this->sphinx->SetFilter($attribute, $values, $exclude);
	}
	
	function filterRange ( $attribute, $min, $max ) {
		$this->sphinx->SetFilterRange($attribute, $min, $max);
	}	

	function matchMode($mode = SPH_MATCH_ALL){
		$this->sphinx->SetMatchMode($mode);
	}	
	
	function sort($mode = SPH_SORT_RELEVANCE, $sortby = ''){
		$this->sphinx->SetSortMode($mode, $sortby);
	}

	function rank($mode = SPH_RANK_PROXIMITY_BM25){
		$this->sphinx->SetRankingMode($mode);
	}

	function UpdateAttributes($index, $attrs, $values){
		return $this->sphinx->UpdateAttributes($index, $attrs, $values);
	}
	
	function resetMatch(){
		
	}
	
	function resetFilters(){
		
	}
	
	function setGroupBy(){
		
	}

	function resetLimit(){
		unset($this->limit);
	}
	
	function resetOverrides(){
		$this->sphinx->ResetOverrides();
		return $this;
	}

	function resetAll(){
		$this->page = 1;
		unset($this->limit);
		$this->sphinx->ResetFilters();
		$this->sphinx->ResetGroupBy();
		$this->sphinx->ResetOverrides();
	}

	function formFields($query, $index = 'main'){
		if($query && strlen($query) > 0){
			$index_attr = $this->indexes[$index];
			$query_array = array();
			foreach($index_attr['query_fields'] as $field){
				$query_array[] = $field;
			}
			$query_string .= '@('.implode(',', $query_array).') '.$query;
			return $query_string;
		}else{
			return '';
		}
	}

	/**
	 * The main query function.
	 *
	 * @example query(array('select' => $_GET['q'], 'where' => array('uid' => array(strval($this->_id))), [ 'query' => '@title "the"' ]), 'media');
	 *
	 * @param $query
	 * @param $index
	 */
	function query($query = array(), $index = ''){

		$query_string = '';
		$this->sphinx->SetMatchMode(SPH_MATCH_EXTENDED2);

		// Lets get the indexes information
		$index_attr = $this->indexes[$index];

		// If that index is to be passed into a cursor
		if(isset($index_attr['cursor'])){
			glue::import('glue/plugins/sphinx/'.$index_attr['cursor'].'.php');
			$cursor = $index_attr['cursor']; // Cos PHP is still a bit weird when trying to set a class name from a element array we use the predefined variable
		}

		// **
		// Does this index have a Delta?
		// **
		$type = isset($index_attr['type']) ? $index_attr['type'] : '';
		if($type == 'delta'){ // If index has delta lets handle that
			$index_string = $index.' '.$index_attr['delta']; //$index.' '.
		}else{
			$index_string = $index;
		}

		// **
		// Lets form the query, if it is a raw query just place that in else go through
		// fields assigning the value
		// **
		if(isset($query['query']) && strlen($query['query']) > 0){
			$query_string .= $query['query'];
		}elseif(isset($query['select']) && strlen($query['select']) > 0){

			$query_array = array();
			foreach($index_attr['query_fields'] as $field){
				$query_array[] = $field;
			}
			$query_string .= '@('.implode(',', $query_array).') '.$query['select'];
		}

		//**
		// Now lets for the where up
		//**
		if(isset($query['where'])){ // then build the filter clause
			$query_array = array();
			foreach($query['where'] as $field => $values){

				$values_array = array();
				foreach($values as $value){
					$values_array[] = '"' . $value . '"';
				}
				$query_array[] = '@'.$field.' '.implode(' ', $values_array);
			}
			$query_string .= ' '.implode(' ', $query_array);
		}

		//**
		// Judge which path to take. If limit is installed then do that else do paging by default
		//**
		if(isset($query['limit']) || isset($this->limit)){
			$this->sphinx->SetLimits(0, isset($query['limit']) ? $query['limit'] : $this->limit, $this->maxLimit, $this->maxLimit); // Always get first page first.
			$result = $this->sphinx->Query($query_string, $index_string);

			$error = $this->sphinx->GetLastError();
//var_dump($result);
//exit();
			if(!$error){
				if($index_attr['cursor']){
					$this->matches = new $cursor(!empty($result['matches']) ? $result['matches'] : array());
				}else{
					$this->matches = $result['matches'];
				}
			}else{
				trigger_error($error);
			}
		}else{ // Lets just assume paging

			if(isset($query['results_per_page'])){
				$this->sphinx->SetLimits(0, $query['results_per_page'], $this->maxLimit, $this->cutoff); // Always get first page first.
			}else{
				$this->sphinx->SetLimits(0, 20, $this->maxLimit, $this->cutoff); // Always get first page first.
			}
			$first_page = $this->sphinx->Query($query_string, $index_string);
//var_dump($query_string); exit();
			$error = $this->sphinx->GetLastError();

			if(!$error){

				if(isset($first_page['matches']) && $first_page['total_found'] > 0){
					$this->total_found = $first_page['total_found'];
					$this->max_page = $this->total_found < $this->maxLimit ? ceil($this->total_found/20) : ceil($this->maxLimit/20);

					if($this->max_page <= 0) $this->max_page = 1;
					if($this->page > $this->max_page) $this->page = $this->max_page;
					if($this->page <= 0) $this->page = 1;

					if($this->page == 1){ // Then just respond with the original query.
						if($index_attr['cursor']){
							$this->matches = new $cursor(is_array($first_page['matches']) ? $first_page['matches'] : array());
						}else{
							$this->matches = $first_page['matches'];
						}
					}else{
						if(isset($query['results_per_page'])){
							$this->sphinx->SetLimits((int)(($this->page-1)*$query['results_per_page']), $query['results_per_page'], $this->maxLimit, $this->cutoff); // Testing first page
						}else{
							//var_dump(($this->page-1)*20); exit();
							$this->sphinx->SetLimits((int)(($this->page-1)*20), 20, $this->maxLimit, $this->cutoff); // Testing first page
						}
						$res = $this->sphinx->Query($query_string, $index_string);

						if($res){
							if($index_attr['cursor']){
								$this->matches = new $cursor(is_array($res['matches']) ? $res['matches'] : array());
							}else{
								$this->matches = $res['matches'];
							}
						}else{
							trigger_error($this->sphinx->GetLastError());
							return $this->sphinx->GetLastError();
						}
					}
				}
			}else{
				trigger_error($error);
				return $error;
			}
		}
	}
	
	public function __construct($matches) {
		$this->_container = $matches;
		//$this->_container->reset();
		reset($this->_container);
	}
	
	function set($matches){
		$this->_container = $matches;
	}
	
	function get(){
		return $this->_container;
	}
	
	function count(){
		return count($this->_container);
	}
	
	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			$this->_container[] = $value;
		} else {
			$this->_container[$offset] = $value;
		}
	}
	
	public function offsetExists($offset) {
		return isset($this->_container[$offset]);
	}
	
	public function offsetUnset($offset) {
		unset($this->_container[$offset]);
	}
	
	public function offsetGet($offset) {
		//var_dump(__METHOD__);
		if(isset($this->_container[$offset])){
	
			$c = $this->_container[$offset]['attrs'];
	
			if($c['type']){
				switch($c['type']){
					case "video":
						$o = Video::model()->findOne(array('_id' => new MongoId($c['_id'])));
						break;
					case "user":
						$o = User::model()->findOne(array('_id' => new MongoId($c['_id'])));
						break;
					case "playlist":
						$o = Playlist::model()->findOne(array('_id' => new MongoId($c['_id'])));
						break;
				}
			}else{
				$o = (Object)$c;
			}
			$o->sphinxdocId = $c['id'];
			return $o;
		}
	
		return null; //Else lets just return normal
	}
	
	function rewind() {
		reset($this->_container);
	}
	
	function current() {
	
		if(current($this->_container) !== false){
			$c = current($this->_container);
			//var_dump($c);
			if($c['attrs']['type']){
				switch($c['attrs']['type']){
					case "video":
						$o = Video::model()->findOne(array('_id' => new MongoId($c['attrs']['_id'])));
						break;
					case "user":
						$o = User::model()->findOne(array('_id' => new MongoId($c['attrs']['_id'])));
						break;
					case "playlist":
						//var_dump($c);
						$o = Playlist::model()->findOne(array('_id' => new MongoId($c['attrs']['_id'])));
						break;
				}
			}else{
				$o = (Object)$c['attrs'];
			}
			//$o->sphinxdocId = $c['id'];
			return $o;
		}else{
			return  false;
		}
	}
	
	public function key() {
		return key($this->_container);
	}
	
	public function next() {
		return next($this->_container);
	}
	
	public function valid() {
		return $this->current() !== false;
	}	
}