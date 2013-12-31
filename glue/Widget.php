<?php

namespace glue;

use Glue;
use \glue\Component;
use \glue\Html;

abstract class Widget extends Component
{
	public $id;
	
	public $name;

	public $model;

	public $attribute;
	
	public static $counter = 0;
	
	public static $IdPrefix = 'gw';
	
	private $_id;
	
	public function getId($autoGenerate = true)
	{
		if($autoGenerate && $this->_id === null){
			return $this->_id = self::$IdPrefix . self::$counter++;
		}
		return $this->_id;
	}
	
	public function setId($_id)
	{
		$this->_id = $_id;
	}	
	
	public static function run($config=array())
	{
		ob_start();
		ob_implicit_flush(false);
		$class = get_called_class();
		$o = new $class($config);
		$o->render();
		return ob_get_clean();
	}
	
	public static function start($config=array())
	{
		$class = get_called_class();
		$o = new $class($config);
		return $o;
	}
	
	public static function end()
	{
		ob_start();
		ob_implicit_flush(false);
		$this->render();
		return ob_get_clean();
	}
	
	function getAttributeNameId()
	{
		return array(
			$this->getAttributeName(),
			$this->getAttributeId()
		);
	}
	
	public function getAttributeName()
	{
		return $this->name = Html::getModelFormVariableName($this->attribute, $this->model);
	}
	
	public function getAttributeId()
	{
		return Html::getIdByName($this->name);
	}

	public function render()
	{
	} 
}