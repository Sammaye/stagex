<?php

namespace app\widgets;

use glue;
use glue\Widget;

class CKEditor extends Widget
{
	public $config;

	public function formOptions()
	{
		return $this->config;
	}

	public function render()
	{
		list($name, $id) = $this->getAttributeNameId();

		glue::controller()->jsFile('ckeditor', '/js/CKEditor/ckeditor.js');
		glue::controller()->js('ckeditor_'.$id, "
			$(function(){
				CKEDITOR.replace('{$id}',
				".js_encode($this->formOptions())."
				);
			});
		");
		echo \html::activeTextarea($this->model, $this->attribute, array('id' => $id));
	}
}