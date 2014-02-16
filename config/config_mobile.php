<?php
return array(
	"debug" => true,

	'theme' => array(
		'@views',
		'@base'
	),

	'directories' => array(
		'app' => dirname(__DIR__),

		'views' => '@app/mobile/views',
		'layouts' => '@app/mobile/layouts',
			
		'base' => '@app/desktop/views',
	),

	'events' => array(
		'beforeRequest' => function(){}
	)
);