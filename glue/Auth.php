<?php

namespace glue;

use glue,
	glue\Exception;

class Auth extends \glue\Component{

	public $shortcuts;
	public $filters;
	public $responses;

	private $controller;

	function init(){
		glue::setEvents(array(
			'beforeAction' => 'beforeAction'
		),$this);
		parent::init();
	}

	function beforeAction($controller,$action){
		if(is_callable(array($controller, $action))){
			$this->controller=$controller;
			if($this->parseControllerRights($controller->authRules(), preg_replace('/'.glue::$actionPrefix.'/', '', $action))){
				return true;
			}else{
				glue::trigger('403');
				return false;
			}
		}
		return true;
	}

	function getShortcut($name){
		return isset($this->shortcuts[$name])?$this->shortcuts[$name]:null;
	}

	function getFilter($name){
		if(($filter=$this->getShortcut($name))!==null)
			$name=$filter;

		if(isset($this->filters[$name]))
			return $this->filters[$name];
		else
			return null;
	}

	/**
	 * If no actions array is defined then it is considered a global rule
	 *
	 * @param unknown_type $controllerPermissions
	 * @param unknown_type $action
	 */
	public function parseControllerRights($controllerPermissions, $action){

		foreach($controllerPermissions as $permission){

			$actions = isset($permission['actions']) && is_array($permission['actions']) ? $permission['actions'] : array();
			//var_dump($actions); var_dump($action);
			if((array_key_exists($action, array_flip($actions)) || count($actions) <= 0)){
				$users = isset($permission['users']) ? $permission['users'] : array('*');
				foreach($users as $role){
					//var_dump($role);
					if(($func=$this->getFilter($role))===null)
						throw new Exception("The auth shortcut: $role you specified within ".get_class($this->controller)." does not exist.");

//var_dump($func); exit();
					if(is_callable($func)){
						if($func()){
							if($permission[0] == "allow"){
								return true;
							}elseif($permission[0] == "deny"){
								return false;
							}
						}
					}else{
						throw new Exception("The auth shortcut you specified within ".get_class($this->controller)." does not exist.");
					}

					// No Hit. Only do on allow rules // This should be returning true shouldn't it??
					if($permission[0] == "allow" && (array_key_exists($action, array_flip($actions)) || count($actions) <= 0)){
						return false;
					}
				}
			}
		}
		return true;
	}

	// This is used as a means to else where
	public function check($roles, $all = true){
		$matched = true;

		if(is_string($roles)) $roles=array($roles);

		foreach($roles as $role=>$params){

			if(is_int($role)){
				$role = $params;
				$params = null;
			}

			if(($func=$this->getFilter($role))===null)
				throw new Exception("The role based management shortcut: $role you specified does not exist.");

			if(is_callable($func)){
				$matched = $func($params) && $matched;
				if(!$all && $matched) return true;
			}else{
				throw new Exception("The role based management shortcut: $role you specified within does not exist.");
			}
		}
		return $matched;
	}
}