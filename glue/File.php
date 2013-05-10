<?php
namespace glue;

use \glue\Exception;

/**
 * This represents any kind of file, including uploaded ones
 */
class File extends \glue\Component /*implements Iterator,ArrayAccess,Countable*/{

	private $container;
	
	public $model;
	public $name;
	public $type;
	public $tmp_name;
	public $error;
	public $size;
	
	public $fh;
	
	public $upload = false;
	
	//protected static $inst=null;

	private function __construct($config=array()){}
	
	/*
	 * Not currently used due to not really knowing how it will work
	function populateMany(){
		$result = array();
		foreach($vector as $key1 => $value1)
			foreach($value1 as $key2 => $value2)
			$result[$key2][$key1] = $value2;
		return $result;
	}
	*/

	function save($path=null){

		if($this->upload){
			if($path!==null){
				$this->saveAs($path);
			}else
				throw new Exception("One simply does not save a uploaded file, you must specify where to put it");
		}else{
			// perform save for a file handler
		}
	}

	function saveAs($path){
		if($this->upload){
			if(!$this->hasUploadError())
				return move_uploaded_file($this->tmp_name,$path);
			else
				throw new Exception('An error was encountered while uploading '.$this->name);
		}else{	
			// perform save as for a file handler
		}
	}
	
	function hasUploadError(){
		return $this->error !== UPLOAD_ERR_OK;
	}
	
	function getUploadError(){
		switch ($this->error) {
			case UPLOAD_ERR_INI_SIZE:
				return 'INI_SIZE_TOO_BIG';
			case UPLOAD_ERR_FORM_SIZE:
				return 'MAX_FILE_TOO_BIG';
			case UPLOAD_ERR_PARTIAL:
				return 'PARTIAL_UPLOAD';
			case UPLOAD_ERR_NO_FILE:
				return 'NO_FILE';
			case UPLOAD_ERR_NO_TMP_DIR:
				return 'NO_TMP_DIR';
			case UPLOAD_ERR_CANT_WRITE:
				return 'CANT_WRITE';
			case UPLOAD_ERR_EXTENSION:
				return 'ERR_EXTENSION';
			default:
				return 'FILE_INVALID';
		}		
	}

	static function populateUpload($model,$id){
		
		static $inst=null;
		if ($inst === null) {
			
			$file_a = $_FILES[$model][$id];
				
			if(is_array($file_a)){
				$inst = new \glue\File();
				$inst->model=$model;
				$inst->upload=true;
				foreach($a as $k => $v)
					$inst->$k=$v;
			}else
				throw new Exception("Field $id in $model was not found to have a valid value");			
		}
		return $inst;			
	}
	
	static function open($handler,$mode='r'){
		
		static $inst=null;
		if ($inst === null) {		
			$inst=new \glue\File();
			if(get_resource_type($handler) == 'file'){
				$inst->fh=$handler;
			}elseif(is_string($handler)){
				if(($handle = fopen($handler, $mode))===false)
					throw new Exception('Could not open file '.$handler);
				else
					$inst->fh=$handle;
			}else
				throw new Exception('A file object must be constructed from something');
		}
		return $inst;
	}

	// Read a file and display its content chunk by chunk
	function readfile_chunked($retbytes = TRUE) {
		$buffer = '';
		$cnt =0;
		$handle=$this->fh;
		// $handle = fopen($filename, 'rb');
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
	
	protected function __clone(){
		throw new Exception('A file object cannot be cloned.');
	}	
}