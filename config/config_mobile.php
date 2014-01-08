<?php
if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])){
	$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];  // If from cloudflare lets switch it all
}

return array(
	"debug"=>true,
	'directories' => array(
		'app' => dirname(__DIR__),
		'views' => '@app/views_m',
		'layouts' => '@views/layouts',
	)
);