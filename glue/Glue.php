<?php
use \glue\Exception;
use \glue\Collection;

/**
 * Main glue framework class
 */
class Glue
{
	public static $debug = false;

	public static $name;
	public static $description;
	public static $keywords;

	public static $params = array();

	public static $www;
	
	public static $theme;

	public static $defaultController = 'index';
	public static $controllerName = '{name}Controller'; 
	public static $controllerNamespace;
	public static $actionPrefix = 'action_';

	public static $preload = array();

	public static $enableIncludePath = false;
	
	private static $_components = array(
		'user' => array(
			'class' => '\\app\\models\\User'
		),
		'session' => array(
			'class' => '\\glue\\Session'
		),
		'errorHandler' => array(
			'class' => '\\glue\\ErrorHandler'
		),
		'http' => array(
			'class' => '\\glue\\Http'
		)
	);
	private static $_events = array();

	private static $_aliases = array('glue' => __DIR__);
	private static $_classes = array();

	/**
	 * This is the magic to return components of the framework
	 * @param unknown_type $name
	 * @param unknown_type $arguments
	 */
	public static function __callStatic($name, $arguments)
	{
		return self::getComponent($name, $arguments);
	}

	/**
	 * Main Call Function
	 *
	 * @param string $url This is the url defined within the address bar which translates down to $_GET['url']
	 */
	public static function run($url = null, $config = array())
	{
		spl_autoload_register(array('glue','autoload'));
		//self::registerErrorHandlers();
		
		if(func_num_args() > 2){
			$args = func_get_args();
			unset($args[0]);
			$config = call_user_func_array(array('\glue\Collection', 'mergeArray'), $args);
		}
		
		// Setup the configuration
		if(is_array($config)){
			
			if(isset($config['events'])){
				foreach($config['events'] as $k =>$v){
					self::on($k,$v);
				}
				unset($config['events']);
			}
			
			if(isset($config['directories'])){
				self::setDirectories($config['directories']);
				unset($config['directories']);
			}
			
			if(isset($config['namespaces'])){
				self::setNamespaces($config['namespaces']);
				unset($config['namespaces']);
			}
			
			if(isset($config['aliases'])){
				self::setAliases($config['aliases']);
				unset($config['aliases']);
			}
			
			if(isset($config['timezone'])){
				date_default_timezone_set($config['timezone']);
				unset($config['timezone']);
			}
			
			if(isset($config['locale'])){
				if(is_array($config['locale'])){
					setlocale($config['locale'][0], $config['locale'][1]);
				}else{
					setlocale(LC_ALL, $config['locale']);
				}
				unset($config['locale']);
			}
			
			if(isset($config['components'])){
				self::setComponents($config['components']);
				unset($config['components']);
			}
			
			foreach($config as $k => $v){
				if(method_exists('glue','set'.$k)){
					$fn='set'.$k;
					self::$fn($v);
				}elseif(property_exists(get_called_class(), $k)){
					self::$$k=$v;
				}
			}
		}
		
		// Check the root path of the application
		if(self::getPath('@app') === null){
			throw new Exception('The "@app" directory within the "directories" configuration variable must be set.');
		}
		
		// Import the includes
		if(isset($config['include'])){
			foreach($config['include'] as $path){
				self::import($path, true);
			}
		}
		
		if(is_array(self::$preload)){
			foreach(self::$preload as $c){
				self::getComponent($c);
			}
		}		
		
		// Let's start processing the request
		if(php_sapi_name() == 'cli'){
			$args = self::http()->parseArgs($_SERVER['argv']);
			self::$www = '/cli';		
		}else{
			self::getComponent('session'); // force the user to be inited
		}
		self::route($url);
	}

	/**
	 * Routes a url segment to a controller and displays that controller action
	 * @param string $route
	 */
	public static function route($route = null, $runEvents = true)
	{
		self::trigger('beforeRequest');
		if(self::runAction($route) === false){
			self::trigger('404');
		}
		self::trigger('afterRequest');
		exit(0);
	}

