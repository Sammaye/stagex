<?php

namespace glue;

use Glue;

/**
 * HTML
 *
 * Warning: This class currently does not support HTML 5.
 * Warning: This class does not sanitise HTML. You must use the HTMLPurifier plugin for that.
 * Warning: This class does not solve HTML errors nor does it prevent them.
 *
 * This class is designed to just allow you to correctly form HTMl tags in no particular order for certain functions.
 */
class Html{

	/////////////////
	// BASIC ELEMENTS
	////////////////

	/**
	 * This is an internal function used for form an array of HTML options for a specific HTML tag/element
	 *
	 * @param $options
	 */
	public static function formOptions($options){
		if(is_string($options))
			return array("class=\"$options\"");
		$inputOptions = array();
		foreach($options as $option => $value){
				$inputOptions[] = $option.'="'.$value.'"';
		}
		return $inputOptions;
	}

	/**
	 * Open any HTML tag can also include tags that do not exist regardless of HTML errors so check your code.
	 *
	 * This function is not discriminate which means it will open a tag at random without checking
	 * if other tags are already open and whether HTML errors would occurr from the usage of this function.
	 *
	 * @param string $tagName
	 * @param array $options
	 */
	public static function openTag($tagName, $options = array()){
		return "<".$tagName." ".implode(" ", self::formOptions($options)).">";
	}

	/**
	 * Closes any HTML tag regardless of whether it was previously opened and/or HTML error resulting from its usage
	 *
	 * @param string $tagName
	 */
	public static function closeTag($tagName){
		return "</".$tagName.">";
	}

	/**
	 * Manipulates the openTag() and closeTag() functions to do both at once for objects such as clearer divs
	 *
	 * @param string $tagName
	 * @param array $options
	 */
	public static function open_closeTag($tagName, $options = array()){
		return self::openTag($tagName, $options).self::closeTag($tagName);
	}

	/**
	 * Creates and returns a meta tag
	 * @param string $content
	 * @param array $options
	 */
	public static function metaTag($content, $options){
		if(isset($options['httpEquiv'])){
			return "<meta http-equiv='{$options['httpEquiv']}' content='{$content}' />";
		}else{
			return "<meta name='{$options['name']}' content='{$content}' />";
		}
	}

	/**
	 * Creates and returns a new link tag (not the same as an <a/>)
	 * @param array $options
	 */
	public static function linkTag($options){
		return "<link ".implode(" ", self::formOptions($options))." />";
	}

	/**
	 * Creates and returns a new style tag
	 * @param string $media
	 * @param string $text
	 */
	public static function css($media, $text){
		return "<style type=\"text/css\" media=\"{$media}\">\n/*<![CDATA[*/\n{$text}\n/*]]>*/\n</style>";
	}

	/**
	 * Creates and returns and new link rel HTML tag designed to specifically house a CSS file
	 * @param string $url
	 * @param string $media
	 */
	public static function cssFile($url,$media=null){
		if($media!=null)
			$media=' media="'.$media.'"';
		return '<link rel="stylesheet" type="text/css" href="'.$url.'"'.$media.' />'."\n";
	}

	/**
	 * Creates and returns a new script js designed to house raw JS
	 * @param string $text
	 */
	public static function js($text){
		return "<script type=\"text/javascript\">\n/*<![CDATA[*/\n{$text}\n/*]]>*/\n</script>";
	}

	/**
	 * Creates and returns a new script tag designed to load JS from an external file
	 * @param string $url
	 */
	public static function jsFile($url){
		return '<script type="text/javascript" src="'.$url.'"></script>'."\n";
	}

	/**
	 * A Wrapper for setting a refresh meta tag from a normal meta tag
	 * @param int $seconds
	 * @param string $url
	 */
	public static function refresh($seconds, $url=null){
		glue::clientScript()->addTag(array('html' => self::metaTag($seconds, array('http-equiv' => 'refresh'))));
	}

