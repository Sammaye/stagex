<?php
class GController {

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
}
