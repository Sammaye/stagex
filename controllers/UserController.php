<?php

use glue\Controller;
use glue\Json;
use glue\Html;
use glue\util\Crypt;
use glue\components\elasticsearch\Query;
use app\models\LoginForm;
use app\models\RecoverForm;
use app\models\User;
use app\models\Follower;
use app\models\Video;
use app\models\Playlist;

class UserController extends Controller
{
	public $defaultAction='videos';
	public $title = 'Your Stuff - StageX';
	public $tab = "settings";

	public function behaviours()
	{
		return array(
			'auth' => array(
				'class' => 'glue\Auth',
				'rules' => array(
					array('allow',
						'actions' => array('create', 'login', 'view', 'recover', 'viewVideos', 'viewPlaylists', 'fbLogin', 'googleLogin'),
						'users' => array('*')
					),
					array('allow', 'actions' => '*', 'users' => array('@*')),
					array("deny", "users"=>array("*"))
				)
			)
		);
	}

	public function action_create()
	{
		$this->title = 'Create a new StageX Account';

		$model = new User;
		if(isset($_POST['User'])){
			$model->setRule(array('csrfToken', 'csrf', 'message' => 'We could not verify the source of your post. Please use the submit button to submit the form.'));
			$model->attributes=$_POST['User'];
			if($model->validate()&&$model->save()){
				if(glue::session()->login($model->email, '', true, false)){
					glue::http()->redirect("/user");
				}else{
					$model->setError("Login failed, however, it seems you are saved to our system so please try to login manually.");
				}
			}
		}
		echo $this->render("create", array("model" => $model));
	}

	public function action_login()
	{
		$this->title = "Login to your StageX Account";

		$model = new LoginForm();
		$model->attributes = isset($_POST['LoginForm']) ? $_POST['LoginForm'] : array();

		/** Count how many times the user has logged in over 5 mins */
		$loginAttempts = Glue::session()->getLogCollection()->findOne(array("email"=>$model->email, "ts"=>array("\$gt"=>new MongoDate(time()-(60*5)))));
		if($loginAttempts['c'] > 4){
			$model->setScenario('captcha');
		}

		if(isset($_POST['LoginForm'])){
			if($model->validate()){
				if(glue::session()->login($model->email, $model->password, $model->remember)){
					if(isset($_GET['nxt'])){
						glue::http()->redirect(glue::http()->param('nxt'));
					}else{
						glue::http()->redirect("/");
					}
				}else{
					$model->setError('email', glue::session()->getError());
				}
			}
		}
		echo $this->render('user/login', array('model' => $model, 'attempts' => $loginAttempts['c']));
	}

	public function action_fbLogin()
	{
		$this->title = 'Logging into Stagex';

		$fb_user = glue::facebook()->getCurrentUser();
		if(!$fb_user){
			glue::http()->redirect('/user/login');
		}

		if(!$fb_user['verified']){
			$this->render('unverified_login');
			exit();
		}

		if(preg_match('/@googlemail.com/i', $fb_user['email']) > 0 || preg_match('/@gmail.com/i', $fb_user['email'])){
			$email_username = explode('@', $fb_user['email']);
			$user = User::findOne(array('$or' => array(
				array('fbUid' => $fb_user['id']), array('email' => array('$in' => array($email_username[0].'@googlemail.com', $email_username[0].'@gmail.com')))
			)));
		}else{
			$user = User::findOne(array('$or' => array(
				array('fbUid' => $fb_user['id']), array('email' => $fb_user['email'])
			)));
		}

		if(!$user){
			// Then lets create one and log them in
			$user = new User('social_signup');
			$user->createUsernameFromSocialSignup(substr($fb_user['username'], 0, 20));
			$user->email = $fb_user['email'];
		}

		$user->fbUid = $fb_user['id'];
		$user->save();

		if($user->banned){
			$this->render('banned_login');
			exit();
		}

		if($user->deleted){
			$this->render('deleted_login');
			exit();
		}

		if($user->login($user->email, '', false, false)){
			if(isset($_GET['nxt'])){
				glue::http()->redirect(glue::http()->param('nxt'));
			}else{
				glue::http()->redirect("/");
			}
		}else{
			glue::trigger('500');
		}
	}

