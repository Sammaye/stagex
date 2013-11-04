<?php

use glue\Html,
	app\models\loginForm,
	app\models\User,
	app\models\Follower,
	app\models\Video,
	app\models\Playlist;

class userController extends \glue\Controller{

	public $defaultAction='videos';
	public $title = 'Your Stuff - StageX';

	public $tab = "settings";

	public function authRules(){
		return array(
			array('allow',
				'actions' => array('create', 'login', 'view', 'recover', 'view_videos', 'view_playlists', 'fbLogin', 'googleLogin'),
				'users' => array('*')
			),
			array('allow', 'actions' => '*', 'users' => array('@*')),
			array("deny", "users"=>array("*")),
		);
	}

	function action_create(){

		$this->title = 'Create a new StageX Account';

		$model = new User;
		if(isset($_POST['User'])){
			$model->setRule(array('hash', 'hash', 'message' => 'CSRF not valid'));
			$model->attributes=$_POST['User'];
			if($model->validate()&&$model->save()){
				if(glue::user()->login($model->email,'',true,false)){
					glue::http()->redirect("/user");
				}else{
					$model->setError("Login failed, however, it seems you are saved to our system so please try to login manually.");
				}
			}
		}
//var_dump($model->getErrors());
		echo $this->render("create", array("model" => $model));
	}

	function action_login(){

		$this->title = "Login to your StageX Account";

		$model = new loginForm();
		$model->attributes=isset($_POST['loginForm']) ? $_POST['loginForm'] : array();

		/** Count how many times the user has logged in over 5 mins */
		$loginAttempts = Glue::db()->session_log->findOne(array("email"=>$model->email, "ts"=>array("\$gt"=>new MongoDate(time()-(60*5)))));
		if($loginAttempts['c'] > 4){
			$model->setScenario('captcha');
		}

		if(isset($_POST['loginForm'])){
			if($model->validate()){
				if(glue::user()->login($model->email,$model->password,$model->remember)){
					if(isset($_GET['nxt'])){
						glue::http()->redirect(glue::http()->param('nxt'));
					}else{
						glue::http()->redirect("/");
					}
				}else{
					foreach(glue::user()->getErrors() as $k=>$v)
						$model->setError($k,$v);
				}
			}
		}
		echo $this->render('user/login', array('model' => $model, 'attempts' => $loginAttempts['c']));
	}

	function action_fbLogin(){

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
			$user = User::model()->findOne(array('$or' => array(
				array('fbUid' => $fb_user['id']), array('email' => array('$in' => array($email_username[0].'@googlemail.com', $email_username[0].'@gmail.com')))
			)));
		}else{
			$user = User::model()->findOne(array('$or' => array(
				array('fbUid' => $fb_user['id']), array('email' => $fb_user['email'])
			)));
		}

