<?php
class errorController extends \glue\Controller{

	public $layout = "blank_page";

	function action_index(){
		$this->title = 'Error - StageX';
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
		echo $this->render('errors/general');
	}

	function action_notfound(){
		$this->title = '404 Error (Content Not Found) - StageX';
		header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
		echo $this->render('errors/notfound');
	}

	function action_forbidden(){
		$this->title = '403 Error (Access Denied) - StageX';
		header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
		echo $this->render('errors/forbidden');
	}
}