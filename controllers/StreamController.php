<?php

use \glue\Controller;
use app\models\Follower;
use app\models\Stream;
use app\models\Notification;

class StreamController extends Controller
{
	public $layout = 'user_section';
	public $subtab;

	public function behaviours()
	{
		return array(
			'auth' => array(
				'class' => 'glue\Auth',
				'rules' => array(
					array('allow', 'actions' => array('getStream'), 'users' => array('*')),
					array("allow", "users"=>array("@*")),
					array("deny", "users"=>array("*")),
				)
			)
		);
	}	

	public function action_index()
	{
		$this->title = 'Your Stream - StageX';

		$this->tab = 'stream';
		echo $this->render('stream/view', array('cursor' => $this->load_single_stream()));
	}

	public function action_news()
	{
		$this->pageTitle = 'Recent News - StageX';

		$this->tab = 'news_feed';
		$subscription_model = new Follower();

		$subscriptions = $subscription_model->getAll_ids();
		$stream = Stream::model()->find(array('user_id' => array('$in' => $subscriptions),
			'$or' => array(
				array('type' => array('$nin' => array(Stream::WALL_POST))),
				array('comment_user' => array('$in' => $subscriptions))
			)
		))->sort(array('created' => -1))->limit(20);
		echo $this->render('stream/news_feed', array('stream' => $stream, 'subscriptions' => $subscriptions));
	}

	public function action_notifications()
	{
		$this->title = 'Notifications - StageX';

		app\models\User::model()->updateAll(array('_id'=>glue::user()->_id),array('$set'=>array('lastNotificationPull'=>new MongoDate())));

		$this->tab = 'notifications';
		echo $this->render('stream/notifications');
	}

	public function action_share()
	{
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

	public function action_add_comment()
	{
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

	public function action_delete()
	{
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');
		
		$ids=glue::http()->param('ids');
		if(!is_array($ids)||count($ids)<=0)
			$this->json_error('No stream was selected for deletion');

		$mongoIds = array();
		foreach($ids as $k=>$v)
			$mongoIds[] = new MongoId($v);
		Stream::model()->deleteAll(array('_id'=>array('$in'=>$mongoIds), 'user_id'=>glue::user()->_id));
		$this->json_success(array('message'=>'Stream items were deleted','updated'=>count($mongoIds)));
	}

	function action_getStream()
	{
		if(!glue::http()->isAjax())
			glue::trigger('404');

		extract(glue::http()->param(array(
			'news', 'ts', 'hide_del', 'user', 'filter'
		),null));
		
		if($user)
			$user = app\models\User::model()->findOne(array('_id' => new MongoId($user)));
		elseif(!$user&&!$news) // If I am not searching for news I don't need a user
			$this->json_error(self::UNKNOWN);
		elseif($news&&!glue::session()->authed) // If they want news they need to be logged in to get it
			$this->json_error(self::LOGIN);		
			
		if($ts && !preg_match( '/^[1-9][0-9]*$/', $ts ))
			$this->json_error('No last stream item could be found');

		if(!$news)
			$stream = $this->load_single_stream($ts, $user, $filter);
		else
			$stream = $this->load_news_stream($ts, $filter);

		$html = '';
		
		if(glue::session()->authed&&glue::auth()->check(array('^' => $user))){
			$hide_del=false;
		}
		$hide_del=true;
		
		if($stream->count() > 0){
			foreach($stream as $k => $item)
				$html.=$this->renderPartial('stream/streamitem', array('item' => $item, 'hideDelete' => $hide_del));
			$this->json_success(array('html'=>$html));
		}else
			$this->json_error(array('remaining'=>0,'initMessage'=>'No stream could be found','message'=>'There are no more stream items to load'));
	}

	function load_single_stream($_ts = null, $user = null, $filter = null)
	{
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
				case "posts":
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

	function load_news_stream($_ts = null, $_id = null)
	{
		$subscription_model = new app\models\Follower();
		$subscriptions = $subscription_model->getAll_ids();

		$_ts_sec = array();
		if($_ts)
			$ts_filter = array('created' => array('$lt' => new MongoDate($_ts)));

		return Stream::model()->find(array_merge(array('user_id' => array('$in' => $subscriptions),
			'type' => array('$nin' => array(Stream::WALL_POST))), $ts_filter))->sort(array('created' => -1))->limit(20);
	}
}