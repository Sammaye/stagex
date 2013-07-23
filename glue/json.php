<?php

namespace glue;

class json{

	const DENIED = 1;
	const LOGIN = 2;
	const UNKNOWN = 3;

	static function success($params){
		if(is_string($params)){
			return json_encode(array('success' => true, 'message' => array($params)));
		}else{
			return json_encode(array_merge(array('success' => true), $params));
		}
		if($exit){
			echo $json;
			exit(0);
		}
		return $json;		
	}

	static function error($params){
		switch(true){
			case $params == self::DENIED:
				return json_encode(array('success' => false, 'message' => array('Action not Permitted')));
				break;
			case $params == self::LOGIN:
				return json_encode(array('success' => false, 'message' => array('You must login to continue')));
				break;
			case $params == self::UNKNOWN:
				return json_encode(array('success' => false, 'message' => array('An unknown error was encountered')));
				break;
			default:
				if(is_string($params)){
					return json_encode(array('success' => false, 'message' => array($params)));
				}else{
					return json_encode(array_merge(array('success' => false), $params));
				}
				break;
		}
		
		if($exit){
			echo $json;
			exit(0);
		}
		return $json;		
	}
}