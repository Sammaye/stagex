<?php
return array(

	// This switches the debug mode
	"debug" => false,

	"minify" => true,

	// This part houses all of the configuration settings for framework components
	'components' => array(

		'session' => array(
			'cookieDomain' => '.stagex.co.uk'
		),

		'facebook' => array(
			'class' => 'glue\\components\\facebook\\Session',
		  	'appId' => '153062384724422',
		  	'secret' => '36e823e43433b6630e827d9cce49cf5d',
		),

		'google' => array(
			'class' => 'glue\\components\\google\\Session',
			'client_id' => '1084037742147.apps.googleusercontent.com',
			'client_secret' => 'xKZbxkiUoWrTZV5T423zqbj2',
			'callback_uri' => 'http://www.stagex.co.uk/user/google_login'
		),
	),

	// Global variables which are accessible via glue::params('example')
	'params' => array(
		'rootUrl' => 'www.stagex.co.uk',
		'imagesUrl' => 'images.stagex.co.uk/',
		'thumbnailBase' => 'images.stagex.co.uk/videos/',
		'uploadBase' => 'upload.stagex.co.uk',
	)
);
