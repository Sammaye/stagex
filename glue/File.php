<?php
namespace glue;

use \glue\Exception;

/**
 * This represents any kind of file, including uploaded ones
 */
class File implements \Iterator,\ArrayAccess,\Countable{

	public $model;
	public $modelClass;
	public $name;
	public $type;
	public $tmp_name;
	public $error;
	public $size;

	public $fh;

	private $upload = false;
	private $collection = array();

	function __construct($config=array()){
		foreach($config as $k=>$v)
			$this->$k=$v;

		if(isset($config['id']))
			$this->populateUpload(isset($config['model'])?$config['model']:null, $config['id']);
	}

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

	function populateMultiUpload($model,$id){
		$vector = $_FILES[get_class($model)];

		if(is_array($vector)){
			$result = array();
			foreach($vector as $key1 => $value1)
				foreach($value1 as $key2 => $value2)
				$result[$key2][$key1] = $value2;

			foreach($result as $i => $file){
				$m=new \glue\File();
				foreach($file as $f=>$v)
					$m->$f=$v;
				$m->upload=true;
				$m->model=$model;
				$this->collection[]=$m;
			}
		}else
			throw new Exception("Field $id in $model was not found to have a valid value");
	}

	function populateUpload($model=null,$id){

		if(!isset($_FILES)||!is_array($_FILES))
			return;

		if($model){
			$cnameParts=explode('\\',get_class($model));
			$file_a = isset($_FILES[end($cnameParts)]) ? $_FILES[end($cnameParts)] : null;
		}else
			$file_a = $_FILES;

		if(is_array($file_a)){
			// We will not blindly prefetch the files, instead we will pick each one out saftely according to what the user
			// asks for

			$this->model=$model;
			$this->upload=true;

			if(isset($file_a[$id])){
				foreach($file_a[$id] as $k => $v)
					$this->$k=$v;
			}else{
				$result = array();
				foreach($file_a as $key1 => $value1){
					foreach($value1 as $key2 => $value2){
						if($key2==$id)
							$result[$key1] = $value2;
					}
				}
				foreach($result as $k => $v)
					$this->$k=$v;
			}
		}else
			throw new Exception("Field $id in $model was not found to have a valid value");
	}

	function open($handler,$mode='r'){

		if(get_resource_type($handler) == 'file'){
			$this->fh=$handler;
			$this->upload=false;
		}elseif(is_string($handler)){
			if(($handle = fopen($handler, $mode))===false)
				throw new Exception('Could not open file '.$handler);
			else{
				$this->fh=$handle;
				$this->upload=false;
			}
		}else
			throw new Exception('A file object must be constructed from something');
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

	public function count(){
		return count($this->collection);
	}

	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			$this->collection[] = $value;
		} else {
			$this->collection[$offset] = $value;
		}
	}

	public function offsetExists($offset) {
		return isset($this->collection[$offset]);
	}

	public function offsetUnset($offset) {
		unset($this->collection[$offset]);
	}

	public function offsetGet($offset) {
		if(isset($this->collection[$offset])){
			return $this->collection[$offset];
		}
		return null; //Else lets just return normal
	}

	public function rewind() {
		reset($this->collection);
	}

	public function current() {
		if(current($this->collection) !== false){
			return current($this->collection);
		}else{
			return false;
		}
	}

	public function key() {
		return key($this->collection);
	}

	public function next() {
		return next($this->collection);
	}

	public function valid() {
		return $this->current() !== false;
	}
}