<?php

use glue\Html,
	app\models\User;

class userController extends \glue\Controller{

	public $tab = "settings";
	public $profile_tab = "settings";

	public $title = 'Your Stuff - StageX';

	public $page;

	public function authRules(){
		return array(
			array('allow',
				'actions' => array('create', 'login', 'view', 'recover', 'view_videos', 'view_playlists', 'fb_login', 'twt_login', 'google_login'),
				'users' => array('*')
			),
			array('allow',
				'actions' => '*',
				'users' => array('@*')
			),
			array("deny",
				"users"=>array("*")
			),
		);
	}

	function action_index(){
		$this->action_videos();
	}

	function action_create(){

		$this->title = 'Create a new StageX Account';

		$model = new User;

		if(isset($_POST['User'])){
			$model->_attributes($_POST['User']);
			if($model->validate()){

				$login = new loginForm();
				$login->email = $model->email;
				$login->password = $model->password;
				$login->hash = $model->hash;

				$model->save();

				if($login->validate()){
					glue::http()->redirect("/user");
				}else{
					$model->addError("Login failed, however, it seems you are saved to our system so please try to login manually.");
				}
			}
		}

		$this->render("register", array("model" => $model));
	}

	function action_login(){

		$this->pageTitle = "Login to your StageX Account";

		$model = new loginForm();
		$model->_attributes(isset($_POST['_login']) ? $_POST['_login'] : null);

		/** Count how many times the user has logged in over 5 mins */
		$loginAttempts = Glue::db()->session_log->findOne(array("email"=>$model->email, "ts"=>array("\$gt"=>new MongoDate(time()-(60*5)))));
		if($loginAttempts['c'] > 4){
			$model->setScenario('captcha');
		}

		if(isset($_POST['loginForm'])){
			$model->_attributes($_POST['loginForm']);
			if($model->validate()){
				if(isset($_GET['nxt'])){
					glue::http()->redirect(glue::http()->param('nxt'));
				}else{
					glue::http()->redirect("/");
				}
			}
		}

		$this->render('user/login', array('model' => $model, 'attempts' => $loginAttempts['c']));
	}

	function action_fb_login(){

		$this->pageTitle = 'Logging into Stagex';

		$fb_user = glue::facebook()->getCurrentUser();
		if(!$fb_user){
			glue::http()->redirect('/user/login');
		}

		if(!$fb_user['verified']){
			$this->render('user/unverified_login');
			exit();
		}

		if(preg_match('/@googlemail.com/i', $fb_user['email']) > 0 || preg_match('/@gmail.com/i', $fb_user['email'])){
			$email_username = explode('@', $fb_user['email']);
			$user = User::model()->findOne(array('$or' => array(
				array('fb_uid' => $fb_user['id']), array('email' => array('$in' => array($email_username[0].'@googlemail.com', $email_username[0].'@gmail.com')))
			)));
		}else{
			$user = User::model()->findOne(array('$or' => array(
				array('fb_uid' => $fb_user['id']), array('email' => $fb_user['email'])
			)));
		}

		if(!$user){
			// Then lets create one and log them in
			$user = new User('social_signup');
			$user->create_username_from_social_signup(substr($fb_user['username'], 0, 20));
			$user->email = $fb_user['email'];
		}

		$user->fb_uid = $fb_user['id'];
		$user->save();

		if(glue::session()->login($user->email, $user->password, false, true)){
			if(isset($_GET['nxt'])){
				glue::http()->redirect(glue::http()->param('nxt'));
			}else{
				glue::http()->redirect("/");
			}
		}else{
			switch(glue::session()->response()){
				case "BANNED":
					$this->render('user/banned_login');
					exit();
				case "DELETED":
					$this->render('user/deleted_login');
					exit();
			}
		}
	}

