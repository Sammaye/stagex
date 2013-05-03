<?php

namespace \mongoglue;

/**
 * This object represents a server for MongoDB. It can huose a connection pool allowing it to be reused for multiple servers
 * by simply switching the connections.
 *
 * The default scope for none explicitly defined variables and functions is public
 *
 * @example $mongo = new mongoglue\Server(new MongoClient(), array('DocumentDir' => __DIRECTORY__.'/Documents'));
 */
class Server{

	/**
	 * Default safe mode unless stated otherwise.
	 * @var int|string
	 */
	public $writeConcern = 1;

	/**
	 * Default value for journal writes
	 * @var boolean
	 */
	public $journaled = false;

	/**
	 * The active MongoClient object representing a connection to a server
	 *
	 * @var MongoClient
	 */
	private $connection;

	/**
	 * An array of cached MongoClient connections
	 *
	 * @var array
	 */
	private $connectionList = array();

	/**
	 * The namespace separator
	 *
	 * @var string
	 */
	private $namespaceSeparator = '\\';

	/**
	 * The default file extension for all files you make
	 *
	 * @var string
	 */
	private $fileExtension = '.php';

	/**
	 * This holds object cache of fields for model and reflection results
	 * so we don't have to go get it everytime and waste resources
	 * @var array
	 */
	private $objCache;

	/**
	 * This will select a database automatically
	 */
	function __get($k){
		return $this->selectDB($k);
	}

	/**
	 * Run the setup stuff that needs to be run
	 *
	 * @param MongoClient $connection
	 * @param array $options
	 */
	function __construct($connection, $options = array()){

		$this->addConnection('default', $connection);
		$this->setConnection('default');

		foreach($options as $k=>$v){
			$this->$k = $v;
		}

		// We take the folder above for our runtime root since all calls will be mongoglue\server etc.
		$this->includeRoot = dirname(dirname(__FILE__));
		$this->registerAutoloader();
	}

	/**
	 * This method will call stuff on the current active connection (MongoClient) so
	 * as to create a transparency between the Server and it connection. I am still unsure
	 * about this so best not to rely on it
	 *
	 * @param string $method
	 * @param mixed $params
	 */
	function __call($method, $params = array()){
		if(method_exists($this->connection, $method)){
			return call_user_func_array(array($this->connection, $method), $params);
		}
	}

	/**
	 * Gets the server name
	 *
	 * @return string $name
	 */
	function getName(){
		return $this->name;
	}

	/**
	 * Gets the connection pool
	 *
	 * @return array of MongoClient
	 */
	function getConnectionList(){
		return $this->connectionList;
	}

	/**
	 * This will select a database and return it
	 *
	 * @param string $db
	 * @return \mongoglue\mongoglue\Database
	 */
	function selectDB($db){
		return new \mongoglue\Database($db, $this);
	}

	/**
	 * This function would normally allow you to select a collection from a database on the server level
	 * however I have deprecated this function since I see it as a confusion of roles within the driver.
	 * You can get this more easily by just using the __get down to the database class.
	 *
	 * @deprecated
	 * @param string $db
	 * @param string $collection
	 */
	function selectCollection($db, $collection){
		trigger_error('Please use the database class for this by doing a __get() or selectDB() on this class', E_DEPRECATED);
	}

	/**
	 * This adds a possible connection to the pool for use
	 *
	 * @param string|int $k
	 * @param MongoClient $connection
	 *
	 * @return int|string $k either the input or the output of adding it to the connectionList
	 */
	function addConnection($k = false /* I want to add ability to not have to use $k */, $connection){
		if(!$k){

			if(version_compare(phpversion('mongo'), '1.3.0', '<')){

				// If this is previous version make sure we are connected
				$connection->connect();
			}

			$this->connectionList[] = $connection;
			return key($this->connectionList);
		}else{
			$this->connectionList[$k] = $connection;
			return $k;
		}
	}

	/**
	 * Returns the active connection
	 *
	 * @return \mongoglue\MongoClient
	 */
	function connection(){
		return $this->connection;
	}

	/**
	 * This sets the active connection to via either a MongoClient object or name of a connection
	 *
	 * @param MongoClient|string|int $connection
	 */
	function setConnection($connection){
		if($connection instanceof \MongoClient || $connection instanceof \Mongo){
			$this->name = '';
			$this->connection = $connection;
			return true;
		}elseif(isset($this->connectionList[$connection])){
			$this->name = $connection;
			$this->connection = $this->connectionList[$connection];
			return true;
		}
		return false;
	}

	/**
	 * This removes a connection from the pool and if it is the active connection it will disconnect
	 *
	 * @param string|MongoClient $k
	 */
	function removeConnection($k){

		if($k instanceof \MongoClient || $k instanceof \Mongo){

			foreach($this->connectionList as $k => $connection){
				if($k===$connection) unset($this->connectionList[$k]);
			}

			if($this->connection===$k){
				$this->name = '';
				$this->connection = null;
			}

			return true;

		}elseif(isset($this->connectionList[$k])){
			$connection = $this->connectionList[$k];
			$connection->close();

			if($this->name == $k){
				$this->name = '';
				$this->connection = null;
			}

			unset($this->connectionList[$k]);

			return true;
		}
		return false; // No remove needed
	}