	/**
	 * Returns an image <img/> tag fully annotated with specific options of your choice.
	 *
	 * @param array $options
	 */
	public static function img($options = array()){
		return "<img ".implode(" ", self::formOptions($options))."/>";
	}

	/**
	 * Creates a standard text link with the ability to add a text property to default the text of the link.
	 *
	 * @param array $options
	 */
	public static function a($options = array()){
		$text = $options['text'];
		unset($options['text']);
		
		if(isset($options['href'])&&is_array($options['href'])){
			$url=$options['href'][0];
			unset($options['href'][0]);
			$options['href']=glue::http()->url($url,$options['href']);
		}

		return "<a ".implode(" ", self::formOptions($options)).">$text</a>";
	}

	/**
	 * Creates an input HTML tag fully annotated with the options you choose.
	 *
	 * @param array $options
	 */
	public static function input($options = array()){
		return "<input ".implode(" ", self::formOptions($options))."/>";
	}

	////////////////
	// FORM ELEMENTS
	///////////////

	/**
	 * Builds a non-activeRecord label element and returns it with specific options
	 *
	 * @param string $label
	 * @param string $for
	 * @param array $options
	 */
	public static function label($label, $for = '', $options = array()){
		return "<label ".implode(" ", self::formOptions($options))." for='{$for}'>{$label}</label>";
	}

	/**
	 * Builds a non-activeRecord text field element and returns it with specific options
	 *
	 * @param string $name
	 * @param mixed $value
	 * @param array $options
	 */
	public static function textfield($name, $value = null, $options = array()){
		if(is_string($options)){
			$options = array('class' => $options);
		}
		if(!isset($options['id'])) $options['id'] = self::getIdByName($name);
		return "<input ".implode(" ", self::formOptions($options))."  type='text' name='{$name}' value='{$value}'/>";
	}

	public static function activeTextField($model, $attribute, $options = array()){
		return self::textfield(self::getAttributeName($attribute, $model), self::getAttributeValue($attribute, $model), $options);
	}

	/**
	 * Builds a non-activeRecord hidden field element and returns it with specific options
	 *
	 * @param string $name
	 * @param mixed $value
	 * @param array $options
	 */
	public static function hiddenfield($name, $value = null, $options = array()){
		if(isset($options['value'])){
			return "<input ".implode(" ", self::formOptions($options))."  type='hidden' name='{$name}'/>";
		}else{
			return "<input ".implode(" ", self::formOptions($options))."  type='hidden' name='{$name}' value='{$value}'/>";
		}
	}

	public static function activeHiddenField($model, $attribute, $options = array()){
		return self::hiddenfield(self::getAttributeName($attribute, $model), self::getAttributeValue($attribute, $model, $options), $options);
	}

	/**
	 * Builds a non-activeRecord password field element and returns it with specific options
	 *
	 * @param string $name
	 * @param mixed $value
	 * @param array $options
	 */
	public static function passwordfield($name, $value = null, $options = array()){
		return "<input ".implode(" ", self::formOptions($options))."  type='password' name='{$name}' value='{$value}'/>";
	}

	public static function activePasswordField($model, $attribute, $options = array()){
		return self::passwordfield(self::getAttributeName($attribute, $model), self::getAttributeValue($attribute, $model), $options);
	}

	/**
	 * Builds a non-activeRecord file field element and returns it with specific options
	 *
	 * @param string $name
	 * @param mixed $value
	 * @param array $options
	 */
	public static function filefield($name, $value = null, $options = array()){
		return "<input ".implode(" ", self::formOptions($options))."  type='file' name='{$name}' value='{$value}'/>";
	}

	/**
	 * Builds an active record file field, to keep standards of HTML and active record the default value for this is null from the model
	 *
	 * @param mixed $model
	 * @param string $attribute
	 * @param array $options
	 */
	public static function activeFileField($model, $attribute, $options = array()){
		return self::filefield(self::getAttributeName($attribute, $model), null, $options);
	}

