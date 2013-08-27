<?php

use app\models\VideoResponse,
	app\models\Video;

class videoresponseController extends \glue\Controller{

	public function authRules(){
		return array(
			array('allow',
				'actions' => array('getMore', 'getNew', 'index', 'list', 'thread'),
				'users' => array('*')
			),
			array( 'allow', 'users' => array('@*') ),
			array( 'deny', 'users' => array('*') ),
		);
	}
	
	public $tab;

	/**
	 * This is the method by which all video responses for a given video are displayed at once
	 */
	public function action_index(){
		glue::trigger('404');
	}

	public function action_list(){
		$video = Video::model()->findOne(array('_id' => new MongoId(glue::http()->param('id',''))));
		if(!glue::auth()->check(array('viewable' => $video))){
			echo $this->render('video/deleted');
			exit();
		}

		$this->title = 'View All Responses for '.$video->title.' - StageX';
		$this->layout='user_section';
		
		$now = new MongoDate();
		$_SESSION['lastCommentPull'] = serialize($now);
		echo $this->render('response/all', array('model' => $video, 'pending' => false, 'comments' => glue::auth()->check(array("^"=>$video)) ? 
			app\models\VideoResponse::model()->find(array('videoId'=>$video->_id))->sort(array('created'=>-1)) :
			app\models\VideoResponse::model()->public()->find(array('videoId'=>$video->_id))->sort(array('created'=>-1))
		)); 
	}
	
	public function action_pending(){
		$video = Video::model()->findOne(array('_id' => new MongoId(glue::http()->param('id',''))));
		if(!glue::auth()->check(array('^' => $video)))
			glue::trigger('404');
		
		$this->title = 'Moderate Responses for '.$video->title.' - StageX';
		$this->layout='user_section';
		
		$now = new MongoDate();
		$_SESSION['lastCommentPull'] = serialize($now);
		echo $this->render('response/all', array('model' => $video, 'pending' => true, 'comments' => 
			app\models\VideoResponse::model()->find(array('videoId'=>$video->_id, 'approved'=>false))->sort(array('created'=>-1)) 
		));		
	}

	public function action_thread(){

		$this->pageTitle = 'View Response Thread - StageX';
		$orig_comment = VideoResponse::model()->findOne(array('_id' => new MongoId($_GET['id'])));

		if(!$orig_comment){
			$this->render('responses/thread_deleted');
			exit();
		}
		$video = $orig_comment->in_reply;

		if(!$video)
			glue::route('error/notfound');

		if(!glue::roles()->checkRoles(array('deletedView' => $video))){
			$this->render('videos/deleted', array('video'=>$video));
			exit();
		}

		$path = $orig_comment->path;
		$path_segs = explode(',', $path);

		if(glue::roles()->checkRoles(array('^' => $video))){
			$thread_parent = VideoResponse::model()->findOne(array('_id' => new MongoId($path_segs[0]), 'deleted' => 0));
			$thread = VideoResponse::model()->find(array('path' => new MongoRegex('/'.$path_segs[0].',/'), 'deleted' => 0))->sort(array('ts' => -1));
		}else{
			$thread_parent = VideoResponse::model()->findOne(array('_id' => new MongoId($path_segs[0]), 'approved' => true, 'deleted' => 0));
			$thread = VideoResponse::model()->find(array('path' => new MongoRegex('/'.$path_segs[0].',/'), 'approved' => true, 'deleted' => 0))->sort(array('ts' => -1));
		}

		if(!$thread_parent){
			$this->render('responses/thread_deleted');
			exit();
		}

		$this->render('responses/thread', array('thread_parent' => $thread_parent, 'thread' => $thread, 'video' => $video));
	}

