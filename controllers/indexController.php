<?php
class indexController extends \glue\Controller{

	public function authRules(){
		return array(
			array('allow', 'users' => array('*')),
			array("deny", "users"=>array("*")),
		);
	}

	public function action_index(){

		$v=new \glue\Validation(array('rules'=>array(
			array('d,e', 'required', 'message' => 'd and e are required'),
			array('email', 'email', 'message'=>'needs an email')
		), 'model' => array('e'=>1,'d'=>2, 'email' => 'sam.millman@googlemail.com')));
		$v->run();
		var_dump($v->getErrors());


//		$this->pageTitle = "Welcome to the StageX Beta";
//		if($_SESSION['logged']){
//			glue::route('/stream/news');
//		}else{
			//$this->render('/index');
//		}
	}
}