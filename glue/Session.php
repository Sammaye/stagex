<?php

namespace glue;

use	Glue;
use \glue\Component;

class Session extends Component
{
	/**
	 * This decides the lifetime (in seconds) of the session
	 * @var int
	 */
	public $lifeTime = '+2 weeks';

	public $collectionName = 'session';

	public function __get($name)
	{
		return $this->get($name);
	}

	public function __set($name, $value)
	{
		return $this->set($name, $value);
	}

	public function get($name)
	{
		return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
	}

	public function set($name, $value = null)
	{
		if(is_array($name)){
			foreach($name as $k => $v){
				$_SESSION[$k] = $v;
			}
		}else{
			$_SESSION[$name] = $value;
		}
	}
	
	public function getCollection()
	{
		return glue::db()->{$this->collectionName};
	}

	/**
	 * Constructor
	 */
	public function start()
	{
		if (session_status() == PHP_SESSION_ACTIVE) {
			return;
		}		
		
		// Register this object as the session handler
		session_set_save_handler(
			array( $this, "open" ),
			array( $this, "close" ),
			array( $this, "read" ),
			array( $this, "write"),
			array( $this, "destroy"),
			array( $this, "gc" )
		);
		session_start(); // Start the damn session
	}

	/**
	 * Open session
	 *
	 * This function opens a session from a save path.
	 * The save path can be changed the method of opening also can
	 * but we do not change that we just do the basics and return
	 *
	 * @param string $save_path
	 * @param string $session_name
	 */
	public function open( $save_path, $session_name )
	{
		global $sess_save_path;
		$sess_save_path = $save_path;

		// Don't need to do anything. Just return TRUE.
		return true;

	}

	/**
	 * This function closes the session (end of session)
	 */
	public function close()
	{
		// Return true to indicate session closed
		return true;

	}

	/**
	 * This is the read function that is called when we open a session.
	 * This function attempts to find a session from the Db. If it cannot then
	 * the session class variable will remain null.
	 *
	 * @param string $id
	 */
	public function read($id)
	{
		// Set empty result
		$data = '';

		if((
			$session = $this->getCollection()->findOne(array("session_id" => $id))
		) !== null){
			$data = isset($session['data']) ? $session['data'] : '';
		}
		return $data;
	}

	/**
	 * This is the write function. It is called when the session closes and
	 * writes all new data to the Db. It will do two actions depending on whether or not
	 * a session already exists. If the session does exist it will just update the session
	 * otherwise it will insert a new session.
	 *
	 * @param string $id
	 * @param mixed $data
	 *
	 * @todo Need to make this function aware of other users since php sessions are not always unique maybe delete all old sessions.
	 */
	public function write($id, $data)
	{
		//Write details to session table
		$time = strtotime($this->lifeTime);

		$response = $this->getCollection()->update(
			array("session_id" => $id), 
			array('$set' => array(
				"session_id" => $id,
				"data" => $data,
				"expires" => $time,
			)), 
			array("upsert" => true)
		);
		
		if($response['err']){
			Glue::error(E_ERROR, $response['err'], __FILE__, __LINE__);
		}
		return true;
	}

	/**
	 * This function is called when a user calls session_destroy(). It
	 * kills the session and removes it.
	 *
	 * @param string $id
	 */
	public function destroy($id)
	{
		// Remove from Db
		$this->getCollection()->remove(array("session_id" => $id));
		return true;
	}

	/**
	 * This function GCs (Garbage Collection) all old and out of date sessions
	 * which still exist in the Db. It will remove by comparing the current to the time of
	 * expiring on the session record.
	 *
	 * @todo Make a cronjob to delete all sessions after about a day old and are still inactive
	 */
	public function gc()
	{
		$this->getCollection()->remove(array('expires' => array('$lt' => strtotime($this->lifeTime))));
		return true;
	}

	public function regenerateID($deleteOldSession = false)
	{
		$oldID = session_id();

		// if no session is started, there is nothing to regenerate
		if(empty($oldID))
			return;

		session_regenerate_id(false);
		$newID = session_id();

		if($this->getCollection()->findOne(array('session_id' => $oldID)) !== null){
			if($deleteOldSession){
				$this->getCollection()->update(
					array('session_id' => $oldID),
					array('$set' => array('session_id' => $newID))
				);
			}else{
				$row['session_id'] = $newID;
				$this->getCollection()->insert($row);
			}
		}else{
			$this->getCollection()->insert(array(
				'id'=>$newID,
				'expire'=>strtotime($this->lifeTime)
			));
		}
	}
}