	/**
	 * Builds a non-activeRecord textarea element and returns it with specific options
	 *
	 * @param string $name
	 * @param mixed $value
	 * @param array $options
	 */
	public static function textarea($name, $value = null, $options = array()){
		return "<textarea ".implode(" ", self::formOptions($options))." name='{$name}'>{$value}</textarea>";
	}

	public static function activeTextarea($model, $attribute, $options = array()){
		return self::textarea(self::getAttributeName($attribute, $model), self::getAttributeValue($attribute, $model), $options);
	}

	/**
	 * Builds a non-activeRecord drop down list field element and returns it with specific options
	 *
	 * @param string $name
	 * @param array $items
	 * @param string|int $selectedValue
	 * @param array $options
	 */
	public static function selectbox($name, $items = array(), $selectedValue = null, $options = array()){
		$select = "<select ".implode(" ", self::formOptions($options))." name='{$name}'>";

		if(isset($options['head'])){
			$select .= "<option value='{$options['head'][0]}'>{$options['head'][1]}</option>";
		}

		foreach($items as $value=>$caption){
			if((string)$selectedValue === (string)$value){
				$select .= "<option value='{$value}' selected='selected'>{$caption}</option>";
			}else{
				$select .= "<option value='{$value}'>{$caption}</option>";
			}
		}
		return $select.'</select>';
	}

	public static function activeSelectbox($model, $attribute, $items, $options = array()){
		return self::selectbox(self::getAttributeName($attribute, $model), $items, self::getAttributeValue($attribute, $model), $options);
	}

	/**
	 * Builds a non-activeRecord radio button and returns it
	 *
	 * @param string $name
	 * @param string|int $chk_value
	 * @param string|int $act_value
	 * @param array $options
	 */
	public static function radiobutton($name, $chk_value = null, $act_value = null, $options = array()){
		if((string)$chk_value === (string)$act_value){
			return "<input ".implode(" ", self::formOptions($options))."  type='radio' name='{$name}' value='{$chk_value}' checked='checked'/>";
		}else{
			return "<input ".implode(" ", self::formOptions($options))."  type='radio' name='{$name}' value='{$chk_value}'/>";
		}
	}

	public static function activeRadiobutton($model, $attribute, $chk_value, $options = array()){
		return self::radiobutton(self::getAttributeName($attribute, $model), $chk_value, self::getAttributeValue($attribute, $model), $options);
	}

	/**
	 * Builds a non-activeRecord checkbox and returns it
	 *
	 * @param string $name
	 * @param string|int $chk_value
	 * @param string|int $act_value
	 * @param array $options
	 */
	public static function checkbox($name, $chk_value = null, $act_value = null, $options = array()){
		if((string)$chk_value === (string)$act_value){
			return "<input ".implode(" ", self::formOptions($options))."  type='checkbox' name='{$name}' value='{$chk_value}' checked='checked'/>";
		}else{
			return "<input ".implode(" ", self::formOptions($options))."  type='checkbox' name='{$name}' value='{$chk_value}'/>";
		}
	}

	/**
	 * Build an active record checkbox and returns it
	 *
	 * @param string $model
	 * @param string $attribute
	 * @param string|int $chk_value
	 * @param array $options
	 */
	public static function activeCheckbox($model, $attribute, $chk_value, $options = array()){
		return self::checkbox(self::getAttributeName($attribute, $model), $chk_value, self::getAttributeValue($attribute, $model), $options);
	}

	/**
	 * Builds a checkbox group and returns it
	 * @param string $name Name of the field
	 * @param array $selectedValues Default selected values, will be overriden by Model
	 * @param array $options
	 */
	public static function checkbox_group($name, $selectedValues = array(), $options = array()){
		$group = new checkBox_group($name, $selectedValues);
		$group->attributes($options);
		return $group;
	}

	/**
	 * Active record edition of self::checkbox_group
	 * @param mixed $model
	 * @param string $attribute
	 * @param array $options
	 */
	public static function activeCheckbox_group($model, $attribute, $options = array()){
		return self::radio_group(self::getAttributeName($attribute, $model), self::getAttributeValue($attribute, $model), $options);
	}

