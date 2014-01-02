<?php

namespace glue;

use Glue;
use \glue\Html;
use \glue\Json;

class Controller extends Component
{
	const HEAD = 1;
	const BODY_BEGIN = 2;
	const BODY_END = 3;

	const DENIED = 1;
	const LOGIN = 2;
	const UNKNOWN = 3;

	public $tplHead = '<![CDATA[GLUE-BLOCK-HEAD]]>';
	public $tplBodyBegin = '<![CDATA[GLUE-BLOCK-BODY]]>';
	public $tplBodyEnd = '<![CDATA[GLUE-BLOCK-BODY_END]]>';

	public $defaultAction = 'index';
	public $layout = "blank_page";

	public $action;

	public $title;

	public $metaTags;
	public $linkTags;

	public $css;
	public $cssFiles;
	
	public $js;
	public $jsFiles;

	public function init()
	{
		$this->title = $this->title ?: glue::$name;
		if(glue::$description !== null){
			$this->metaTag('description', glue::$description);
		}
		if(glue::$keywords !== null){
			$this->metaTag('keywords', glue::$keywords);
		}
		parent::init();
	}
	
	public function run($action)
	{
		if($this->beforeAction($this, $action)){
			$this->action=$action; // We set this so we know what action in that controller is being run
			call_user_func_array(array($this,$action),array());
		}
		$this->afterAction($this, $action);
	}	

	public function cssFile($map, $path = null, $media = null)
	{
		if(is_array($map)){
				
			$path = is_null($path) ? $media : $path;
			foreach($map as $k => $v){
				if(is_numeric($k)){
					$this->cssFile($v, null, $path);
				}else{
					$this->cssFile($k, $v, $path);
				}
			}
			
		}else{		
			if($path === null){
				$this->cssFiles[basename($map,'.css')] = Html::cssFile($map,$media);
			}else{
				$this->cssFiles[$map] = Html::cssFile($path, $media);
			}
		}
	}

	public function css($map, $script, $media = null)
	{
		$this->css[$map] = Html::css($media, $script);
	}

	public function jsFile($map, $path = null, $pos = self::BODY_END)
	{
		if(is_array($map)){
			
			$path = is_null($path) ? $pos : $path;
			foreach($map as $k => $v){
				if(is_numeric($k)){
					$this->jsFile($v, null, $path);
				}else{
					$this->jsFile($k, $v, $path);
				}
			}
			
		}else{
			if($path === null){
				$this->jsFiles[$pos][basename($map,'.js')] = Html::jsFile($map);
			}else{
				$this->jsFiles[$pos][$map] = Html::jsFile($path);
			}
		}
	}

	public function js($map, $script, $pos = self::BODY_END)
	{
		$this->js[$pos][$map] = $script;
	}

	public function metaTag($name, $html)
	{
		$this->metaTags[$name] = Html::metaTag($html,array('name'=>$name));
	}

	public function linkTag($options,$key=null)
	{
		if($key === null){
			$this->linkTags[] = Html::linkTag($options);
		}else{
			$this->linkTags[$name] = Html::linkTag($options);
		}
	}
	
	/**
	 * Marks the beginning of an HTML page.
	 */
	public function beginPage(){
		ob_start();
		ob_implicit_flush(false);
		$this->beforeRender($this, $this->action);
	}
	
	public function head(){
		echo $this->tplHead;
	}
	
	public function beginBody(){
		echo $this->tplBodyBegin;
	}
	
	public function endBody(){
		echo $this->tplBodyEnd;
	}
	
