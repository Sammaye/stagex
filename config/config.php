<?php
/**
 * Main configuration
 *
 * This file denotes the configuration for most parts of the framework
 */

date_default_timezone_set('UTC');

return array(

	// App name // Can be used as title at times
	"name"=>'StageX',

	'url' => 'stagex-local.co.uk',

	"description" => 'StageX is a video site. Share, enjoy, laugh, cry and remember the good times in life with video.',
	"keywords" => 'video, sharing, social, watch, free, upload',

	// Global variables which are accessible via glue::params('example')
	'params' => array(
		'maxUpload' => 4294967296,
		'uploadBase' => '/',
		'maxVideoFileSize' => 524288000,
	),

	// load startup components. These components will be loaded at the start and always required before execution of any script.
	// Good for binding things like logs and auth modules etc to before controller actions
	'startUp' => array(
		'Auth'
	),

	// Defines modules which can be access like parts of the site.
	'modules' => array(),

	// This part houses all of the configuration settings for framework components
	'components' => array(

		// MongoDB configuration settings
		"db"=>array(
			"connection"=>"mongodb://localhost:27017",
			"db" => "the_stage",
			"class"=>"GMongo",
			"path"=>"glue/plugins/storage/mongo/GMongo.php",
			'indexPath' => 'application/core/mongoIndexes.php',
			"persistent"=>true,
			"autoConnect"=>true,

			/**
			 * These are indexes that are used in MongoDB indexed by collection name.
			 * Note: Due to how bad indexing can be if the indexes change I recommend you don't do this tbh
			 */
			"indexes" => array(
				'users' => array(
					array(array('email' => 1)),
					array(array('_id' => 1, 'username' => 1)),
					array(array('fb_uid' => 1)),
					array(array('username' => 1))
				),

				'subscription' => array(
					array(array('from_id' => 1, 'to_id' => 1)),
					array(array('to_id' => 1)),
					array(array('from_id' => 1))
				),

				'videos' => array(
					array(array('title' => 1, 'user_id' => 1)),
					array(array('user_id' => 1)),
					array(array('file' => 1, 'user_id' => 1)),
					array(array('state' => 1))
				),

				'videoresponse' => array(
					array(array('vid' => 1)),
					array(array('vid' => 1, 'ts' => 1)),
					array(array('path' => 1)),
					array(array('vid' => 1, 'path' => 1))
				),

				'videoresponse_likes' => array(
					array(array('user_id' => 1, 'response_id' => 1)),
					array(array('video_id' => 1))
				),

				'stream' => array(
					array(array('stream_type' => 1, 'user_id' => 1, 'type' => 1)),
					array(array('stream_type' => 1, 'user_id' => 1, 'type' => 1, 'ts' => 1)),
					array(array('_id' => 1, 'user_id' => 1)),
					array(array('user_id' => 1))
				),

				'help' => array(
					array(array('t_normalised' => 1)),
					array(array('title' => 1, 'path' => 1, 'type' => 1)),
					array(array('title' => 1, 'path' => 1, 'type' => 1))
				),

				'video_likes' => array(
					array(array('user_id' => 1, 'item' => 1))
				),

				'report.video' => array(
					array(array('vid' => 1, 'uid' => 1))
				),

				'playlists' => array(
					array(array('_id' => 1, 'title' => 1)),
					array(array('_id' => 1, 'user_id' => 1, 'title' => 1)),
					array(array('title' => 1)),
					array(array('user_id' => 1)),
				),

				'playlist_likes' => array(
					array(array('user_id' => 1, 'item' => 1))
				),

				'watched_history' => array(
					array(array('user_id' => 1)),
					array(array('user_id' => 1, 'item' => 1))
				),

				'image_cache' => array(
					array(array('object_id' => 1, 'width' => 1, 'height' => 1, 'type' => 1))
				)
			)
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
	),

	/**
	 * Configures the session handler
	 */
	"session"=>array(
		"timeout"=>5,
		"allowCookies"=>true,
		'cookieDomain' => '.stagex-local.co.uk'
	),

	/**
	 * Controls the error handler
	 */
	'errors' => array(
		'report' => 'E_ALL',

		"output" => array("email", "screen"),
		"emails" => array("sam.millman@googlemail.com"),

		"action" => "index/error",

		'log' => false,

		// This switches the debug mode
		"DEBUG" => true,

		'logger' => function($exception){
			return true;
		}
	),

	/**
	 * These events are bound at startup to the framework
	 */
	'events' => array(

		/**
		 * Certain error cases
		 */
		'404' => function(){
			\glue\app()->http->redirect('error/notfound');
		},
		'403' => function(){
			\glue\app()->http->redirect('error/forbidden');
		},
		'500' => function(){
			\glue\app()->http->redirect('error');
		},

		/**
		 * Hooks for before page load and after page load
		 */
		'before' => function(){},
		'after' => function(){}
	),

	/**
	 * This holds the auth configuration. Of course this particular auth module uses programmed auth roles, better to make
	 * an extension to store this shit in a database...
	 */
	'auth' => array(

		/**
		 * These are shortcuts used to make short hand notation to certain commonly used filters
		 */
		'shortcuts' => array(
			'@' 	=> 'roleLogged',
			'@*' 	=> "loginRequired",
			'^@' 	=> "roleAdmin",
			'*' 	=> "roleUser",
			'^' 	=> "Owns"
		),

		/**
		 * These are the filters used to determine if they are authorised to actually access what they wanna
		 */
		'filters' => array(
			'roleUser' => function(){
				return true;
			},

			'roleLogged' => function(){
				if($_SESSION['logged']){
					return true;
				}
				return false;
			},
			'canView' => function($item){
				if(!$item){
					return false;
				}

				if($item->deleted){
					return false;
				}

				if($item->author instanceof User){
					if((bool)$item->author->deleted){
						return false;
					}
				}

				if($item->listing){
					if($item->listing == 3 && (strval(glue::session()->user->_id) != strval($item->author->_id)))
						return false;
				}
				return true;
			},
			'deletedView' => function($item){
				if(!$item){
					return false;
				}

				if($item->deleted){
					return false;
				}

				if($item->author instanceof User){
					if((bool)$item->author->deleted){
						return false;
					}
				}elseif(!$item instanceof User){
					return false;
				}
				return true;
			},
			'deniedView' => function($item){
				if($item->listing){
					if($item->listing == 3 && strval(glue::session()->user->_id) != strval($item->author->_id))
						return false;
				}
				return true;
			},
			'loginRequired' => function(){
				if($_SESSION['logged']){
					return true;
				}

				if(glue::http()->isAjax()){
					GJSON::kill(GJSON::LOGIN);
					exit();
				}else{
					html::setErrorFlashMessage('You must be logged in to access this page');
					header('Location: /user/login?nxt='.Glue::url()->create('SELF', array(), ''));
					exit();
				}
				return false;
			},

			'roleAdmin' => function(){
			  	if(Glue::session()->user->group == 10 || Glue::session()->user->group == 9){
			  		return true;
			  	}
			  	return false;
			},

			'Owns' => function($object){
				if(is_array($object)){
					foreach($object as $item){
						if(strval(Glue::session()->user->_id) == strval($item->user_id)){
							return true;
						}
					}
				}elseif($object instanceof MongoDocument){
					if(strval(Glue::session()->user->_id) == strval($object->user_id)){
						return true;
					}
				}
				return false;
			},

			/**
			 * Ajax is class as an auth item, note this in case you do not use the inbuilt auth
			 */
			'ajax' => function(){
				if(glue::http()->isAjax()){
					return true;
				}
				return false;
			}
		)

	),

	'preLoad' => array(
		'glue/Globals'
	),

	/**
	 * This will add namespaces as Aliases to our autoloader
	 *
	 * Good if you have a complicated folder structure due to composer or something and want to keep Pr0 notation while
	 * making your life bareable
	 */
	'namespaces' => array(
		'mongoglue' => 'glue\\plugins\\mongodb'
	),

	/**
	 * This will catalogue directories for autoloading from. It won't actually read from the directories
	 * in a eager manner, it will just store these as places to look within the global namespace.
	 */
	'dirctories' => array(
		"models", // Models are added to global scope
	),

	/**
	 * These are class aliases to make life easier, there is no mapping of this stored in the framework, this is
	 * the mapping ready for you to break.
	 *
	 * If you wish to override one of these files then you can just replace the default value here for your version
	 */
	'aliases' => array(
		"Controller" 			=> "glue\\Controller",
		"View" 					=> "glue\\View",
		"ErrorHandler" 			=> "glue\\ErrorHandler",
		"Collection"			=> "glue\\Collection",
		"Model"					=> "glue\\Model",
		"ModelBehaviour"		=> "glue\\Model",
		"Validators"			=> "glue\\Validators",
		"Validator"				=> "glue\\Validator",
		"Http"					=> "glue\\Http",
		"Widget" 				=> "glue\\Widget",
		"ApplicationComponent" 	=> "glue\\ApplicationComponent",
		"Html"					=> "glue\\Html",
		"Auth"					=> "glue\\Auth",

		/**
		 * Core utils
		 */
		"DateTime"				=> "glue\\util\\DateTime",
		"Crypt"					=> "glue\\util\\Crypt",
		"JSON"					=> "glue\\util\\JSON",
		"JSMin" 				=> "glue\\util\\JSMin",

		"CoreValidators"		=> "glue\\CoreValidators",

		/**
		 * Core Widgets
		 */
		"GridView"				=> "glue\\widgets\\GridView",
		"ListView"				=> "glue\\widgets\\ListView",

		/**
		 * Custom session handling
		 */
		'Session' 				=> "extended\\Session",
		'SessionStore'			=> "extended\\MongoSession"
	)
);

// LinkedIn
//$consumer_key = "WP7tjwrppK5R7i_dRON8mv8lch5Yt2cXqKTMZll1zM16I1PISLc32Kc-e9EqkLiD",
//$consumer_secret = "cP4hc4-t9qdqnpCj2l9kTCEmhL9u63hCTdkJ8VBZxKPauHmDe48o1VhcB9lAbeP2",
