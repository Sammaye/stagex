<?php

use glue\Html,
	app\models\loginForm,
	app\models\User,
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
		$this->layout = 'profile';

		if(isset($_GET['id'])){
			$user = User::model()->findOne(array('_id' => new MongoId($_GET['id'])));
		}else{
			$user = glue::session()->user;
		} //var_dump($user);

		if(!glue::roles()->checkRoles(array('deletedView' => $user)) || !$user->_id instanceof MongoId){
			$this->layout = 'blank_page';
			$this->pageTitle = 'User Not Found - StageX';
			$this->render('user/deleted');
			exit();
		}
		$this->pageTitle = $user->getUsername().' - StageX';

		$stream = Stream::model()->find(array('user_id' => $user->_id))->sort(array('ts' => -1))->limit(20);
		$this->render('profile/stream', array('user' => $user, 'selected_page' => 'stream', 'stream' => $stream));
	}

	function action_view_videos(){
		$this->layout = 'profile';

		if(isset($_GET['id'])){
			$user = User::model()->findOne(array('_id' => new MongoId($_GET['id'])));
		}else{
			$user = glue::session()->user;
		}

		if(!glue::roles()->checkRoles(array('deletedView' => $user)) || !$user->_id instanceof MongoId){
			$this->layout = 'blank_page';
			$this->pageTitle = 'User Not Found - StageX';
			$this->render('user/deleted');
			exit();
		}
		$this->pageTitle = $user->getUsername().' - StageX';

		$sphinx = glue::sphinx()->getSearcher();
		$sphinx->page = isset($_GET['page']) ? $_GET['page'] : 1;
		$sphinx->setFilter('listing', array(2, 3), true);
		$sphinx->setSortMode(SPH_SORT_ATTR_DESC, "date_uploaded");

		$sphinx->query(array('select' => urldecode(isset($_GET['query']) ? $_GET['query'] : ''), 'where' => array('uid' => array(strval($user->_id)),
			'type' => array('video')), 'results_per_page' => 21), 'main');

		$this->render('profile/videos', array('user' => $user, 'selected_page' => 'videos', 'sphinx' => $sphinx));
	}

	function action_view_playlists(){
		$this->layout = 'profile';

		if(isset($_GET['id'])){
			$user = User::model()->findOne(array('_id' => new MongoId($_GET['id'])));
		}else{
			$user = glue::session()->user;
		}

		if(!glue::roles()->checkRoles(array('deletedView' => $user)) || !$user->_id instanceof MongoId){
			$this->layout = 'blank_page';
			$this->pageTitle = 'User Not Found - StageX';
			$this->render('user/deleted');
			exit();
		}
		$this->pageTitle = $user->getUsername().' - StageX';

		$sphinx = glue::sphinx()->getSearcher();
		$sphinx->page = isset($_GET['page']) ? $_GET['page'] : 1;
		$sphinx->setFilter('listing', array(2, 3), true);
		$sphinx->setSortMode(SPH_SORT_TIME_SEGMENTS, "date_uploaded");

		$sphinx->query(array('select' => urldecode(isset($_GET['query']) ? $_GET['query'] : ''), 'where' => array('uid' => array(strval($user->_id)),
			'type' => array('playlist'))), 'main');

		$this->render('profile/playlists', array('user' => $user, 'selected_page' => 'playlists', 'sphinx' => $sphinx));
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
				array('user_id' => glue::user()->_id, 'deleted' => 0), $filter_obj))
			->sort(array('created' => -1));

		echo $this->render('videos', array('video_rows' => $video_rows, 'filter' => $filter));
	}

	function action_playlists(){

		$this->title = 'Your Playlists - StageX';

		$this->layout = 'user_section';
		$this->tab = 'playlists';

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

		$playlist_rows = Playlist::model()->fts(
			array('title', 'description'), isset($_GET['query']) ? $_GET['query'] : '', array_merge(
				array('user_id' => glue::user()->_id, 'title' => array('$ne' => 'Watch Later'), 'deleted' => 0),
				$filter_obj
			))
			->sort(array('created' => -1));

		echo $this->render('playlists', array('playlist_rows' => $playlist_rows, 'filter' => $filter));
	}

	function action_watch_later(){
		$this->title = 'Watch Later - StageX';

		$this->layout = 'user_section';
		$this->tab = 'watch_later';
		$watch_later = Playlist::model()->findOne(array('title' => 'Watch Later', 'user_id' => glue::user()->_id));
		echo $this->render('user/watch_later', array('model' => $watch_later));
	}

	function action_subscriptions(){
		$this->pageTitle = 'Your Subscriptions - StageX';

		$this->layout = 'user_section';
		$this->tab = 'subscriptions';

		$this->render('user/subscriptions', array(
			'model' => $this->loadModel()
		));
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
		$this->tab = "profile";

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

	function action_subscribe(){
		$this->pageTitle = 'Subscribe To User - StageX';

		if(glue::http()->isAjax()){
			$user = User::model()->findOne(array("_id"=>new MongoId($_GET['id'])));

			if($user){
				if(!Subscription::model()->findOne(array('from_id' => glue::session()->user->_id, 'to_id' => $user->_id))){
					$subscription = new Subscription();
					$subscription->from_id=  glue::session()->user->_id;
					$subscription->to_id = $user->_id;
					$subscription->save();

					Stream::subscribedTo(glue::session()->user->_id, $user->_id);
					echo json_encode(array("success"=>true));
				}else{
					echo json_encode(array("success"=>false));
				}
			}else{
				echo json_encode(array("success"=>false));
			}
		}else{
			Glue::getController("error/notfound");
		}
	}

	function action_unsubscribe(){
		$this->pageTitle = 'Unsubscribe From User - StageX';

		if(glue::http()->isAjax()){
			$user = User::model()->findOne(array('_id' => new MongoId($_GET['id'])));
			$subscription = Subscription::model()->findOne(array('from_id' => glue::session()->user->_id, 'to_id' => new MongoId($_GET['id'])));

			if($subscription && $user){
				$subscription->delete();

				echo json_encode(array("success"=>true));
			}else{
				echo json_encode(array("success"=>false));
			}
		}else{
			Glue::getController("error/notfound");
		}
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
				header("Location: ".Glue::http()->createUrl("/user/login"));
				exit();
			}else{
				html::setErrorFlashMessage("You must be logged in to change your email address");
				header("Location: ".Glue::http()->createUrl("/user/login", array("next"=>$user->accessToken['url'])));
				exit();
			}
		}else{
			glue::route("error/notfound");
		}
	}

	public function action_search_subscribers(){
		$this->pageTitle = 'Search For Subscriptions - StageX';

		if(!glue::http()->isAjax())
			glue::route('error/notfound');

		$query = isset($_POST['query']) ? $_POST['query'] : null;
		$page = isset($_POST['page']) ? $_POST['page'] : null;

		$sub_model = new Subscription();
		$subs = $sub_model->Db()->find(array('from_id' => glue::session()->user->_id));

		$_ids = array();
		foreach($subs as $k=>$v){
			$_ids[] = $v['to_id'];
		}

		if(!$query || strlen($query) <= 0){
			$users = User::model()->find(array('_id' => array('$in' => $_ids)));
		}else{
			$users = User::model()->find(array(
				'$or' => array(
					array('username' => new MongoRegex('/'.$query.'/i')),
					array('name' => new MongoRegex('/'.$query.'/i'))
				), '_id' => array('$in' => $_ids)));
		}

		if($users->count() > 0){
			ob_start();
			?> <div class='list' style='padding:7px 10px;'>{items}<div style='margin-top:7px;'>{pager}<div class="clear"></div></div></div> <?php
			$template = ob_get_contents();
			ob_end_clean();

			$this->widget('glue/widgets/GListView.php', array(
					'pageSize'	 => 20,
					'page' 		 => $page,
					"cursor"	 => $users,
					'template' 	 => $template,
					'itemView' => 'user/_subscription.php',
					'pagerCssClass' => 'grid_list_pager'
			));
		}else{
			?>
			<div style='font-size:16px; font-weight:normal; padding:45px; text-align:center;'>
				No subscriptions were found
			</div>
			<?php
		}
	}

	public function action_video_search_suggestions(){
		$this->pageTitle = 'Video Search - StageX';
		if(!glue::http()->isAjax()){
			glue::route('error/notfound');
		}

		$ret = array();

		$sphinx = glue::sphinx()->getSearcher();
		$sphinx->limit = 5;
		$sphinx->query(array('select' => glue::http()->param('term', ''), 'where' => array('type' => array('video'), 'uid' => array(strval(glue::session()->user->_id)))), 'main');

		if($sphinx->matches){
			foreach($sphinx->matches as $item){
					$ret[] = array('label' => $item->title);
			}
		}
		echo json_encode($ret);
	}

	/**
	 * UTIL functions
	 */

	function loadModel(){
		$user = User::model()->findOne(array("_id"=>glue::user()->_id));
		if(!$user){
			Html::setErrorFlashMessage("You must be logged in to access this area.");
			glue::http()->redirect('/user/login', array('nxt' => glue::http()->createUrl('SELF')));
		}
		return $user;
	}

	function action_manage(){
		$this->render('manage');
	}
}