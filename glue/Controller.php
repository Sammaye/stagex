<?php

namespace glue;

use \glue\core;

class Controller {

	public $layout = "blank_page";
	public $pageTitle;
	public $pageDescription;
	public $pageKeywords;

	public function filters(){ return array(); }

	function addCssFile($map, $path){
		glue::clientScript()->addCssFile($map, $path);
	}

	function addCssScript($map, $script){
		glue::clientScript()->addCssScript($map, $script);
	}

	function addJsFile($map, $script){
		glue::clientScript()->addJsFile($map, $script);
	}

	function addJsScript($map, $script){
		glue::clientScript()->addJsScript($map, $script);
	}

	function addHeadTag($html){
		glue::clientScript()->addTag($html, GClientScript::HEAD);
	}

	function widget($path, $args = array()){
		return glue::widget($path, $args);
	}

	function beginWidget($path, $args = array()){
		return glue::beginWidget($path, $args);
	}

	function accessRules(){ return array(); }

	function render($page, $args = null){

		core::view()->render();

		if(isset($args['page']) || isset($args['args'])) throw new Exception("The \$page and \$args variables are reserved variables within the render function.");

		if($args){
			foreach($args as $k=>$v){
				$$k = $v;
			}
		}

		if(!$this->pageTitle){
			$this->pageTitle = glue::config('pageTitle');
		}

		if(!$this->pageDescription){
			$this->pageDescription = glue::config('pageDescription');
		}

		if(!$this->pageKeywords){
			$this->pageKeywords = glue::config('pageKeywords');
		}

		ob_start();
			include $this->getView($page);
			$page = ob_get_contents();
		ob_clean();

		ob_start();
			include_once $this->getLayout($this->layout);
			$layout = ob_get_contents();
		ob_clean();

		$layout = glue::clientScript()->renderHead($layout);
		$layout = glue::clientScript()->renderBodyEnd($layout);
		echo $layout;
	}

	function partialRender($page, $args = null, $returnString = false){

		if($args){
			foreach($args as $k=>$v){
				$$k = $v;
			}
		}

		ob_start();
			ob_implicit_flush(false);
			include $this->getView($page);
			$view = ob_get_contents();
		ob_end_clean();

		if($returnString): return $view; else: echo $view; endif;
	}

	function getView($path){

		$path = strlen(pathinfo($path, PATHINFO_EXTENSION)) <= 0 ? $path.'.php' : $path;

		if(strpos($path, '../') === 0){

			// Then this should go from doc root
			return str_replace('../', DIRECTORY_SEPARATOR, ROOT.$path);

		}elseif(strpos($path, '/')!==false){

			// Then this should go from views root (/application/views) because we have something like user/edit.php
			return str_replace('/', DIRECTORY_SEPARATOR, ROOT.'/application/views/'.$path);

		}else{

			// Then lets attempt to get the cwd from the controller. If the controller is not set we use siteController as default. This can occur for cronjobs
			return str_replace('/', DIRECTORY_SEPARATOR, ROOT.'/application/views/'.str_replace('controller', '',
					strtolower(isset(glue::$action['controller']) ? glue::$action['controller'] : 'siteController')).'/'.$path);
		}
	}

	function getLayout($path){

		if(mb_substr($path, 0, 1) == '/'){

			// Then this should go from doc root
			return str_replace('/', DIRECTORY_SEPARATOR, ROOT.$path.'.php');

		}else{

			// Then this should go from layouts root (/application/layouts) because we have something like user/blank
			return str_replace('/', DIRECTORY_SEPARATOR, ROOT.'/application/layouts/'.$path.'.php');

		}
	}


	/**
	 * Starts a widget but does not run the render() function
	 *
	 * @param string $path
	 * @param array $args
	 */
	public static function beginWidget($path, $args = null){
		$pieces = explode("/", $path);
		$cName = substr($pieces[sizeof($pieces)-1], 0, strrpos($pieces[sizeof($pieces)-1], "."));
		Glue::import($path);
		$widget = new $cName();
		$widget->attributes($args);
		$widget->init();
		return $widget;
	}

