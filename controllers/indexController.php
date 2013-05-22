<?php
class indexController extends \glue\Controller{

	public function authRules(){
		return array(
			array('allow', 'users' => array('*')),
			array("deny", "users"=>array("*")),
		);
	}

	public function action_index(){

//		$this->pageTitle = "Welcome to the StageX Beta";
//		if($_SESSION['logged']){
//			glue::route('/stream/news');
//		}else{
			$this->render('/index');
//		}
	}
}