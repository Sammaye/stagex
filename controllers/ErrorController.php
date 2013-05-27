<?php
class errorController extends \glue\Controller{

	public $layout = "blank_page";

	function action_index(){
		$this->title = 'Error - StageX';
		echo $this->render('errors/general');
	}

	function action_notfound(){
		$this->title = '404 Error (Content Not Found) - StageX';
		echo $this->render('errors/404');
	}

	function action_forbidden(){
		$this->title = '403 Error (Access Denied) - StageX';
		echo $this->render('errors/403');
	}
}