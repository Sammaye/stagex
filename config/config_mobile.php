<?php
if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])){
	$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];  // If from cloudflare lets switch it all
}

return array(
	"debug"=>true,

	'theme' => array(
		'@views',
		'@base'
	),

	'directories' => array(
		'app' => dirname(__DIR__),

		'views' => '@app/mobile/views',
		'layouts' => '@app/mobile/layouts',
			
		'base' => '@app/desktop/views',
	)
);