	/**
	 * Runs an action according to a route
	 * @param string $route
	 * @param array $params
	 */
	public static function runAction($route, $params = array())
	{
		$controller = self::createController($route);
		if(!is_array($controller)){
			return false;
		}
		
		list($controller,$action) = $controller;

		if(!method_exists($controller,$action)){
			return false;
		}

		self::setComponents(array(
			'controller' => array(
				'class' => 'glue\\Controller',
				'__i_' => $controller
			)
		));

		self::controller()->run($action);
		return true;
	}

	/**
	 * Create a new controller
	 * @param string $route
	 */
	public static function createController($route)
	{
		if($route === ''||$route === null){
			$route = self::$defaultController;
		}
		if(($pos = strpos($route, '/')) !== false){
			$id = substr($route, 0, $pos); // Lets get the first bit before the first /
			$route = substr($route, $pos + 1); // then lets get everything else
		}else{
			$id = $route;
			$route = '';
		}

		$controllerName = preg_replace('/\{name\}/', $id, static::$controllerName);
		if(php_sapi_name() == 'cli'){
			$controllerFile = ( self::getPath('@cli') !== null ? 
				self::getPath('@cli') : 
				self::getPath('@app') . DIRECTORY_SEPARATOR . 'cli' 
			) . DIRECTORY_SEPARATOR . $controllerName . '.php';			
		}else{
			$controllerFile = ( self::getPath('@controllers') !== null ? 
				self::getPath('@controllers') :
				self::getPath('@app') . DIRECTORY_SEPARATOR . 'controllers' 
			) . DIRECTORY_SEPARATOR . $controllerName . '.php';
		}
		$className = ltrim(self::$controllerNamespace . '\\' . $controllerName, '\\');

		if(is_file($controllerFile)){
			include_once $controllerFile;
			$controller = new $className;

			if($route===''){
				$route=self::$actionPrefix.$controller->defaultAction;
			}else{
				$route=self::$actionPrefix.$route;
			}
		}
		return isset($controller) ? array($controller,$route) : false;
	}

	/**
	 * Registers our error handlers, which atm, cannot be unregistered :\
	 */
	private static function registerErrorHandlers(){
		ini_set('display_errors', 0);
		set_error_handler(array('glue','error'));
		set_exception_handler(array('glue','exception')); // Exceptions are costly beware!
		register_shutdown_function(array('glue','end'));
	}

	/**
	 * The bootstrap function to call the error handler for handling our exceptions
	 * @param Exception $exception
	 */
	public static function exception($exception){

		// disable error capturing to avoid recursive errors while handling exceptions
//		restore_error_handler();
//		restore_exception_handler();
		$e=self::getComponent('errorHandler');
		$e->handle($exception);
		exit(1);
	}

	/**
	 * The bootstrap function to call the error handler to handle both normal errors and
	 * fatal errors.
	 * @param int $code
	 * @param string $message
	 * @param string $file
	 * @param int $line
	 * @param boolean $fatal
	 */
	public static function error($code, $message, $file, $line, $fatal=false)
	{
		if (error_reporting() != 0){ /** Error has not been surpressed via an @ **/
			$e=self::getComponent('errorHandler');
			if($fatal===true) // $fatal can sometimes be the symbol table, it depends on what mood PHP is in
				$e->handleFatal($code,$message,$file,$line);
			else
				$e->handle($code,$message,$file,$line);
			exit(1);
		}
	}

