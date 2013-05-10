<?php

namespace glue;

class Component{

	public function __construct($config=array()){
		foreach($config as $k=>$v)
			$this->$k=$v;
		$this->init();
	}

	function init(){ return true; }

	function setAttributes($a){
		if($a){
			foreach($a as $k=>$v){
				$this->$k = $v;
			}
		}
	}
}