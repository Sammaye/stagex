<?php

namespace glue;

use Glue;

class Component{

	private $_events;
	private $_behaviours = array();

	public function behaviours()
	{
		return array();
	}

	/**
	 * Magically you can call any function within a behaviour as though they are part of the
	 * parent model
	 * @param string $name
	 * @param array $parameters
	 */
	public function __call($name, $parameters)
	{
		foreach($this->_behaviours as $k => $m){
			if($m->method_exists($name)){
				return call_user_func_array(array($m,$name),$parameters); // Call behaviour methods
			}
		}
		return false;
	}

	public function __get($k)
	{
		if(method_exists($this, 'get' . $k))
			return $this->{'get' . $k}();
	}

	public function __set($k, $v)
	{
		if(method_exists($this, 'set' . $k))
			return $this->{'set' . $k}($v);
	}

	public function __construct($config=array())
	{
		foreach($config as $k=>$v)
			$this->$k=$v;
		foreach($this->behaviours() as $name => $attr){
			$this->attach($name, $attr);
		}
		$this->init();
	}

	public function init()
	{
	}
	
	public static function getName()
	{
		$class = get_called_class();
		$parts = explode('\\',$class);
		return end($parts);
	}	

	/**
	 * Checks to see if a method exists. This will search all behaviours as well to see if a method exists
	 * @param string $f
	 * @return boolean
	 */
	public function method_exists($f)
	{
		if(method_exists($this, $f)){
			return true;
		}else{
			foreach($this->_behaviours as $b){
				if(method_exists($b, $f)){
					return true;
				}
			}
		}
		return false;
	}

	public function attach($name, $options = array())
	{
		if(!isset($options['class']))
			throw new Exception("There is no class set for {$name} behaviour");

		if(!isset($this->_behaviours[$name])){
			$cname=$options['class'];
			$behaviour = new $cname($options);
			$behaviour->owner = $this;

			foreach($behaviour->events() as $e => $f){
				$this->on($e, is_string($f) ? array($behaviour, $f) : $f);
			}
			$this->_behaviours[$name] = $behaviour;
		}
	}

	public function detach($name)
	{
		if($behaviour = $this->_behaviours[$name]){
			foreach($behaviour->events() as $e => $f){
				$this->off($e, is_string($f) ? array($behaviour, $f) : $f);
			}
			unset($behaviour[$name]);
		}
	}

	public function trigger($event, $data = array())
	{
		$event_success = true;
		if(is_array($this->_events) && isset($this->_events[$event])){
			foreach($this->_events[$event] as $i => $f){
				if(is_array($f)){
					$event_success = call_user_func_array($f, $data) && $event_success;
				}else{
					$event_success = $f() && $event_success;
				}
			}
		}
		return $event_success;
	}

	public function on($event, $callback = array())
	{
		$this->_events[$event][] = $callback;
	}

	public function off($event, $handler = null)
	{
		if(isset($this->_events[$name])){
			if($handler === null){
				$this->_events[$name] = array();
			}else{
				$removed=false;
				foreach($this->_events[$name] as $i => $f){
					if($f === $handler){
						unset($this->_events[$name][$i]);
						$removed=true;
						break; // If I have removed it, I don't need to carry on removing it
					}
				}

				if($removed){
					$this->_events[$name] = array_values($this->_events[$name]);
				}
				return $removed;
			}
		}
		return false;
	}
}