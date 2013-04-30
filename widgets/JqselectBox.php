<?php
class JqselectBox extends GWidget{

	public $items;
	public $id;
	public $class;
	public $value;

	function render(){
		if($this->model)
			list($name, $id) = $this->getAttributeNameId();
		else{
			$id = $this->id;
		}

		glue::clientScript()->addJsFile('selectBox', '/js/selectBox.js');
		glue::clientScript()->addJsScript('JuiselectBox.'.$id, "
			$(function(){
				$('SELECT#".$id."').selectBox();
			});
		");

		if($this->model)
			echo html::activeSelectbox($this->model, $this->attribute, $this->items, array('id' => $id));
		else{
			if($this->class)
				echo html::selectbox($this->id, $this->items, $this->value ? $this->value : null, array('id' => $id, 'class' => $this->class));
			else
				echo html::selectbox($this->id, $this->items, $this->value ? $this->value : null, array('id' => $id));
		}
	}
}