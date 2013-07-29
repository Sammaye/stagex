<?php

use app\models\Follower,
	app\models\Stream;

class StreamController extends glue\Controller{

	public $layout = 'user_section';
	public $subtab;

	public function authRules(){
		return array(
			array('allow', 'actions' => array('get_stream'), 'users' => array('*')),
			array("allow", "users"=>array("@*")),
			array("deny", "users"=>array("*")),
		);
	}

	public function action_index(){
		$this->pageTitle = 'Your Stream - StageX';

		$this->tab = 'stream';
		echo $this->render('stream/view', array('model' => $this->load_single_stream()));
	}

	public function action_news(){
		$this->pageTitle = 'Recent News - StageX';

		$this->tab = 'news_feed';
		$subscription_model = new Follower();

		$subscriptions = $subscription_model->getAll_ids();
		$stream = Stream::model()->find(array('user_id' => array('$in' => $subscriptions),
			'$or' => array(
				array('type' => array('$nin' => array(Stream::WALL_POST))),
				array('comment_user' => array('$in' => $subscriptions))
			)
		))->sort(array('ts' => -1))->limit(20);
		echo $this->render('stream/news_feed', array('model' => $stream, 'subscriptions' => $subscriptions));
	}

	public function action_notifications(){
		$this->pageTitle = 'Notifications - StageX';

		glue::session()->user->last_notification_pull = new MongoDate();
		glue::session()->user->save();

		$this->tab = 'notifications';
		$this->render('stream/notifications');
	}

	public function action_share(){
		$this->pageTitle = 'Share - StageX';

		if(!glue::http()->isAjax()){
			glue::route('/error/notfound');
		}

		$type = isset($_GET['type']) ? $_GET['type'] : null;
		if($type != 'video' && $type != 'playlist'){
			echo json_encode(array('success' => false));
			exit();
		}

		$text = '';
		if(strlen(strip_all($_GET['text'])) > 0){
			$text = $_GET['text'];
		}

		if(strlen(strip_all($_GET['text'])) > 1500){
			echo json_encode(array('success' => false, 'message' => 'You can only enter 1500 characters'));
			exit();
		}

		if($type == 'video'){
			$item_shared = Video::model()->findOne(array('_id' => new MongoId($_GET['id'])));
		}elseif($type == 'playlist'){
			$item_shared = Playlist::model()->findOne(array('_id' => new MongoId($_GET['id'])));
		}

		if($item_shared){
			Stream::shareItem(glue::session()->user->_id, $item_shared->_id, $type, $text);
			echo json_encode(array('success' => true, 'message' => 'You shared this with your subscribers'));
		}else{
			echo json_encode(array('success' => false, 'message' => 'We could not find that item!'));
		}
	}

	public function action_add_comment(){
		$this->pageTitle = 'Add Comment - StageX';

		if(!glue::http()->isAjax()){
			glue::route('/error/notfound');
		}

		$text = strip_whitespace($_POST['text']);
		$user = User::model()->findOne(array('_id' => new MongoId($_POST['user_id'])));

		if($user && strlen($text) > 0){
			$comment = Stream::newWallPost_on_OtherUserWall(glue::session()->user->_id, $user->_id, $text);
			$notification = Notification::newWallPost_on_OtherUserWall(glue::session()->user->_id, $user->_id);

			if($user->email_wall_comments){
				glue::mailer()->mail($user->email, array('no-reply@stagex.co.uk', 'StageX'), 'Someone posted a comment on your profile on StageX',
					"user/new_profile_comment.php", array( 'username' => $user->username, 'comment' => $text, 'from' => glue::session()->user ));
			}

			ob_start;
				$this->partialRender('stream/streamitem', array('item' => $comment));
				$html = ob_get_contents();
			ob_end_clean();
			echo json_encode(array('success' => true, 'html' => $html));
		}else{
			echo json_encode(array('success' => false));
		}
	}

	public function action_deleteitems(){
		$this->pageTitle = 'Remove Stream - StageX';

		if(!glue::http()->isAjax())
			glue::route(glue::config('404', 'errorPages'));

		if(count($_POST['items']) > 0){
			$items = array();

			foreach($_POST['items'] as $k=>$v){
				$items[] = new MongoId($v);
			}
			$stream = new Stream;
			$stream->removeAll_byid($items);

			if(count($_POST['items']) > 1){
				GJSON::kill('Stream items were deleted', true);
			}else{
				GJSON::kill('Stream item was deleted', true);
			}
		}else{
			GJSON::kill('No stream items were selected for deletion');
		}
	}

