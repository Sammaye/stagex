<?php

use glue\Controller;
use glue\Json;
use app\models\VideoResponse;
use app\models\Video;

class VideoResponseController extends Controller
{
	public $tab;
	
	public function behaviours()
	{
		return array(
			'auth' => array(
				'class' => 'glue\Auth',
				'rules' => array(
					array('allow',
						'actions' => array('getMore', 'getNew', 'index', 'list', 'thread', 'loadThread'),
						'users' => array('*')
					),
					array('allow', 'users' => array('@*')),
					array('deny', 'users' => array('*')),
				)
			)
		);
	}	

	/**
	 * This is the method by which all video responses for a given video are displayed at once
	 */
	public function action_index()
	{
		glue::trigger('404');
	}

	public function action_list($pending = false)
	{
		$video = Video::findOne(array('_id' => new MongoId(glue::http()->param('id',''))));
		if(!glue::auth()->check(array('viewable' => $video))){
			echo $this->render('video/deleted');
			exit();
		}

		$this->title = 'View All Responses for '.$video->title.' - StageX';
		$this->layout='user_section';
		
		$query = array('videoId' => $video->_id);
		
		if(($keywords = glue::http()->param('keywords')) && strlen(trim($keywords)) > 0){
			$keywords = preg_split('/\s/', trim($keywords));
			array_walk($keywords, function(&$n){
				$n = new \MongoRegex("/$n/");
			});
			$query['content'] = array('$in' => $keywords);
		}
		
		if($from_date = glue::http()->param('from_date')){
			$query['created']['$gte'] = new MongoDate(strtotime(str_replace('/', '-', $from_date)));
		}
		if($to_date = glue::http()->param('to_date')){
			$query['created']['$lt'] = new MongoDate(strtotime(str_replace('/', '-', $to_date . ' +1 day')));
		}
		
		$usernames_string = '';
		if($usernames = glue::http()->param('usernames')){
			$usernames = preg_split('/,/', $usernames);
			array_walk($usernames, function(&$n){
				$n = new \MongoId($n);
			});
			$query['userId'] = array('$in' => $usernames);
			$users = iterator_to_array(app\models\User::find(array('_id' => array('$in' => $usernames)))->limit(10));
			
			// Once again just to formulate the users
			foreach($usernames as $k => $v){
				$usernames_string .= (string)$v . ':' . $users[(string)$v]->getUsername() . ',';
			}
			$usernames_string = rtrim($usernames_string, ',');
		}
		
		$_SESSION['lastCommentPull'] = serialize(new \MongoDate());
		if($pending){
			$comments = VideoResponse::find(array_merge($query, array('approved' => false)));
		}else{
			$comments = VideoResponse::find($query)->visible();
		}
		
		echo $this->render('response/all', array(
			'model' => $video, 
			'pending' => $pending, 
			'username_filter_string' => $usernames_string, 
			'comments' => $comments->sort(array('created'=>-1))
		)); 
	}
	
	public function action_pending()
	{
		$video = Video::findOne(array('_id' => new \MongoId(glue::http()->param('id'))));
		if(!glue::auth()->check(array('^' => $video))){
			return glue::trigger('404');
		}		
		echo $this->action_list(true);
	}

	public function action_thread()
	{
		$this->title = 'View Response Thread - StageX';
		$this->layout = 'user_section';
		
		$comment = VideoResponse::findOne(array('_id' => new MongoId(glue::http()->param('id'))));
		if(
			!glue::auth()->check(array('viewable' => $comment)) || 
			!glue::auth()->check(array('viewable' => $comment->video))
		){
			return glue::trigger('404');
		}

		$path = $comment->path;
		$path_segs = explode(',', $path);

		if(glue::auth()->check(array('^' => $comment->video))){
			$thread_parent = VideoResponse::findOne(array('_id' => new MongoId($path_segs[0]), 'deleted' => 0));
			$thread = VideoResponse::find(array('path' => new MongoRegex('/'.$path_segs[0].',/'), 'deleted' => 0))->sort(array('created' => -1));
		}else{
			$thread_parent = VideoResponse::find(array('_id' => new MongoId($path_segs[0]), 'deleted' => 0))->visible()->one();
			$thread = VideoResponse::find(array('path' => new MongoRegex('/'.$path_segs[0].',/'), 'deleted' => 0))->visible()->sort(array('created' => -1));
		}

		if(!glue::auth()->check(array('viewable' => $thread_parent))){
			return glue::trigger('404');
		}

		echo $this->render('response/thread', array('thread_parent' => $thread_parent, 'thread' => $thread, 'video' => $comment->video));
	}

