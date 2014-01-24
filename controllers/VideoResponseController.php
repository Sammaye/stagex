<?php

use \glue\Controller;
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
						'actions' => array('getMore', 'getNew', 'index', 'list', 'thread'),
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

	public function action_list()
	{
		$video = Video::findOne(array('_id' => new MongoId(glue::http()->param('id',''))));
		if(!glue::auth()->check(array('viewable' => $video))){
			echo $this->render('video/deleted');
			exit();
		}

		$this->title = 'View All Responses for '.$video->title.' - StageX';
		$this->layout='user_section';
		
		$query=array();
		if($filter_type=glue::http()->param('filter-type')){
			if($filter_type=='text')
				$query['type']='text';
			if($filter_type=='video')
				$query['type']='video';
		}
		
		if(($keywords=glue::http()->param('filter-keywords'))&&strlen(trim($keywords))>0){
			$keywords=preg_split('/\s/', trim($keywords));
			$formed_keywords=array();
			foreach($keywords as $k)
				$formed_keywords[]=new MongoRegex("/$k/");
			$query['content']=array('$in' => $formed_keywords);
		}
		
		if($from_date=glue::http()->param('from_date'))
			$query['created']['$gte']=new MongoDate(strtotime(str_replace('/','-',$from_date)));
		if($to_date=glue::http()->param('to_date'))
			$query['created']['$lt']=new MongoDate(strtotime(str_replace('/','-',$to_date.' +1 day')));		
		
		$usernames_string='';
		if($usernames=glue::http()->param('filter-username')){
			$usernames=preg_split('/,/',$usernames);
			foreach($usernames as $k=>$v){
				$usernames[$k]=new MongoId($v);
			}
			$query['userId'] = array('$in'=>$usernames);
			$users=iterator_to_array(app\models\User::find(array('_id'=>array('$in'=>$usernames))));
			
			// Once again just to formulate the users
			foreach($usernames as $k => $v){
				$usernames_string.=(string)$v.':'.$users[(string)$v]->getUsername().',';
			}
			$usernames_string=rtrim($usernames_string,',');
		}
		
		$now = new MongoDate();
		$_SESSION['lastCommentPull'] = serialize($now);
		echo $this->render('response/all', array('model' => $video, 'pending' => false, 'username_filter_string' => $usernames_string, 'comments' => glue::auth()->check(array("^"=>$video)) ? 
			app\models\VideoResponse::find(array_merge(array('videoId'=>$video->_id),$query))->sort(array('created'=>-1)) :
			app\models\VideoResponse::find(array_merge(array('videoId'=>$video->_id),$query))->visible()->sort(array('created'=>-1))
		)); 
	}
	
	public function action_pending()
	{
		$video = Video::findOne(array('_id' => new MongoId(glue::http()->param('id',''))));
		if(!glue::auth()->check(array('^' => $video)))
			glue::trigger('404');
		
		$this->title = 'Moderate Responses for '.$video->title.' - StageX';
		$this->layout='user_section';
		
		$query=array();
		if($filter_type=glue::http()->param('filter-type')){
			if($filter_type=='text')
				$query['type']='text';
			if($filter_type=='video')
				$query['type']='video';
		}
		
		if(($keywords=glue::http()->param('filter-keywords'))&&strlen(trim($keywords))>0){
			$keywords=preg_split('/\s/', trim($keywords));
			$formed_keywords=array();
			foreach($keywords as $k)
				$formed_keywords[]=new MongoRegex("/$k/");
			$query['content']=array('$in' => $formed_keywords);
		}
		
		if($from_date=glue::http()->param('from_date'))
			$query['created']['$gte']=new MongoDate(strtotime(str_replace('/','-',$from_date)));
		if($to_date=glue::http()->param('to_date'))
			$query['created']['$lt']=new MongoDate(strtotime(str_replace('/','-',$to_date.' +1 day')));
		
		$usernames_string='';
		if($usernames=glue::http()->param('filter-username')){
			$usernames=preg_split('/,/',$usernames);
			foreach($usernames as $k=>$v){
				$usernames[$k]=new MongoId($v);
			}
			$query['userId'] = array('$in'=>$usernames);
			$users=iterator_to_array(app\models\User::find(array('_id'=>array('$in'=>$usernames))));
				
			// Once again just to formulate the users
			foreach($usernames as $k => $v){
				$usernames_string.=(string)$v.':'.$users[(string)$v]->getUsername().',';
			}
			$usernames_string=rtrim($usernames_string,',');
		}		
		
		$now = new MongoDate();
		$_SESSION['lastCommentPull'] = serialize($now);
		echo $this->render('response/all', array('model' => $video, 'pending' => true, 'username_filter_string' => $usernames_string, 'comments' => 
			app\models\VideoResponse::find(array('videoId'=>$video->_id, 'approved'=>false))->sort(array('created'=>-1)) 
		));		
	}

	public function action_thread()
	{
		$this->title = 'View Response Thread - StageX';
		$this->layout='user_section';
		
		$comment = VideoResponse::findOne(array('_id' => new MongoId(glue::http()->param('id'))));
		if(
			!glue::auth()->check(array('viewable'=>$comment)) || 
			!glue::auth()->check(array('viewable'=>$comment->video))
		)
			glue::trigger('404');

		$path = $comment->path;
		$path_segs = explode(',', $path);

		if(glue::auth()->check(array('^' => $comment->video))){
			$thread_parent = VideoResponse::findOne(array('_id' => new MongoId($path_segs[0]), 'deleted' => 0));
			$thread = VideoResponse::find(array('path' => new MongoRegex('/'.$path_segs[0].',/'), 'deleted' => 0))->sort(array('ts' => -1));
		}else{
			$thread_parent = VideoResponse::find(array('_id' => new MongoId($path_segs[0]), 'deleted' => 0))->visible()->one();
			$thread = VideoResponse::find(array('path' => new MongoRegex('/'.$path_segs[0].',/'), 'deleted' => 0))->visible()->sort(array('ts' => -1));
		}

		if(!glue::auth()->check(array('viewable' => $thread_parent)))
			glue::trigger('404');

		echo $this->render('response/thread', array('thread_parent' => $thread_parent, 'thread' => $thread, 'video' => $comment->video));
	}

	public function action_add()
	{
		$this->title = 'Add Video Response - StageX';
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');
		extract(glue::http()->param(array('video_id','type','mode','reply_vid','parent_comment')),null);

		$comment = new VideoResponse();
		$video = Video::findOne(array('_id' => new MongoId($video_id)));
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

	public function action_approve()
	{
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');
		extract(glue::http()->param(array('video_id','ids')),null);
		
		$video = app\models\Video::findOne(array('_id' => new MongoId($video_id)));
		if(!$ids||!$video||(is_array($ids)&&count($ids)<=0))
			$this->json_error(self::UNKNOWN);
		if(!glue::auth()->check(array('^' => $video)))
			$this->json_error(self::DENIED);		
		
		$mongoIds=array();
		foreach($ids as $k => $v){
			$mongoIds[] = new MongoId($v);
		}
		
		$comments = app\models\VideoResponse::find(array('_id' => array('$in' => $mongoIds), 'videoId' => $video->_id))->limit(1000); 
		$row_count = $comments->count();
		foreach($comments as $comment)
			$comment->approve();
		$this->json_success(array('message'=>'The comments you specified were approved', 'total'=>count($mongoIds), 'updated'=>$row_count,
				'failed' => count($mongoIds)-$row_count));		
	}

	public function action_like()
	{
		if(!glue::auth()->check('ajax'))
			glue::trigger('404');

		$comment = VideoResponse::findOne(array("_id"=>new MongoId($_GET['id'])));

		if($comment){

			if(!(bool)$comment->video->voteableComments)
				$this->json_error('Comment voting has currently been disabled on this video');

			$comment->like();
			echo json_encode(array("success"=>true));
		}else{
			echo json_encode(array('success' => false, 'messages' => 'An unknown error occured. Please try again later.'));
		}
	}

	public function action_unlike()
	{
		if(!glue::auth()->check('ajax'))
			glue::trigger('404');

		$comment = VideoResponse::findOne(array("_id"=>new MongoId($_GET['id'])));

		if($comment){

			if(!(bool)$comment->video->voteableComments)
				$this->json_error('Comment voting has currently been disabled on this video');

			$comment->unlike();
			echo json_encode(array("success"=>true));
		}else{
			echo json_encode(array("success"=>false, 'messages' => 'An unknown error occured. Please try again later.'));
		}
	}

	public function action_delete()
	{
		$this->title = 'Remove Responses - StageX';
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');
		extract(glue::http()->param(array('video_id','ids')),null);

		$video = app\models\Video::findOne(array('_id' => new MongoId($video_id)));
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

		$comments = app\models\VideoResponse::findAll($condition)->limit(1000); 
		$row_count = $comments->count();
		
		$text_count=app\models\VideoResponse::findAll(array_merge($condition,array('type'=>'text')))->limit(1000)->count();
		$video_count=app\models\VideoResponse::findAll(array_merge($condition,array('type'=>'video')))->limit(1000)->count();
		
		app\models\VideoResponse::deleteAll($condition);
		glue::db()->videoresponse_likes->remove(array("response_id"=>array('$in' => $mongoIds)));
		$video->saveCounters(array('totalResponses'=>-$row_count,'totalTextResponses'=>-$text_count,'totalVideoResponses'=>-$video_count),0);
		
		$this->json_success(array('message'=>'The comments you specified were deleted', 'total'=>count($mongoIds), 'updated'=>$row_count,
			'failed' => count($mongoIds)-$row_count));
	}

	function action_getmore()
	{
		// Potential sort options:
		// * user_id - Displays all comments only by that user
		// * approved=true - Displays only approved comments
		// * approved=false - Displays only unapproved comments
		//
		// At the moment the approved sort is only availble to admins of the video but in time it might be available to normal users too.

		if(!glue::http()->isAjax())
			gkue::trigger('404');
		
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

			if(isset($_POST['userId'])) $query['userId'] = $_POST['userId'];

			$comments = VideoResponse::find($query)->sort(array('ts' => -1));
		}
		
		ob_start();
		echo glue\widgets\ListView::run(array(
		'pageSize'	 => 1,
		'page' 		 => $page?:1,
		"cursor"	 => $comments,
		'template' 	 => $template,
		'data' 		 => array('mode' => $mode),
		'enableAjaxPagination' => true,
		'itemView' => 'response/_response.php',
		'pagerCssClass' => 'grid_list_pager'
		));
		$html=ob_get_contents();
		ob_end_clean();		

		echo json_encode(array('success' => true, 'html' => $html));
	}

	/**
	 * For the minute gettting live comments will refresh the list and filters and sorts. I am not sure whether I wish
	 * to keep it this way or make it keep the filters and sorts.
	 */
	function action_getNew()
	{
		if(!glue::http()->isAjax())
			glue::trigger('404');

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
		$this->json_success(array('number_comments'=>$comments->count()));
	}

	function action_videosuggestions()
	{
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