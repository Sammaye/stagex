<?php
setlocale(LC_ALL, 'en_GB.UTF8');

//use \glue\Exception as Exception,
//	\glue\Collection;

class glue{

	public static $DEBUG=false;

	public static $name;
	public static $description;
	public static $keywords;

	public static $params = array();

	public static $www;
	public static $defaultController='index';
	public static $controllerNamespace;

	public static $actionPrefix='action_';

	private static $startUp = array();

	private static $components = array();
	private static $events = array();

	private static $include=array();

	private static $namespaces = array();
	private static $directories = array();
	private static $aliases = array();

	public static $controller;

	private static $_components = array();
	private static $_imported = array();

	private static $classMap=array();

	/**
	 * This is the magic to return components of the framework
	 * @param unknown_type $name
	 * @param unknown_type $arguments
	 */
	public static function __callStatic($name, $arguments){
		return self::getComponent($name, $arguments);
	}

	/**
	 * Main Call Function
	 *
	 * @param string $url This is the url defined within the address bar which translates down to $_GET['url']
	 */
	public static function run($url = null,$config=array()){

		if(isset($config['events'])&&is_array($config['events'])){
			foreach($config['events'] as $k => $v){
				self::on($k,$v);
			}
		}

		if(isset($config['errors'])&&is_array($config['errors'])){
			$errorHandler=$config['errors'];
		}

		unset($config['events'],$config['errors']);
		if(is_array($config)){
			foreach($config as $k => $v)
				self::$$k=$v;
		}
		if(self::getDirectory('@app')===null)
			throw new Exception('The "@app" directory within the "directories" configuration variable must be set.');

		self::registerAutoloader();
		self::registerErrorHandlers();

		// Register the core components
		self::registerComponents(array(
			'auth' => Collection::mergeArray(array(
				'class' => '\\glue\\Auth'
			), isset(self::$components['auth']) && is_array(self::$components['auth'])?self::$components['auth']:array()),
			'user' => Collection::mergeArray(array(
				'class' => '\\glue\\User'
			),isset(self::$components['user'])&&is_array(self::$components['user'])?self::$components['user']:array()),
			'errorHandler' => Collection::mergeArray(array(
				'class' => '\\glue\\ErrorHandler',
			),isset($errorHandler)?$errorHandler:array()),
			'http' => array(
				'class' => '\\glue\\Http'
			)
		));

		// Add the alias for the the framework root
		self::setDirectory('@glue', __DIR__);

		if(php_sapi_name() == 'cli'){
			$args = self::http()->parseArgs($_SERVER['argv']);
			self::$www='/cli';

			self::runCliAction();
		}else{

			// There is no session in CLI...
			self::registerComponents(array(
				'session' => isset(self::$components['session'])&&is_array(self::$components['session'])?self::$components['session']:array()
			));
			self::$www = self::$www?:self::http()->baseUrl();

			// since there is no controller as such for CLI atm lets not run the startUp stuff on cli actions
			// So after that lets touch all startup items and get them to run their contructors and init functions
			if(is_array(self::$startUp)){
				foreach(self::$startUp as $c)
					self::getComponent($c);
			}
			//self::getComponent('user'); // force the user to be inited
//exit();
			self::route($url);
		}
	}

	/**
	 * Routes a url segment to a controller and displays that controller action
	 * @param string $route
	 */
	public static function route($route = null, $runEvents=true){
		self::trigger('beforeRequest');
		if(self::runAction($route /* Do not yet support params transposed here */) === false)
			self::trigger('404');
		exit(0);
	}

	/**
	 * Runs an action according to a route
	 * @param string $route
	 * @param array $params
	 * @throws Exception If the controller could not be resolved.
	 */
	static function runAction($route,$params = array()){

		$controller = self::createController($route);
		//var_dump($controller);
		if(is_array($controller)){
			list($controller,$action)=$controller;

			if(!is_callable(array($controller,$action)))
				return false;

			if(self::trigger('beforeAction',array($controller,$action))){
				self::$controller = $controller;
				echo "running action now";
				call_user_func_array(array($controller,$action),$params);
				//$controller->runAction($route,$params);
			}
			self::trigger('afterAction',array($controller,$action));
		}else
			return false;
	}