	/**
	 * Marks the ending of an HTML page.
	 */
	public function endPage(){
		$this->afterRender($this, $this->action);
	
		$content = ob_get_clean();
		echo strtr($content, array(
				$this->tplHead => $this->renderHeadHtml(),
				$this->tplBodyBegin => $this->renderBodyBeginHtml(),
				$this->tplBodyEnd => $this->renderBodyEndHtml(),
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
	 * Renders the content to be inserted in the head section.
	 * The content is rendered using the registered meta tags, link tags, CSS/JS code blocks and files.
	 * @return string the rendered content
	 */
	protected function renderHeadHtml()
	{
		$lines = array();
		if (!empty($this->metaTags)) {
			$lines[] = implode("\n", $this->metaTags);
		}
		if (!empty($this->linkTags)) {
			$lines[] = implode("\n", $this->linkTags);
		}
		if (!empty($this->cssFiles)) {
			$lines[] = implode("", $this->cssFiles);
		}
		if (!empty($this->css)) {
			$lines[] = implode("\n", $this->css);
		}
		if (!empty($this->jsFiles[self::HEAD])) {
			$lines[] = implode("", $this->jsFiles[self::HEAD]);
		}
		if (!empty($this->js[self::HEAD])) {
			$lines[] = Html::js(implode("\n", $this->js[self::HEAD]));
		}
		return empty($lines) ? '' : implode("\n", $lines) . "\n";
	}
	
	/**
	 * Renders the content to be inserted at the beginning of the body section.
	 * The content is rendered using the registered JS code blocks and files.
	 * @return string the rendered content
	 */
	protected function renderBodyBeginHtml()
	{
		$lines = array();
		if (!empty($this->jsFiles[self::BODY_BEGIN])) {
			$lines[] = implode("", $this->jsFiles[self::BODY_BEGIN]);
		}
		if (!empty($this->js[self::BODY_BEGIN])) {
			$lines[] = Html::js(implode("\n", $this->js[self::BODY_BEGIN]));
		}
		return empty($lines) ? '' : implode("\n", $lines) . "\n";
	}
	
	/**
	 * Renders the content to be inserted at the end of the body section.
	 * The content is rendered using the registered JS code blocks and files.
	 * @return string the rendered content
	 */
	protected function renderBodyEndHtml()
	{
		$lines = array();
		if (!empty($this->jsFiles[self::BODY_END])) {
			$lines[] = implode("", $this->jsFiles[self::BODY_END]);
		}
		if (!empty($this->js[self::BODY_END])) {
			$lines[] = Html::js(implode("\n", $this->js[self::BODY_END]));
		}
		return empty($lines) ? '' : implode("\n", $lines) . "\n";
	}	

	public function render($view, $params = array())
	{
		$viewFile = $this->getViewPath($view);
		$content = $this->renderFile($viewFile, $params);
		$layoutFile = $this->getLayoutPath($this->layout);
		if($layoutFile !== false){
			return $this->renderFile($layoutFile, array_merge($params, array('content'=>$content)));
		}else{
			return $content;
		}
	}

	public function renderPartial($view, $params = array())
	{
		$viewFile = $this->getViewPath($view);
		return $this->renderFile($viewFile, $params, $this);
	}

	public function renderFile($_file_, $_params_ = array())
	{
		ob_start();
		ob_implicit_flush(false);
		extract($_params_, EXTR_OVERWRITE);
		require($_file_);
		return ob_get_clean();
	}
	
	public function getViewPath($path)
	{
		$path = strlen(pathinfo($path, PATHINFO_EXTENSION)) <= 0 ? $path.'.php' : $path;

		if(strpos($path, '../') === 0){

			// Then this should go from doc root
			return str_replace('../', DIRECTORY_SEPARATOR, glue::getPath('@app').$path);

		}elseif(strpos($path, '/')!==false){

			// Then this should go from views root (/application/views) because we have something like user/edit.php
			return str_replace('/', DIRECTORY_SEPARATOR, glue::getPath('@app').'/views/'.$path);

		}else{

			// Then lets attempt to get the cwd from the controller. If the controller is not set we use siteController as default. This can occur for cronjobs
			return str_replace('/', DIRECTORY_SEPARATOR, glue::getPath('@app').'/views/'.str_replace('Controller', '',
					glue::controller() instanceof \glue\Controller ? get_class(glue::controller()) : 'siteController').'/'.$path);
		}
	}

	public function getLayoutPath($path)
	{
		$path = strlen(pathinfo($path, PATHINFO_EXTENSION)) <= 0 ? $path.'.php' : $path;

		if(mb_substr($path, 0, 1) == '/'){

			// Then this should go from doc root
			return str_replace('/', DIRECTORY_SEPARATOR, glue::getPath('@app').$path);

		}else{

			// Then this should go from layouts root (/application/layouts) because we have something like user/blank
			return str_replace('/', DIRECTORY_SEPARATOR, glue::getPath('@app').'/layouts/'.$path);

		}
	}

	public function json_success($params,$exit=true)
	{

		$json='';
		if(is_string($params)){
			$json= json_encode(array('success' => true, 'message' => array($params)));
		}else{
			$json= json_encode(array_merge(array('success' => true), $params));
		}

		if($exit){
			echo $json;
			exit(0);
		}
		return $json;
	}

	public function json_error($params,$exit=true)
	{
		$json='';
		switch(true){
			case $params == self::DENIED:
				$json= json_encode(array('success' => false, 'message' => 'Action not Permitted'));
				break;
			case $params == self::LOGIN:
				$json= json_encode(array('success' => false, 'message' => 'You must login to continue'));
				break;
			case $params == self::UNKNOWN:
				$json= json_encode(array('success' => false, 'message' => 'An unknown error was encountered'));
				break;
			default:
				if(is_string($params)){
					$json= json_encode(array('success' => false, 'message' => $params));
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

	function createUrl($path = '/', $params = array(), $host = '/', $scheme = 'http')
	{
		return glue::http()->url($path, $params, $host, $scheme);
	}
	
	public function beforeAction($controller, $action)
	{
		return $this->trigger('beforeAction', array($controller, $action));
	}
	
	public function afterAction($controller, $action)
	{
		return $this->trigger('afterAction', array($controller, $action));
	}
	
	public function beforeRender($controller, $action)
	{
		return $this->trigger('beforeRender', array($controller, $action));
	}
	
	public function afterRender($controller, $action)
	{
		return $this->trigger('afterRender', array($controller, $action));
	}	
}