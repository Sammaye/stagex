<?php
namespace glue;

use Glue;

/**
 * This represents any kind of file, including uploaded ones
 */
class File implements \Iterator, \ArrayAccess, \Countable
{
	public $model;
	public $modelClass;
	public $name;
	public $type;
	public $tmp_name;
	public $error;
	public $size;

	public $fh;

	private $_upload = false;
	private $_collection = array();

	public function __construct($config = array())
	{
		foreach($config as $k=>$v){
			$this->$k=$v;
		}

		if(isset($config['id'])){
			$this->populateUpload(isset($config['model']) ? $config['model'] : null, $config['id']);
		}
	}

	public function save($path = null)
	{
		if($this->_upload){
			if($path !== null){
				$this->saveAs($path);
			}else{
				throw new Exception("One simply does not save a uploaded file, you must specify where to put it");
			}
		}else{
			// perform save for a file handler
		}
	}

	public function saveAs($path)
	{
		if($this->_upload){
			if(!$this->hasUploadError()){
				return move_uploaded_file($this->tmp_name,$path);
			}else{
				throw new Exception('An error was encountered while uploading '.$this->name);
			}
		}else{
			// perform save as for a file handler
		}
	}

	public function hasUploadError()
	{
		return $this->error !== UPLOAD_ERR_OK;
	}

	public function getUploadError()
	{
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

	public function populateMultiUpload($model, $id){
		$vector = $_FILES[get_class($model)];

		if(is_array($vector)){
			$result = array();
			foreach($vector as $key1 => $value1){
				foreach($value1 as $key2 => $value2){
					$result[$key2][$key1] = $value2;
				}
			}

			foreach($result as $i => $file){
				$m = new \glue\File();
				foreach($file as $f=>$v){
					$m->$f = $v;
				}
				$m->_upload = true;
				$m->model = $model;
				$this->_collection[] = $m;
			}
		}else{
			throw new Exception("Field $id in $model was not found to have a valid value");
		}
	}

	public function populateUpload($model = null, $id)
	{
		if(!isset($_FILES)||!is_array($_FILES)){
			return;
		}

		if($model){
			$cnameParts = explode('\\',get_class($model));
			$file_a = isset($_FILES[end($cnameParts)]) ? $_FILES[end($cnameParts)] : null;
		}else{
			$file_a = $_FILES;
		}

		if(is_array($file_a)){
			// We will not blindly prefetch the files, instead we will pick each one out saftely according to what the user
			// asks for

			$this->model = $model;
			$this->_upload = true;

			if(isset($file_a[$id])){
				foreach($file_a[$id] as $k => $v){
					$this->$k = $v;
				}
			}else{
				$result = array();
				foreach($file_a as $key1 => $value1){
					foreach($value1 as $key2 => $value2){
						if($key2 == $id){
							$result[$key1] = $value2;
						}
					}
				}
				foreach($result as $k => $v){
					$this->$k = $v;
				}
			}
		}else{
			throw new Exception("Field $id in $model was not found to have a valid value");
		}
	}

	public function open($handler, $mode = 'r')
	{
		if(get_resource_type($handler) == 'file'){
			$this->fh = $handler;
			$this->_upload = false;
		}elseif(is_string($handler)){
			if(($handle = fopen($handler, $mode)) === false){
				throw new Exception('Could not open file '.$handler);
			}else{
				$this->fh = $handle;
				$this->_upload = false;
			}
		}else{
			throw new Exception('A file object must be constructed from something');
		}
	}

	// Read a file and display its content chunk by chunk
	public function readfile_chunked($retbytes = true)
	{
		$buffer = '';
		$cnt =0;
		$handle=$this->fh;
		// $handle = fopen($filename, 'rb');
		while(!feof($handle)){
			$buffer = fread($handle, 1024*1024);
			echo $buffer;
			ob_flush();
			flush();
			if($retbytes){
				$cnt += strlen($buffer);
			}
		}
		$status = fclose($handle);
		if($retbytes && $status){
			return $cnt; // return num. bytes delivered like readfile() does.
		}
		return $status;
	}

	public function count()
	{
		return count($this->_collection);
	}

	public function offsetSet($offset, $value)
	{
		if(is_null($offset)){
			$this->_collection[] = $value;
		}else{
			$this->_collection[$offset] = $value;
		}
	}

	public function offsetExists($offset)
	{
		return isset($this->_collection[$offset]);
	}

	public function offsetUnset($offset)
	{
		unset($this->_collection[$offset]);
	}

	public function offsetGet($offset)
	{
		if(isset($this->_collection[$offset])){
			return $this->_collection[$offset];
		}
		return null; //Else lets just return normal
	}

	public function rewind()
	{
		reset($this->_collection);
	}

	public function current()
	{
		if(current($this->_collection) !== false){
			return current($this->_collection);
		}else{
			return false;
		}
	}

	public function key()
	{
		return key($this->_collection);
	}

	public function next()
	{
		return next($this->_collection);
	}

	public function valid()
	{
		return $this->current() !== false;
	}
}