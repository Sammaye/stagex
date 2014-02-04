<?php

namespace app\widgets;

use glue;
use glue\Widget;

class autoresizetextarea extends Widget
{
	public $value;

	public $style; // This should never be used unless your a cock
	public $class;

	public function render()
	{
		if($this->model){
			list($name, $id) = $this->getAttributeNameId();
		}else{
			$id = str_replace(' ', '_', $this->attribute);
		}

		glue::controller()->jsFile("/js/autosize.js");
		glue::controller()->js('autoresize#'.$this->attribute.'.init', "
			$(function(){
				$('#".$id."').autosize();
			});
		");

		if($this->model){
			echo \html::activeTextarea($this->model, $this->attribute, array('id' => $id, 'style' => $this->style, 'class' => $this->class));
		}else{
			echo \html::textarea($this->attribute, $this->value, array('id' => $id, 'style' => $this->style, 'class' => $this->class));
		}
	}
}