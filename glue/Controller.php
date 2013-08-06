<?php

namespace glue;

use glue,
	\glue\Html;

class Controller {

	const HEAD = 1;
	const BODY_BEGIN = 2;
	const BODY_END = 3;

	const DENIED = 1;
	const LOGIN = 2;
	const UNKNOWN = 3;

	public $tpl_head = '<![CDATA[GLUE-BLOCK-HEAD]]>';
	public $tpl_body_begin = '<![CDATA[GLUE-BLOCK-BODY]]>';
	public $tpl_body_end = '<![CDATA[GLUE-BLOCK-BODY_END]]>';

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
	
	function getName(){
		$class=get_class($this);
		$parts=explode('\\',$class);
		return end($parts);
	}

	function authRules(){ return array(); }

	public function __construct(){
		$this->title = $this->title ?: glue::$name;
		if(glue::$description!==null)
			$this->metaTag('description', glue::$description);
		if(glue::$keywords!==null)
			$this->metaTag('keywords', glue::$keywords);
	}

	function cssFile($map, $path, $media = null){
		if($path===null)
		$this->cssFiles[basename($map,'.css')] = Html::cssFile($path,$media);
		else
		$this->cssFiles[$map] = Html::cssFile($path, $media);
	}

	function css($map, $script, $media = null){
		$this->css[$map] = Html::css($media, $script);
	}

	function jsFile($map, $path=null, $POS = self::HEAD){
		if($path===null)
			$this->jsFiles[$POS][basename($map,'.js')] = Html::jsFile($map);
		else
			$this->jsFiles[$POS][$map] = Html::jsFile($path);
	}

	function js($map, $script, $POS = self::BODY_END){
		$this->js[$POS][$map] = $script;
	}

	function metaTag($name, $html){
		$this->metaTags[$name] = Html::metaTag($html,array('name'=>$name));
	}

	function linkTag($options,$key=null){
		if($key===null)
			$this->linkTags[] = Html::linkTag($content);
		else
			$this->linkTags[$name] = Html::linkTag($content);
	}

	public function render($view, $params = array()){
		$viewFile = $this->getViewPath($view);
		$content = $this->renderFile($viewFile, $params);
		$layoutFile = $this->getLayoutPath($this->layout);
		if ($layoutFile !== false) {
			return $this->renderFile($layoutFile, array_merge($params,array('content'=>$content)));
		} else {
			return $content;
		}
	}

	public function renderPartial($view, $params = array()){
		$viewFile = $this->getViewPath($view);
		return $this->renderFile($viewFile, $params, $this);
	}

	public function renderFile($_file_, $_params_ = array()){
		ob_start();
		ob_implicit_flush(false);
		extract($_params_, EXTR_OVERWRITE);
		require($_file_);
		return ob_get_clean();
	}

	/**
	 * Marks the beginning of an HTML page.
	 */
	public function beginPage(){
		ob_start();
		ob_implicit_flush(false);
		glue::trigger('beforeRender');
	}

	public function head(){
		echo $this->tpl_head;
	}

	public function beginBody(){
		echo $this->tpl_body_begin;
	}

	public function endBody(){
		echo $this->tpl_body_end;
	}

	/**
	 * Marks the ending of an HTML page.
	 */
	public function endPage(){
		glue::trigger('afterRender');

		$content = ob_get_clean();
		echo strtr($content, array(
			$this->tpl_head => $this->renderHeadHtml(),
			$this->tpl_body_begin => $this->renderBodyBeginHtml(),
			$this->tpl_body_end => $this->renderBodyEndHtml(),
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
			$lines[] = implode("\n", $this->cssFiles);
		}
		if (!empty($this->css)) {
			$lines[] = implode("\n", $this->css);
		}
		if (!empty($this->jsFiles[self::HEAD])) {
			$lines[] = implode("\n", $this->jsFiles[self::HEAD]);
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
			$lines[] = implode("\n", $this->jsFiles[self::BODY_BEGIN]);
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
			$lines[] = implode("\n", $this->jsFiles[self::BODY_END]);
		}
		if (!empty($this->js[self::BODY_END])) {
			$lines[] = Html::js(implode("\n", $this->js[self::BODY_END]));
		}
		return empty($lines) ? '' : implode("\n", $lines) . "\n";
	}

	function getViewPath($path){

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
					glue::$controller instanceof \glue\Controller ? get_class(glue::$controller) : 'siteController').'/'.$path);
		}
	}

	function getLayoutPath($path){

		$path = strlen(pathinfo($path, PATHINFO_EXTENSION)) <= 0 ? $path.'.php' : $path;

		if(mb_substr($path, 0, 1) == '/'){

			// Then this should go from doc root
			return str_replace('/', DIRECTORY_SEPARATOR, glue::getPath('@app').$path);

		}else{

			// Then this should go from layouts root (/application/layouts) because we have something like user/blank
			return str_replace('/', DIRECTORY_SEPARATOR, glue::getPath('@app').'/layouts/'.$path);

		}
	}

	function json_success($params,$exit=true){

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

	function json_error($params,$exit=true){
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

	function compressCSS($buffer) {
		/* remove comments */
		$buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
		/* remove tabs, spaces, newlines, etc. */
		$buffer = preg_replace('/(?:\s\s+|\n|\t)/', '', $buffer);
		return $buffer;
	}
	
	function createUrl($path = '/', $params = array(), $host = '/', $scheme = 'http'){
		return glue::http()->url($path, $params, $host, $scheme);
	}
}
