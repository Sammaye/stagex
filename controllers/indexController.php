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
		), 'model' => array('email' => 'sam.millman@googlemail.com')));
		$v->run();
		var_dump($v->getErrors());
		var_dump($v->getErrorCodes(array(
			'd_required||e_required' => 'Poop those fields are required'		
		)));

		//'e'=>1,'d'=>2,
		$this->title = "Welcome to the StageX Beta";
		if($_SESSION['logged']){
			glue::route('/stream/news');
		}else{
			echo $this->render('/index');
		}
	}
}