<?php

namespace glue;

class Component{

	public function __construct($config=array()){
		foreach($config as $k=>$v)
			$this->$k=$v;
		$this->init();
	}

	function init(){ return true; }

	function attributes($a){
		if($a){
			foreach($a as $k=>$v){
				$this->$k = $v;
			}
		}
	}

	function beforeAction($controller, $action){ return true; }

	function afterAction($controler, $action){ return true; }
}