	/**
	 * Builds a radio group and returns it
	 * @param string $name
	 * @param mixed $selectedValue
	 * @param array $options
	 */
	public static function radio_group($name, $selectedValue = null, $options = array()){
		$val = isset($options['value']) ? $options['value'] : $selectedValue;

		$group = new radioButton_group($name, $val);
		$group->attributes($options);
		return $group;
	}

	public static function activeRadio_group($model, $attribute, $options = array()){
		return self::radio_group(self::getAttributeName($attribute, $model), self::getAttributeValue($attribute, $model), $options);
	}

	/**
	 * Creates and echos out a submit button onto the page
	 * @param string $label
	 * @param array $options
	 */
	public static function submitbutton($label, $options = array()){
		$inputOptions = self::formOptions($options);
		if(isset($options['name'])) $inputOptions['name'] = "name='".$options['name']."'";
		if(isset($options['id'])) $inputOptions['id'] = "id='".$options['id']."'";

		return "<input ".implode(" ", $inputOptions)."  type='submit' value='{$label}' />";
	}

	/**
	 * Wrapper for a normal non activerecord form
	 * @param array $attributes
	 */
	public static function form($attributes = array()){
		$form = new formhtml();
		$form->attributes($attributes);
		$form->init();
		return $form;
	}

	/**
	 * Wrapper for an activerecord form
	 * @param array $attributes
	 */
	public static function activeForm($attributes = array()){
		$form = new activeFormhtml();
		$form->attributes($attributes);
		$form->init();
		return $form;
	}

	/**
	 * Builds a form summary component for the view, can be used in AJAX
	 * This function includes a number of param options whihc can tailor the output:
	 * * errorHead: Defines what to place before the error list/message
	 * * successMessage: Defines the success message for all models validating
	 * * showOnlyFirstError: Tells the function to not show a list and only show the first error
	 * * closable: If set to false or not set at all will not render the "x" otherwise if not set to true will not
	 *
	 * @param GModel|array $models The models by which to grab errors from
	 * @param array $options The options for the function
	 */
	static function form_summary($models, $options = array()){
		$messages = array();
		$html = '';

		$succeeded = true;
		$model_validated = false;

		if($models){
			if(is_array($models)){
				foreach($models as $k=>$v){
					if(!$v->getSuccess()){
						$succeeded = false;
						$messages = array_merge($messages, $v->getErrors());
					}

					if(!$model_validated)
						$model_validated = $v->getHasBeenValidated() ? true : false;
				}
			}else{
				//$succeeded = $models->getSuccess();
				$messages = $models->getErrors();
				$model_validated = $models->getValidated() ? true : false;
			}
		}

		// Has model been validated?
		if($model_validated){
			if(count($messages) > 0){ // If the model/s did not validate
				$html .= self::openTag('div', array('class' => 'alert alert-error form_summary'));
				$html .= self::a(array('href' => '#', 'text' => utf8_decode('&#215;'), 'class'=>'close'));

				if(isset($options['errorHead'])){
					$html .= $options['errorHead'];
				}elseif(!$options['showOnlyFirstError']){
					$html .= 'The record could be saved because:';
				}

				if(isset($options['showOnlyFirstError'])){
					foreach($messages as $field=>$v){
						$html .= $messages[$field][0];
						break;
					}
				}else{
					$html .= self::openTag("ul", array());
					foreach($messages as $message){
						if(!is_array($message)){ //If it's an array it is something custom
							$html .= self::openTag("li").$message.self::closeTag("li");
						}else{
							foreach($message as $field_m)
								$html .= self::openTag("li").$field_m.self::closeTag("li");
						}
					}
					$html .= self::closeTag("ul");
				}

				//if(isset($options['closable']) && $options['closable'] == false)

				$html .= self::closeTag('div');
			}elseif(isset($options['successMessage'])){ // If the model/s validated
				$html .= self::openTag('div', array('class' => 'block_summary success_summary'));
					$html .= self::openTag('div', array('class' => 'close')).self::a(array('href' => '#', 'text' => utf8_decode('&#215;'))).self::closeTag('div');
					$html .= $options['successMessage'];
				$html .= self::closeTag('div');
			}
		}
		return $html;
	}