	public function action_add()
	{
		$this->title = 'Add Video Response - StageX';
		if(!glue::auth()->check('ajax','post')){
			glue::trigger('404');
		}
		
		extract(glue::http()->param(array('video_id', 'mode', 'reply_vid', 'parent_comment')), null);

		$comment = new VideoResponse();
		$video = Video::findOne(array('_id' => new MongoId($video_id)));
		if(
			!$video || !glue::auth()->check(array('viewable' => $video))
		){
			Json::error(Json::DENIED);
		}
		$comment->videoId = $video->_id;
		$comment->video = $video;
		
		if(!glue::auth()->check(array('^' => $comment->video)) && $mode == 'admin'){
			$mode = null;
		}

		if(!$video->allowTextComments){
			Json::error('Text responses have been disabled on this video');
		}
		$comment->setScenario('text_comment');
		if($parent_comment){ // Should be a truthy value
			$comment->threadParentId = new MongoId($parent_comment);
			if($comment->thread_parent instanceof app\models\VideoResponse && $comment->thread_parent->author instanceof app\models\User){
				$comment->threadParentUsername = $comment->thread_parent->author->getUsername();
			}
		}
		$comment->content = $_POST['content'];

		if($comment->validate() && $comment->save()){
			$comment_html=$this->renderPartial('response/_response', array('item' => $comment, 'mode' => $mode));
			if(glue::user()->autoshareResponses){
				app\models\AutoPublishQueue::queue(app\models\AutoPublishQueue::V_RES, glue::user()->_id, $video->_id, null, $comment->content);
			}
			//var_dump($comment->in_reply);
			Json::success(array('approved' => $comment->approved, 'html' => $comment_html));
		}else{
			//var_dump($comment->getErrorMessages());
			Json::error(array('messages' => $comment->getErrors()));
		}
	}

	public function action_approve()
	{
		if(!glue::auth()->check('ajax','post')){
			glue::trigger('404');
		}
		
		extract(glue::http()->param(array('video_id', 'ids')), null);
		
		$video = app\models\Video::findOne(array('_id' => new MongoId($video_id)));
		if(!$ids || !$video || (is_array($ids) && count($ids) <= 0)){
			Json::error(Json::UNKNOWN);
		}
		if(!glue::auth()->check(array('^' => $video))){
			Json::error(Json::DENIED);
		}
		
		$mongoIds = array();
		foreach($ids as $k => $v){
			$mongoIds[] = new MongoId($v);
		}
		
		$comments = app\models\VideoResponse::find(array('_id' => array('$in' => $mongoIds), 'videoId' => $video->_id))->limit(1000); 
		$row_count = $comments->count();
		foreach($comments as $comment){
			$comment->approve();
		}
		Json::success(array('message'=>'The comments you specified were approved', 'total'=>count($mongoIds), 'updated'=>$row_count,
			'failed' => count($mongoIds)-$row_count));		
	}

	public function action_like()
	{
		if(!glue::auth()->check('ajax')){
			glue::trigger('404');
		}

		$comment = VideoResponse::findOne(array("_id" => new MongoId($_GET['id'])));

		if($comment){

			if(!(bool)$comment->video->voteableComments){
				Json::error('Comment voting has currently been disabled on this video');
			}
			$comment->like();
			Json::success();
		}else{
			Json::error(array('messages' => 'An unknown error occured. Please try again later.'));
		}
	}

	public function action_unlike()
	{
		if(!glue::auth()->check('ajax')){
			glue::trigger('404');
		}

		$comment = VideoResponse::findOne(array("_id"=>new MongoId($_GET['id'])));

		if($comment){

			if(!(bool)$comment->video->voteableComments){
				Json::error('Comment voting has currently been disabled on this video');
			}
			$comment->unlike();
			Json::success();
		}else{
			Json::error(array('messages' => 'An unknown error occured. Please try again later.'));
		}
	}

	public function action_delete()
	{
		$this->title = 'Remove Responses - StageX';
		if(!glue::auth()->check('ajax','post')){
			glue::trigger('404');
		}
		
		extract(glue::http()->param(array('video_id', 'ids')), null);

		$video = app\models\Video::findOne(array('_id' => new MongoId($video_id)));
		if(!$ids || !$video || (is_array($ids) && count($ids) <= 0)){
			Json::error(Json::UNKNOWN);
		}

		$mongoIds = array();
		foreach($ids as $k => $v){
			$mongoIds[] = new MongoId($v);
		}

		if(glue::auth()->check(array('^' => $video))){
			$condition = array('_id' => array('$in' => $mongoIds), 'videoId' => $video->_id);
		}else{ // If this is not done by the owner the user can only delete their own comments
			$condition = array('_id' => array('$in' => $mongoIds), 'videoId' => $video->_id, 'userId'=>glue::user()->_id);
		}

		$comments = app\models\VideoResponse::findAll($condition)->limit(1000); 
		$row_count = $comments->count();
		
		app\models\VideoResponse::deleteAll($condition);
		glue::db()->videoresponse_likes->remove(array("responseId" => array('$in' => $mongoIds)));
		$video->saveCounters(array('totalResponses' => -$row_count,'totalTextResponses' => -$row_count), 0);
		
		Json::success(array('message' => 'The comments you specified were deleted', 'total' => count($mongoIds), 'updated' => $row_count,
			'failed' => count($mongoIds) - $row_count));
	}