	/**
	 * Registers our Autoloader
	 */
	function registerAutoloader(){
		spl_autoload_register(array($this, 'loadClass'));
	}

	/**
	 * Unregisters the autoloader, useful for when you need to put something before it
	 */
	function unregisterAutoloader(){
		spl_autoload_unregister(array($this, 'loadClass'));
	}

	/**
	 * Our actual autoloader
	 *
	 * @param string $cname
	 * @param string $type
	 * @return boolean or the loaded file contents
	 */
	function loadClass($cname, $type = null){

		// What I need to do is resolve the namespace of the class signature to understand what I am loading
		// unless the type is set in which case I kow exactly what I am doing
		if($filename = $this->canLoadClass($cname, $type)){
			return require_once $filename;
		}else{
			//trigger_error("The file {$cname} does not exist within mongoglue");
			return false;
		}
	}

	/**
	 * Checks to see if the file we want to load really exists
	 *
	 * @param string $cname
	 * @param string $type
	 * @return boolean|string
	 */
	function canLoadClass($cname, $type = null){

		if(strlen(trim($cname)) <= 0)
			return false;

		// If there is any trailing \ lets get rid of them
		$cname = trim($cname, '\\');

		// If this is looking for a core file then let it, otherwise do not
		if(preg_match('/^mongoglue/', $cname) > 0){
			$resolved_namespace = str_replace($this->namespaceSeparator, DIRECTORY_SEPARATOR, $cname);
			$resolved_filename = ( $this->includeRoot !== null ? $this->includeRoot . DIRECTORY_SEPARATOR : '' ) .
			$resolved_namespace . $this->fileExtension;

			if(file_exists($resolved_filename)){
				return $resolved_filename;
			}
		}

		// Then I am dealing with most likely a document, if not then I dunno what you are
		$resolved_filename = ( $this->documentDir !== null ? $this->documentDir . DIRECTORY_SEPARATOR : '' ) .
		$cname . $this->fileExtension;

		if(file_exists($resolved_filename)){
			return $resolved_filename;
		}

		return false;
	}

	/**
	 * Basically returns a document class name with it's namespace so we can load it in other places.
	 * @param string $cname
	 * @return string
	 */
	function resolveDocumentClassName($cname){
		return $this->documentns ? $this->documentns . $this->namespaceSeparator . $cname : $cname;
	}

	/**
	 * Provides a method by which to set some sort of cache for a Document to
	 * remember things such as reflection of fields
	 * @param string $name
	 * @param array $virtualFields
	 * @param array $documentFields
	 */
	function setObjectCache($name, $virtualFields = null, $documentFields = null){

		if($virtualFields)
			$this->objCache[$name]['virtual'] = $virtualFields;

		if($documentFields)
			$this->objCache[$name]['document'] = $documentFields;
	}

	/**
	 * Gets the virtual fields of a Document from cache
	 * @param string $name
	 * @return NULL|array
	 */
	function getVirtualObjCache($name){
		return isset($this->objCache[$name], $this->objCache[$name]['virtual']) ? $this->objCache[$name]['virtual'] : null;
	}

	/**
	 * Gets the field of a Document from cache
	 * @param string $name
	 * @return NULL|array
	 */
	function getFieldObjCache($name){
		return isset($this->objCache[$name], $this->objCache[$name]['document']) ? $this->objCache[$name]['document'] : null;
	}

	/**
	 * Just gets the object cache for a Document
	 * @param string $name
	 * @return NULL|array
	 */
	function getObjCache($name){
		return isset($this->objCache[$name]) ? $this->objCache[$name] : null;
	}

	/**
	 * This recursively merges two arrays and returns the result
	 * @return void|array
	 */
	function merge(){

		if (func_num_args() < 2) {
			trigger_error(__FUNCTION__ .' needs two or more array arguments', E_USER_WARNING);
			return;
		}
		$arrays = func_get_args();
		$merged = array();

		while ($arrays) {
			$array = array_shift($arrays);
			if (!is_array($array)) {
				trigger_error(__FUNCTION__ .' encountered a non array argument', E_USER_WARNING);
				return;
			}
			if (!$array)
				continue;
			foreach ($array as $key => $value)
				if (is_string($key))
				if (is_array($value) && array_key_exists($key, $merged) && is_array($merged[$key]))
				$merged[$key] = call_user_func(array($this,__FUNCTION__), $merged[$key], $value);
			else
				$merged[$key] = $value;
			else
				$merged[] = $value;
		}
		return $merged;
	}

	/**
	 * Gets the default write concern options for all queries through active record
	 * @return array
	 */
	function getDefaultWriteConcern(){
		if(version_compare(phpversion('mongo'), '1.3.0', '<')){
			if((bool)$this->writeConcern){
				return array('safe' => true);
			}
		}else{
			return array('w' => $this->writeConern, 'j' => $this->journaled);
		}
		return array();
	}
}

