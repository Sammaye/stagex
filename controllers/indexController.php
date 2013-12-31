<?php

class indexController extends \glue\Controller
{
	public function behaviours()
	{
		return array(
			'auth' => array(
				'class' => 'glue\Auth',
				'rules' => array(
					array('allow', 'users' => array('*')),
					array("deny", "users"=>array("*")),
				)
			)
		);
	}

    public function action_index()
    {
        $this->title = "Welcome to the StageX Beta";
        if(glue::auth()->check('@')){
            glue::runAction('stream/news');
        }else{
            echo $this->render('/index');
        }
    }
}