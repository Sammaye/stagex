<?php
namespace app\widgets;

use glue;

class stickytoolbar extends \glue\Widget{

	public $element;
	public $options = array();
	public $html;

	function render(){
		glue::controller()->jsFile('stickytoolbar', "/js/stickytoolbar.js");
		glue::controller()->js('stickytoolbar.init.'.$this->element, "
			$(function(){
				$('".$this->element."').stickytoolbar(".js_encode($this->options).");
			});
		");
		echo $this->html;
	}
}