	/**
	 * Starts a widget and runs the render() function of a widget
	 *
	 * @param string $path
	 * @param array $params
	 */
	public static function widget($path, $params = null){
		$widget = self::beginWidget($path, $params);
		return $widget->render();
	}

	const DENIED = 1;
	const LOGIN = 2;
	const UNKNOWN = 3;

	static function kill($params, $success = false){
		if(!$success)
			echo self::error($params);
		else
			echo self::success($params);
		exit();
	}

	static function success($params){
		if(is_string($params)){
			return json_encode(array('success' => true, 'messages' => array($params)));
		}else{
			return json_encode(array_merge(array('success' => true), $params));
		}
	}

	static function error($params){
		switch(true){
			case $params == self::DENIED:
				return json_encode(array('success' => false, 'messages' => array('Action not Permitted')));
				break;
			case $params == self::LOGIN:
				return json_encode(array('success' => false, 'messages' => array('You must login to continue')));
				break;
			case $params == self::UNKNOWN:
				return json_encode(array('success' => false, 'messages' => array('An unknown error was encountered')));
				break;
			default:
				if(is_string($params)){
					return json_encode(array('success' => false, 'messages' => array($params)));
				}else{
					return json_encode(array_merge(array('success' => false), $params));
				}
				break;
		}
	}

	const HEAD = 1;
	const BODY_END = 2;

	private $coreCSS = array();

	private $cssTags = array();
	private $jsTags = array();
	private $tags = array();

	function addCoreCSSFile($map, $path, $media = null, $POS = self::HEAD){
		$this->cssTags[$map] = array('map' => $map, 'path' => $path, 'type' => 'file', 'media' => $media, 'pos' => $POS, 'core' => true );
	}

	function addCssFile($map, $path, $media = null, $POS = self::HEAD){
		$this->cssTags[$map] = array('map' => $map, 'path' => $path, 'type' => 'file', 'media' => $media, 'pos' => $POS, 'core' => false );
	}

	function addCssScript($map, $script, $media = null, $POS = self::HEAD){
		$this->cssTags[$map] = array( 'script' => $script, 'type' => 'script', 'media' => $media, 'pos' => $POS );
	}

	function addCoreJsFile($map, $path){
		if(is_array($this->jsTags)){
			$this->jsTags = array_merge(array($map => array( 'path' => $path, 'type' => 'file', 'pos' => self::HEAD )), $this->jsTags);
		}else{
			$this->jsTags[$map] = array( 'path' => $path, 'type' => 'file', 'pos' => self::HEAD  );
		}
	}

	function addJsFile($map, $path, $POS = self::HEAD){
		$this->jsTags[$map] = array( 'path' => $path, 'type' => 'file', 'pos' => $POS  );
	}

	function addJsScript($map, $script, $POS = self::BODY_END){
		$this->jsTags[$map] = array( 'script' => $script, 'type' => 'script', 'pos' => $POS );
	}

	function addTag($html, $POS = self::HEAD){
		$this->tags[] = array( 'html' => $html, 'pos' => $POS );
	}

	/**
	 * Inserts the scripts in the head section.
	 * @param string $output the output to be inserted with scripts.
	 */
	public function renderHead(&$output){
		$html='';
		foreach($this->tags as $k=>$val){
			if($val['pos'] == self::HEAD){
				$html.=$val['html'];
			}
		}

		foreach($this->cssTags as $k => $val){
			if($val['type'] == 'file' && $val['pos'] == self::HEAD && $val['core'] == true){
				$html.=html::cssFile($val['path'], $val['media'])."\n";
				unset($this->cssTags[$k]);
			}
		}
		foreach($this->cssTags as $k => $val){
			if($val['type'] == 'file' && $val['pos'] == self::HEAD){
				$html.=html::cssFile($val['path'], $val['media'])."\n";
			}
		}
		foreach($this->jsTags as $k => $val){
			if($val['type'] == 'file' && $val['pos'] == self::HEAD){
				$html.=html::jsFile($val['path'])."\n";
			}
		}
		foreach($this->cssTags as $k => $val){
			if($val['type'] == 'script' && $val['poos'] == self::HEAD){
				$html.=html::css($val['media'], $val['script'])."\n";
			}
		}

		$code = '';
		foreach($this->jsTags as $k => $val){
			if($val['type'] == 'script' && $val['pos'] == self::HEAD){
				if(Glue::config("Minify_JS")){
					$code.= JSMin::minify($val['script']);
				}else{
					$code.= $val['script'];
				}
			}
		}

		if(!empty($code)){
			$html.=html::js($code)."\n";
		}

		if($html!=='')
		{
			$count=0;
			$output=preg_replace('/(<title\b[^>]*>|<\\/head\s*>)/is','<###head###>$1',$output,1,$count);
			if($count)
				$output=str_replace('<###head###>',$html,$output);
			else
				$output=$html.$output;
		}
		return $output;
	}

