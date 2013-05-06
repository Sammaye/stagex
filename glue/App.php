<?php
namespace glue;

setlocale(LC_ALL, 'en_GB.UTF8');

use \glue\Exception as Exception;

class App{

	public static $params = array();

	public static $action;
	
	public static $controller;
	public static $view;
	public static $http;
	public static $session;
	public static $auth;
	
	public static $config;

	private static $_events = array();
	
	private static $_namespaces = array();
	private static $_directories = array();
	private static $_aliases = array();
	
	private static $_components = array();
	private static $_imported = array();

	public static function __callStatic($name, $arguments){
		$config = self::config($name, "components");

		if(isset($config) && $config){ // If is still unset then go to error clause
			if(isset(self::$_components[$name])){
				return self::$_components[$name];
			}else{
				return self::$_components[$name] = self::createObject($config);
			}
		}else{
			throw new Exception("The component or variable or alias of a variable or plugin (".$name.") in the glue class could not be found");
		}
	}

	/**
	 * Main Call Function
	 *
	 * @param string $url This is the url defined within the address bar which translates down to $_GET['url']
	 */
	public static function run($url = null){

		self::registerAutoloader();

		set_error_handler("ErrorHandler");
		set_exception_handler("ErrorHandler"); // Exceptions are costly beware!
		register_shutdown_function('shutdown');

		if(php_sapi_name() == 'cli'){
			$args = self::http()->parseArgs($_SERVER['argv']);

			if(isset($args[0])):
				$file_name = ROOT.'/application/cli/'.$args[0];
				if(file_exists($file_name)): include $file_name; else: trigger_error("Could not find ".$args[0]." for cli"); endif;
			else:
				trigger_error("Could not find ".$args[0]." for cli");
			endif;
			exit(); // END further processing
		}else{
			self::session()->start(); // NO SESSION IN CLI
			$url = (empty($url)) || ($url == "/") ? 'index' : $url;
			self::route($url);
		}
	}

	/**
	 * Routes a url segment to a controller and displays that controller action
	 *
	 * @param string $route
	 */
	public static function route($route = null){
		if($route===null)
			throw new Exception("You cannot get no controller. You must supply a controller to get in the params.");

		$route = (empty($route)) || ($route == "/") ? 'index' : $route;

		/** Explode the url so we can analyse it */
		$urlParts = array_merge(array_filter(explode('/', $route)));

		/** Define the controller name as a variable to stop ambiquity within PHP */
		$controller_name = $urlParts[0]."Controller";

		/** Lets get the controller path ready. */
		$controllerFile = ROOT.'/application/controllers/'.$urlParts[0].'Controller.php';

		/** Lets see if an action is defined */
		$action = isset($urlParts[1]) && (string)$urlParts[1] ? $urlParts[1] : "";

		if(!isset($urlParts[1]))
			$action = 'index';

		/** Does the page exist? */
		if(!file_exists($controllerFile)){
			if(!file_exists(ROOT.'/application/controllers/'.ucfirst($urlParts[0]).'Controller.php')){
				self::trigger('404');
			}else{
				$controllerFile = ROOT.'/application/controllers/'.ucfirst($urlParts[0]).'Controller.php';
			}
		}

		/** So lets load the controller now that it exists */
		include_once $controllerFile;
		if(is_callable(array($controller_name, 'action_'.$action))){
			$action = 'action_'.$action;
		}else{
			self::trigger('404');
		}

		/** store info about the action */
		$controller = new $controller_name();

		$reflector = new ReflectionClass($controller_name);
		$method = $reflector->getMethod($action);
		self::$action = array('controller' => $method->class, 'name' => str_replace('action_', '', $method->name), 
								'actionID' => $method->name,  'params' => $method->getParameters());

		/** run the action */
		// Now run the filters for the controller
		$filters = is_array($controller->filters()) ? $controller->filters() : array();
		$runAction = true;
		foreach($filters as $k => $v){
			$runAction = glue::$v()->beforeControllerAction($controller, self::$action) && $runAction;
		}

		if($runAction)
			$controller->$action();

		foreach($filters as $k => $v){
			if(!is_numeric($k)){
				glue::$v()->afterControllerAction($controller, self::$action);
			}
		}

//		if(!self::http()->isAjax() && glue::config('DEBUG') === true){
//			$size = memory_get_peak_usage(true);
//			$unit=array('','KB','MB','GB','TB','PB');
//			echo '<div class="clearer"></div>';
//			var_dump($size/pow(1024,($i=floor(log($size,1024)))));
//		}
		exit(); // Finished rendering exit now
	}

