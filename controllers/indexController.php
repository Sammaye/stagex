<?php

class indexController extends \glue\Controller{

    public function authRules(){
        return array(
                array('allow', 'users' => array('*')),
                array("deny", "users"=>array("*")),
        );
    }

    public function action_index(){
        $this->title = "Welcome to the StageX Beta";
        if(glue::auth()->check('@')){
            glue::route('stream/news');
        }else{
            echo $this->render('/index');
        }
    }
}