/**
 * Mongo Connection Class Plugin
 *
 * @author Sam Millman
 *
 * This is a plugin for the glue framework
 * which provides Mongo DB connectivity for
 * this app.
 */
class GMongo extends \glue\ApplicationComponent{

	/**
	 * The connection string for connecting with Db
	 *
	 * @access protected
	 * @var string
	 */
	public $connection;

	/**
	 * Set to true for persistent connections
	 *
	 * @access protected
	 * @var boolean
	 */
	public $persistent = true;

	/**
	 * Set to true for autoconnect
	 *
	 * @access protected
	 * @var boolean
	 */
	public $autoConnect = true;

	/**
	 * Name of Db to connect with
	 *
	 * @access protected
	 * @var string
	 */
	public $db;

	public $indexPath;

	private $_indexes;

	/**
	 * The mongo instance
	 *
	 * @var Mongo
	 */
	private $_mongo;

	public function __get($key){
		return $this->getCollection($key);
	}

	/**
	 * Destructor
	 *
	 * Will call close on the Db connection
	 */
	function __destruct(){
		$this->close();
	}

	/** Construct */
	function init(){

		$this->_mongo = new Mongo($this->connection, array("connect" => $this->autoConnect, "persist" => $this->persistent));

		if($this->autoConnect){
			$this->_mongo->connect();
		}

		if(empty($this->_mongo)){
			trigger_error("Could not connect to DB with connection string ".$this->connection);
		}else{
			return $this;
		}
	}

	/**
	 * Opens a connection with the Db
	 */
	public function connect(){
		$this->_mongo->connect();
	}

	/**
	 * This function drops a database
	 *
	 * This function drops and effectively deletes
	 * the database from mongo
	 *
	 * @return boolean $fail
	 */
	public function drop(){
		if(empty($this->db)){
			return false;
		}

		$this->_mongo->drop($this->db);
	}

	/**
	 * This will get the raw collection
	 *
	 * This function returns the pointer
	 * to a raw collection object
	 *
	 * @param string $collectionName
	 * @throws Exception
	 * @return MongoCollection $collection The collection found
	 */
	public function getCollection($collectionName){

		if(!$this->_mongo){

			/** Cannot connect to nothing */
			trigger_error("Mongo Object was empty whilst attempting to get a collection");
		}

		if($collectionName == '' || !$collectionName){
			var_dump(debug_backtrace());
			var_dump($collectionName); exit();
		}

		$collection = $this->getDb()->selectCollection($collectionName);

		if($collection != '_sub'){
			if(!$this->_indexes){
				$this->_indexes = glue::import($this->indexPath);
			}

			if(isset($this->_indexes[$collectionName])){
				$index_info = $this->_indexes[$collectionName];

				foreach($index_info as $k=>$v){
					$collection->ensureIndex($v[0], isset($v[1]) ? $v[1] : array());
				}
			}
		}

		/** Return the mongo collection found */
		return $collection;
	}

	/**
	 * Get GridFS
	 */
	function getGridFS(){
		if(!$this->_mongo){

			/** Cannot connect to nothing */
			trigger_error("Mongo Object was empty whilst attempting to get a GridFS");
		}

		return $this->getDb()->getGridFS();
	}

	/**
	 * Get a raw DB object
	 *
	 * This function gets a raw Database object
	 * from the mongo connection. If the mongo
	 * connection is not active then it will throw
	 * a new exception.
	 *
	 * @throws Exception
	 * @return Mongo $database
	 */
	public function getDb($dbname = null){

		if($dbname) $this->db = $dbname;

		if(empty($this->_mongo)){
			trigger_error("Mongo Object was empty whilst attempting to get a Database");
		}

		return $this->_mongo->selectDB($this->db);
	}

	/**
	 * Run a terminal command
	 *
	 * This function provides the jumper required
	 * to produce and execute terminal (base) commands
	 * on the mongo DB itself. This command does not
	 * concern itself with querying more with settings
	 * and configuration.
	 *
	 * @param mixed $data
	 * @return Mongo $results
	 */
	public function command($data){
		return $this->getDb()->command($data);
	}

	function getActiveCursor($mongoCursor, $className, $isMR = false){
		return new GMongoCursor($mongoCursor, $className, $isMR);
	}

	/**
	 * Close Mongo connection
	 *
	 * It is incredibly rare you would wish
	 * to close the Database connection but when you
	 * do this function will allow you to do just
	 * that.
	 *
	 * Please Note: This function has no return and
	 * the persistant connection variable must be set
	 * to false for this function to run correctly.
	 *
	 * @param void
	 * @return void
	 */
	public function close(){
		if($this->_mongo){
			if(!$this->persistent){
				$this->_mongo->close();
			}
		}
	}

    /**
     * Stop users from cloning the Singleton
     */
    public function __clone(){
        trigger_error('Clone is not allowed.', E_USER_ERROR);
    }
}