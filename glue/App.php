<?php
setlocale(LC_ALL, 'en_GB.UTF8');

use \glue\Exception as Exception;

class glue{

	public static $name;
	public static $www;
	public static $root;

	public static $description;
	public static $keywords;

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

	private static $classMap=array();

	public static function __callStatic($name, $arguments){
		$config = self::config($name, "components");

		if(isset($config) && $config){ // If is still unset then go to error clause
			if(isset(self::$_components[$name])){
				return self::$_components[$name];
			}else{
				return self::$_components[$name] = self::createComponent($config);
			}
		}else{
			throw new Exception("The component (".$name.") could not be found");
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

		self::registerComponents(array(
			'auth' => Collection::mergeArray(array(
				'class' => '\\glue\\Auth'
			), self::conf('auth')),
			'session' => self::conf('session'),
			'http' => array(
				'class' => '\\glue\\Http'
			)
		));

		self::$session->start(); // I want a session to exist in

		if(php_sapi_name() == 'cli'){
			$args = self::http()->parseArgs($_SERVER['argv']);
			self::runCliAction();
		}else{
			self::route($url);
		}
	}

	/**
	 * Routes a url segment to a controller and displays that controller action
	 * @param string $route
	 */
	public static function route($route = null){
		self::trigger('beforeRequest');
		try{
			self::runAction($route /* Do not yet support params transposed here */);
		}catch(Exception $e){
			self::trigger('404');
		}
		self::trigger('afterRequest');
		exit(); // Finished rendering exit now
	}

	/**
	 * Runs an action according to a route
	 * @param string $route
	 * @param array $params
	 * @throws Exception If the controller could not be resolved.
	 */
	static function runAction($route,$params = array()){

		$controller = self::createController($route);
		if(is_array($controller)){
			list($controller,$action)=$controller;
			if(self::trigger('beforeAction')){
				self::$controller = $controller;
				$controller->runAction($route,$params);
			}
			self::trigger('afterAction');
		}else
			throw new Exception('Could not resolve the request: '.$route);
	}

	static function runCliAction(){
		if ($route === '') {
			$route = self::conf('defaultRoute', 'index');
		}
		if (($pos = strpos($route, '/')) !== false) {
			$id = substr($route, 0, $pos); // Lets get the first bit before the first /
			$route = substr($route, $pos + 1); // then lets get everything else
		} else {
			$id = $route;
			$route = '';
		}

		$controllerName = $id."Controller";
		$controllerFile = self::getBasePath() . ( self::config('controllerRoot')!==null ? self::config('controllerRoot') : 'controllers' ) .
								$controllerName . '.php';
		include($controllerFile);
	}

	/**
	 * Create a new controller
	 * @param string $route
	 */
	static function createController($route){

		if ($route === '') {
			$route = self::conf('defaultRoute', 'index');
		}
		if (($pos = strpos($route, '/')) !== false) {
			$id = substr($route, 0, $pos); // Lets get the first bit before the first /
			$route = substr($route, $pos + 1); // then lets get everything else
		} else {
			$id = $route;
			$route = '';
		}

		$controllerName = $id."Controller";
		$controllerFile = self::getBasePath() . ( self::config('controllerRoot')!==null ? self::config('controllerRoot') : 'controllers' ) .
								$controllerName . '.php';
		$className = ltrim(self::config('controllerNamespace') . '\\' . $controllerName, '\\');

		if(isset(self::$classMap[$className])){
			$controller = new self::$classMap[$className]['file'];
		}elseif(is_file($controllerFile)){

			// cache this response so we don't do it everytime
			self::$classMap[$className] = array(
				'class' => $className,
				'name' => $controllerName,
				'file' => $controllerFile
			);
			$controller = new $className;
		}

		return isset($controller) ? array($controller,$route) : false;
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

		// PSR-0 denotes that classes can be loaded with both \ and _ being translated to / (DIRECTORY_SEPARATOR)
		$file_name=str_replace(array('\\', '_'), DIRECTORY_SEPARATOR, ROOT.'\\'.$class).'.php';
		if(file_exists($file_name)){
			self::$_imported[$class] = $file_name;
			return include file_name;
		}

		// Go through each of the directories
		$file_name=str_replace('/', DIRECTORY_SEPARATOR, ROOT.'/'.$class.'.php');
		var_dump($file_name);
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
		spl_autoload_unregister(array('\glue\App','import'));
		if($callback) spl_autoload_register($callback);
		spl_autoload_register(array('\glue\App','import'));
	}

	/**
	 * Sets the current configuration values
	 *
	 * @param string $path
	 */
	public static function setConfig($conf){
var_dump($conf);
		if(is_string($conf))
			$config = self::import($conf);
		else
			$config=$conf;

		if(isset($config['extends'])){
			$parent_config = self::import($config['extends']);
			$config = self::mergeConfiguration($parent_config, $config);
		}

		self::$config = $config;

		self::$_components = array();
		self::$params=self::config('params',array());
var_dump(self::$config);
		foreach(self::config('preLoad',array()) as $k => $path)
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

	public static function trigger($event){

		$events = self::config($events, 'events');

		if($events!==null){



		}
		if(isset(self::$_events[$event])){
			foreach(self::$_events[$event] as $event => $f){
				//if($f)
				if($f instanceof Closure || is_function($f))
					$f();
			}
		}
	}

	public static function on($event, $callback){

	}

	public static function off($event){

	}

	public static function getRootPath(){

	}

	public static function setRootPath(){

	}

	public static function setControllerPath(){

	}

	public static function getControllerPath(){

	}

	public static function registerComponents($config){



		//self::$componentConfig
	}

	public static function unregisterComponents($){

	}

	/**
	 * Gets a top level configuration variable
	 *
	 * @param string $key
	 * @param string $section
	 */
	public static function config($path, $default=null){
		$path_parts = explode('::', $path);
		$last_part = self::$config;
		foreach($path_parts as $k){
			if(!isset($last_part[$k])){
				return $default;
			}
			$last_part = &$last_part[$k];
		}
		return $last_part;
	}
}

class Config extends \glue\Component{





}


class Exception extends \Exception{}