<?php
/**
 * Main configuration
 *
 * This file denotes the configuration for most parts of the framework
 *
 * @author Sam Millman
 */

if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])){
	$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];  // If from cloudflare lets switch it all
}

return array(

	// This switches the debug mode
	"DEBUG"=>false,

	"Minify_JS" => true,

	// This part houses all of the configuration settings for framework components
	'components' => array(

		'session' => array(
			'cookieDomain' => '.stagex.co.uk'
		),

		// MongoDB configuration settings
		//"db"=>array(
			//"connection"=>"mongodb://root:s4mi2llAmanMon2@localhost:27017", // Auth has been turned off for the min
		//),

		'facebook' => array(
			'class' => 'facebook_session',
			'path' => 'glue/plugins/facebook/facebook_session.php',
		  	'appId' => '',
		  	'secret' => '',
		),

		'twitter' => array(
			'class' => 'TwitterSession',
			'path' => 'glue/plugins/twitter/TwitterSession.php',
			'consumer_key' => "",
			'secret_key' => "",
			'callback' => "http://stagex.co.uk/autoshare/auth?network=twt"
		),

		'google' => array(
			'class' => 'google_session',
			'path' => 'glue/plugins/googleapi/google_session.php',
			'client_id' => '',
			'client_secret' => '',
			'callback_uri' => 'http://www.stagex.co.uk/user/google_login'
		),

		// Yes some MySQL
		"mysql"=>array(
			"password" => "",
		)
	),

	// Global variables which are accessible via glue::params('example')
	'params' => array(
		'rootUrl' => 'www.stagex.co.uk',
		'imagesUrl' => 'images.stagex.co.uk/',
		'thumbnailBase' => 'images.stagex.co.uk/videos/',
		'uploadBase' => 'upload.stagex.co.uk',
	)
);
