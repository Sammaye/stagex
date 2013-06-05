<?php

namespace glue;

class Component{

	private $behaviours = array();

	public function behaviours(){ return array(); }

	public function __construct($config=array()){
		foreach($config as $k=>$v)
			$this->$k=$v;
		//foreach($this->behaviours() as $name => $attr){
			//$this->attachBehaviour($name, $attr);
		//}
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

	function raiseEvent($event){
		foreach($this->behaviours as $behaviour => $attrs){
			if(isset($attrs[$event])){ // If event exists
				call_user_func_array($attrs[$event], array()); // Lets call its
			}
		}
	}

	/**
	 * ATTACH / DETACH FUNCTIONS
	 *
	 * These various functions concern themselves with attaching and detaching certain aspects of the model.
	 * This enables us to be able to build models dynamically and even use the std::Model class to give us anon models to play with
	 */

	function attachBehaviours($behaviours){
		if(is_array($behaviours)){
			foreach($behaviours as $name => $behaviour)
				$this->attachBehaviour($name, $behaviour);
		}
	}

	function attachBehaviour($name, $options = array()){

		if(!isset($options['class']))
			throw new Exception("There is no class set for {$name} behaviour");

		if(!isset($this->behaviours[$name])){
			$cname=$options['class'];
			$behaviour = new $cname;
			$behaviour->setAttributes($options);

			$this->behaviours[$name] = array(
				'obj' => $behaviour
			);
			$behaviour->attach($this);
		}
	}

	function detachBehaviour($name){
		$behaviour = $this->behaviours[$name];
		if(isset($behaviour['obj'])){
			$behaviour['obj']->detach();
		}
	}

	function attachEventHandler($event, $call_array = array()){
		$class_name = get_class($call_array[0]); // Get the name of the behaviour so we can index it
		$this->behaviours[$class_name][$event] = $call_array;
	}

	function detachEventHandler($event, $call_array = array()){
		$class_name = get_class($call_array[0]); // Get the name of the behaviour so we can index it
		unset($this->behaviours[$class_name][$event]);
	}
}