	public function action_googleLogin()
	{
		$this->title = 'Logging into Stagex';

		if(isset($_REQUEST['code'])){
			if(glue::google()->authorize()){
				$g_user = glue::google()->Google->get('userinfo');

				if(empty($g_user)){
					glue::http()->redirect('/user/login');
				}

				$x_un = explode('@', $g_user->email);
				$username = $x_un[0];
				$user = User::findOne(array('$or' => array(
					array('googleUid' => $g_user->id), array('email' => array('$in' => array($username.'@googlemail.com', $username.'@gmail.com')))
				)));

				if(!$user){
					// Then lets create one and log them in
					$user = new User('social_signup');
					$user->createUsernameFromSocialSignup($username);
					$user->email = $g_user->email;
				}

				$user->googleUid = $g_user->id;
				$user->save();

				if($user->banned){
					$this->render('banned_login');
					exit();
				}

				if($user->deleted){
					$this->render('deleted_login');
					exit();
				}

				if(glue::session()->login($user->email, '', false, false)){
					if(isset($_GET['nxt'])){
						glue::http()->redirect(glue::http()->param('nxt'));
					}else{
						glue::http()->redirect("/");
					}
				}else{
					glue::trigger('500');
				}
			}else{
				glue::http()->redirect('/user/login');
			}
		}
	}

	public function action_recover()
	{
		$this->title = 'Recover your StageX Account';

		$model = new RecoverForm;
		if(isset($_POST['RecoverForm'])){
			$model->attributes = $_POST['RecoverForm'];
			if($model->validate()){
				$user = User::findOne(array('email' => $model->email));
				if($user){
					$user->setScenario('recoverPassword');
					$user->password = Crypt::generate_new_pass();
					$user->save();

					Html::setSuccessFlashMessage('Your password was successfully reset and has been emailed to you. It is advised you change your password as soon as you login.');
					glue::http()->redirect('/user/recover', array('success' => true));
				}
			}
		}
		echo $this->render('user/forgot_password', array('model' => $model));
	}

	public function action_view()
	{ 
		if(!glue::http()->param('id', null) && glue::auth()->check(array('@'))){
			$user = glue::user();
		}elseif(
			!glue::http()->param('id', null) ||
			!($user = User::findOne(array('_id' => new MongoId(glue::http()->param('id',''))))) || 
			!glue::auth()->check(array('viewable' => $user))
		){
			glue::trigger('404');
		}
		
		glue::import('@app/controllers/StreamController.php', true);
		$streamController = new StreamController();

		$this->layout = 'profile_layout';
		if(glue::auth()->check(array('^' => $user))){
			$this->tab = 'profile';
		}else{
			$this->tab = null;
		}
		$this->title = $user->getUsername() . ' - StageX';		
		
		echo $this->render('view_stream', array('user' => $user, 'page' => 'stream', 'cursor' => $streamController->loadStream(null, $user)));
	}

	public function action_viewVideos()
	{
		if(!glue::http()->param('id', null) && glue::auth()->check(array('@'))){
			$user = glue::user();
		}elseif(
			!glue::http()->param('id',null) ||
			!($user = User::findOne(array('_id' => new MongoId(glue::http()->param('id',''))))) || 
			!glue::auth()->check(array('viewable' => $user))
		){
			glue::trigger('404');
		}
		
		$this->layout = 'profile_layout';
		$this->tab = 'profile';
		$this->title = $user->getUsername().' - StageX';
		
		$c = new Query();
		$c->type = 'video';
		$c->filtered = true;
		if(glue::http()->param('query')){
			$c->query()->multiPrefix(array('blurb', 'title', 'tags', 'username'), glue::http()->param('query'));
		}
		$c->filter()->and('term', array('userId' => strval($user->_id)))
					->and('term', array('deleted' => 0));
		
		if(!glue::user()->equals($user)){
			$c->filter()->and('range', array('listing' => array('lt' => 1)));
		}
		
		$c->sort('created', 'desc');
		$c->page(glue::http()->param('page', 1));
		
		$from_time = strtotime(str_replace('/', '-', glue::http()->param('from_date')));
		$to_time = strtotime(str_replace('/', '-', glue::http()->param('to_date')));
		
		if($from_time > 0 || $to_time > 0){
			if($from_time > 0 && $to_time <= 0){
				$c->filter()->and('range', array('created' => array('gte' => date('c', $from_time))));
			}
			if($to_time > 0 && $from_time <= 0){
				$c->filter()->and('range', array('created' => array('lte' => date('c', $to_time))));
			}
			if(date('d', $to_time) === date('d', $from_time)){
				$c->filter()->and('range', array('created' => array(
					'gte' => date('c', mktime(0, 0, 0, date('n', $from_time), date('d', $from_time), date('Y', $from_time))), 
					'lte' => date('c', mktime(0, 0, 0, date('n', $from_time), date('d', $from_time)+1, date('Y', $from_time)))
				)));
			}elseif($from_time > 0 && $to_time > 0){
				$c->filter()->and('range', array('created' => array('gte' => date('c', $from_time), 'lte' => date('c', $to_time))));
			}
		}
		echo $this->render('view_videos', array('user' => $user, 'page' => 'videos', 
			'sphinx_cursor' => $cursor = glue::elasticSearch()->search($c, 'app\models\Video')));
	}

