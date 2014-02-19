<?php

function p($message)
{
	print $message . "\n";
}

require '../glue/components/vendor/autoload.php';

$client = new Elasticsearch\Client();
$init = false;

p("OK! Setting up Elastic Search, I guess");

if($client->indices()->exists(array('index' => 'main')) && in_array('--reindex', $_SERVER['argv'])){
	
	p("Deleting the index...");
	
	var_dump($client->indices()->delete(array('index' => 'main')));
	
	p("Sleeping 10 seconds to let the delete take effect");
	sleep(10);
}

if(!$client->indices()->exists(array('index' => 'main')) || in_array('--reindex', $_SERVER['argv'])){
	
	p("Creating the index...");
	
	var_dump($client->indices()->create(array(
		'index' => 'main',
		'body' => array(
			'settings' => array(
				'number_of_shards' => 5,
				'number_of_replicas' => 1,
				'analysis' => array(
					'analyzer' => array(
					'noStopFilter' => array(
						'tokenizer' => 'standard',
						'filter' => array("standard", "lowercase"),
					)
				)
			)
		)
	))));	
	
	$init = true;
	
	p("Sleeping 10 seconds to let the index take effect");
	sleep(10);	
}

if(in_array('--reindex', $_SERVER['argv']) || in_array('--remap', $_SERVER['argv']) || $init){
	
	p('Adding the mapping...');
	
	var_dump($client->indices()->putMapping(array(
	    'index' => 'main',
	    'type' => '_default_',
	    'body' => array(
	    	'_default_' => array(
		        'properties' => array(
			       	'title' => array(
				       	'type' => 'string',
				       	'analyzer' => 'noStopFilter'
			       	),
		        	'userId' => array(
		        		'type' => 'string',
		        		'index' => 'not_analyzed'
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
	)));
	
	var_dump($client->indices()->putMapping(array(
		'index' => 'main',
		'type' => 'help',
		'body' => array(
			'help' => array(
				'properties' => array(
					'title' => array(
						'type' => 'string',
						'analyzer' => 'noStopFilter'
					),
					'normalisedTitle' => array(
						'type' => 'string',
						'analyzer' => 'noStopFilter'
					),
					'path' => array(
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
	)));
	
	p('done adding the mapping');
}

p('done setting up Elastic Search...let\'s see if it works I guess');