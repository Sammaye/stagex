<?php

class indexController extends \glue\Controller{

    public function authRules(){
        return array(
                array('allow', 'users' => array('*')),
                array("deny", "users"=>array("*")),
        );
    }

    public function action_index(){

        $res = glue::elasticSearch()->search(
                array('body' => array(
                        'query' => array(
                                'filtered' => array(
                                        'query' => array(
                                                'bool' => array(
                                                        'should' => array(
                                                                 
                                                                array('prefix' => array('username' => 'the')),

                                                                array('prefix' => array('username' => 'n')),
                                                                array('prefix' => array('username' => 'm')),
                                                                array('match' => array('about' => 'the')),
                                                        )
                                                )
                                        ),
                                        'filter' => array(
                                                'and' => array(
                                                        array('range' => array('created' => array('gte' => date('c',time()-3600), 'lte' => date('c',time()+3600))))
                                                )
                                        ),
                                        'sort' => array()
                                )
                        )
                )));
        var_dump($res); //exit();
        foreach($res['hits']['hits'] as $hit)
            var_dump($hit);

        exit();
        $this->title = "Welcome to the StageX Beta";
        if(glue::auth()->check('@')){
            glue::route('stream/news');
        }else{
            echo $this->render('/index');
        }
    }
}