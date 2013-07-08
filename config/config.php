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

	//'www' => 'stagex-local.co.uk',

	// This switches the debug mode
	"DEBUG" => true,

	"description" => 'StageX is a video site. Share, enjoy, laugh, cry and remember the good times in life with video.',
	"keywords" => 'video, sharing, social, watch, free, upload',

	// Global variables which are accessible via glue::params('example')
	'params' => array(
		'defaultAllowedBandwidth' => 4294967296,
		'uploadBase' => '/',
		'maxFileSize' => 524288000,
	),

	// load startup components. These components will be loaded at the start and always required before execution of any script.
	// Good for binding things like logs and auth modules etc to before controller actions
	'startUp' => array(
		'auth'
	),

	// This part houses all of the configuration settings for framework components
	'components' => array(

		'user' => array(
			'class'=>'app\\models\\User'
		),

		/**
		 * Configures the session handler
		 */
		"session"=>array(
			"class" => "glue\\Session",
			"timeout"=>5,
			"allowCookies"=>true,
			'cookieDomain' => '.stagex-local.co.uk'
		),

		// MongoDB configuration settings
		"db"=>array(
			"class"=>"glue\db\Client",
			"server"=>"mongodb://localhost:27017",
			"db" => "stagex",

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

		/**
		 * This holds the auth configuration. Of course this particular auth module uses programmed auth roles, better to make
		 * an extension to store this shit in a database...
		 */
		'auth' => array(

			/**
			 * The location of the auth file
			 */
			'class' => '\\glue\\Auth',

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
					if(glue::session()->authed){
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
					if(glue::session()->authed){
						return true;
					}

					if(glue::http()->isAjax()){
						GJSON::kill(GJSON::LOGIN);
						exit();
					}else{
						\glue\Html::setErrorFlashMessage('You must be logged in to access this page');
						header('Location: /user/login?nxt='.Glue::http()->createUrl('SELF', array(), null));
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

		/**
		 * Controls the error handler
		 */
		'errorHandler' => array(

			// To change error reporting please do it in the php.ini, this will not deal
			// with the level of error reporting, only how they are displayed

			"emails" => array("sam.millman@googlemail.com"),

			"action" => "index/error",

			'log' => false,
			'logger' => function($exception){
				return true;
			}
		),


		// Yes some MySQL
		"mysql"=>array(
				"host" => "localhost",
				"user" => "root",
				"password" => "samill2man",
				"db" => 'sphinx_index',
				"class"=>"glue\\components\\mysql\\Record"
		),

		// Woo Sphinx!
		"sphinx"=>array(
				'class' => 'glue\\components\\Sphinx\\Sphinx',
				"host"=>"localhost",
				"port"=>9312,
				'indexes' => array(
					'main' => array(
						'delta' => 'main_delta',
						'fields' => array( 'title', 'description', 'tags', 'author_name' ),
					),
					'help' => array(
						'cursor' => 'HelpSearch_SphinxCursor',
						'fields' => array( 'title', 'content', 'tags', 'path' ),
					)
				)
		),

		'facebook' => array(
				'class' => 'glue\\components\\facebook\\Session',
				'appId' => '455165987850786',
				'secret' => '6c6336958eec554bfb2326e6824ea427',
				'redirect_uri' => 'http://www.stagex.co.uk/user/fbLogin'
		),

		'twitter' => array(
				'class' => 'glue\\components\\twitter\\Session',
				'consumer_key' => "E1uIs3dzvlrodsj4R3I8w",
				'secret_key' => "HxbMV2giKXekGI41TXp2A2rJh9P5OroGCSxlEYPogwc",
				'callback' => "http://stagex.co.uk/autoshare/auth?network=twt"
		),

		'google' => array(
				'class' => 'glue\\components\\google\\Session',
				'client_id' => '170938211589.apps.googleusercontent.com',
				'client_secret' => 'lTJpybuvyAD-zWTBI-mnyT1Q',
				'callback_uri' => 'http://stagex-local.co.uk/user/googleLogin'
		),

		'aws' => array(
			'class' => 'glue\\components\\aws\\aws',
			'key' => 'AKIAICYRUYXAXE3MTUXA',
			'secret' => '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',
			'bucket' => 'videos.stagex.co.uk',
			'input_queue' => 'https://us-west-2.queue.amazonaws.com/663341881510/stagex-uploadsQueue',
			'output_queue' => 'https://us-west-2.queue.amazonaws.com/663341881510/stagex-outputsQueue'
		),

		'purifier' => array(
				'class' => 'glue/plugins/purifier/purify.php'
		),

		'mailer' => array(
				'class' => 'glue\\components\\phpmailer\\mailer'
		),

		'sitemap' => array(
				'class' => 'glue\\components\\sitemap\\sitemap'
		),
	),

	/**
	 * These events are bound at startup to the framework
	 */
	'events' => array(

		/**
		 * Certain error cases
		 */
		'404' => function(){
			glue::route('error/notfound');
		},
		'403' => function(){
			glue::route('error/forbidden');
		},
		'500' => function(){
			glue::route('error');
		},

		/**
		 * Hooks for before page load and after page load
		 */
		'beforeRequest' => function(){},
		'afterRequest' => function(){
			if(!glue::http()->isAjax()){
				$size = memory_get_peak_usage(true);
				$unit=array('','KB','MB','GB','TB','PB');
				echo '<div class="clear"></div>';
				var_dump($size/pow(1024,($i=floor(log($size,1024)))));
			}
		}
	),

	/**
	 * This will include files directly into the global scope
	 */
	'include' => array(
		'@glue/helpers.php'
	),

	/**
	 * This will add namespaces as Aliases to our autoloader
	 *
	 * Good if you have a complicated folder structure due to composer or something and want to keep Psr-0 notation while
	 * making your life bareable
	 */
	'namespaces' => array(
		'mongoglue' => '@app/glue/components/mongodb'
	),

	/**
	 * This will catalogue directories for autoloading from. It won't actually read from the directories
	 * in a eager manner, it will just store these as places to look within the global namespace.
	 */
	'directories' => array(
		'@app' => dirname(__DIR__),

		// These are not required but are here to show you how it is done
		'@controllers' => 'controllers',
		'@models' => 'models', // Models are added to global scope
	),

	/**
	 * These are class aliases to make life easier. The framework itself will reference these statically as such killing these will
	 * not harm the framework workings
	 */
	'aliases' => array(
		"html"					=> "glue\\Html",
		"Collection"			=> "glue\\Collection",

		/**
		 * Core utils
		 */
		"DateTime"				=> "glue\\util\\DateTime",
		"Crypt"					=> "glue\\util\\Crypt",
		"JSON"					=> "glue\\util\\JSON",
		"JSMin" 				=> "glue\\util\\JSMin",

		"Validation"			=> "glue\\Validation",

		/**
		 * Core Widgets
		 */
		"GridView"				=> "glue\\widgets\\GridView",
		"ListView"				=> "glue\\widgets\\ListView",
	)
);

// LinkedIn
//$consumer_key = "WP7tjwrppK5R7i_dRON8mv8lch5Yt2cXqKTMZll1zM16I1PISLc32Kc-e9EqkLiD",
//$consumer_secret = "cP4hc4-t9qdqnpCj2l9kTCEmhL9u63hCTdkJ8VBZxKPauHmDe48o1VhcB9lAbeP2",
