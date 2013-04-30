<?php
class autoresizetextarea extends GWidget{

	public $value;

	public $style; // This should never be used unless your a cock
	public $class;

	function render(){
		if($this->model){
			list($name, $id) = $this->getAttributeNameId();
		}else{
			$id = str_replace(' ', '_', $this->attribute);
		}

		glue::clientScript()->addJsFile('autoresize.include', "/js/autoresizetextarea.js");
		glue::clientScript()->addJsScript('autoresize#'.$this->attribute.'.init', "
			$(function(){
				$('#".$id."').autoResize();
			});
		");

		if($this->model){
			echo html::activeTextarea($this->model, $this->attribute, array('id' => $id, 'style' => $this->style, 'class' => $this->class));
		}else{
			echo html::textarea($this->attribute, $this->value, array('id' => $id, 'style' => $this->style, 'class' => $this->class));
		}
	}
}