	public function action_add(){
		$this->title = 'Add Video Response - StageX';
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');
		extract(glue::http()->param(array('video_id','type','mode','reply_vid','parent_comment')),null);

		$comment = new VideoResponse();
		$video = Video::model()->findOne(array('_id' => new MongoId($video_id)));
		if(
			!$video||!$type||!glue::auth()->check(array('viewable' => $video))
			||($type!='text'&&$type!='video')
		)
			$this->json_error(self::DENIED);
		$comment->videoId = $video->_id;
		$comment->video=$video;
		$comment->type=$type;
		
		if(!glue::auth()->check(array('^' => $comment->video))&&$mode=='admin')
			$mode=null;		
		if($type == 'text'){
			if(!$video->allowTextComments)
				$this->json_error('Text responses have been disabled on this video');
			$comment->setScenario('text_comment');
			if($parent_comment){ // Should be a truthy value
				$comment->threadParentId = new MongoId($parent_comment);
				if($comment->thread_parent instanceof app\models\VideoResponse && $comment->thread_parent->author instanceof app\models\User)
					$comment->threadParentUsername = $comment->thread_parent->author->getUsername();
			}
			$comment->content = $_POST['content'];
		}elseif($type == 'video'){
			if(!$video->allowVideoComments)
				$this->json_error('Video responses have been disabled on this video');
			$comment->setScenario('video_comment');
			$comment->replyVideoId = new MongoId($reply_vid);
		}

		if($comment->validate()&&$comment->save()){
			$comment_html=$this->renderPartial('response/_response', array('item' => $comment, 'mode' => $mode));
			if(glue::user()->autoshareResponses)
				app\models\AutoPublishQueue::add_to_qeue(app\models\AutoPublishQueue::V_RES, glue::user()->_id, $video->_id, null, $comment->content);
			//var_dump($comment->in_reply);
			$this->json_success(array('success' => true, 'approved' => $comment->approved, 'html' => $comment_html));
		}else{
			//var_dump($comment->getErrorMessages());
			echo json_encode(array('success' => false, 'messages' => $comment->getErrors()));
		}
	}

	public function action_approve(){
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');
		extract(glue::http()->param(array('video_id','ids')),null);
		
		$video = app\models\Video::model()->findOne(array('_id' => new MongoId($video_id)));
		if(!$ids||!$video||(is_array($ids)&&count($ids)<=0))
			$this->json_error(self::UNKNOWN);
		if(!glue::auth()->check(array('^' => $video)))
			$this->json_error(self::DENIED);		
		
		$mongoIds=array();
		foreach($ids as $k => $v){
			$mongoIds[] = new MongoId($v);
		}
		
		$comments = app\models\VideoResponse::model()->find(array('_id' => array('$in' => $mongoIds), 'videoId' => $video->_id))->limit(1000); 
		$row_count = $comments->count();
		foreach($comments as $comment)
			$comment->approve();
		$this->json_success(array('message'=>'The comments you specified were approved', 'total'=>count($mongoIds), 'updated'=>$row_count,
				'failed' => count($mongoIds)-$row_count));		
	}

	public function action_like(){
		$this->pageTitle = 'Like Video Response - StageX';
		if(!glue::http()->isAjax())
			glue::route('error/notfound');

		$comment = VideoResponse::model()->findOne(array("_id"=>new MongoId($_GET['id'])));

		if($comment){

			if(!(bool)$comment->video->voteable_comments)
				GJSON::kill('Comment voting has currently been disabled on this video');

			$comment->like();
			echo json_encode(array("success"=>true));
		}else{
			echo json_encode(array('success' => false, 'messages' => 'An unknown error occured. Please try again later.'));
		}
	}

	public function action_unlike(){
		$this->pageTitle = 'Unlike Video Response - StageX';
		if(!glue::http()->isAjax())
			glue::route('error/notfound');

		$comment = VideoResponse::model()->findOne(array("_id"=>new MongoId($_GET['id'])));

		if($comment){

			if(!(bool)$comment->video->voteable_comments)
				GJSON::kill('Comment voting has currently been disabled on this video');

			$comment->unlike();
			echo json_encode(array("success"=>true));
		}else{
			echo json_encode(array("success"=>false, 'messages' => 'An unknown error occured. Please try again later.'));
		}
	}

	public function action_delete(){
		$this->title = 'Remove Responses - StageX';
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');
		extract(glue::http()->param(array('video_id','ids')),null);

		$video = app\models\Video::model()->findOne(array('_id' => new MongoId($video_id)));
		if(!$ids||!$video||(is_array($ids)&&count($ids)<=0))
			$this->json_error(self::UNKNOWN);

		$mongoIds=array();
		foreach($ids as $k => $v){
			$mongoIds[] = new MongoId($v);
		}

		if(glue::auth()->check(array('^' => $video)))
			$condition=array('_id' => array('$in' => $mongoIds), 'videoId' => $video->_id);
		else // If this is not done by the owner the user can only delete their own comments
			$condition=array('_id' => array('$in' => $mongoIds), 'videoId' => $video->_id, 'userId'=>glue::user()->_id);

		$comments = app\models\VideoResponse::model()->findAll($condition)->limit(1000); $row_count = $comments->count();
		app\models\VideoResponse::model()->deleteAll($condition);
		glue::db()->videoresponse_likes->remove(array("response_id"=>array('$in' => $mongoIds)));

		$video->saveCounters(array('totalResponses'=>-$row_count),0);
		$this->json_success(array('message'=>'The comments you specified were deleted', 'total'=>count($mongoIds), 'updated'=>$row_count,
			'failed' => count($mongoIds)-$row_count));
	}

