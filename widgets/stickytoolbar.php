<?php
class stickytoolbar extends GWidget{

	public $element;
	public $options = array();
	public $html;

	function render(){
		glue::clientScript()->addJsFile('stickytoolbar', "/js/stickyToolbar.js");
		glue::clientScript()->addJsScript('stickytoolbar.init.'.$this->element, "
			$(function(){
				$('".$this->element."').stickytoolbar(".GClientScript::encode($this->options).");
			});
		");

		echo $this->html;
	}
}