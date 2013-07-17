<?php

namespace app\widgets;

use glue;

class Jqautocomplete extends \glue\Widget{

	public $options = array();
	public $renderItem;
	public $value;
	
	public $placeholder;

	function render(){
		if(!$this->model){

			$js = "$(function(){";

				if($this->renderItem && !isset($this->options['select'])){
					$this->options['select'] = "
					function( event, ui ) {
						$( '.".$this->attribute."' ).val( ui.item.label );
						return false;
					}";
				}

				$js .= "
						$('.".$this->attribute."').autocomplete(
							" . js_encode($this->options) . "
						)
				";

				if($this->renderItem){
					$js .= "
						.data( 'uiAutocomplete' )._renderItem = function( ul, item ) {
							" . $this->renderItem . "
						};
					";
				}else{
					$js .= ";";
				}

			$js .= "});";

			glue::$controller->js('jqauto_complete_'.$this->attribute, $js);
			echo \html::textfield($this->attribute, $this->value ? $this->value : null, array('class' => $this->attribute, 'placeholder'=>$this->placeholder));
		}
	}
}