	/**
	 * Checks if there is a flash message in session of the index $flash
	 *
	 * @param string $flash
	 */
	public static function hasFlashMessage(){
		if(isset($_SESSION['flash_message']))
			return true;
	}

	/**
	 * Adds a error flash to the session
	 *
	 * @param string $i
	 * @param string $message
	 */
	public static function setErrorFlashMessage($message){
		$_SESSION['flash_message'] = array($message, "flash-error");
	}

	/**
	 * Adds a success type flash to the session
	 *
	 * @param string $i
	 * @param string $message
	 */
	public static function setSuccessFlashMessage($message){
		$_SESSION['flash_message'] = array($message, "flash-success");
	}

	/**
	 * Displays the flash message stored in session
	 *
	 * @param string $flash
	 */
	public static function getFlashMessage(){
		$session_flash = $_SESSION['flash_message'];

		$message = $session_flash[0] ? $session_flash[0] : $message;
		$class = $session_flash[1] ? $session_flash[1] : $class;

		$html = html::openTag('div', array('class' => 'alert '.$class));
		$html .= '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
		$html .= html::openTag('p', array('class' => 'message')).$message.html::closeTag('p');

		
		//$html .= html::a(array('href' => glue::http()->url('SELF'), 'text' => utf8_decode('&#215;'), 'class' => 'close'));
		$html .= html::open_closeTag('div', array('class' => 'clear'));

		$html .= html::closeTag('div');

		unset($_SESSION['flash_message']);

		return $html;
	}

	/**
	 * HTML encodes a variable ready for output (can be used to make shit safe but best to use HTMLPurifier)
	 * @param string $html
	 */
	static public function encode($html){
		return htmlspecialchars($html);
	}

	static public function decode($html){
		return htmlspecialchars_decode($text,ENT_QUOTES);
	}

	static public function nl2br($text){
		return nl2br(html::encode($text));
	}

	/**
	 * Strips all HTML tags from a string
	 * @param string $html
	 */
	public static function stripTags($html){
		$html = stripslashes(strip_tags($html));
		return preg_replace('/<[^>]*>/', '', $html);
	}
	
	public static function getAttributeName($attribute, $model = null)
	{
		if($model === null){
			return $attribute;
		}
		
		if(($pos = strpos($attribute,'[')) !== false){
			if($pos !== 0){  // e.g. name[a][b]
				$attribute = '['.substr($attribute, 0, $pos).']' . substr($attribute,$pos);
			}elseif(($pos=strrpos($attribute,']')) !== false && $pos !== strlen($attribute)-1){  // e.g. [a][b]name{
				$sub=substr($attribute,0,$pos+1);
				$attribute=substr($attribute,$pos+1);

				$attribute = $sub.'['.$attribute.']';
			}elseif(preg_match('/\](\w+\[.*)$/', $attribute, $matches)){
				$attribute = '['.str_replace(']','][',trim(strtr($attribute,array(']['=>']','['=>']')),']')).']';
			}
		}else{
			$attribute = '['.$attribute.']';
		}
		return $model::getName().$attribute;
	}
	
