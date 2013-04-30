<?php
class errorController extends GController{

	public $layout = "blank_page";

	function action_index(){
		$this->pageTitle = 'Error - StageX';
		$this->render('errors/general');
	}

	function action_notfound(){
		$this->pageTitle = '404 Error (Content Not Found) - StageX';
		$this->render('errors/404');
	}

	function action_forbidden(){
		$this->pageTitle = '403 Error (Access Denied) - StageX';
		$this->render('errors/403');
	}
}