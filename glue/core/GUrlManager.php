<?php
class GUrlManager{

	function create($path = '/', $params = array(), $host = '/', $scheme = 'http'){

		if($host === null){
			$host = glue::http()->baseUrl();
		}else if($host == '/'){
			$host = glue::http()->baseUrl(true);
		}else{
			if(strpos($host, 'http')!==0)
				$host = $scheme.'://'.$host;
		}

		$fragment = '';
		if(array_key_exists('#', $params)){
			$fragment = $params['#']; unset($params['#']);
		}

		if(!is_array($path) && $path == 'SELF'){
			$params = $_GET;
			unset($params['url']);

			$path = glue::http()->path();
			return $host.'/'.$path.(sizeof($params) > 0 ? '?'.$this->getParams($params) : '').($fragment ? '#'.$fragment : '');
		}

		if(is_array($path)){
			// Then this is a mege scenario
			if(array_key_exists('#', $path)){
				$fragment = $path['#']; unset($path['#']);
			}

			$getParams = $_GET;
			unset($getParams['url']);
			$params = array_merge($getParams, $path);
			$path = '/'.glue::http()->path();
		}
		return $host.$path.(sizeof($params) > 0 ? '?'.$this->getParams($params) : '').($fragment ? '#'.$fragment : '');
	}

	public function get($returnObj = false){
		if($returnObj){
			return array();
		}else{
			return $this->create('SELF');
		}
	}

	public function getParams($params = null){
		$ar = array();
		if(empty($params)){
			$params = $_GET;
			unset($_GET['url']);
		}

		foreach($params as $field => $value){
			$ar[] = $field.'='.$value;
		}
		return implode('&amp;', $ar);
	}
}