	public function action_viewPlaylists()
	{
		if(!glue::http()->param('id', null) && glue::auth()->check(array('@'))){
			$user = glue::user();
		}elseif(
			!glue::http()->param('id', null) ||
			!($user = User::findOne(array('_id' => new MongoId(glue::http()->param('id',''))))) || 
			!glue::auth()->check(array('viewable' => $user))
		){
			glue::trigger('404');
		}

		$this->layout = 'profile_layout';
		$this->tab = 'profile';
		$this->title = $user->getUsername().' - StageX';
		
		$c = new Query();
		$c->type = 'playlist';
		$c->filtered = true;
		if(glue::http()->param('query')){
			$c->query()->multiPrefix(array('blurb', 'title', 'username'), glue::http()->param('query'));
		}
		$c->filter()->and('term', array('userId' => strval($user->_id)))
					->and('term', array('deleted' => 0));
		if(!glue::user()->equals($user)){
			$c->filter()->and('range', array('listing' => array('lt' => 1)));
		}
		$c->sort('created', 'desc');
		$c->page(glue::http()->param('page', 1));
		
		echo $this->render('view_playlists', array('user' => $user, 'page' => 'playlists', 
			'sphinx' => glue::elasticSearch()->search($c, 'app\models\Playlist')));
	}

	public function action_videos()
	{
		$this->title = 'Your Videos - StageX';
		$this->layout = 'user_section';
		$this->tab = 'videos';
		
		$video = new Video;
		if(
			!($video_rows = $video->advancedSearch(
				glue::http()->param('query'), 
				array('userId' => glue::user()->_id, 'deleted' => 0)
			))
		){
			$video_rows = Video::fts(
				array('title', 'description', 'tags'), isset($_GET['query']) ? $_GET['query'] : '',
				array('userId' => glue::user()->_id, 'deleted' => 0)
			)->sort(array('created' => -1));
		}
		echo $this->render('videos', array('video_rows' => $video_rows));
	}

	public function action_playlists()
	{
		$this->title = 'Your Playlists - StageX';
		$this->layout = 'user_section';
		$this->tab = 'playlists';
		
		$playlist = new Playlist;
		if(
			!($playlist_rows = $playlist->advancedSearch(
				glue::http()->param('query'),
				array('userId' => glue::user()->_id, 'title' => array('$ne' => 'Watch Later'), 'deleted' => 0)
			))
		){
			$playlist_rows = Playlist::fts(
				array('title', 'description'), glue::http()->param('query',''), 
				array('userId' => glue::user()->_id, 'title' => array('$ne' => 'Watch Later'), 'deleted' => 0)
			)->sort(array('created' => -1));
		}		
		echo $this->render('playlists', array('playlist_rows' => $playlist_rows));
	}

	public function action_watchLater()
	{
		$this->title = 'Watch Later - StageX';
		$this->layout = 'user_section';
		$this->tab = 'watch_later';
		
		$watch_later = Playlist::findOne(array('title' => 'Watch Later', 'userId' => glue::user()->_id));
		echo $this->render('user/watch_later', array('model' => $watch_later));
	}

	public function action_subscriptions()
	{
		$this->title = 'Your Subscriptions - StageX';
		$this->layout = 'user_section';
		$this->tab = 'subscriptions';

		echo $this->render('user/subscriptions', array('model' => $this->loadModel()));
	}
	
	public function action_subscribers()
	{
		$this->title = 'Your subscribers - StageX';
		$this->layout = 'user_section';
		$this->tab = 'subscribers';
		echo $this->render('user/subscribers', array('model' => $this->loadModel));
	}
	