	public static function getAttributeValue($attribute, $model, $options = array())
	{
		if(isset($options['value'])){
			return $options['value'];
		}
		if(($pos = strpos($attribute,'[')) !== false){
			if($pos !== 0){  // e.g. name[a][b]
				//var_dump($attribute);
				$exploded_path = explode('.', trim(strtr($attribute,array(']['=>'.','['=>'.')),']'));
				if(count($exploded_path) > 0){
					$previous = $model;
					foreach($exploded_path as $part){
						if(is_object($previous)){
							//var_dump($previous);
							if(!property_exists($previous, $part)) return null;
							$previous = $previous->$part;
						}else{
							if(!isset($previous[$part])) return null;
							$previous = $previous[$part];
						}
					}
					return $previous;
				}else{
					return $model->{substr($attribute,0,$pos)};
				}
			}
			if(($pos=strrpos($attribute,']'))!==false && $pos!==strlen($attribute)-1){
				$sub=substr($attribute,0,$pos+1);
				$attribute=substr($attribute,$pos+1);
				return $model->$attribute;
			}
			if(preg_match('/\](\w+\[.*)$/',$attribute,$matches)){
				$attribute=$matches[1];
				return $model->$attribute;
			}
		}else{
			return $model->$attribute;
		}
		return "";		
	}
	
	public static function getIdByName($name)
	{
		return str_replace(array('[]', '][', '[', ']', ' '), array('', '_', '_', '', '_'), $name);		
	}
	
	public static function getAttributeNameId($attribute, $model)
	{
		$name = static::getAttributeName($attribute, $model);
		return array($name, static::getIdByName($name));
	}
}

class form_base{

	public $action;
	public $method;

	protected $formOptions = array(
		"name",
		"id",
		"class",
		"action",
		"method",
		"enctype",
		"target"
	);

	public function attributes($a){
		if($a){
			foreach($a as $k=>$v)
				$this->$k = $v;
		}
	}

	public function init(){

		//$this->name = $name;
		if(!$this->action) $this->action = Glue::http()->url('SELF');
		if(!$this->method) $this->method = "post";

		$options = array();
		foreach($this->formOptions as $option){
			if(isset($this->$option))
				$options[] = $option.'="'.$this->$option.'"';
		}

		echo "<form ".implode(" ", $options).">";
	}

	public function end(){
		echo "</form>";
	}
}


class formhtml extends form_base{

	public function textfield($attribute, $value = null, $options = array()){
		return html::textfield($attribute, $value, $options);
	}

	public function hiddenfield($attribute, $value = null, $options = array()){
		return html::hiddenfield($attribute, $value, $options);
	}

	public function passwordfield($attribute, $value = null, $options = array()){
		return html::passwordfield($attribute, $value, $options);
	}

	public function textarea($attribute, $value = null, $options = array()){
		return html::textarea($attribute, $value, $options);
	}

	public function selectbox($attribute, $items = array(), $value = null, $options = array()){
		return html::selectbox($attribute, $items, $value, $options);
	}

	public function radiobutton($attribute, $chk_value = null, $value = null, $options = array()){
		return html::radiobutton($attribute, $chk_value, $value, $options);
	}

	public function checkbox($attribute, $chk_value = null, $value = null, $options = array()){
		return html::hiddenfield($attribute, 0) . html::checkbox($attribute, $chk_value, $value, $options);
	}

	public function checkbox_group($attribute, $value = null, $options = array()){
		return html::checkbox_group($attribute, $value, $options);
	}

	public function radio_group($attribute, $value = null, $options = array()){
		return html::radio_group($attribute, $value, $options);
	}
}

/**
 * Active form class
 *
 * This class allows the use of active record models in conjunction with active forms to
 * produce RAD and easy to assemble forms for the masses.
 *
 * @author Sam Millman
 *
 */
class activeFormhtml extends form_base{
	/**
	 * Creates a text field
	 * @see htdocs/glue/core/formhtml::textfield()
	 */
	public function textfield($model, $attribute, $options = array()){
		return html::activeTextField($model, $attribute, $options);
	}

	/**
	 * Creates a hidden field
	 * @see htdocs/glue/core/formhtml::hiddenfield()
	 */
	public function hiddenfield($model, $attribute, $options = array()){
		return html::activeHiddenField($model, $attribute, $options);
	}

	/**
	 * Creates a password field
	 * @see htdocs/glue/core/formhtml::passwordfield()
	 */
	public function passwordfield($model, $attribute, $options = array()){
		return html::activePasswordField($model, $attribute, $options);
	}