	function action_getmore(){
		// Potential sort options:
		// * user_id - Displays all comments only by that user
		// * approved=true - Displays only approved comments
		// * approved=false - Displays only unapproved comments
		//
		// At the moment the approved sort is only availble to admins of the video but in time it might be available to normal users too.
		$this->pageTitle = 'Get Video Responses - StageX';

		if(!glue::http()->isAjax())
			glue::route('error/notfound');

		if($_POST['refresh'] == 1){
			$_SESSION['last_comment_pull'] = serialize(new MongoDate());
		}

		ob_start();
			?>{items}<div style='margin-top:7px;'>{pager}<div class="clear"></div></div><?php
			$template = ob_get_contents();
		ob_end_clean();

		$video = Video::model()->findOne(array("_id"=>new MongoId($_POST['id'])));
		if(glue::roles()->checkRoles(array('^' => $video))){

			if(isset($_POST['user_id'])){
				$query = array('vid' => $video->_id, 'deleted' => 0, 'user_id' => $_POST['user_id'],
					'ts' => array('$lte' => unserialize($_SESSION['last_comment_pull'])));
			}else{
				$query = array('$or' => array(
					array('vid' => $video->_id),
					array('user_id' => glue::session()->user->_id, 'vid' => $video->_id)
				), 'deleted' => 0, 'ts' => array('$lte' => unserialize($_SESSION['last_comment_pull'])));
			}

			if(isset($_POST['sort'])){
				switch($_POST['sort']){
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

			if(isset($_POST['mode'])){
				switch($_POST['mode']){
					case "admin":
						$mode = 'admin';
						break;
					default:
						$mode = '';
						break;
				}
			}

			$comments = VideoResponse::model()->find($query)->sort(array('ts' => -1));
		}else{
			$query = array('$or' => array(
				array('vid' => $video->_id, 'approved' => true),
				array('user_id' => glue::session()->user->_id, 'vid' => $video->_id)
			), 'deleted' => 0, 'ts' => array('$lte' => unserialize($_SESSION['last_comment_pull'])));

			if(isset($_POST['user_id'])) $query['user_id'] = $_POST['user_id'];

			$comments = VideoResponse::model()->find($query)->sort(array('ts' => -1));
		}

		$this->widget('glue/widgets/GListView.php', array(
				'pageSize'	 => isset($_POST['responses_per_page']) && $_POST['responses_per_page'] > 0 ? $_POST['responses_per_page'] : 10,
				'page' 		 => isset($_GET['page']) ? $_GET['page'] : 1,
				"cursor"	 => $comments,
				'template' 	 => $template,
				'data' 		 => array('mode' => $mode),
				'enableAjaxPagination' => true,
				'itemView' => 'responses/_response.php',
				'pagerCssClass' => 'grid_list_pager'
		));
	}

	/**
	 * For the minute gettting live comments will refresh the list and filters and sorts. I am not sure whether I wish
	 * to keep it this way or make it keep the filters and sorts.
	 */
	function action_getNew(){
		if(!glue::http()->isAjax())
			glue::trigger('404');

		$video = Video::model()->findOne(array("_id"=>new MongoId(glue::http()->param('id'))));
		if(glue::auth()->check(array('^'=>$video))){
			$comments = VideoResponse::model()->find(array('videoId' => $video->_id, 'deleted' => 0,
				'userId' => array('$ne' => glue::user()->_id), 'ts' => array('$gt' => unserialize($_SESSION['LastCommentPull'])))
			)->sort(array('ts' => -1));
		}else{
			$comments = VideoResponse::model()->find(array('videoId' => $video->_id, 'approved' => true, 'deleted' => 0,
				'userId' => array('$ne' => glue::user()->_id), 'ts' => array('$gt' => unserialize($_SESSION['LastCommentPull'])))
			)->sort(array('ts' => -1));
		}
		// Now that I got all comments greater lets reset the session
		$this->json_success(array('number_comments'=>$comments->count()));
	}

	function action_videosuggestions(){
		$this->pageTitle = 'Suggest Responses - StageX';

		if(!glue::http()->isAjax())
			glue::route('error/notfound');

		$ret = array();

		$sphinx = glue::sphinx();
		$sphinx->limit = 5;
		//var_dump($_GET['term']);
		$sphinx->query(array('select' => $_GET['term'], 'where' => array('type' => array('video'), 'uid' => array(strval(glue::session()->user->_id)))), "main");

		if($sphinx->matches){
			foreach($sphinx->matches as $item){
				$ret[] = array(
					'_id' => strval($item->_id),
					'label' => $item->title,
					'description' => $item->description,
					'image_src' => $item->getImage(33, 18)
				);
			}
		}
		echo json_encode($ret);
	}
}