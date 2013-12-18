<?php

require glue::getPath('@glue').'/components/vendor/autoload.php';

class indexController extends \glue\Controller{

    public function authRules(){
        return array(
                array('allow', 'users' => array('*')),
                array("deny", "users"=>array("*")),
        );
    }

    public function action_index(){

        $client = new Elasticsearch\Client();
        //$client->index(array( 'index' =>  'main', 'type' => 'video', 'id' => 13, 'body' => array('username' => 'N.W.A Music', 'created' => date('c'), 'tags' => array('weekend', 'awesome', 'party'))));
        //$client->index(array( 'index' =>  'main', 'type' => 'video', 'id' => 14, 'body' => array('username' => 'cheese', 'about' => 'the poop', 'created' => date('c'))));
        //$client->index(array( 'index' =>  'main', 'type' => 'video', 'id' => 15, 'body' => array('username' => 'the-dude', 'created' => date('c'))));
        $res=$client->search(array('index' => 'main', 'body' => array(
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