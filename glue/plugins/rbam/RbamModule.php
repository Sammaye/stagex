<?php
class RbamModule extends GApplicationComponent{

	public $defFile;
	public $config;

	function init(){
		$this->config = glue::import($this->defFile);
	}

	function beforeControllerAction($controller, $action){
		if(is_callable(array($action['controller'], 'accessRules'))){
			if($this->hasAccessRights($controller->accessRules(), $action['name'])){
				//echo "here";
				return true;
			}else{
				glue::route(glue::config("403", "errorPages"));
				return false;
			}
		}else{
			trigger_error('You defined a filter of RBAM but you did not provide any rules with which to evaluate the access rights. Please provide an accessRules() function.');
		}
	}

	function getShortcut($name){
		return $this->getRole($this->config['shortcuts'][$name]);
	}

	function getRole($name){
		return $this->config['roles'][$name];
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