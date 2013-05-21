<?php

namespace glue;

use glue;

class Controller {

	const HEAD = 1;
	const BODY_END = 2;
	
	const DENIED = 1;
	const LOGIN = 2;
	const UNKNOWN = 3;	

	public $defaultAction = 'index';
	public $layout = "blank_page";
	
	public $action;
	
	public $title;
	
	public $metaTags;
	public $linkTags;
	
	public $jsFiles;
	public $js;
	
	public $cssFiles;
	public $css;
	
	public $description;
	public $keywords;

	public function filters(){ return array(); }
	
	function authRules(){ return array(); }

	function addCssFile($map, $path, $media = null, $POS = 'HEAD', $core=false){
		$this->cssTags[$map] = array('map' => $map, 'path' => $path, 'type' => 'file', 'media' => $media, 'pos' => $POS);
	}

	function addCssScript($map, $script, $media = null, $POS = 'END'){
		$this->cssTags[$map] = array( 'script' => $script, 'type' => 'script', 'media' => $media, 'pos' => $POS );
	}

	function addJsFile($map, $path, $POS = 'HEAD', $core=false){
		if(is_array($this->jsTags)){
			$this->jsTags = array_merge(array($map => array( 'path' => $path, 'type' => 'file', 'pos' => self::HEAD )), $this->jsTags);
		}else{
			$this->jsTags[$map] = array( 'path' => $path, 'type' => 'file', 'pos' => self::HEAD  );
		}
	}

	function addJsScript($map, $script, $POS = 'END'){
		$this->jsTags[$map] = array( 'script' => $script, 'type' => 'script', 'pos' => $POS );
	}

	function addHeadTag($html, $POS = self::HEAD){
		$this->tags[] = array( 'html' => $html, 'pos' => $POS );
	}

	

	function render($page, $args = null){

		//core::view()->render();

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
	
	/**
	 * Marks the beginning of an HTML page.
	 */
	public function beginPage(){
		ob_start();
		ob_implicit_flush(false);
	
		$this->trigger(self::EVENT_BEGIN_PAGE);
	}

	/**
	 * Marks the ending of an HTML page.
	 */
	public function endPage(){
		$this->trigger(self::EVENT_END_PAGE);
	
		$content = ob_get_clean();
		echo strtr($content, array(
				self::PL_HEAD => $this->renderHeadHtml(),
				self::PL_BODY_BEGIN => $this->renderBodyBeginHtml(),
				self::PL_BODY_END => $this->renderBodyEndHtml(),
		));
	
		unset(
				$this->metaTags,
				$this->linkTags,
				$this->css,
				$this->cssFiles,
				$this->js,
				$this->jsFiles
		);
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
	public static function beginWidget($class, $args = null){
		$widget = new $class($args);
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

	function json_success($params,$exit=true){
		
		$json='';
		if(is_string($params)){
			$json= json_encode(array('success' => true, 'messages' => array($params)));
		}else{
			$json= json_encode(array_merge(array('success' => true), $params));
		}
		
		if($exit){
			echo $json;
			exit(0);
		}
		return $json;		
	}

	function json_error($params,$exit=true){
		$json='';
		switch(true){
			case $params == self::DENIED:
				$json= json_encode(array('success' => false, 'messages' => array('Action not Permitted')));
				break;
			case $params == self::LOGIN:
				$json= json_encode(array('success' => false, 'messages' => array('You must login to continue')));
				break;
			case $params == self::UNKNOWN:
				$json= json_encode(array('success' => false, 'messages' => array('An unknown error was encountered')));
				break;
			default:
				if(is_string($params)){
					$json= json_encode(array('success' => false, 'messages' => array($params)));
				}else{
					$json= json_encode(array_merge(array('success' => false), $params));
				}
				break;
		}
		
		if($exit){
			echo $json;
			exit(0);
		}
		return $json;
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