		if(!$user){
			// Then lets create one and log them in
			$user = new User('social_signup');
			$user->create_username_from_social_signup(substr($fb_user['username'], 0, 20));
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

	function action_googleLogin(){
		$this->title = 'Logging into Stagex';

		if(isset($_REQUEST['code'])){
			if(glue::google()->authorize()){
				$g_user = glue::google()->Google->get('userinfo');

				if(empty($g_user)){
					glue::http()->redirect('/user/login');
				}

				$x_un = explode('@', $g_user->email);
				$username = $x_un[0];
				$user = User::model()->findOne(array('$or' => array(
					array('google_uid' => $g_user->id), array('email' => array('$in' => array($username.'@googlemail.com', $username.'@gmail.com')))
				)));

				if(!$user){
					// Then lets create one and log them in
					$user = new User('social_signup');
					$user->create_username_from_social_signup($username);
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

	function action_recover(){

		$this->title = 'Recover your StageX Account';

		$model = new app\models\recoverForm();
		if(isset($_POST['recoverForm'])){
			$model->attributes=$_POST['recoverForm'];
			if($model->validate()){
				$user =  User::model()->findOne(array('email' => $model->email));
				if($user){
					$user->setScenario('recoverPassword');
					$user->password = \glue\util\Crypt::generate_new_pass();
					$user->save();

					Html::setSuccessFlashMessage('Your password was successfully reset and has been emailed to you. It is advised you change your password as soon as you login.');
					glue::http()->redirect('/user/recover', array('success'=>true));
				}
			}
		}
		echo $this->render('user/forgot_password', array('model' => $model));
	}

	function action_view(){ 
		if(!glue::http()->param('id',null)&&glue::auth()->check(array('@'))){
			$user=glue::user();
		}elseif(
			!($user = User::model()->findOne(array('_id' => new MongoId(glue::http()->param('id','')) ))) || 
			!glue::auth()->check(array('viewable' => $user))
		){
			$this->layout = 'blank_page';
			$this->title = 'User Not Found - StageX';
			echo $this->render('deleted');
			exit();
		}
		
		glue::import('@app/controllers/StreamController.php',true);
		$streamController=new StreamController();
		
		$this->layout = 'profile';
		$this->tab='profile';
		$this->title = $user->getUsername().' - StageX';
		
		echo $this->render('profile/stream', array('user' => $user, 'page' => 'stream', 'cursor' => $streamController->load_single_stream()));
	}

	function action_viewVideos(){
		if(!glue::http()->param('id',null)&&glue::auth()->check(array('@'))){
			$user=glue::user();
		}elseif(
			!($user = User::model()->findOne(array('_id' => new MongoId(glue::http()->param('id','')) ))) || 
			!glue::auth()->check(array('viewable' => $user))
		){
			$this->layout = 'blank_page';
			$this->title = 'User Not Found - StageX';
			echo $this->render('deleted');
			exit();
		}
		
		$this->layout='profile';
		$this->tab='profile';
		$this->title = $user->getUsername().' - StageX';

		$sphinx=glue::sphinx()->index('main')
			->match(array('title', 'description', 'tags', 'author_name'),glue::http()->param('q',''))
			->match('type','video')->match('uid',strval($user->_id))
			->sort(SPH_SORT_TIME_SEGMENTS, "date_uploaded")
			->filter('deleted', array(1), true)
			->page(glue::http()->param('page',1));	
		if(!glue::user()->equal($user))
			$sphinx->filter('listing',array(1, 2), true);

		echo $this->render('profile/videos', array('user' => $user, 'page' => 'videos', 'sphinx' => $sphinx));
	}

	function action_viewPlaylists(){
		if(!glue::http()->param('id',null)&&glue::auth()->check(array('@'))){
			$user=glue::user();
		}elseif(
			!($user = User::model()->findOne(array('_id' => new MongoId(glue::http()->param('id','')) ))) || 
			!glue::auth()->check(array('viewable' => $user))
		){
			$this->layout = 'blank_page';
			$this->title = 'User Not Found - StageX';
			echo $this->render('deleted');
			exit();
		}

		$this->layout='profile';
		$this->tab='profile';
		$this->title = $user->getUsername().' - StageX';
		
		$sphinx=glue::sphinx()->index('main')
		->match(array('title', 'description', 'author_name'),glue::http()->param('q',''))
		->match('type','playlist')->match('uid',strval($user->_id))
		->sort(SPH_SORT_TIME_SEGMENTS, "date_uploaded")
		->filter('deleted', array(1), true)
		->page(glue::http()->param('page',1));
		if(!glue::user()->equal($user))
			$sphinx->filter('listing',array(1, 2), true);		

		echo $this->render('profile/playlists', array('user' => $user, 'page' => 'playlists', 'sphinx' => $sphinx));
	}

	function action_videos(){

		$this->title = 'Your Videos - StageX';
		$this->layout = 'user_section';
		$this->tab = 'videos';
		
		$filter = isset($_GET['filter']) ? $_GET['filter'] : null;

		$filter_obj = array();
		switch($filter){
			case "listed":
				$filter_obj = array('listing' => 1);
				break;
			case "unlisted":
				$filter_obj = array('listing' => 2);
				break;
			case "private":
				$filter_obj = array('listing' => 3);
				break;
		}

		$video_rows = Video::model()->fts(
			array('title', 'description', 'tags'), isset($_GET['query']) ? $_GET['query'] : '', array_merge(
				array('userId' => glue::user()->_id, 'deleted' => 0), $filter_obj))
			->sort(array('created' => -1));

		echo $this->render('videos', array('video_rows' => $video_rows, 'filter' => $filter));
		
	}

	function action_playlists(){

		$this->title = 'Your Playlists - StageX';

		$this->layout = 'user_section';
		$this->tab = 'playlists';

		$playlist_rows = Playlist::model()->fts(
			array('title', 'description'), glue::http()->param('query',''), 
				array('userId' => glue::user()->_id, 'title' => array('$ne' => 'Watch Later'), 'deleted' => 0)
			)->sort(array('created' => -1));

		echo $this->render('playlists', array('playlist_rows' => $playlist_rows));
	}

	function action_watchLater(){
		$this->title = 'Watch Later - StageX';

		$this->layout = 'user_section';
		$this->tab = 'watch_later';
		$watch_later = Playlist::model()->findOne(array('title' => 'Watch Later', 'userId' => glue::user()->_id));
		echo $this->render('user/watch_later', array('model' => $watch_later));
	}

	function action_following(){
		$this->title = 'Your Subscriptions - StageX';

		$this->layout = 'user_section';
		$this->tab = 'subscriptions';

		echo $this->render('user/subscriptions', array('model' => $this->loadModel()));
	}
	
	public function action_watched(){
		$this->title = 'Watched Videos - StageX';
		$this->layout = 'user_section';
		$this->tab = 'videos';
	
		extract(glue::http()->param(array('query','from_date','to_date'),null));
	
		$timeRange=array();
		$idRange=array();
		if($from_date)
			$timeRange['ts']['$gte']=new MongoDate(strtotime(str_replace('/','-',$from_date)));
		if($to_date)
			$timeRange['ts']['$lt']=new MongoDate(strtotime(str_replace('/','-',$to_date.' +1 day')));
		if($query){
			$videos=iterator_to_array(\app\models\Video::model()->find(array('title'=>new \MongoRegex("/^$query/")))->sort(array('title'=>1))->limit(1000));
			$mongoIds=array();
			foreach($videos as $_id=>$video)
				$mongoIds[]=new \MongoId($_id);
			$idRange=array('item'=>array('$in'=>$mongoIds));
		}
		echo $this->render('watched', array('items' =>
				glue::db()->watched_history->find(array_merge(array("user_id" => Glue::user()->_id),$timeRange,$idRange))->sort(array('ts' => -1))
		));
	}
	
	public function action_rated(){
		$this->title = 'Rated Videos - StageX';
		$this->layout = 'user_section';
		$this->tab = 'videos';
	
		extract(glue::http()->param(array('tab','query','from_date','to_date'),null));
	
		$timeRange=array();
		$idRange=array();
		if($from_date)
			$timeRange['ts']['$gte']=new MongoDate(strtotime(str_replace('/','-',$from_date)));
		if($to_date)
			$timeRange['ts']['$lt']=new MongoDate(strtotime(str_replace('/','-',$to_date.' +1 day')));
		if($query){
			$videos=iterator_to_array(\app\models\Video::model()->find(array('title'=>new \MongoRegex("/^$query/")))->sort(array('title'=>1))->limit(1000));
			$mongoIds=array();
			foreach($videos as $_id=>$video)
				$mongoIds[]=new \MongoId($_id);
			$idRange=array('item' => array('$in'=>$mongoIds));
		}
	
		if($tab=='dislikes')
			$rated=glue::db()->video_likes->find(array_merge(array("user_id" => Glue::user()->_id, 'like' => 0),$timeRange,$idRange));
		else
			$rated=glue::db()->video_likes->find(array_merge(array("user_id" => Glue::user()->_id, 'like' => 1),$timeRange,$idRange));
		$rated->sort(array('ts' => -1));
	
		echo $this->render('rated_videos', array('items' => $rated));
	}
	
	public function action_followedPlaylists(){
		$this->title = 'Rated Playlists - StageX';
		$this->layout = 'user_section';
		$this->tab = 'videos';
	
		$_filter = isset($_GET['filter']) ? $_GET['filter'] : null;
		$items = glue::db()->playlist_likes->find(array("user_id" => Glue::session()->user->_id, 'like' => 1))->sort(array('ts' => -1))->limit(20);
	
		$this->render('followed_playlists', array('items' => $items, '_filter' => $_filter));
	}
	
	function action_removeRated(){
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');
		extract(glue::http()->param(array('ids')));
		if(!$ids||(is_array($ids)&&count($ids) <= 0))
			$this->json_error(self::UNKNOWN);
	
		$mongoIds = array();
		foreach($ids as $k=>$v){
			$mongoIds[$k] = new MongoId($v);
		}
	
		$updated=0; $failed=0;
		$rows=glue::db()->video_likes->find(array('user_id'=>glue::user()->_id, "_id" => array('$in' => $mongoIds)));
	
		foreach($rows as $k=>$v){
			$item = app\models\Video::model()->findOne(array('_id' => $v['item']));
			if($item instanceof app\models\Video){
				if($v['like'] == 1){
					$item->saveCounters(array('likes'=>-1),0);
				}elseif($v['like'] == 0)
				$item->saveCounters(array('dislikes'=>-1),0);
				$updated++;
			}else
				$failed++;
		}
		glue::db()->video_likes->remove(array('user_id'=>glue::user()->_id, "_id" => array('$in' => $mongoIds)));
	
		$this->json_success(array('message'=>'Videos deleted', 'updated'=>$updated,'failed'=>$failed));
	}
	
	function action_removeWatched(){
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');
		$ids=glue::http()->param('ids');
		if(!$ids||(is_array($ids)&&count($ids) <= 0))
			$this->json_error(self::UNKNOWN);
	
		$mongoIds = array();
		foreach($ids as $k=>$v){
			$mongoIds[$k] = new MongoId($v);
		}
		glue::db()->watched_history->remove(array('_id' => array('$in' => $mongoIds), 'user_id' => glue::user()->_id));
		$this->json_success('The history items you selected have been deleted');
	}
	
	function action_clearWatched(){
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');
		glue::db()->watched_history->remove(array('user_id' => glue::user()->_id));
		$this->json_success('Your watch history has been cleared');
	}	

	function action_settings(){

		$this->title = 'Account Settings - StageX';
		$this->layout = "user_section";

		$model = $this->loadModel();
		if(isset($_POST['User'])){
			if(isset($_POST['User']['action'])){
				$model->setScenario($_POST['User']['action']);
				unset($_POST['User']['action']);
			}

			$model->attributes=$_POST['User'];

			if($model->validate()&&$model->save()){
				if($model->getScenario()=='updateEmail'){
					Html::setSuccessFlashMessage('An email has been sent asking for confirmation of your new address');
				}else{
					Html::setSuccessFlashMessage('Your account settings have been saved');
				}
				glue::http()->redirect("/user/settings");
			}
		}

		$this->tab = "settings";

		echo $this->render(
			"settings",
			array("model"=>$model)
		);
	}

	function action_activity(){
		$this->title = 'Account Activity - StageX';

		$this->tab = 'activity';
		$this->layout = "user_section";

		$model = $this->loadModel();

		echo $this->render('user/activity', array(
			'model' => $model
		));
	}

	function action_removesession(){
		$this->title = 'Remove Session - StageX';
		if(!glue::http()->isAjax())
			glue::trigger('404');

		if(isset($_GET['id'])){
			$user = $this->loadModel();

			if(!$user)
				echo json_encode(array("success" => false));

			unset($user->sessions[$_GET['id']]);
			$user->save();

			echo json_encode(array("success" => true));
		}else{
			echo json_encode(array("success" => false));
		}
	}

	function action_profile(){
		$this->title = 'Profile Settings - StageX';
		$this->layout = "user_section";
		$this->tab = "profile_settings";

		$model = $this->loadModel();
		if(isset($_POST['User'])){
			if(isset($_POST['User']['action']))
				$model->setScenario($_POST['User']['action']);
			if($model->getScenario()=='updatePic'){
				$model->avatar=new glue\File(array('model'=>$model,'id'=>'avatar'));
				if($model->validate()&&$model->setAvatar()){
					Html::setSuccessFlashMessage('Your profile picture has been changed');
					glue::http()->redirect("/user/profile");
				}
			}else{
				$model->attributes=$_POST['User'];
				if($model->validate()&&$model->save()){
					Html::setSuccessFlashMessage('Your profile settings have been saved');
					glue::http()->redirect("/user/profile");
				}
			}
		}
		echo $this->render('user/profile_settings', array('model' => $model, 'success_message' => ''));
	}

	function action_follow(){
		$this->title = 'Subscribe To User - StageX';

		if(glue::auth()->check('ajax','post')){
			
			if(
				($id=glue::http()->param('id',null))===null ||
				($user=User::model()->findOne(array("_id"=>new MongoId($id))))===null
			)
				$this->json_error('User not found');

			if(!Follower::model()->findOne(array('fromId' => glue::user()->_id, 'toId' => $user->_id))){
				$follower = new Follower();
				$follower->fromId=  glue::user()->_id;
				$follower->toId = $user->_id;
				$follower->save();

				app\models\Stream::subscribedTo(glue::user()->_id, $user->_id);
				$this->json_success('You are now following this user');
			} // Be silent about the relationship already existing
		}else
			glue::trigger('404');
	}

	function action_unfollow(){
		$this->title = 'Unsubscribe From User - StageX';

		if(glue::auth()->check('ajax','post')){
			if(($id=glue::http()->param('id',null))===null)
				$this->json_error('User not found');
			
			$user = User::model()->findOne(array('_id' => new MongoId($id)));
			$follow = Follower::model()->findOne(array('fromId' => glue::user()->_id, 'toId' => new MongoId($id)));

			if($follow && $user && $follow->delete())
				$this->json_success('You have unfollowed this user');
			else
				$this->json_error(self::UNKNOWN);
		}else
			glue::trigger('404');
	}

	function action_logout(){
		$this->title = 'Logout of StageX';

		Glue::user()->logout(false);
		if(isset($_GET['nxt']))
			header("Location: ".$_GET['nxt']);
		else
			header("Location: /");
		exit();
	}

	function action_deactivate(){
		$this->title = 'Deactivate Your StageX Account - StageX';
		$this->layout = "blank_page";

		$model = $this->loadModel();
		$toDelete = isset($_GET['delete']) ? $_GET['delete'] : null;

		if($toDelete == 1){
			$model->deactivate();
			glue::user()->logout(false);
			html::setSuccessFlashMessage("Your account has been deactivated and is awaiting deletion!");
			header("Location: /user/login");
			exit();
		}
		echo $this->render('deactivate');
	}


	public function action_confirminbox(){
		$this->title = 'Confirm Your New Email Address - StageX';

		$email = urldecode(glue::http()->param('e', ''));
		$hash = urldecode(glue::http()->param('h', ''));
		$id = new MongoId(urldecode(glue::http()->param('uid', '')));

		$user = User::model()->findOne(array('_id' => $id));

		if(
			($user!==null&&is_array($user->accessToken)) &&
			($user->accessToken['to'] > time() && $user->accessToken['hash'] == $hash && $user->accessToken['y'] == "E_CHANGE" && $user->accessToken['email'] == $email)
		){
			if(glue::session()->authed){
				$user->email = $email;
				$user->accessToken=null;
				$user->sessions=array();
				$user->save();

				Glue::user()->logout(false);

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

	public function action_searchFollowers(){
		$this->title = 'Search Folllowers - StageX';

		if(!glue::http()->isAjax())
			glue::trigger('404');
		extract(glue::http()->param(array('query','page')));
		$users=app\models\Follower::model()->search(glue::user()->_id,$query);

		if(count($users) > 0){
			glue\widgets\ListView::widget(array(
				'pageSize'	 => 20,
				'page' 		 => $page,
				"cursor"	 => new Collection($users),
				'itemView' => 'user/_subscription.php',
			));
		}else{
			?><div class="no_results_found">No subscriptions were found</div><?php
		}
	}
	
	public function action_searchSuggestions(){
		if(!glue::http()->isAjax())
			glue::trigger('404');
		$term=glue::http()->param('query');
		$users=app\models\User::model()->find(array('username'=>new MongoRegex("/$term/")));
		
		$suggestions=array();
		foreach($users as $user)
			$suggestions[]=array(
				'label'=>$user->username
			);
		echo json_encode($suggestions);
		exit();
	}

	function action_manage(){
		$this->render('manage');
	}	

	/**
	 * UTIL functions
	 */

	function loadModel(){
		$user = User::model()->findOne(array("_id"=>glue::user()->_id));
		if(!$user){
			Html::setErrorFlashMessage("You must be logged in to access this area.");
			glue::http()->redirect('/user/login', array('nxt' => glue::http()->url('SELF')));
		}
		return $user;
	}
}