	public function action_watched()
	{
		$this->title = 'Watched Videos - StageX';
		$this->layout = 'user_section';
		$this->tab = 'videos';
	
		extract(glue::http()->param(array('query','from_date','to_date'),null));
	
		$timeRange = array();
		$idRange = array();
		
		if($from_date){
			$timeRange['ts']['$gte']=new MongoDate(strtotime(str_replace('/','-',$from_date)));
		}
		if($to_date){
			$timeRange['ts']['$lt']=new MongoDate(strtotime(str_replace('/','-',$to_date.' +1 day')));
		}
		if($query){
			$videos = iterator_to_array(\app\models\Video::find(array('title'=>new \MongoRegex("/^$query/")))->sort(array('title'=>1))->limit(1000));
			$mongoIds = array();
			foreach($videos as $_id=>$video){
				$mongoIds[] = new \MongoId($_id);
			}
			$idRange = array('item' => array('$in' => $mongoIds));
		}
		echo $this->render('watched', array('items' =>
				glue::db()->watched_history->find(array_merge(array("user_id" => Glue::user()->_id),$timeRange,$idRange))->sort(array('ts' => -1))
		));
	}
	
	public function action_rated()
	{
		$this->title = 'Rated Videos - StageX';
		$this->layout = 'user_section';
		$this->tab = 'videos';
	
		extract(glue::http()->param(array('tab','query','from_date','to_date'),null));
	
		$timeRange = array();
		$idRange = array();
		
		if($from_date){
			$timeRange['ts']['$gte']=new MongoDate(strtotime(str_replace('/','-',$from_date)));
		}
		if($to_date){
			$timeRange['ts']['$lt']=new MongoDate(strtotime(str_replace('/','-',$to_date.' +1 day')));
		}
		if($query){
			$videos=iterator_to_array(\app\models\Video::find(array('title'=>new \MongoRegex("/^$query/")))->sort(array('title'=>1))->limit(1000));
			$mongoIds=array();
			foreach($videos as $_id=>$video){
				$mongoIds[]=new \MongoId($_id);
			}
			$idRange = array('item' => array('$in'=>$mongoIds));
		}
	
		if($tab === 'dislikes'){
			$rated = glue::db()->video_likes->find(array_merge(array("user_id" => Glue::user()->_id, 'like' => 0),$timeRange,$idRange));
		}else{
			$rated = glue::db()->video_likes->find(array_merge(array("user_id" => Glue::user()->_id, 'like' => 1),$timeRange,$idRange));
		}
		$rated->sort(array('ts' => -1));
	
		echo $this->render('rated_videos', array('items' => $rated));
	}
	
	public function action_PlaylistSubscriptions()
	{
		$this->title = 'Playlist Subscriptions - StageX';
		$this->layout = 'user_section';
		$this->tab = 'playlists';
	
		echo $this->render('playlist_subscriptions', array(
			'playlist_rows' => glue::db()->playlist_subscription->find(array('user_id' => glue::user()->_id))->sort(array('ts' => -1))->limit(20)
		));
	}
	
	public function action_removeRated()
	{
		if(!glue::auth()->check('ajax','post')){
			glue::trigger('404');
		}
		
		extract(glue::http()->param(array('ids')));
		if(!$ids || (is_array($ids) && count($ids) <= 0)){
			Json::error(Json::UNKNOWN);
		}
	
		$mongoIds = array();
		foreach($ids as $k=>$v){
			$mongoIds[$k] = new MongoId($v);
		}
	
		$updated = 0; 
		$failed = 0;
		$rows = glue::db()->video_likes->find(array('user_id' => glue::user()->_id, "_id" => array('$in' => $mongoIds)));
	
		foreach($rows as $k => $v){
			$item = Video::findOne(array('_id' => $v['item']));
			if($item instanceof app\models\Video){
				if($v['like'] == 1){
					$item->saveCounters(array('likes' => -1), 0);
				}elseif($v['like'] == 0){
					$item->saveCounters(array('dislikes' => -1), 0);
				}
				$updated++;
			}else{
				$failed++;
			}
		}
		glue::db()->video_likes->remove(array('user_id'=>glue::user()->_id, "_id" => array('$in' => $mongoIds)));
	
		Json::success(array('message' => 'Videos deleted', 'updated' => $updated,'failed' => $failed));
	}
	
