<?php

/**
 * The Mongo Session Store
 *
 * @author Sam Millman
 *
 * This is the session store Mongo plugin. This allows us to have
 * database sessions within PHP. If you do not wish for database
 * sessions then within the configuration ini just return false
 * on $session_store.
 *
 * @example new session(); start_session();
 *
 */
class MongoSession{

	/**
	 * This decides the lifetime (in seconds) of the session
	 *
	 * @access private
	 * @var int
	 */
	private $life_time;

	/**
	 * This stores the found session collection so that we don't
	 * waste resources by constantly going back for it
	 *
	 * @access private
	 * @var sessions
	 */
	private $_sessions = array();

	/**
	 * Constructor
	 */
	function __construct() {

		// Ensure index on Session ID
		Glue::db()->sessions->ensureIndex(array('session_id' => 1), array("unique" => true));

		// Register this object as the session handler
		session_set_save_handler(
		array( $this, "open" ),
		array( $this, "close" ),
		array( $this, "read" ),
		array( $this, "write"),
		array( $this, "destroy"),
		array( $this, "gc" )
		);
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
	function open( $save_path, $session_name ) {

		global $sess_save_path;

		$sess_save_path = $save_path;

		// Don't need to do anything. Just return TRUE.
		return true;

	}

	/**
	 * This function closes the session (end of session)
	 */
	function close() {

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
	function read( $id ) {

		// Set empty result
		$data = '';

		// Fetch session data from the selected database
		$time = time();

		$this->_sessions = Glue::db()->sessions->findOne(array("session_id"=>$id));

		if (!empty($this->_sessions)) {
			$data = $this->_sessions['session_data'];
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
	function write( $id, $data ) {

		//Write details to session table
		$time = strtotime('+2 weeks');

		// If the user is logged in record their uid
		$uid = $_SESSION['logged'] ? $_SESSION['uid'] : 0;

		$fields = array(
			"session_id"=>$id,
			"user_id"=>$uid,
			"session_data"=>$data,
			"expires"=>$time,
			"active"=>1
		);

		$fg = Glue::db()->sessions->update(array("session_id"=>$id), array('$set'=>$fields), array("fsync"=>1, "upsert"=>true));

		// DONE
		return true;
	}

	/**
	 * This function is called when a user calls session_destroy(). It
	 * kills the session and removes it.
	 *
	 * @param string $id
	 */
	function destroy( $id ) {

		// Remove from Db
		Glue::db()->sessions->remove(array("session_id" => $id), true);

		return true;
	}

	/**
	 * This function GCs (Garbage Collection) all old and out of date sessions
	 * which still exist in the Db. It will remove by comparing the current to the time of
	 * expiring on the session record.
	 *
	 * @todo Make a cronjob to delete all sessions after about a day old and are still inactive
	 */
	function gc() {
		glue::db()->sessions->remove(array('expires' => array('$lt' => strtotime('+2 weeks'))));
		return true;
	}

}
