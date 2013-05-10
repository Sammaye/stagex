<?php

namespace glue;

class User extends \glue\Model{
	function init(){
		if(php_sapi_name() != 'cli'){
			glue::session()->open();
		}

		// else we don't do anything if we are in console but we keep this class so that
		// it can be used to assign users to cronjobs.
	}
}