	public function action_removeWatched()
	{
		if(!glue::auth()->check('ajax','post')){
			glue::trigger('404');
		}
		
		$ids = glue::http()->param('ids');
		if(!$ids || (is_array($ids) && count($ids) <= 0)){
			Json::error(Json::UNKNOWN);
		}
	
		$mongoIds = array();
		foreach($ids as $k=>$v){
			$mongoIds[$k] = new MongoId($v);
		}
		glue::db()->watched_history->remove(array('_id' => array('$in' => $mongoIds), 'user_id' => glue::user()->_id));
		Json::success('The history items you selected have been deleted');
	}
	
	public function action_clearWatched()
	{
		if(!glue::auth()->check('ajax','post')){
			glue::trigger('404');
		}
		
		glue::db()->watched_history->remove(array('user_id' => glue::user()->_id));
		Json::success('Your watch history has been cleared');
	}	

	public function action_settings()
	{
		$this->title = 'Account Settings - StageX';
		$this->layout = "user_section";
		$this->tab = "settings";

		$model = $this->loadModel();
		if(isset($_POST['User'])){
			if(isset($_POST['User']['action'])){
				$model->setScenario($_POST['User']['action']);
				unset($_POST['User']['action']);
			}

			$model->attributes = $_POST['User'];
			if($model->save()){
				if($model->getScenario()=='updateEmail'){
					Html::setSuccessFlashMessage('An email has been sent asking for confirmation of your new address');
				}else{
					Html::setSuccessFlashMessage('Your account settings have been saved');
				}
				glue::http()->redirect("/user/settings");
			}
		}
		echo $this->render("settings", array("model"=>$model));
	}

	public function action_activity()
	{
		$this->title = 'Account Activity - StageX';
		$this->tab = 'activity';
		$this->layout = "user_section";

		echo $this->render('user/activity', array('model' => $this->loadModel()));
	}

	public function action_removesession()
	{
		$this->title = 'Remove Session - StageX';
		if(!glue::http()->isAjax()){
			glue::trigger('404');
		}

		if(isset($_GET['id'])){
			$user = $this->loadModel();

			if(!$user){
				Json::error();
			}

			unset($user->sessions[$_GET['id']]);
			$user->save();

			Json::success();
		}else{
			Json::error();
		}
	}

	public function action_profile()
	{
		$this->title = 'Profile Settings - StageX';
		$this->layout = "user_section";
		$this->tab = "profile_settings";

		$model = $this->loadModel();
		if(isset($_POST['User'])){
			if(isset($_POST['User']['action'])){
				$model->setScenario($_POST['User']['action']);
			}
			if($model->getScenario() == 'updatePic'){
				$model->avatar = new glue\File(array('model'=>$model,'id'=>'avatar'));
				if($model->validate() && $model->setAvatar()){
					Html::setSuccessFlashMessage('Your profile picture has been changed');
					glue::http()->redirect("/user/profile");
				}
			}else{
				$model->attributes = $_POST['User'];
				if($model->save()){
					Html::setSuccessFlashMessage('Your profile settings have been saved');
					glue::http()->redirect("/user/profile");
				}
			}
		}
		echo $this->render('user/profile_settings', array('model' => $model, 'success_message' => ''));
	}

	public function action_follow()
	{
		$this->title = 'Subscribe To User - StageX';

		if(glue::auth()->check('ajax','post')){
			
			if(
				($id = glue::http()->param('id', null)) === null ||
				($user = User::findOne(array("_id" => new MongoId($id)))) === null
			){
				Json::error('User not found');
			}

			if(!Follower::findOne(array('fromId' => glue::user()->_id, 'toId' => $user->_id))){
				$follower = new Follower();
				$follower->fromId=  glue::user()->_id;
				$follower->toId = $user->_id;
				$follower->save();

				app\models\Stream::subscribedTo(glue::user()->_id, $user->_id);
				Json::success('You are now following this user');
			} // Be silent about the relationship already existing
		}else{
			glue::trigger('404');
		}
	}

	public function action_unfollow()
	{
		$this->title = 'Unsubscribe From User - StageX';

		if(glue::auth()->check('ajax','post')){
			if(($id = glue::http()->param('id')) === null){
				Json::error('User not found');
			}
			
			$user = User::findOne(array('_id' => new MongoId($id)));
			$follow = Follower::findOne(array('fromId' => glue::user()->_id, 'toId' => new MongoId($id)));

			if($follow && $user && $follow->delete()){
				Json::success('You have unfollowed this user');
			}else{
				Json::error(Json::UNKNOWN);
			}
		}else{
			glue::trigger('404');
		}
	}