	/**
	 * Creates a file field
	 * @see htdocs/glue/core/formhtml::filefield()
	 */
	public function filefield($model, $attribute, $options = array()){
		return html::activeFileField($model, $attribute, $options);
	}

	/**
	 * Creates a text area
	 * @see htdocs/glue/core/formhtml::textarea()
	 */
	public function textarea($model, $attribute, $options = array()){
		return html::activeTextarea($model, $attribute, $options);
	}

	/**
	 * Creates a drop down list
	 * @see htdocs/glue/core/formhtml::selectbox()
	 */
	public function selectbox($model, $attribute, $items = array(), $options = array()){
		return html::activeSelectbox($model, $attribute, $items, $options);
	}

	/**
	 * Creates a radio button
	 * @see htdocs/glue/core/formhtml::radiobutton()
	 */
	public function radiobutton($model, $attribute, $chk_value = null, $options = array()){
		return html::activeRadiobutton($model, $attribute, $chk_value, $options);
	}

	/**
	 * Creates a checkbox
	 * @see htdocs/glue/core/formhtml::checkbox()
	 */
	public function checkbox($model, $attribute, $chk_value = null, $options = array()){
		return html::activeHiddenField($model, $attribute, array('value' => 0)) .
			html::activeCheckbox($model, $attribute, $chk_value, $options);
	}

	/**
	 * Creates a checkbox group
	 * @see htdocs/glue/core/formhtml::checkbox_group()
	 */
	public function checkbox_group($model, $attribute, $options = array()){
		return html::activeCheckbox_group($model, $attribute, $options);
	}

	/**
	 * Creates a radio group
	 * @see htdocs/glue/core/formhtml::radio_group()
	 */
	public function radio_group($model, $attribute, $options = array()){
		return html::activeRadio_group($model, $attribute, $options);
	}
}

/**
 * This class deals with making and managing radio button groups
 *
 * @author Sam Millman
 */
class radioButton_group{
	protected $selectedValue;

	public function __construct($name, $selectedValue = null){
		$this->name = $name;
		$this->selectedValue = $selectedValue;
	}

	public function attributes($a){
		if($a){
			foreach($a as $k=>$v)
				$this->$k = $v;
		}
	}

	/**
	 * Adds a new radio button to the group
	 *
	 * This function basically uses the HTML radio button method to add a new radio button to the group
	 *
	 * @param string|int $value
	 * @param array $options
	 */
	public function add($value, $options  = array()){
		if(strval($value) === strval($this->selectedValue)){
			return html::radiobutton($this->name, $value, $this->selectedValue, $options);
		}else
			return html::radiobutton($this->name, $value, null, $options);
	}
}

/**
 * This class manages a checkbox group and selects the correct value that are filled in supplied by the $value variable
 *
 * This class can be used in conjunction with both formhtml, activeformhtml and no form at all to pre-populate a
 * set of checkboxes.
 *
 * @author smillman
 */
class checkBox_group{
	protected $selectedValues;

	/**
	 * Main construct
	 *
	 * @param $name
	 * @param $selectedValues
	 */
	public function __construct($name, $selectedValues = array()){
		$this->name = $name; // attribute name
		$this->selectedValues = $selectedValues; // Selected values within the attribute
	}

	/**
	 * Populate class
	 * @param array $a
	 */
	public function attributes($a){
		if($a){
			foreach($a as $k=>$v)
				$this->$k = $v;
		}
	}

	/**
	 * Adds a new checkbox to the group
	 *
	 * This method basically gets the value from the value variable and returns
	 * a new checkbox.
	 *
	 * @param string|int $value
	 * @param array $options
	 */
	public function add($value, $options  = array()){
		if($this->selectedValues){
			if(array_key_exists($value, array_flip($this->selectedValues)))
				return html::checkbox($this->name."[]", $value, $value, $options);
			else
				return html::checkbox($this->name."[]", $value, null, $options);
		}else{
			return html::checkbox($this->name."[]", $value, null, $options);
		}
	}
}