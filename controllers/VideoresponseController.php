<?php
class videoresponseController extends GController{

	// A set of filters to be run before and after the controller action
	public function filters(){
		return array('rbam');
	}

	public function accessRules(){
		return array(
			array('allow',
				'actions' => array('get_comments', 'live_comments', 'index', 'view_all', 'thread'),
				'users' => array('*')
			),
			array( 'allow', 'users' => array('@*') ),
			array( 'deny', 'users' => array('*') ),
		);
	}

	/**
	 * This is the method by which all video responses for a given video are displayed at once
	 */
	public function action_index(){
		glue::route('error/notfound');
	}

	public function action_view_all(){
		$video = Video::model()->findOne(array('_id' => new MongoId($_GET['id'])));

		$this->pageTitle = 'View All Responses - StageX';

		if(!glue::roles()->checkRoles(array('deletedView' => $video))){
			$this->render('videos/deleted', array('video'=>$video));
			exit();
		}

		$now = new MongoDate();
		$_SESSION['last_comment_pull'] = serialize($now);

		$this->render('responses/all', array('model' => $video,
			'comments' => Glue::roles()->checkRoles(array("^"=>$video)) ? VideoResponse::findAllComments($video, array('$lte' => $now)) :
					VideoResponse::findPublicComments($video, array('$lte' => $now))));
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

	public function action_add_response(){
		$this->pageTitle = 'Add Video Response - StageX';
		if(!glue::http()->isAjax())
			glue::route('error/notfound');

		$comment = new VideoResponse();
		$video = Video::model()->findOne(array('_id' => new MongoId($_POST['vid'])));

		if(!glue::roles()->checkRoles(array('canView' => $video))){
			GJSON::kill(GJSON::DENIED);
		}

		if(isset($_POST['type']) && $video){
			if($_POST['type'] == 'text'){

				if(!(bool)$video->txt_coms_allowed){
					GJSON::kill('Text responses have been disabled on this video');
				}
				$comment->setScenario('text_comment');

				if(isset($_POST['parent_comment'])){
					$_SESSION['last_comment_pull'] = serialize(new MongoDate()); // reset the pull time so the new comment will show correctly on page change
					$comment->parent_comment = new MongoId($_POST['parent_comment']);
					if($comment->thread_parent){
						if($comment->thread_parent->author instanceof User) $comment->reply_tousername = $comment->thread_parent->author->username;
					}
				}
				$comment->vid = new MongoId($_POST['vid']);
				$comment->content = $_POST['content'];
			}elseif($_POST['type'] == 'video'){

				if(!(bool)$video->vid_coms_allowed){
					GJSON::kill('Video responses have been disabled on this video');
				}
				$comment->setScenario('video_comment');
				$comment->vid = new MongoId($_POST['vid']);
				$comment->xtn_vid = new MongoId($_POST['id']);
			}

			if($comment->validate()){
				$comment->save();

				$mode = '';
				if(glue::roles()->checkRoles(array('^' => $comment->video))){
					if(isset($_POST['mode'])){
						switch($_POST['mode']){
							case "admin":
								$mode = 'admin';
								break;
						}
					}
				}

				ob_start();
					$this->partialRender('responses/_response', array('item' => $comment, 'mode' => $mode));
					$comment_html = ob_get_contents();
				ob_end_clean();

				if(glue::session()->user->should_autoshare('c_video')){
					AutoPublishStream::add_to_qeue(AutoPublishStream::V_RES, glue::session()->user->_id, $video->_id, null, $comment->content);
				}

				//var_dump($comment->in_reply);
				echo json_encode(array('success' => true, 'approved' => $comment->approved, 'html' => $comment_html, 'messages' => $comment->getErrorMessages()));
			}else{
				//var_dump($comment->getErrorMessages());
				echo json_encode(array('success' => false, 'messages' => $comment->getErrorMessages()));
			}
		}
	}

	public function action_approve(){
		$this->pageTitle = 'Approve Video Response - StageX';
		if(!glue::http()->isAjax())
			glue::route('error/notfound');

		$comment = VideoResponse::model()->findOne(array("_id"=>new MongoId($_GET['id'])));

		if($comment){
			if(!glue::roles()->checkRoles(array('^' => $comment->video)))
				GJSON::kill(GJSON::DENIED);

			$comment->approve();
			ob_start();
				$this->partialRender('responses/_response', array('item' => $comment, 'mode' => isset($_GET['mode']) ? $_GET['mode'] : ''));
				$comment_html = ob_get_contents();
			ob_end_clean();
			GJSON::kill(array('html' => $comment_html), true);
		}else{
			GJSON::kill(GJSON::UNKNOWN);
		}
	}

	public function action_approve_many(){
		$this->pageTitle = 'Approve Many Video Responses - StageX';
		if(!glue::http()->isAjax())
			glue::route('error/notfound');

		$video_id = isset($_GET['vid']) ? $_GET['vid'] : null;
		$_ids = isset($_GET['ids']) ? $_GET['ids'] : array();

		$video = Video::model()->findOne(array('_id' => new MongoId($video_id)));

		if(sizeof($_ids) <= 0 || !is_array($_ids) || !$video){
			GJSON::kill(GJSON::UNKNOWN);
		}

		if(!glue::roles()->checkRoles(array('^' => array($video))))
			GJSON::kill(GJSON::DENIED);

		foreach($_ids as $k => $v){
			$_ids[$k] = new MongoId($v);
		}

		$comment_rows = VideoResponse::model()->find(array('_id' => array('$in' => $_ids), 'vid' => $video->_id));
		if(sizeof($_ids) != $comment_rows->count())
			GJSON::kill('Some of the comments you specified could not be found');

		foreach($comment_rows as $k => $v){
			$v->approve();
		}
		GJSON::kill('The comments you selected were approved', true);
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
		$this->pageTitle = 'Delete Video Response - StageX';
		if(!glue::http()->isAjax())
			glue::route('error/notfound');

		$comment = VideoResponse::model()->findOne(array("_id"=>new MongoId($_GET['id'])));
		if($comment){
			if(!glue::roles()->checkRoles(array('^' => array($comment->video, $comment))))
				GJSON::kill(GJSON::DENIED);

			$comment->delete();
			echo json_encode(array("success"=>true));
		}else{
			GJSON::kill(GJSON::UNKNOWN);
		}
	}

	public function action_delete_many(){
		$this->pageTitle = 'Remove Video Responses - StageX';
		if(!glue::http()->isAjax())
			glue::route('error/notfound');

		$video_id = isset($_GET['vid']) ? $_GET['vid'] : null;
		$_ids = isset($_GET['ids']) ? $_GET['ids'] : array();

		$video = Video::model()->findOne(array('_id' => new MongoId($video_id)));

		if(sizeof($_ids) <= 0 || !is_array($_ids) || !$video){
			GJSON::kill(GJSON::UNKNOWN);
		}

		if(!glue::roles()->checkRoles(array('^' => array($video))))
			GJSON::kill(GJSON::DENIED);

		foreach($_ids as $k => $v){
			$_ids[$k] = new MongoId($v);
		}

		$comment_rows = glue::db()->videoresponse->find(array('_id' => array('$in' => $_ids), 'vid' => $video->_id));
		$row_count = $comment_rows->count();

		if(sizeof($_ids) != $row_count)
			GJSON::kill('Some of the comments you specified could not be found');

		glue::db()->videoresponse->remove(array('_id' => array('$in' => $_ids), 'vid' => $video->_id), array('safe' => true));
		glue::db()->videoresponse_likes->remove(array("response_id"=>array('$in' => $_ids)));

		$video->total_responses = $video->total_responses-$row_count;
		$video->save();

		GJSON::kill('The comments you specified were deleted', true);
	}

	function action_get_comments(){
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
			?>{items}<div style='margin-top:7px;'>{pager}<div class="clearer"></div></div><?php
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
	function action_live_comments(){
		$this->pageTitle = 'Get live Responses - StageX';

		if(!glue::http()->isAjax())
			glue::route('error/notfound');

		$video = Video::model()->findOne(array("_id"=>new MongoId(glue::http()->param('id'))));
		if($video->user_id == glue::session()->user->_id){
			$comments = VideoResponse::model()->find(array('vid' => $video->_id, 'deleted' => 0,
				'user_id' => array('$ne' => glue::session()->user->_id), 'ts' => array('$gt' => unserialize($_SESSION['last_comment_pull'])))
			)->sort(array('ts' => -1));
		}else{
			$comments = VideoResponse::model()->find(array('vid' => $video->_id, 'approved' => true, 'deleted' => 0,
				'user_id' => array('$ne' => glue::session()->user->_id), 'ts' => array('$gt' => unserialize($_SESSION['last_comment_pull'])))
			)->sort(array('ts' => -1));
		}

		// Now that I got all comments greater lets reset the session
		echo json_encode(array('success' => true, 'number_comments' => $comments->count()));
	}

	function action_response_suggestions(){
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