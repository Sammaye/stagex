<?php
class CKEditor extends GWidget{

	public $config;

	function formOptions(){
		return $this->config;
	}

	function render(){
		list($name, $id) = $this->getAttributeNameId();

		glue::clientScript()->addJsFile('ckeditor', '/js/CKEditor/ckeditor.js');
		glue::clientScript()->addJsScript('ckeditor_'.$id, "
			$(function(){
				CKEDITOR.replace('{$id}',
				".GClientScript::encode($this->formOptions())."
				);
			});
		");

		echo html::activeTextarea($this->model, $this->attribute, array('id' => $id));
	}
}