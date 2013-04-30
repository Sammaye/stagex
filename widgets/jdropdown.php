<?php
class jdropdown extends GWidget{
//more_options_button
	public $name;
	public $class;

	function render(){

		glue::clientScript()->addJsFile('jdropdown', '/js/jdropdown.js');
		glue::clientScript()->addJsScript('jdropdown.'.$this->name, "
			$(function(){
				$('".$this->class."').jdropdown();
			});
		");

	}
}