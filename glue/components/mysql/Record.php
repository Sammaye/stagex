<?php
namespace glue\components\mysql;

use glue\Exception;

class Record extends \glue\Component{

	public $host;
	public $user;
	public $password;
	public $db;

	private $link;
	private $pdo_link;

	function init(){
		$this->pdo_link = new PDO('mysql:host='.$this->host.';dbname='.$this->db, $this->user, $this->password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
		return $this;
	}

	function findOne($query, $params = array()){
		$result = $this->query($query, $params);
		if($result)
			return $result->fetch();

		return null;
	}

	/**
	 * mysql::pdo_query('SELECT * FROM TBL_WHOOP WHERE type_of_whoop IN :param AND siz_of_whoop = :size', array(':param' => array(1,2,3), ':size' => 3))
	 *
	 * @param $query
	 * @param $params
	 */
	function query($query, $params = array()){
		if(!$query)
			throw new Exception('Could not query nothing');

		// Lets get our IN fields first
		$in_fields = array();
		foreach($params as $field => $value){
			if(is_array($value)){
				for($i=0,$size=count($value);$i<$size;$i++)
					$in_array[] = $field.$i;

				$query = str_replace($field, "(".implode(',', $in_array).")", $query); // Lets replace the position in the quiery string with the full version
				$in_fields[$field] = $value; // Lets add this field to an array for use later
				unset($params[$field]); // Lets unset so we don't bind the param later down the line
			}
		}

		$query_obj = $this->pdo_link->prepare($query);
		$query_obj->setFetchMode(PDO::FETCH_ASSOC);

		// Now lets bind normal params.
		foreach($params as $field => $value){
			$query_obj->bindValue($field, $value);
		}

		// Now lets bind the IN params
		foreach($in_fields as $field => $value){
			for($i=0,$size=count($value);$i<$size;$i++){
				$query_obj->bindValue($field.$i, $value[$i]); // Both the named param index and this index are based off the array index which has not changed...hopefully
			}
		}

		$query_obj->execute() or $this->sqlerrorhandler("(".mysql_errno().") ".mysql_error(), $query, $_SERVER['PHP_SELF'], __LINE__);

		if($query_obj->rowCount() <= 0)
			return null;

		return $query_obj;
	}

	function sqlerrorhandler($ERROR, $QUERY, $PHPFILE, $LINE){
		define("SQLQUERY", $QUERY);
		define("SQLMESSAGE", $ERROR);
		define("SQLERRORLINE", $LINE);
		define("SQLERRORFILE", $PHPFILE);
		trigger_error("(SQL)", E_USER_ERROR);
	}
}