	/**
	 * Runs a CLI script, may be redone in the future to make CLI run like controllers
	 * @throws Exception
	 */
	static function runCliAction(){
		if ($route === '') {
			throw new Exception('You must provide a cli file to run');
		}
		if (($pos = strpos($route, '/')) !== false) {
			$id = substr($route, 0, $pos); // Lets get the first bit before the first /
			$route = substr($route, $pos + 1); // then lets get everything else
		} else {
			$id = $route;
			$route = '';
		}

		$controllerName = $id;
		$controllerFile = self::getDirectory('@app') . DIRECTORY_SEPARATOR . ( self::getDirectory('@cli')!==null ? self::getDirectory('@cli') : 'cli' ) . DIRECTORY_SEPARATOR .
								$controllerName . '.php';
		include($controllerFile);
	}

	/**
	 * Create a new controller
	 * @param string $route
	 */
	static function createController($route){
var_dump($route);
		if ($route === ''||$route === null) {
			$route = self::$defaultController;
		}
		if (($pos = strpos($route, '/')) !== false) {
			$id = substr($route, 0, $pos); // Lets get the first bit before the first /
			$route = substr($route, $pos + 1); // then lets get everything else
		} else {
			$id = $route;
			$route = '';
		}

		$controllerName = $id."Controller";
		var_dump($controllerName);
		$controllerFile = self::getDirectory('@app') . DIRECTORY_SEPARATOR . ( self::getDirectory('@controllers')!==null ? self::getDirectory('@controllers') : 'controllers' ) .
								DIRECTORY_SEPARATOR . $controllerName . '.php';
		var_dump($controllerFile);
		$className = ltrim(self::$controllerNamespace . '\\' . $controllerName, '\\');

		if(isset(self::$classMap[$className])){
			$controller = new self::$classMap[$className]['file'];
		}elseif(is_file($controllerFile)){

			// cache this response so we don't do it everytime
			self::$classMap[$className] = array(
				'class' => $className,
				'name' => $controllerName,
				'file' => $controllerFile
			);
			include($controllerFile);
			$controller = new $className;
		}

		if($route==='')
			$route=self::$actionPrefix.$controller->defaultAction;
		else
			$route=self::$actionPrefix.$route;

		return isset($controller) ? array($controller,$route) : false;
	}

	static function registerErrorHandlers(){
		set_error_handler(array('glue','handleError'));
		set_exception_handler(array('glue','handleException')); // Exceptions are costly beware!
		register_shutdown_function(array('glue','end'));
	}

	static function handleException($exception){

		// disable error capturing to avoid recursive errors while handling exceptions
		restore_error_handler();
		restore_exception_handler();

		$e=self::getComponent('errorHandler');
		$e->handle($exception);
		exit(1);
	}

	static function handleError($code, $message, $file, $line, $fatal=false){
		echo "hretjfdgljnfd";
		$e=self::getComponent('errorHandler');
//		var_dump($fatal);
		if($fatal===true) // $fatal can sometimes be the symbol table, it depends on what mood PHP is in
			$e->handleFatal($code,$message,$file,$line);
		else
			$e->handle($code,$message,$file,$line);
		exit(1);
	}

	static function end($status=0,$exit=true){
		echo "in end";
var_dump(error_get_last()); //exit();
		if ($error = error_get_last()){
			self::handleError($error['type'], $error['message'], $error['file'], $error['line'], true);
		}else if ($status<1){ // If there was no error
			self::trigger('afterRequest');
		}

		if($exit)
			exit($status);
	}

	/**
	 * Creates and initialises a Glue component and returns it.
	 * @param unknown_type $config
	 * @throws Exception
	 */
	public static function createComponent($config){

//var_dump($config);
		if (is_string($config)) {
			$class = $config;
			$config = array();
		} elseif (isset($config['class'])) {
			$class = $config['class'];
			unset($config['class']);
		} else {
			throw new Exception('Component configuration must be an array containing a "class" element.');
		}

		//if (!class_exists($class, false)) {
			//$class = self::import($class);
		//}

		$class = ltrim($class, '\\');
		if (isset(self::$components[$class]) && self::$components[$class]!==null) {
			$config = array_merge(self::$components[$class], $config);
		}
		return $config === array() ? new $class : new $class($config);
	}

	/**
	 * Gets a component
	 * @param string $name
	 * @param array $config
	 * @throws Exception
	 */
	public static function getComponent($name, $config=array()){
		//echo "fkdefjkdsf;fds";
		$config = Collection::mergeArray(isset(self::$components[$name])&&is_array(self::$components[$name])?self::$components[$name]:array(), $config);
//var_dump($config);
		if(!empty($config) && $config){ // If is still unset then go to error clause
			if(isset(self::$_components[$name])&&self::$_components[$name]===null){
				return self::$_components[$name];
			}else{
				return self::$_components[$name] = self::createComponent($config);
			}
		}else{
			throw new Exception("The component (".$name.") could not be found");
		}
	}

