<?php

require '/components/vendor/autoload.php';

$client = new Elasticsearch\Client();
//$client->indices()->delete(array('index' => 'main')); 
//exit();

var_dump($client->indices()->create(array(
    'index' => 'main',
    'body' => array(
        'settings' => array(
            'number_of_shards' => 5,
            'number_of_replicas' => 2,
            'analysis' => array(
                'analyzer' => array(
                    'noStopFilter' => array(
                        'tokenizer' => 'standard',
                        'filter' => array("standard", "lowercase"),
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
            ),
            'help' => array(
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