	function action_getmore()
	{
		// Potential sort options:
		// * user_id - Displays all comments only by that user
		// * approved=true - Displays only approved comments
		// * approved=false - Displays only unapproved comments
		//
		// At the moment the approved sort is only availble to admins of the video but in time it might be available to normal users too.

		if(!glue::http()->isAjax()){
			glue::trigger('404');
		}
		
		extract(glue::http()->param(array('user_id', 'id', 'sort', 'mode', 'pagesize', 'page')));

		if($_POST['refresh'] == 1){
			$_SESSION['LastCommentPull'] = serialize(new MongoDate());
		}

		ob_start();
			?>{items}<div style='margin-top:7px;'>{pager}<div class="clear"></div></div><?php
			$template = ob_get_contents();
		ob_end_clean();

		$video = Video::findOne(array("_id"=>new MongoId($id)));
		if(glue::auth()->check(array('^' => $video))){

			if(isset($_POST['user_id'])){
				$query = array('videoId' => $video->_id, 'deleted' => 0, 'userId' => $user_id,
					'created' => array('$lte' => unserialize($_SESSION['LastCommentPull'])));
			}else{
				$query = array('$or' => array(
					array('videoId' => $video->_id),
					array('userId' => glue::user()->_id, 'videoId' => $video->_id)
				), 'deleted' => 0, 'created' => array('$lte' => unserialize($_SESSION['LastCommentPull'])));
			}

			if(isset($sort)){
				switch($sort){
					case "approved":
						$query['approved'] = true;
						break;
					case "unapproved":
						$query['approved'] = false;
						break;
					case "all":
					default:
						break;
				}
			}

			if(isset($mode)){
				switch($mode){
					case "admin":
						$mode = 'admin';
						break;
					default:
						$mode = '';
						break;
				}
			}

			$comments = VideoResponse::find($query)->sort(array('created' => -1));
		}else{
			$query = array('$or' => array(
				array('videoId' => $video->_id, 'approved' => true),
				array('userId' => glue::user()->_id, 'vid' => $video->_id)
			), 'deleted' => 0, 'ts' => array('$lte' => unserialize($_SESSION['LastCommentPull'])));

			if(isset($_POST['userId'])){
				$query['userId'] = $_POST['userId'];
			}
			$comments = VideoResponse::find($query)->sort(array('ts' => -1));
		}
		
		ob_start();
		echo glue\widgets\ListView::run(array(
		'page' 		 => $page?:1,
		"cursor"	 => $comments,
		'template' 	 => $template,
		'data' 		 => array('mode' => $mode),
		'itemView' => 'response/_response.php',
		'pagination' => array(
			'enableAjaxPagination' => true,
			'cssClass' => 'video-responses-pager',
			'pageSize'	 => $pagesize?:20,
		)
		));
		$html=ob_get_contents();
		ob_end_clean();		

		Json::success(array('html' => $html));
	}

	/**
	 * For the minute gettting live comments will refresh the list and filters and sorts. I am not sure whether I wish
	 * to keep it this way or make it keep the filters and sorts.
	 */
	function action_getNew()
	{
		if(!glue::http()->isAjax()){
			glue::trigger('404');
		}

		$video = Video::findOne(array("_id"=>new MongoId(glue::http()->param('id'))));
		if(glue::auth()->check(array('^'=>$video))){
			$comments = VideoResponse::find(array('videoId' => $video->_id, 'deleted' => 0,
				'userId' => array('$ne' => glue::user()->_id), 'ts' => array('$gt' => unserialize($_SESSION['LastCommentPull'])))
			)->sort(array('ts' => -1));
		}else{
			$comments = VideoResponse::find(array('videoId' => $video->_id, 'approved' => true, 'deleted' => 0,
				'userId' => array('$ne' => glue::user()->_id), 'ts' => array('$gt' => unserialize($_SESSION['LastCommentPull'])))
			)->sort(array('ts' => -1));
		}
		// Now that I got all comments greater lets reset the session
		Json::success(array('number_comments'=>$comments->count()));
	}
	
	public function action_loadThread()
	{
		if(!glue::http()->isAjax()){
			glue::trigger('404');
		}		
		if(
			(($id = glue::http()->param('id')) === null) ||
			($response = VideoResponse::findOne(array('_id' => new \MongoId($id)))) === null
		){
			Json::error(Json::UNKNOWN);
		}

		$parents = explode(',', $response->path);
		array_walk($parents, function(&$n){
			$n = new \MongoId($n);
		});
		array_pop($parents);
		
		ob_start();
		?><div><a class="close_thread" href="#">Close thread</a></div>
		<div class="thread_list">
		<?php foreach(
			VideoResponse::find(array('_id' => array('$in' => $parents)))->sort(array('created' => -1)) as $parent
		){ ?>
			<?php echo $this->renderPartial('response/_response', array('view' => 'ajaxthread', 'item' => $parent)) ?>
		<?php } ?>
		</div>
		<?php
		$html = ob_get_contents();
		ob_clean();
		
		Json::success(array('html' => $html));
	}
}