	/**
	 * Imports a single file or directory into the app
	 *
	 * @param string $cName
	 * @param string $cPath
	 */
	public static function import($class, $return_cName = false){
		
		$class = ltrim($class, '\\');
		$pathinfo = pathinfo($class);
		
		if(isset(self::$_aliases[$class])){
			return class_alias(self::$_aliases[$class],$class);
		}
		
		if(isset($pathinfo['extension'])){
			// from the best that I can tell this is a normal old file
			return include str_replace('/', DIRECTORY_SEPARATOR, ROOT.'/'.$class);
		}		
		
		// PSR-0 denotes that classes can be loaded with both \ and _ being translated to / (DIRECTORY_SEPARATOR)
		$file_name=str_replace(array('\\', '_'), DIRECTORY_SEPARATOR, ROOT.'\\'.$class).'.php';
		if(file_exists($file_name)){
			self::$_imported[$class] = $file_name;
			return include file_name;				
		}

			// Go through each of the directories			
			$file_name=str_replace('/', DIRECTORY_SEPARATOR, ROOT.'/'.$class);
			if(file_exists($file_name)){
				self::$_imported[$pathinfo['filename']] = $file_name;
				return include file_name;
			}

			// Test if the file in one of the other directories
			foreach(self::$_directories as $directory){
				$file_name = str_replace('/', DIRECTORY_SEPARATOR, ROOT.'/'.$directory.'/'.$pathinfo['filename'].'.php');
				if(file_exists($file_name)){
					self::$_imported[$pathinfo['filename']] = $file_name;
					return include file_name;
				}
			}
		return false;
	}

	public static function registerAutoloader($callback = null){
		spl_autoload_unregister(array('App','import'));
		if($callback) spl_autoload_register($callback);
		spl_autoload_register(array('App','import'));
	}

	/**
	 * Sets the current configuration values
	 *
	 * @param string $path
	 */
	public static function setConfig($conf){
		
		if(is_string($conf))
			$config = self::import($conf);
		else
			$config=$conf;
		
		if(isset($config['extends'])){
			$parent_config = self::import($config['extends']);
			$config = self::mergeConfiguration($parent_config, $config);
		}
		
		self::$_components = array();
		self::$config = $config;		
		
		if(isset($config['params']) && is_array($config['params']))
			self::$params = $config['params'];	
		
		if(isset($config['aliases']) && is_array($config['aliases']))
			self::$_aliases = $config['aliases'];

		if(isset($config['namespaces']) && is_array($config['namespaces']))
			self::$_namespaces = $config['namespaces'];
		
		if(isset($config['directories']) && is_array($config['directories']))
			self::$_directories = $config['directories'];		

		foreach($config['preload'] as $k => $path)
			self::import($path);		
		

	}
	
	public static function mergeConfiguration(){
		if (func_num_args() < 2) {
			throw new Exception(__FUNCTION__ .' needs two or more array arguments');
			return;
		}
		$arrays = func_get_args();
		$merged = array();
		
		while ($arrays) {
			$array = array_shift($arrays);
			if (!is_array($array)) {
				throw new Exception(__FUNCTION__ .' encountered a non array argument');
				return;
			}
			if (!$array)
				continue;
			foreach ($array as $key => $value)
				if (is_string($key))
				if (is_array($value) && array_key_exists($key, $merged) && is_array($merged[$key]))
				$merged[$key] = call_user_func(__FUNCTION__, $merged[$key], $value);
			else
				$merged[$key] = $value;
			else
				$merged[] = $value;
		}
		return $merged;		
	}
	
	/**
	 * Creates and initialises a Glue component and returns it.
	 * @param unknown_type $config
	 * @throws Exception
	 */
	public static function createComponent($config){

		if (is_string($config)) {
			$class = $config;
			$config = array();
		} elseif (isset($config['class'])) {
			$class = $config['class'];
			unset($config['class']);
		} else {
			throw new Exception('Object configuration must be an array containing a "class" element.');
		}

		if (!class_exists($class, false)) {
			$class = self::import($class);
		}

		$class = ltrim($class, '\\');

		if (self::config($class,'components')!==null) {
			$config = array_merge(self::config($class,'components'), $config);
		}
		return $config === array() ? new $class : new $class($config);
	}
	
	public static function trigger(){
		
	}
	
	public static function bindEvent($event, $callback){
		
	}
	
	public static function unbindEvent($event){
		
	} 

	/**
	 * Gets a top level configuration variable
	 *
	 * @param string $key
	 * @param string $section
	 */
	public static function config($key = null, $section = null){
		if(!$key && !$section){
			return self::$config;
		}elseif($key && !$section){
			return isset(self::$config[$key]) ? self::$config[$key] : null;
		}elseif($key && $section){
			return isset(self::$config[$section][$key]) ? self::$config[$section][$key] : null;
		}
	}
}

class Exception extends Exception{}