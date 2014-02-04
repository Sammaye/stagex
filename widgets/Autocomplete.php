<?php

namespace app\widgets;

use glue;
use glue\Widget;
use glue\Html;

class Autocomplete extends Widget
{
	public $htmlOptions = array();
	
	public $options = array();
	public $renderItem;
	public $value;
	
	public $placeholder;

	public function render()
	{
		if(!$this->model){

			$js = "$(function(){";

			if($this->renderItem && !isset($this->options['select'])){
				$this->options['select'] = "
				function( event, ui ) {
					$( '#".$this->attribute."' ).val( ui.item.label );
					return false;
				}";
			}

			$js .= "
				$('#".$this->attribute."').autocomplete(
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

			glue::controller()->js('jqauto_complete_'.$this->attribute, $js);
			
			echo Html::textfield(
				$this->attribute, 
				$this->value ? $this->value : null, 
				array_merge(array('class' => $this->attribute, 'placeholder'=>$this->placeholder),$this->htmlOptions)
			);
		}
	}
}