<?php
class Jqautocomplete extends GWidget{

	public $options = array();
	public $renderItem;
	public $value;

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
							" . GClientScript::encode($this->options) . "
						)
				";

				if($this->renderItem){
					$js .= "
						.data( 'autocomplete' )._renderItem = function( ul, item ) {
							" . $this->renderItem . "
						};
					";
				}else{
					$js .= ";";
				}

			$js .= "});";

			glue::clientScript()->addJsScript('jqauto_complete_'.$this->attribute, $js);
			echo html::textfield($this->attribute, $this->value ? $this->value : null, array('class' => $this->attribute));
		}
	}
}