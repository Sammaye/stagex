<?php

namespace glue;

use \glue\Exception;

class Auth extends \glue\Component{

	public $shortcuts;
	public $filters; 
	
	function init(){
		glue::registerEvents(array(
			'beforeAction' => 'beforeAction'
		),$this);
		parent::init();
	}

	function beforeAction($controller,$action){
		if(is_callable(array($controller, $action))){
			if($this->hasAccessRights($controller->accessRules(), $action)){
				return true;
			}else{
				glue::trigger('403');
				return false;
			}
		}else{
			throw new Exception('You defined a auth filter but you did not provide any rules with which to evaluate the access rights. Please provide an accessRules() function.');
		}
	}

	function getShortcut($name){
		return $this->getRole(isset($this->shortcuts[$name])?$this->shortcuts[$name]:null);
	}

	function getFilter($name=null){
		if(!$name)
			return null;
		else
			return $this->fitlers[$name];
	}

	/**
	 * If no actions array is defined then it is considered a global rule
	 *
	 * @param unknown_type $controllerPermissions
	 * @param unknown_type $action
	 */
	public function hasAccessRights($controllerPermissions, $action){

		foreach($controllerPermissions as $permission){

			$actions = isset($permission['actions']) && is_array($permission['actions']) ? $permission['actions'] : array();
			if((array_key_exists($action, array_flip($actions)) || count($actions) <= 0)){

				$users = isset($permission['users']) ? $permission['users'] : array('*');
				//var_dump($users);
				foreach($users as $role){
					//var_dump($role);
					$func = array_key_exists($role, $this->config['shortcuts']) ? $this->getShortcut($role) : $this->getRole($role);
					if(is_callable($func)){
						if($func()){
							//var_dump($permission);
							if($permission[0] == "allow"){
								//echo "here";
								return true;
							}elseif($permission[0] == "deny"){
								return false;
							}
						}
					}else{
						//var_dump($permission); exit();
						trigger_error("The role based management shortcut you specified within ".Glue::$action['controller']." does not exist.");
					}

					// No Hit. Only do on allow rules
					if($permission[0] == "allow" && (array_key_exists($action, array_flip($actions)) || count($actions) <= 0)){
						if(isset($permission['response']['redirect'])){
							if($permission['response']['flash'])
							//glue::setErrorFlashMessage(', $message)()->ERROR($permission['response']['flash']);

							header("Location: ".$permission['response']['redirect']);
							exit();
							return false;
						}else{
							$f_n = $permission['response'];
							$f_n();
							exit();
							return false;
						}
					}
				}
			}
		}
		return true;
	}

	public function checkRoles($roles, $all = false){
		$matched = true;
		foreach($roles as $role=>$params){

			if(is_int($role)){
				$role = $params;
				$params = null;
			}

			$func = array_key_exists($role, $this->config['shortcuts']) ? $this->getShortcut($role) : $this->getRole($role);
			if(is_callable($func)){
				$matched = $func($params) && $matched;
				if(!$all && $matched) return true;
			}else{
				trigger_error("The role based management shortcut you specified within ".Glue::$action['controller']." does not exist.");
			}
		}
		return $matched;
	}
}