	/**
	 * The end function.
	 *
	 * All functions in the framework will call this function when they force a closing of the thread via any function
	 * include exit();. This function will check tio see if the program exited due to an error and if not run the after request event
	 * and then exit for real.
	 * @param int $status
	 * @param boolean $exit
	 */
	public static function end($status=0,$exit=true)
	{
		//echo "in end";
//var_dump(error_get_last()); //exit();
		if ($error = error_get_last()){
			self::error($error['type'], $error['message'], $error['file'], $error['line'], true);
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
	public static function createComponent($config)
	{
		if(is_string($config)){
			$class = $config;
			$config = array();
		}elseif(isset($config['class'])){
			$class = $config['class'];
			unset($config['class']);
		}else{
			var_dump($config);
			throw new Exception('Component configuration must be an array containing a "class" element.');
		}

		$class = ltrim($class, '\\');
		return $config === array() ? new $class : new $class($config);
	}

	/**
	 * Gets a component
	 * @param string $name
	 * @param array $config
	 * @throws Exception
	 */
	public static function getComponent($name, $config=array())
	{
		if(!array_key_exists($name, self::$_components)){
			return null;
		}

		if($config !== array() || !isset(self::$_components[$name]['__i_'])){
			$cConfig = array_key_exists($name, self::$_components) ? self::$_components[$name] : array();
			
			if(isset($cConfig['__i_'])){
				unset($cConfig['__i_']);
			}
			
			$config = Collection::mergeArray($cConfig, $config);

			$config['__i_'] = self::createComponent($config);
			
			self::$_components[$name] = $config;
		}
		return self::$_components[$name]['__i_'];
	}

	/**
	 * Autoloads a file into the glue framework
	 * @param string $cName
	 * @param string $cPath
	 */
	public static function autoload($class, $return_cName = false)
	{ 
		$class = ltrim($class, '\\');

		if(isset(self::$_aliases[$class])){
			
			return class_alias(self::$_aliases[$class], $class);
			
		}elseif(isset(self::$_classes[strtolower($class)])){
			
			$fullPath = self::$_classes[strtolower($class)]['file'];
			if(is_file($fullPath)){
				$classFile=$fullPath;
			}
			
		}else{

			// follow PSR-0 to determine the class file
			if(($pos = strrpos($class, '\\')) !== false){
				$path = str_replace('\\', '/', substr($class, 0, $pos + 1))
				. str_replace('_', '/', substr($class, $pos + 1)) . '.php';
			}else{
				$path = str_replace('_', '/', $class) . '.php';
			}
			// var_dump($path);
			// try via path alias first
			if(($spos=strpos($path, '/')) !== false){
				$rootAlias = self::getPath(substr('@' . $path,0,$spos+1));

				// If an alias could not be gotten just see if this is a path from the @app root else we dunno what it is
				$fullPath = $rootAlias === null ? self::getPath('@app' . '/' . $path) : $rootAlias.substr($path,$spos);
				if($fullPath !== false && is_file($fullPath)){
					$classFile = $fullPath;
				}
			}

			// search include_path
			if(!isset($classFile) && self::$enableIncludePath && ($fullPath = stream_resolve_include_path($path)) !== false){
				$classFile = $fullPath;
			}
		}

		if(!isset($classFile)){
			// return false to let other autoloaders to try loading the class
			return false;
		}
		include $classFile;
	}

	/**
	 * This function imports a file/class into the application.
	 *
	 * By default it will not actually include it in the same breath but instead will just map
	 * the basename() to a place that can then be autoloaded, however, you can set it to $include and
	 * can even specify the $op (include,require,include_once,require_once) to be used when including it.
	 *
	 * This means you don't have to use the PHP functions and this one to do your work.
	 * @param string $path
	 * @param boolean $include
	 * @param string $op The include opeation to perform (include,include_once,require,require_once)
	 */
	public static function import($path, $include = false, $op = 'include')
	{
		$pos = strpos($path,'/');
		if($pos !== false){
			if (strncmp($path, '@', 1)){
				$path = '@'.$path;
			}
			$filePath = self::getPath($path);
		}else{
			$filePath = self::getPath('@glue/' . $path);
		}
		$className = strtolower(basename($path,'.php'));

		if(!isset(self::$_classes[$className])){
			self::$_classes[$className] = array(
				'class' => $className,
				'name' => $path,
				'file' => $filePath,
				'included' => false
			);
		}else{
			$filePath=self::$_classes[$className]['file'];
		}
		$classInfo = self::$_classes[$className];
		
		if(!$classInfo['included'] && $include){
			self::$_classes[$className]['included']=true;
			switch($op){
				case "include_once":
					return include_once $filePath;
					break;
				case "require":
					return require $filePath;
					break;
				case "require_once":
					return require_once $filePath;
					break;
				default:
					return include $filePath;
					break;
			}
		}
		return $className;
	}
	
	/**
	 * Translate a directory alias into a real path.
	 *
	 * As an example it will transalte @glue/app into path/to/framework/glue/app.
	 * If the alias path begins with / it will treat as a true path.
	 * If the alias path does not begin with / or @ then it will be treated as a relative path and the @app alias will
	 * be used to root the path.
	 * @param string $alias
	 */
	public static function getPath($alias)
	{
		// If the @ is not there lets add it
		if(strncmp($alias, '@', 1)){
			return $alias;
		}
		$alias = trim($alias, '@');
	
		$pos = strpos($alias, '/');
		$root = $pos === false ? $alias : substr($alias, 0, $pos); // Lets get the root alias of this path

		if(isset(self::$_aliases[$root])){
	
			$root=self::$_aliases[$root];
			if(strpos($root, '@') !== false){
				$root = self::getPath($root);
			}elseif(strncmp($root, '/', 1)){
				$root = self::getPath('@app'.'/'.trim($root, '\\/'));
			}
			
			return $pos === false ?
				rtrim($root, '\\/') :
				rtrim($root, '\\/') . DIRECTORY_SEPARATOR . trim(substr($alias, $pos), '\\/');			
		}
		return null;
	}

	/**
	 * Triggers a new event which will bubble to all attached event handlers
	 *
	 * @param string $event
	 * @param array $data Parameters to be bound to the event call
	 */
	public static function trigger($event, $data=array())
	{
		$event_success = true;
		//var_dump($event);
		if(is_array(self::$_events) && isset(self::$_events[$event])){
			foreach(self::$_events[$event] as $i => $f){
				if(is_array($f)){
					$event_success=call_user_func_array($f,$data)&&$event_success;
				}else{
					$event_success=$f()&&$event_success;
				}
			}
		}
		return $event_success;
	}

	/**
	 * Register a single event either from an anon function or model.
	 *
	 * If you are using this function from a model you will want to make the $callback an array of
	 * array($model,'someFunctionInModel') in order to register the event correctly.
	 *
	 * @param string $event
	 * @param closure|array $callback
	 */
	public static function on($event, $callback)
	{
		self::$_events[$event][] = $callback;
	}

	/**
	 * Takes off either a set of handlers from an event or an single handler from an event.
	 *
	 * If the $handler is null it will remove all handlers under that event otherwise it will
	 * seek that specific handler and attempt to remove it from the running of the event.
	 *
	 * @param string $event
	 * @param closure|array $handler
	 */
	public static function off($event,$handler=null)
	{
		if(isset(self::$_events[$name])){
			if($handler===null){
				self::$_events[$name] = array();
			}else{
				$removed=false;
				foreach(self::$_events[$name] as $i => $f){
					if($f===$handler){
						unset(self::$_events[$name][$i]);
						$removed=true;
						break; // If I have removed it, I don't need to carry on removing it
					}
				}

				if($removed){
					self::$_events[$name] = array_values(self::$_events[$name]);
				}
				return $removed;
			}
		}
		return false;
	}

	/**
	 * Sets namespaces within the configuration
	 * @param array $namespaces
	 */
	public static function setNamespaces($namespaces)
	{
		foreach($namespaces as $name => $path){
			$name = trim(strtr($name, array('\\' => '/', '_' => '/')), '/');
			self::$_aliases[$name] = rtrim($path, '/\\');
		}
	}	

	/**
	 * Registers a set of directories within the configuration
	 * @param array $directories
	 */
	public static function setDirectories($directories)
	{
		foreach($directories as $dir => $path){
			$dir = ltrim(rtrim($dir, '\\/'), '@');
			self::$_aliases[$dir] = $path;
		}
	}
	
	public static function setAliases($aliases)
	{
		foreach($aliases as $a => $path){
			self::$_aliases[$a] = $path;
		}
	}

	/**
	 * Registers a set of components within the configuration
	 * @param array $components
	 */
	public static function setComponents($components)
	{
		foreach($components as $id => $component){
			if(isset(self::$_components[$id]['class']) && !isset($component['class'])){
				$component['class'] = self::$_components[$id]['class'];
			}
			self::$_components[$id] = $component;
		}
	}
}