	/**
	 * Inserts the scripts at the end of the body section.
	 * @param string $output the output to be inserted with scripts.
	 */
	public function renderBodyEnd(&$output){

		$fullPage=0;
		$output=preg_replace('/(<\\/body\s*>)/is','<###end###>$1',$output,1,$fullPage);
		$html='';
		foreach($this->cssTags as $k => $val){
			if($val['type'] == 'file' && $val['pos'] == self::BODY_END){
				$html.=html::cssFile($val['path'], $val['media'])."\n";
			}
		}

		$code = '';
		foreach($this->jsTags as $k => $val){
			if($val['type'] == 'script' && $val['pos'] == self::BODY_END){
				if(Glue::config("Minify_JS")){
					$code .= JSMin::minify($val['script']);
				}else{
					$code .= $val['script'];
				}
			}
		}

		if(!empty($code))
			$html.=html::js($code);

		if($fullPage)
			$output=str_replace('<###end###>',$html,$output);
		else
			$output=$output.$html;

		return $output;
	}

	/**
	 * Encodes a PHP variable into javascript representation.
	 *
	 * Example:
	 * <pre>
	 * $options=array('key1'=>true,'key2'=>123,'key3'=>'value');
	 * echo CJavaScript::encode($options);
	 * // The following javascript code would be generated:
	 * // {'key1':true,'key2':123,'key3':'value'}
	 * </pre>
	 *
	 * For highly complex data structures use {@link jsonEncode} and {@link jsonDecode}
	 * to serialize and unserialize.
	 *
	 * @param mixed $value PHP variable to be encoded
	 * @return string the encoded string
	 */
	public static function encode($value)
	{
		if(is_string($value))
		{
			if(strpos($value,'js:')===0)
				return substr($value,3);
			else
				return "'".self::quote($value)."'";
		}
		else if($value===null)
			return 'null';
		else if(is_bool($value))
			return $value?'true':'false';
		else if(is_integer($value))
			return "$value";
		else if(is_float($value))
		{
			if($value===-INF)
				return 'Number.NEGATIVE_INFINITY';
			else if($value===INF)
				return 'Number.POSITIVE_INFINITY';
			else
				return rtrim(sprintf('%.16F',$value),'0');  // locale-independent representation
		}
		else if(is_object($value))
			return self::encode(get_object_vars($value));
		else if(is_array($value))
		{
			$es=array();
			if(($n=count($value))>0 && array_keys($value)!==range(0,$n-1))
			{
				foreach($value as $k=>$v)
					$es[]="'".self::quote($k)."':".self::encode($v);
				return '{'.implode(',',$es).'}';
			}
			else
			{
				foreach($value as $v)
					$es[]=self::encode($v);
				return '['.implode(',',$es).']';
			}
		}
		else
			return '';
	}

	public static function quote($js,$forUrl=false)
	{
		if($forUrl)
			return strtr($js,array('%'=>'%25',"\t"=>'\t',"\n"=>'\n',"\r"=>'\r','"'=>'\"','\''=>'\\\'','\\'=>'\\\\','</'=>'<\/'));
		else
			return strtr($js,array("\t"=>'\t',"\n"=>'\n',"\r"=>'\r','"'=>'\"','\''=>'\\\'','\\'=>'\\\\','</'=>'<\/'));
	}

	function compressCSS($buffer) {
		/* remove comments */
		$buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
		/* remove tabs, spaces, newlines, etc. */
		$buffer = preg_replace('/(?:\s\s+|\n|\t)/', '', $buffer);
		return $buffer;
	}
}
