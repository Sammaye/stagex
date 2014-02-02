<?php

namespace glue;

class Json
{
	public static $succeeded = 0;
	public static $failed = 0;
	public static $total = 0;
	
	const DENIED = 1;
	const LOGIN = 2;
	const UNKNOWN = 3;
	
	public static function op($total)
	{
		return static::success(array('n' => static::$succeeded, 'failed' => static::$failed, 'total' => $total));
	}

	public static function success($params = array(), $exit = true)
	{
		$json='';
		if(is_string($params)){
			$json= json_encode(array('success' => true, 'message' => array($params)));
		}else{
			$json= json_encode(array_merge(array('success' => true), $params));
		}

		if($exit){
			echo $json;
			exit(0);
		}
		return $json;
	}

	public static function error($params = array(), $exit = true)
	{
		$json='';
		switch(true){
			case $params == self::DENIED:
				$json= json_encode(array('success' => false, 'message' => 'Action not Permitted'));
				break;
			case $params == self::LOGIN:
				$json= json_encode(array('success' => false, 'message' => 'You must login to continue'));
				break;
			case $params == self::UNKNOWN:
				$json= json_encode(array('success' => false, 'message' => 'An unknown error was encountered'));
				break;
			default:
				if(is_string($params)){
					$json= json_encode(array('success' => false, 'message' => $params));
				}else{
					$json= json_encode(array_merge(array('success' => false), $params));
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