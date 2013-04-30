<?php
//use app\User;
setlocale(LC_ALL, 'en_GB.UTF8');

class Glue{

	public static $params = array();

	public static $action;

	/**
	 * Not all of these classes are loaded! Merely mapped.
	 */
	private static $_classMapper = array(
		"GClientScript"			=> "glue/core/GClientScript.php",
		"GCommon" 				=> "glue/core/GCommon.php",
		"GCommandLine"			=> "glue/core/GCommandLine.php",
		"GController"			=> "glue/core/GController.php",
		"GErrorHandler" 		=> "glue/core/GErrorHandler.php",
		"GListProvider"			=> "glue/core/GListProvider.php",
		"GModel"				=> "glue/core/GModel.php",
		"GModelBehaviour"		=> "glue/core/GModelBehaviour.php",
		"GValidators"			=> "glue/core/GValidators.php",
		"GValidationComponent"	=> "glue/core/GValidationComponent.php",
		"GUrlManager"			=> "glue/core/GUrlManager.php",
		"GHttp"					=> "glue/core/GHttp.php",
		"GCrypt"				=> "glue/core/GCrypt.php",
		"GWidget" 				=> "glue/core/GWidget.php",
		"GApplicationComponent" => 'glue/core/GApplicationComponent.php',
		"GJSON" 				=> 'glue/core/GJSON.php',
		"html"					=> "glue/core/html.php",

		// Core addons
		"JSMin" 				=> "glue/core/util/JSMin.php"
	);

	private static $_components = array();
	private static $_componentLoaded = array();
	private static $_classLoaded = array();
	private static $_configVars;
	private static $_GRBAM;
	private static $_error;

	private static $_http;
	private static $_user;
	private static $_clientScript;
	private static $_url;

	public static function __callStatic($name, $arguments){
		$compConfig = self::config($name, "components");

		if(!isset($compConfig) && !$compConfig){ // Then lets try the alias
			$name = self::config($name, "alias");
			$compConfig = self::config($name, "components");
		}

		if(isset($compConfig) && $compConfig){ // If is still unset then go to error clause
			if(isset(self::$_components[$name])){
				return self::$_components[$name];
			}else{
				self::import($compConfig['path']);
				$o = new $compConfig['class'];
				unset($compConfig['class']);
				unset($compConfig['path']);

				foreach($compConfig as $k => $v){
					$o->$k = $v;
				}
				$o->init();
				return self::$_components[$name] = $o;
			}
		}else{
			trigger_error("The component or variable or alias of a variable or plugin (".$name.") in the glue class could not be found");
		}
	}

	/**
	 * Main Call Function
	 *
	 * @param string $url This is the url defined within the address bar which translates down to $_GET['url']
	 */
	public static function run($url = null){

		self::registerAutoloader();
		self::import("GErrorHandler");
		self::import("GCommon");

		set_error_handler("GErrorHandler");
		set_exception_handler("GErrorHandler"); // Exceptions are costly beware!
		register_shutdown_function('shutdown');

		//PRELOAD
		foreach(self::config('preload') as $k => $path){
			self::import($path);
		}

		foreach(self::config('params') as $k => $v){
			self::$params[$k] = $v;
		}

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
		if(!$route){
			trigger_error("You cannot get no controller. You must supply a controller to get in the params.", E_USER_ERROR);
			exit();
		}

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
				self::route(self::config("404", "errorPages"));
			}else{
				$controllerFile = ROOT.'/application/controllers/'.ucfirst($urlParts[0]).'Controller.php';
			}
		}

		/** So lets load the controller now that it exists */
		include_once $controllerFile;
		if(is_callable(array($controller_name, 'action_'.$action))){
			$action = 'action_'.$action;
		}else{
			self::route(self::config("404", "errorPages"));
		}

		/** run the action */
		$controller = new $controller_name();

		$reflector = new ReflectionClass($controller_name);
		$method = $reflector->getMethod($action);
		self::$action = array('controller' => $method->class, 'name' => str_replace('action_', '', $method->name), 'actionID' => $method->name,  'params' => $method->getParameters());

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
	public static function import($path = null, $return_cName = false){

		if(substr($path, -2) == "/*"){

			$d_name = str_replace('/', DIRECTORY_SEPARATOR, ROOT.'/'.substr($path, 0, -2));
			$d_files = getDirectoryFileList($d_name, array("\.php")); // Currently only accepts .php

			foreach($d_files as $file){
				self::$_classMapper[pathinfo($file, PATHINFO_FILENAME)] = substr($path, 0, -2).'/'.$file;
			}
		}else{

			$pathinfo = pathinfo($path);

			if(!isset(self::$_classLoaded[$pathinfo['filename']])){
				if(isset(self::$_classMapper[$pathinfo['filename']])){
					$path = self::$_classMapper[$pathinfo['filename']];
					$pathinfo = pathinfo(self::$_classMapper[$pathinfo['filename']]);
				}

				$filepath = str_replace('/', DIRECTORY_SEPARATOR, ROOT.'/'.$path);
				if(file_exists($filepath)){
					self::$_classLoaded[$pathinfo['filename']] = true;
					if($return_cName){
						include $filepath;
						return $pathinfo['filename'];
					}else{
						return include $filepath;
					}
				}
			}
		}
	}

	public static function registerAutoloader($callback = null){
		spl_autoload_unregister(array('Glue','import'));
		if($callback) spl_autoload_register($callback);
		spl_autoload_register(array('Glue','import'));
	}

	/**
	 * Sets the current configuration values
	 *
	 * @param string $path
	 */
	public static function setConfigFile($path){

		glue::import('GCommon');

		$config = self::import($path);
		if(isset($config['extends'])){
			$parent_config = self::import($config['extends']);
			$config = farray_merge_recursive($parent_config, $config);
		}
		self::$_configVars = $config;
	}

	/**
	 * Gets a top level configuration variable
	 *
	 * @param string $key
	 * @param string $section
	 */
	public static function config($key = null, $section = null){
		if(!$key && !$section){
			return self::$_configVars;
		}elseif($key && !$section){
			return isset(self::$_configVars[$key]) ? self::$_configVars[$key] : null;
		}elseif($key && $section){
			return isset(self::$_configVars[$section][$key]) ? self::$_configVars[$section][$key] : null;
		}
	}

	public static function user(){
		if(self::$_user === null) self::$_user = new User();
		return self::$_user;
	}

	/**
	 * Returns the url manager for the app
	 */
	public static function url(){
		if(self::$_url === null) self::$_url = new GUrlManager();
		return self::$_url;
	}

	public static function http(){
		if(self::$_http === null) self::$_http = new GHttp();
		return self::$_http;
	}

	/**
	 * Returns the client script object for the app
	 */
	public static function clientScript(){
		if(self::$_clientScript === null) self::$_clientScript = new GClientScript();
		return self::$_clientScript;
	}

	/**
	 * Starts a widget but does not run the render() function
	 *
	 * @param string $path
	 * @param array $args
	 */
	public static function beginWidget($path, $args = null){
		$pieces = explode("/", $path);
		$cName = substr($pieces[sizeof($pieces)-1], 0, strrpos($pieces[sizeof($pieces)-1], "."));
		Glue::import($path);
		$widget = new $cName();
		$widget->attributes($args);
		$widget->init();
		return $widget;
	}

	/**
	 * Starts a widget and runs the render() function of a widget
	 *
	 * @param string $path
	 * @param array $params
	 */
	public static function widget($path, $params = null){
		$widget = self::beginWidget($path, $params);
		return $widget->render();
	}
}