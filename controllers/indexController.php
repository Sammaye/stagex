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

        // 		$sphinx=glue::sphinx()
        // 		->match(array('title', 'description', 'tags', 'author_name'),glue::http()->param('query',''))
        // 		->match('type','video')
        // 		->match('uid',strval($user->_id))
        // 		->sort(SPH_SORT_TIME_SEGMENTS, "date_uploaded")
        // 		->filter('deleted', array(1), true)
        // 		->page(glue::http()->param('page',1));
        // 		if(!glue::user()->equals($user)){
        // 			$sphinx->filter('listing',array(1, 2), true);
        // 		}

        $client = new Elasticsearch\Client();
        //var_dump($client);
        //$client->indices()->delete(array('index' => 'main')); //exit();
        /*
        var_dump($client->indices()->create(array(
                'index' => 'main',
                'body' => array(
                        'settings' => array(
                                'number_of_shards' => 5,
                                'number_of_replicas' => 2,
                                'analysis' => array(
                                    'analyzer' => array(
                                        'noStopFilter' => array(
                                            'tokenizer'    => 'standard',
                                            'filter'       => array("standard", "lowercase"),
                                        )
                                    ),   
                                    )                             
                        ),
                        'mappings' => array(
                            'video' => array(
                                'properties' => array(
                                        'title' => array(
                                                'type' => 'string',
                                                'analyzer' => 'noStopFilter'
                                        ),
                                        'username' => array(
                                                'type' => 'string',
                                                'analyzer' => 'noStopFilter'
                                        ),
                                        'tags' => array(
                                            'type' => 'string',
                                            'analyzer' => 'noStopFilter'
                                        )
                                  )     
                              ),
                              'playlist' => array(
                                      'properties' => array(
                                              'title' => array(
                                                      'type' => 'string',
                                                      'analyzer' => 'noStopFilter'
                                              ),
                                              'username' => array(
                                                      'type' => 'string',
                                                      'analyzer' => 'noStopFilter'
                                              ),
                                              'tags' => array(
                                                      'type' => 'string',
                                                      'analyzer' => 'noStopFilter'
                                              )
                                      )
                              ),
                              'user' => array(
                                      'properties' => array(
                                              'title' => array(
                                                      'type' => 'string',
                                                      'analyzer' => 'noStopFilter'
                                              ),
                                              'username' => array(
                                                      'type' => 'string',
                                                      'analyzer' => 'noStopFilter'
                                              ),
                                              'tags' => array(
                                                      'type' => 'string',
                                                      'analyzer' => 'noStopFilter'
                                              )
                                      )
                              )                              
                        )
                )
        )));
        */
        //exit();
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