	public function action_logout()
	{
		$this->title = 'Logout of StageX';

		Glue::session()->logout(false);
		if(isset($_GET['nxt'])){
			header("Location: ".$_GET['nxt']);
		}else{
			header("Location: /");
		}
		exit();
	}

	public function action_deactivate()
	{
		$this->title = 'Deactivate Your StageX Account - StageX';
		$this->layout = "blank_page";

		$model = $this->loadModel();
		$toDelete = isset($_GET['delete']) ? $_GET['delete'] : null;

		if($toDelete == 1){
			$model->deactivate();
			glue::session()->logout(false);
			html::setSuccessFlashMessage("Your account has been deactivated and is awaiting deletion!");
			header("Location: /user/login");
			exit();
		}
		echo $this->render('deactivate');
	}


	public function action_confirminbox()
	{
		$this->title = 'Confirm Your New Email Address - StageX';

		$email = urldecode(glue::http()->param('e', ''));
		$hash = urldecode(glue::http()->param('h', ''));
		$id = new MongoId(urldecode(glue::http()->param('uid', '')));

		$user = User::findOne(array('_id' => $id));

		if(
			($user !== null && is_array($user->accessToken)) &&
			($user->accessToken['to'] > time() && $user->accessToken['hash'] == $hash && $user->accessToken['y'] == "E_CHANGE" && $user->accessToken['email'] == $email)
		){
			if(glue::session()->authed){
				$user->email = $email;
				$user->accessToken=null;
				$user->sessions=array();
				$user->save();

				Glue::session()->logout(false);

				html::setSuccessFlashMessage("Email Changed! All devices have been signed out. You must login again.");
				header("Location: ".Glue::http()->url("/user/login"));
				exit();
			}else{
				html::setErrorFlashMessage("You must be logged in to change your email address");
				header("Location: ".Glue::http()->url("/user/login", array("next"=>$user->accessToken['url'])));
				exit();
			}
		}else{
			glue::route("error/notfound");
		}
	}

	public function action_searchFollowing()
	{
		if(!glue::http()->isAjax()){
			glue::trigger('404');
		}
		
		extract(glue::http()->param(array('query', 'page')));
		$users = app\models\Follower::search(glue::user()->_id, $query);

		ob_start();
		ob_implicit_flush(false);
		if(count($users) > 0){
			echo glue\widgets\ListView::run(array(
				'pageSize'	 => 20,
				'page' 		 => $page,
				"cursor"	 => $query?new Collection($users):$users,
				'itemView' => 'user/_subscription.php',
			));
		}else{
			?><div class="no_results_found">No subscriptions were found</div><?php
		}
		Json::success(array('html' => ob_get_clean()));
	}
	
	public function action_searchFollowers()
	{
		if(!glue::http()->isAjax()){
			glue::trigger('404');
		}
	
		extract(glue::http()->param(array('query', 'page')));
		$users = app\models\Follower::searchFollowers(glue::user()->_id, $query);
	
		ob_start();
		ob_implicit_flush(false);
		if(count($users) > 0){
			echo glue\widgets\ListView::run(array(
				'pageSize' => 20,
				'page' => $page,
				'cursor' => $query ? new Collection($users) : $users,
				'itemView' => 'user/_subscriber.php',
			));
		}else{
			?><div class="no_results_found">No subscribers were found</div><?php
		}
		Json::success(array('html' => ob_get_clean()));
	}	
	
	public function action_suggestions()
	{
		if(!glue::http()->isAjax()){
			glue::trigger('404');
		}
		
		$ret = array();
		
		$c = new Query();
		$c->type = 'user';
		$c->filtered = true;
		
		if(glue::http()->param('term')){
			$c->query()->multiPrefix(array('title'), glue::http()->param('term'));
		}
		$c->filter()->and('term', array('deleted' => 0));

		$c->sort('created', 'desc');
		$c->page(1);
		
		foreach(glue::elasticSearch()->search($c, '\app\models\User') as $item){
			$ret[] = array(
				'_id' => $item->_id,
				'username' => $item->username
			);
		}
		Json::success(array('results' => $ret));
	}

	/**
	 * UTIL functions
	 */

	function loadModel()
	{
		$user = User::findOne(array("_id"=>glue::user()->_id));
		if(!$user){
			Html::setErrorFlashMessage("You must be logged in to access this area.");
			glue::http()->redirect('/user/login', array('nxt' => glue::http()->url('SELF')));
		}
		return $user;
	}
}