	/**
	 * Imports a single file or directory into the app
	 *
	 * @param string $cName
	 * @param string $cPath
	 */
	public static function autoload($class, $return_cName = false){

		$class = ltrim($class, '\\');
		$pathinfo = pathinfo($class);
		//var_dump($class);
//var_dump(self::$aliases);
		if(isset(self::$aliases[$class])){
			//echo "infff";
			return class_alias(self::$aliases[$class],$class);
		}

		// PSR-0 denotes that classes can be loaded with both \ and _ being translated to / (DIRECTORY_SEPARATOR)
		$file_name=self::getDirectory('@app').str_replace(array('\\', '_'), DIRECTORY_SEPARATOR, '\\'.$class).'.php';
		if(file_exists($file_name)){
			return include $file_name;
		}
//		var_dump($file_name);
//		exit();
		foreach(self::$namespaces as $n=>$p){
			if(!strncmp($class, $n, strlen($n))){
				$file_name=str_replace(array('\\', '_'), DIRECTORY_SEPARATOR, self::getDirectory('@app').'\\'.str_replace($n,$p.'\\', $class)).'.php';
				if(file_exists($file_name)){
					return include $file_name;
				}
			}
		}
		return false;
	}

	public static function import($path){

		$path = ltrim($path, '\\');

		if(strpos('\\', $path)!==false){
			// Import via PSR-0 notation
			$file_name=str_replace(array('\\', '_'), DIRECTORY_SEPARATOR, self::getDirectory('@app').'\\'.$class).'.php';
			if(file_exists($file_name)){
				self::$_imported[$class] = $file_name;
				return include file_name;
			}
		}else{

			if(strpos('@',$path)===1){

				// Then this is aliasing a directory
				$dir=substr($path,0,strpos('/',$path));
				$realPath = self::getDirectory('@root') . DIRECTORY_SEPARATOR . self::getDirectory($dir) . DIRECTORY_SEPARATOR .
					str_replace('/',DIRECTORY_SEPARATOR,substr($path,strpos('/',$path)));
				if(file_exists($realPath)){
					self::$_imported[$path] = $realPath;
					return include $realPath;
				}
			}else{

			}

		}

	}

	public static function registerAutoloader($callback = null){
		spl_autoload_unregister(array('glue','autoload'));
		if($callback) spl_autoload_register($callback);
		spl_autoload_register(array('glue','autoload'));
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

	public static function trigger($event, $data=array()){

		$event_success = true;
		var_dump($event);
		if(is_array(self::$events) && isset(self::$events[$event])){
			foreach(self::$events[$event] as $i => $f){
				if(is_array($f)){
					$event_success=call_user_func_array($f,$data)&&$event_success;
				}else{
					$event_success=$f()&&$event_success;
				}
			}
		}
		return $event_success;
	}

	public static function on($event, $callback){
		//echo "bdinging";
		self::$events[$event][] = $callback;
	}

	public static function off($event,$handler=null){

		if(isset(self::$events[$name])){
			if($handler===null){
				self::$events[$name] = array();
			}else{
				$removed=false;
				foreach(self::$events[$name] as $i => $f){
					if($f===$handler){
						unset(self::$events[$name][$i]);
						$removed=true;
						break; // If I have removed it I don't need to carry on removing it
					}
				}

				if($removed)
					self::$events[$name] = array_values(self::$events[$name]);
				return $removed;
			}
		}
		return false;
	}

	public static function registerEvents($events,$model=null){
		foreach($events as $k =>$v){
			if($model)
				self::on($k,array($model,$v));
			else
				self::on($k,$v);
		}
		return true;
	}

	public static function getDirectory($alias){
		return isset(self::$directories[$alias]) ? self::$directories[$alias] : null;
	}

	public static function setDirectory($alias, $path){
		$alias = strpos('@',$alias)===1?$alias:'@'.$alias;
		self::$directories[$alias]=$path;
	}

	public static function registerComponents($components){
		self::$components = array_merge(self::$components,$components);
	}

	public static function unregisterComponent($component){
		unset(self::$components[$component]);
	}
}