	function action_google_login(){
		$this->pageTitle = 'Logging into Stagex';

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

				$user->google_uid = $g_user->id;
				$user->save();

				if(glue::session()->login($user->email, $user->password, false, true)){
					if(isset($_GET['nxt'])){
						glue::http()->redirect(glue::http()->param('nxt'));
					}else{
						glue::http()->redirect("/");
					}
				}else{
					switch(glue::session()->response()){
						case "BANNED":
							$this->render('user/banned_login');
							exit();
						case "DELETED":
							$this->render('user/deleted_login');
							exit();
					}
				}
			}else{
				glue::http()->redirect('/user/login');
			}
		}
	}

	function action_recover(){

		$this->pageTitle = 'Recover your StageX Account';

		$model = new recoverForm();
		if(isset($_POST['recoverForm'])){
			$model->_attributes($_POST['recoverForm']);
			if($model->validate()){
				$user =  User::model()->findOne(array('email' => $model->email));
				if($user){
					$user->setScenario('recoverPassword');
					$user->password = generate_new_pass();
					$user->save();
					glue::http()->redirect('/user/recover', array('success'=>true));
				}
			}
		}

		if(isset($_GET['success']) && !$model->hasErrors()){
			$model->setSuccess(true);
			$model->setHasBeenValidated(true);
		}
//var_dump($model->getErrors());
		$this->render('user/forgot_password', array('model' => $model));
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

		$this->pageTitle = 'Your Videos - StageX';
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

		$video_rows = Video::model()->search(
			array('title', 'description', 'tags'), isset($_GET['query']) ? $_GET['query'] : '', array_merge(
				array('user_id' => glue::session()->user->_id, 'deleted' => 0), $filter_obj))
			->sort(array('created' => -1));

		$this->render('user/videos', array('video_rows' => $video_rows, 'filter' => $filter));
	}

	function action_playlists(){

		$this->pageTitle = 'Your Playlists - StageX';

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

		$playlist_rows = Playlist::model()->search(
			array('title', 'description'), isset($_GET['query']) ? $_GET['query'] : '', array_merge(
				array('user_id' => glue::session()->user->_id, 'title' => array('$ne' => 'Watch Later'), 'deleted' => 0),
				$filter_obj
			))
			->sort(array('ts' => -1));

		$this->render('user/playlists', array('playlist_rows' => $playlist_rows, 'filter' => $filter));
	}

	function action_watch_later(){
		$this->pageTitle = 'Watch Later - StageX';

		$this->layout = 'user_section';
		$this->tab = 'watch_later';
		$watch_later = Playlist::model()->findOne(array('title' => 'Watch Later', 'user_id' => glue::session()->user->_id));
		$this->render('user/watch_later', array('model' => $watch_later));
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

		$this->pageTitle = 'General Settings - StageX';

		$this->layout = "user_section";

		$model = $this->loadModel();
		$previousModel = $model->getAttributes();

		$success_message = '';
		if(isset($_SESSION['success_message']) && !isset($_POST['User'])){
			// DONE
			$model->setSuccess(true);
			$model->setHasBeenValidated(true);
			$success_message = $_SESSION['success_message'];
			unset($_SESSION['success_message']);
		}

		if(isset($_POST['User'])){

			$model->setScenario($_POST['User']['action']);
			unset($_POST['User']['action']);

			$model->_attributes($_POST['User']);
			//var_dump($model);

			if($model->validate()){
				$model->save();

				switch($model->getScenario()){
					case "updateUsername":
						$_SESSION['success_message'] = "Your username has been changed";
						break;
					case "updateEmail":
						$_SESSION['success_message'] = "A confirmation email has been sent to your current inbox. Please click on the link within that Email to confirm your switch to your new mailbox.";
						break;
					case "updatePassword":
						$_SESSION['success_message'] = "Your password has been changed";
						break;
					case "updatePrivacy":
						$_SESSION['success_message'] = "Your privacy settings have been changed";
						break;
					case "updateSecurity":
						$_SESSION['success_message'] = "Your security settings have been changed";
						break;
					case "updateSafeSearch":
						$_SESSION['success_message'] = "Your safe search settings have been saved";
						break;
					case "updatePlayback":
						$_SESSION['success_message'] = "Your playback settings have been saved";
						break;
					case "updateENots":
						$_SESSION['success_message'] = "Your email notification settings have been saved";
						break;
					case "updateAnalytics":
						$_SESSION['success_message'] = "Your analytics settings have been saved";
						break;
					default:
						$_SESSION['success_message'] = "Your account settings have been changed";
						break;
				}

				glue::http()->redirect("/user/settings");
			}else{
				$model->attributes($previousModel);
			}
		}

		$this->page = "settings";

		$this->render(
			"settings",
			array("model"=>$model, 'success_message' => $success_message)
		);
	}

	function action_autoshare(){
		$this->pageTitle = 'Autoshare Settings - StageX';

		$this->layout = "user_section";
		$this->tab = "sharing";
		$this->page = "settings";

		$model = $this->loadModel();
		if(isset($_SESSION['success_message']) && !isset($_POST['User'])){
			// DONE
			$model->setSuccess(true);
			$model->setHasBeenValidated(true);
			unset($_SESSION['success_message']);
		}

		if(isset($_POST['User'], $_POST['User']['autoshare_opts'])){
			$valid = $model->setAutoshareOptions($_POST['User']['autoshare_opts']);
			if($valid){
				$model->save();

				$_SESSION['success_message'] = "Your auto-sharing settings have been saved.";
				glue::http()->redirect("/user/autoshare");
			}
		}

		$this->render('user/sharing', array( 'model' => $model ));
	}

	function action_uploadpref(){
		$this->pageTitle = 'Upload Preferences - StageX';

		$this->layout = "user_section";
		$this->tab = "uploadpref";
		$this->page = "settings";

		$model = $this->loadModel();

		if(isset($_SESSION['success_message']) && !isset($_POST['User'])){
			// DONE
			//echo "here";
			$model->setSuccess(true);
			$model->setHasBeenValidated(true);
			unset($_SESSION['success_message']);
		}

		if(isset($_POST['User'], $_POST['User']['default_video_settings'])){
			$valid = $model->setDefaultVideoSettings($_POST['User']['default_video_settings']);

			if($valid){
				$model->save();

				$_SESSION['success_message'] = "Your upload preferences have been saved";
				glue::http()->redirect("/user/uploadpref");
			}
		}

		$this->render('user/uploadpref', array(
			"defaults_model"=>$model
		));
	}

	function action_activity(){
		$this->pageTitle = 'Account Activity - StageX';

		$this->tab = 'activity';
		$this->layout = "user_section";
		$this->page = "settings";

		$model = $this->loadModel();

		$this->render('user/activity', array(
			'model' => $model
		));
	}

	function action_removesession(){
		$this->pageTitle = 'Remove Session - StageX';
		if(!glue::http()->isAjax())
			glue::getController("error/notfound");

		if(isset($_GET['id'])){
			$user = $this->loadModel();

			if(!$user)
				echo json_encode(array("success" => false));

			unset($user->ins[$_GET['id']]);
			$user->save();

			echo json_encode(array("success" => true));
		}else{
			echo json_encode(array("success" => false));
		}
	}

	function action_profile(){
		$this->pageTitle = 'Profile Settings - StageX';

		$this->layout = "user_section";
		$model = $this->loadModel();
		//var_dump($model->getAttributes());
		$this->page = "settings";
//do_dump($_FILES);
//exit();

		$success_message = '';
		if(isset($_SESSION['success_message']) && !$_POST){
			$model->setSuccess(true);
			$model->setHasBeenValidated(true);
			$success_message = $_SESSION['success_message'];
			unset($_SESSION['success_message']);
		}

		$this->tab = "profile";

		if(isset($_POST['User'])){
			$model->setScenario($_POST['User']['action']);

			switch($model->getScenario()){
				case "updatePic":
					if(isset($_POST['User'])){
						$model->files($_FILES['User']);
						if($model->validate()){
							$model->setPic();

							$_SESSION['success_message'] = "Your profile picture have been changed";
							glue::http()->redirect("/user/profile");
						}
					}
					break;
				case "updateProfile":
					$valid = $model->setProfilePrivacy(isset($_POST['User']['profile_privacy']) ? $_POST['User']['profile_privacy'] : array());
					$model->_attributes($_POST['User']);
					if($model->validate() && $valid){
						$model->save();

						$_SESSION['success_message'] = "Your profile settings have saved";
						glue::http()->redirect("/user/profile");
					}
					break;
				case "updateSocialProfiles":

					$valid = $model->setExternalLinks(isset($_POST['User'], $_POST['User']['external_links']) ? $_POST['User']['external_links'] : array());

					if($valid){
						$model->save();
						$_SESSION['success_message'] = "Your external links have been updated";
						glue::http()->redirect("/user/profile");
					}
					break;
			}
		}
		$this->render('user/profile_settings', array('model' => $model, 'success_message' => $success_message));
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
		$this->pageTitle = 'Logout of StageX';

		Glue::session()->logout(false);

		if(isset($_GET['nxt'])){
			header("Location: ".$_GET['nxt']);
			exit();
		}
		header("Location: /");
		exit();
	}

	function action_deactivate(){
		$this->pageTitle = 'Deactivate Your StageX Account';
		$this->layout = "blank_page";

		$model = $this->loadModel();
		$toDelete = isset($_GET['delete']) ? $_GET['delete'] : null;

		if($toDelete == 1){
			$model->deleted = true;
			unset($model->ins);
			$model->save();

			glue::db()->users->save(array('_id' => $model->_id, 'deleted' => 1, 'username' => '[User Deleted]')); // Empty the document

			glue::db()->delete_queue->insert(array('object_id' => $model->_id, 'type' => 'user', 'ts' => new MongoDate()));

			glue::session()->logout(false);
			html::setSuccessFlashMessage("Your account has been deleted!");
			header("Location: /user/login");
			exit();
		}

		$this->render('user/deactivate');
	}


	public function action_confirminbox(){
		$this->pageTitle = 'Confirm Your New Email Address - StageX';

		$email = urldecode(glue::http()->param('e', ''));
		$hash = urldecode(glue::http()->param('h', ''));
		$id = new MongoId(urldecode(glue::http()->param('uid', '')));

		$user = User::model()->findOne(array('_id' => $id));
		$to = $user->temp_access_token['to'];

		if($to > time() && $user->temp_access_token['hash'] == $hash && $user->temp_access_token['y'] == "E_CHANGE" && $user->temp_access_token['email'] == $email){
			if($_SESSION['logged']){

				$user->email = $email;
				unset($user->temp_access_token);
				unset($user->ins);
				$user->save();

				Glue::session()->logout(false);

				html::setSuccessFlashMessage("Email Changed! All devices have been signed out. You must login again.");
				header("Location: ".Glue::url()->create("/user/login"));
				exit();
			}else{
				html::setErrorFlashMessage("You must be logged in to change your email address");
				header("Location: ".Glue::url()->create("/user/login", array("next"=>$user->temp_access_token['url'])));
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
			?> <div class='list' style='padding:7px 10px;'>{items}<div style='margin-top:7px;'>{pager}<div class="clearer"></div></div></div> <?php
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
		$user = User::model()->findOne(array("_id"=>glue::session()->user->_id));
		if(!$user){
			glue::flash()->ERROR("You must be logged in to access this area.");
			glue::http()->redirect('/user/login', array('nxt' => glue::url()->create('SELF')));
			exit();
		}
		return $user;
	}

	function action_manage(){
		$this->render('manage');
	}
}