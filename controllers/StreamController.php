<?php

use glue\Controller;
use glue\Model;
use glue\Json;
use app\models\User;
use app\models\Follower;
use app\models\Stream;
use app\models\Notification;

class StreamController extends Controller
{
	public $layout = 'user_section';
	public $tab = 'news_feed';

	public function behaviours()
	{
		return array(
			'auth' => array(
				'class' => 'glue\Auth',
				'rules' => array(
					array('allow', 'actions' => array('getStream'), 'users' => array('*')),
					array("allow", "users" => array("@*")),
					array("deny", "users" => array("*")),
				)
			)
		);
	}	

	public function action_index()
	{
		$this->title = 'Your Stream - StageX';
		$this->tab = 'stream';
		
		echo $this->render('stream/view', array('cursor' => $this->loadStream()));
	}

	public function action_news()
	{
		$this->title = 'Recent News - StageX';
		$this->tab = 'news_feed';
		
		$subscriptions = Follower::getAllIds();
		$stream = Stream::find(array('user_id' => array('$in' => $subscriptions),
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
		$this->tab = 'notifications';

		User::updateAll(array('_id' => glue::user()->_id),array('$set' => array('lastNotificationPull' => new MongoDate())));
		echo $this->render('stream/notifications');
	}

	public function action_share()
	{
		if(!glue::http()->isAjax()){
			glue::trigger('404');
		}

		$model = Model::create($_GET, array(
			array('type', 'in', 'range' => array('video', 'playlist')),
			array('text', 'string', 'max' => 1500),
			array('id', 'safe')
		));
		
		if(!$model->getValid()){
			Json::error(array('messages' => $model->getErrors()));
		}

		if($model->type == 'video'){
			$item_shared = Video::findOne(array('_id' => new MongoId($model->id)));
		}elseif($model->type == 'playlist'){
			$item_shared = Playlist::findOne(array('_id' => new MongoId($model->id)));
		}
		
		if(!$item_shared){
			Json::error('We could not find that item!');
		}
		
		Stream::shareItem(glue::session()->user->_id, $item_shared->_id, $model->type, strip_whitespace($model->text));
		Json::success('You shared this with your subscribers');
	}

	public function action_createComment()
	{
		if(!glue::http()->isAjax()){
			glue::trigger('404');
		}
		
		$model = Model::create($_POST, array(
			array('user_id', 'safe'),
			array('text', 'required'),
			array('text', 'string', 'max' => 1500)
		));

		if(
			($user = User::findOne(array('_id' => new MongoId($model->user_id)))) && $model->getValid()
		){
			$text = strip_whitespace($model->text);
			
			$comment = Stream::directlyMessageUser(glue::session()->user->_id, $user->_id, $text);
			$notification = Notification::directlyMessageUser(glue::session()->user->_id, $user->_id);

			if($user->email_wall_comments){
				glue::mailer()->mail($user->email, array('no-reply@stagex.co.uk', 'StageX'), 'Someone posted a comment on your profile on StageX',
					"user/new_profile_comment.php", array('username' => $user->username, 'comment' => $text, 'from' => glue::session()->user));
			}

			ob_start;
				$this->partialRender('stream/streamitem', array('item' => $comment));
				$html = ob_get_contents();
			ob_end_clean();
			Json::success(array('html' => $html));
		}else{
			Json::error();
		}
	}

	public function action_delete()
	{
		if(!glue::auth()->check('ajax','post')){
			glue::trigger('404');
		}
		
		$ids = glue::http()->param('ids');
		if(!is_array($ids) || count($ids) <= 0){
			Json::error('No stream was selected for deletion');
		}

		$mongoIds = array();
		foreach($ids as $k=>$v){
			$mongoIds[] = new MongoId($v);
		}
		Stream::deleteAll(array('_id' => array('$in' => $mongoIds), 'user_id' => glue::user()->_id));
		Json::success(array('message' => 'Stream items were deleted','updated' => count($mongoIds)));
	}

	public function action_getStream()
	{
		if(!glue::http()->isAjax()){
			glue::trigger('404');
		}

		extract(glue::http()->param(array(
			'news', 'ts', 'hide_del', 'user', 'filter'
		),null));
		
		if($user){
			$user = User::findOne(array('_id' => new MongoId($user)));
		}elseif(!$user && !$news){ // If I am not searching for news I don't need a user
			Json::error(Json::UNKNOWN);
		}elseif($news && !glue::session()->authed){ // If they want news they need to be logged in to get it
			Json::error(Json::LOGIN);
		}
			
		if($ts && !preg_match( '/^[1-9][0-9]*$/', $ts )){
			Json::error('No last stream item could be found');
		}
		if(!$news){
			$stream = $this->loadStream($ts, $user, $filter);
		}else{
			$stream = $this->loadNewsStream($ts, $filter);
		}

		$html = '';
		
		if(glue::session()->authed && glue::auth()->check(array('^' => $user))){
			$hide_del = false;
		}
		$hide_del = true;
		
		if($stream->count() > 0){
			foreach($stream as $k => $item){
				$html .= $this->renderPartial('stream/streamitem', array('item' => $item, 'hideDelete' => $hide_del));
			}
			Json::success(array('html' => $html));
		}else{
			Json::error(array('remaining' => 0, 'initMessage' => 'No stream could be found', 'message' => 'There are no more stream items to load'));
		}
	}

	public function loadStream($ts = null, $user = null, $filter = null)
	{
		$ts_sec = array();
		if($ts){
			$ts_sec = array('created' => array('$lt' => new MongoDate($ts)));
		}
		
		$filter_a = array();
		if($filter){
			switch($filter){
				case "watched":
					$filter_a = array('type' => Stream::VIDEO_WATCHED);
					break;
				case "liked":
					$filter_a = array('type' => Stream::VIDEO_RATE, 'like' => 1);
					break;
				case "posts":
					$filter_a = array('type' => array('$nin' => array(Stream::WALL_POST)));
					break;
				case "comments":
					$filter_a = array('type' => Stream::WALL_POST);
					break;
			}
		}

		return Stream::find(array_merge(
			array('user_id' => $user ? $user->_id : glue::user()->_id), 
			$ts_sec, 
			$filter_a
		))->sort(array('created' => -1))->limit(20);
	}

	public function loadNewsStream($_ts = null, $_id = null)
	{
		$subscriptions = Follower::getAllIds();

		$_ts_sec = array();
		if($_ts){
			$ts_filter = array('created' => array('$lt' => new MongoDate($_ts)));
		}

		return Stream::find(array_merge(
			array('user_id' => array('$in' => $subscriptions), 'type' => array('$nin' => array(Stream::WALL_POST))), 
			$ts_filter
		))->sort(array('created' => -1))->limit(20);
	}
}