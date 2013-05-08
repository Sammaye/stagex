<?php
namespace glue;

/**
 * This represents any kind of file, including uploaded ones
 */
class File extends \glue\Component{

	function open($path){

	}

	function save(){

	}

	function saveAs(){

	}

	static function getInstance($model,$id){
		if(is_string($model)){

		}
	}

	// Read a file and display its content chunk by chunk
	function readfile_chunked($filename, $retbytes = TRUE) {
		$buffer = '';
		$cnt =0;
		// $handle = fopen($filename, 'rb');
		$handle = fopen($filename, 'rb');
		if ($handle === false) {
			return false;
		}
		while (!feof($handle)) {
			$buffer = fread($handle, 1024*1024);
			echo $buffer;
			ob_flush();
			flush();
			if ($retbytes) {
				$cnt += strlen($buffer);
			}
		}
		$status = fclose($handle);
		if ($retbytes && $status) {
			return $cnt; // return num. bytes delivered like readfile() does.
		}
		return $status;
	}
}