	public function action_clearall(){
		$this->pageTitle = 'Remove Stream - StageX';

		exit(); // We exit this function for the min, I am not sure if I can trust it

		if(!glue::http()->isAjax())
			glue::route(glue::config('404', 'errorPages'));

		$stream = new Stream;
		$stream->removeAll();

		ob_start();
			$items = Stream::model()->find(array('user_id' => glue::session()->user->_id))->sort(array('ts' => -1))->limit(20);

			if(count($items) > 0){
				foreach($model as $k => $item){
					$this->partialRender('stream/streamitem', array('item' => $item));
				}
			}else{ ?>
				<div style='font-size:16px; font-weight:normal; padding:21px;'>No stream has yet been recorded for your user</div>
			<?php }
			$html = ob_get_contents();
		ob_end_clean();

		if($html){
			echo json_encode(array('success' => true, 'html' => $html));
		}else{
			echo json_encode(array('success' => false));
		}
	}

	function action_get_stream(){
		$this->pageTitle = 'Get Stream - StageX';

		if(!glue::http()->isAjax())
			glue::route(glue::config('404', 'errorPages'));

		$get_news = isset($_GET['news']) ? $_GET['news'] : null;
		$_last_ts = isset($_GET['ts']) ? $_GET['ts'] : null;
		$_hide_del = isset($_GET['hide_del']) ? true : false;
		$_user_id = isset($_GET['uid']) ? User::model()->findOne(array('_id' => new MongoId($_GET['uid']))) : null;
		$_filter = isset($_GET['filter']) ? $_GET['filter'] : null;

		if($_last_ts && !( 1 === preg_match( '/^[1-9][0-9]*$/', $_last_ts ))){
			GJSON::kill(array('No last stream item could be found'));
		}

		if(!$_user_id && !glue::session()->authed)
			GJSON::kill(GJSON::UNKNOWN);

		if(!$get_news){
			$stream = $this->load_single_stream($_last_ts, $_user_id, $_filter);
		}else{
			$stream = $this->load_news_stream($_last_ts, $_filter);
		}

		$html = '';
		if($stream->count() > 0){
			foreach($stream as $k => $item){
				ob_start();
					$this->partialRender('stream/streamitem', array('item' => $item, 'hideDelete' => $_hide_del));
					$partial_html = ob_get_contents();
				ob_end_clean();
				$html .= $partial_html;
			}
			GJSON::kill(array('html' => $html), true);
		}else{
			GJSON::kill(array('noneleft' => true, 'messages' => array('There are no more stream items to load'), 'initMessage' => 'No stream could be found'));
		}
	}

	function load_single_stream($_ts = null, $user = null, $filter = null){

		$_ts_sec = array();
		if($_ts)
			$_ts_sec = array('created' => array('$lt' => new MongoDate($_ts)));

		$_filter_a = array();
		if($filter){
			switch($filter){
				case "watched":
					$_filter_a = array('type' => Stream::VIDEO_WATCHED);
					break;
				case "liked":
					$_filter_a = array('type' => Stream::VIDEO_RATE, 'like' => 1);
					break;
				case "actions":
					$_filter_a = array('type' => array('$nin' => array(Stream::WALL_POST)));
					break;
				case "comments":
					$_filter_a = array('type' => Stream::WALL_POST);
					break;
			}
		}

		return Stream::model()->find(array_merge(array(
			'user_id' => $user ? $user->_id : glue::user()->_id
		), $_ts_sec, $_filter_a))->sort(array('created' => -1))->limit(20);
	}

	function load_news_stream($_ts = null, $_id = null){
		$subscription_model = new Subscription();
		$subscriptions = $subscription_model->getAll_ids();

		$_ts_sec = array();
		if($_ts)
			$_ts_sec = array('ts' => array('$lt' => new MongoDate($_ts)));

		$_filter_a = array();
		if($filter){
			switch($filter){
				case "watched":
					$_filter_a = array('type' => array('$nin' => array(Stream::VIDEO_WATCHED)));
					break;
				case "liked":
					$_filter_a = array('type' => array('$nin' => array(Stream::VIDEO_RATE), 'like' => 1));
					break;
				case "actions":
					$_filter_a = array('type' => array('$nin' => array(Stream::WALL_POST)));
					break;
				case "comments":
					$_filter_a = array('type' => Stream::WALL_POST);
					break;
			}
		}

		return Stream::model()->find(array_merge(array('user_id' => array('$in' => $subscriptions),
			'type' => array('$nin' => array(Stream::WALL_POST))), $_ts_sec, $_filter_a))->sort(array('ts' => -1))->limit(20);
	}
}