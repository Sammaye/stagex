<?php
/**
 * Main configuration
 *
 * This file denotes the configuration for most parts of the framework
 *
 * @author Sam Millman
 */

date_default_timezone_set('UTC');

return array(

	// This switches the debug mode
	"DEBUG"=>true,

	// App name // Can be used as title at times
	"name"=>'StageX',

	"pageDescription" => 'StageX is a video site. Share, enjoy, laugh, cry and remember the good times in life with video.',
	"pageKeywords" => 'video, sharing, social, watch, free, upload',

	// This preloads certain files into the framework
	"preload"=>array(
		"application/models/*",
		"glue/plugins/storage/mongo/*"
	),

	// load startup components. These components will be loaded at the start and always required before execution of any script.
	'startUp' => array(),

	// Defines modules which can be access like parts of the site.
	'modules' => array(),

	// This part houses all of the configuration settings for framework components
	'components' => array(

		'purifier' => array(
			'class' => 'purify',
			'path' => 'glue/plugins/purifier/purify.php'
		),

		'mailer' => array(
			'class' => 'mailer',
			'path' => 'glue/plugins/phpmailer/mailer.php'
		),

		'rbam' => array(
			'class' => 'RbamModule',
			'path' => 'glue/plugins/rbam/RbamModule.php',
			'defFile' => 'application/core/rbam.php'
		),

		// Glue session component for the users session
		"session"=>array(
			"class" => "session",
			"path" => "application/core/session.php",
			"timeout"=>5,
			"allowCookies"=>true,
			'store' => array(
				"class" => "MongoSession",
				"path" => "application/core/MongoSession.php"
			),
			'cookieDomain' => '.stagex-local.co.uk'
		),

		// MongoDB configuration settings
		"db"=>array(
			"connection"=>"mongodb://localhost:27017",
			"db" => "the_stage",
			"class"=>"GMongo",
			"path"=>"glue/plugins/storage/mongo/GMongo.php",
			'indexPath' => 'application/core/mongoIndexes.php',
			"persistent"=>true,
			"autoConnect"=>true
		),

		// Yes some MySQL
		"mysql"=>array(
			"host" => "localhost",
			"user" => "root",
			"password" => "samill2man",
			"db" => 'sphinx_index',
			"path"=>"glue/plugins/storage/mysql/GMySQL.php",
			"class"=>"GMySQL"
		),

		// Woo Sphinx!
		"sphinx"=>array(
			'class' => 'sphinx_searcher',
			'path' => 'glue/plugins/sphinx/sphinx_searcher.php',
			"host"=>"localhost",
			"port"=>9312,
			'indexes' => array(
				'main' => array(
					'type' => 'delta',
					'delta' => 'main_delta',
					'cursor' => 'MainSearch_SphinxCursor',
					'query_fields' => array( 'title', 'description', 'tags', 'author_name' ),
				),
				'help' => array(
					'cursor' => 'HelpSearch_SphinxCursor',
					'query_fields' => array( 'title', 'content', 'tags', 'path' ),
				)
			)
		),

		'sitemap' => array(
			'class' => 'sitemap',
			'path' => 'glue/plugins/sitemap/sitemap.php'
		),

		'facebook' => array(
			'class' => 'facebook_session',
			'path' => 'glue/plugins/facebook/facebook_session.php',
		  	'appId' => '455165987850786',
		  	'secret' => '6c6336958eec554bfb2326e6824ea427',
		),

		'twitter' => array(
			'class' => 'TwitterSession',
			'path' => 'glue/plugins/twitter/TwitterSession.php',
			'consumer_key' => "E1uIs3dzvlrodsj4R3I8w",
			'secret_key' => "HxbMV2giKXekGI41TXp2A2rJh9P5OroGCSxlEYPogwc",
			'callback' => "http://stagex.co.uk/autoshare/auth?network=twt"
		),

		'google' => array(
			'class' => 'google_session',
			'path' => 'glue/plugins/googleapi/google_session.php',
			'client_id' => '170938211589.apps.googleusercontent.com',
			'client_secret' => 'lTJpybuvyAD-zWTBI-mnyT1Q',
			'callback_uri' => 'http://stagex-local.co.uk/user/google_login'
		),

		'aws' => array(
			'class' => 'aws_bootstrap',
			'path' => 'glue/plugins/aws/aws_bootstrap.php',
			'key' => 'AKIAICYRUYXAXE3MTUXA',
			'secret' => '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',
			'bucket' => 'videos.stagex.co.uk',
			'input_queue' => 'https://us-west-2.queue.amazonaws.com/663341881510/stagex-uploadsQueue',
			'output_queue' => 'https://us-west-2.queue.amazonaws.com/663341881510/stagex-outputsQueue'
		),

		'crypt' => array(
			'class' => 'GCrypt',
			'path' => 'glue/plugins/GCrypt/GCrypt.php'
		),

		// Settings for how error should be handled
		"errorHandler"=>array(
			"output"=>array("email", "screen"),
			"emailAddresses"=>array("sam.millman@googlemail.com"),
			"action"=>"index/error"
		)
	),

	// Try to never use this! This part defines an alias of a plugin so it can take two different names. Should only ever be used for legacy code!
	'alias' => array(
		'roles' => 'rbam' // Denotes an alias by which this plugin can be referred to, only use for legacy code!
	),

	// Global variables which are accessible via glue::params('example')
	'params' => array(
		'rootUrl' => 'stagex-local.co.uk',
		'maxUpload' => 4294967296,
		'uploadBase' => '/',

		'maxVideoFileSize' => 524288000,

	),

	'errorPages' => array(
		'403' => 'error/forbidden',
		'404' => 'error/notfound',
		'*' => 'error' // This is generally used by the error handler but can be used anywhere
	)
);

// LinkedIn
//$consumer_key = "WP7tjwrppK5R7i_dRON8mv8lch5Yt2cXqKTMZll1zM16I1PISLc32Kc-e9EqkLiD",
//$consumer_secret = "cP4hc4-t9qdqnpCj2l9kTCEmhL9u63hCTdkJ8VBZxKPauHmDe48o1VhcB9lAbeP2",
