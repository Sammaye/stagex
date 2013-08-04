<?php

use app\models\Video,
	app\models\Queue;

class videoController extends glue\Controller{
	
	public function authRules(){
		return array(
			array("allow",
				"actions"=>array( 'upload', 'addUpload', 'getUploadStatus', 'createUpload', 'saveUpload', 'save', 'set_detail',
					'delete_responses', 'batch_delete', 'delete', 'report', 'like', 'dislike', 'statistics', 'get_more_statistics', 'undoDelete', 'batchSave' ),
				"users"=>array("@*")
			),
			array('allow', 'actions' => array('index', 'watch', 'embedded')),
			array("deny", "users"=>array("*")),
		);
	}

	public function action_index(){
		$this->title = 'Browse All Videos - StageX';
		
		extract(glue::http()->param(array(
			'sort', 'time', 'duration', 'cat', 'page'=>1
		),null));
		$sphinx = Video::model()->search()->page($page);

		$categories = Video::model()->categories();
		if(isset($categories[$cat])){
			$row = $categories[$cat];
			
			$sphinx->filter('category', array($row[1]));
			$this->title = 'Browse '.$row[0].' videos - StageX';
		}else
			$cat = null;

		if($time=='today')
			$sphinx->filterRange('date_uploaded', time()-24*60*60, time());
			//mktime(0, 0, 0, date('n'), date('j'), date('Y'))
		elseif($time=='week')
			$sphinx->filterRange('date_uploaded', strtotime('7 days ago'), time());
		elseif($time=='month')
			$sphinx->filterRange('date_uploaded', mktime(0, 0, 0, date('n'), 1, date('Y')), time());

		if($sort=='views')
			$sphinx->sort(SPH_SORT_ATTR_DESC, "views");
		elseif($sort=='rating')
			$sphinx->sort(SPH_SORT_ATTR_DESC, "rating");
		else
			$sphinx->sort(SPH_SORT_ATTR_DESC, "date_uploaded");

		if($duration=='ltthree')
			$sphinx->filterRange('duration', 1, 240000);
		elseif($duration=='gtthree')
			$sphinx->filterRange('duration', 240000, 23456789911122000000);
		$this->render('videos/browse', array('sphinx' => $sphinx, 'filter' => $filter, 'sort' => $sort, 'time' => $time, 'duration' => $duration, 'cat' => $cat));
	}

	function action_upload(){
		$this->title = "Upload a new video - StageX";
		$this->layout = 'user_section';

		glue::user()->reset_upload_bandwidth();
		if((!strstr($_SERVER['SERVER_NAME'], 'upload.')) && glue::$params['uploadBase'] == 'http://upload.stagex.co.uk/')
			glue::http()->redirect(glue::$params['uploadBase'].'video/upload');
		if(glue::user()->canUpload)
			echo $this->render('upload', array('model'=>glue::user()));
		else
			echo $this->render('uploadForbidden', array());
	}

	function action_addUpload(){
		if(!glue::http()->isAjax())
			glue::trigger('404');

		$video = new Video();
		$video->populateDefaults();

		echo json_encode(array(
			'success' => true, 
			'html' => $this->renderPartial('_upload', array( 'u_id' => strval(new MongoId()), 'model' => $video ))
		));
	}

	function action_getUploadStatus(){

		$ret = array();

		for($i = 0; $i<count($_GET['ids']); $i++){

			$upload_id = $_GET['ids'][$i];

			// This is our magic function. It gets our upload information
			$info = uploadprogress_get_info($upload_id);

			if(!$info){

				// Then this file is done uploading. Get the queue
				// If it dont have a queue row return null
				$video = Video::model()->findOne(array('uploadId' => strval($upload_id)));
				if(!$video){
					$ret[$i] = null;
				}elseif($video->state == 'uploading'){
					$ret[$i] = array('id' => $upload_id, 'message' => 'Your video is propagating and then we will process it. This normally takes about 5 minutes.');
				}elseif($video->state == 'pending' || $video->state == 'submitting' || $video->state == 'transcoding'){
					$ret[$i] = array('id' => $upload_id, 'message' => 'Your video is processing, this may take a little time (hopefully not too long).');
				}elseif($video->state == 'finished'){
					$ret[$i] = array('id' => $upload_id, 'message' => 'Your video is done', 'done' => true);
				}
			}else{
				$ret[$i] = array('id' => $upload_id, 'file' => $info['filename'], 'uploaded' => $info['bytes_uploaded'], 'total' => $info['bytes_total'],
								'left' => gmdate("H:i:s", $info['est_sec']), 'speed' => convert_size_human($info['speed_average']));
			}
		}
		echo json_encode(array('success'=>true,'status'=>$ret));
	}

	function action_createUpload(){

		header('P3P: CP="CAO PSA OUR"');

		$video = new Video();
		$video->upload($_GET['id']);
		?>
		<html>
			<head>
				<title>Saving Your Video - StageX</title>
				<?php if($video->response("ERROR")){

					switch($video->response("ERROR")){
						case "EXISTS":
							$message = "This file already exists for your user. Please upload a different file.";
							break;
						case "FILE_A_EMPTY":
							$message = "The file was not found on the server when trying to access it. Please make sure you did actually upload a file.";
							break;
						case "NOT_VALID":
							$message = "The file failed to pass last minute checks. Please ensure you upload a valid file to avoid delays.";
							break;
						case "NOT_ENOUGH_SP":
							$message = "You do not have enough upload capacity left to upload that. Please wait for your capacity to refill.";
							break;
						default:
							$message = "An unknown error was encountered. Please try again later.";
							break;
					}

					?><script type="text/javascript">
						parent.finish_upload("<?php echo $_GET['id'] ?>", false, "<?php echo $message ?>");
					</script><?php
				}else{
					?>
					<script type="text/javascript">
						parent.finish_upload("<?php echo $_GET['id'] ?>", true, "Your video has been uploaded and added to your library");
					</script>
				<?php } ?>
			</head>
			<body>
			</body>
		</html><?php

	}

	function action_saveUpload(){
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');

		$video = Video::model()->findOne(array('userId' => glue::user()->_id, 'uploadId' => $_POST['uploadId']));
		$exists = true;

		if(!$video){
			$video = new Video();
			$exists = false;
		}

		$upload_id = $_POST['uploadId'];
		unset($_POST['uploadId']);

		if(isset($_POST['Video'])){
			$video->attributes=$_POST['Video'];
			if($video->validate()){
				if($exists){
					$video->save();
				}else{
					if(isset($_SESSION['_upload'])){
						$_SESSION['_upload'][$upload_id] = $_POST['Video'];
					}else{
						$_SESSION['_upload'] = array($upload_id => $_POST['Video']);
					}
				}
				//Success
				echo json_encode(array('success' => true, 'errors' => $video->getErrors()));
			}else{
				//fail
				echo json_encode(array('success' => false, 'errors' => $video->getErrors()));
			}
		}
	}

	function action_watch(){
		$now = new MongoDate();
		$_SESSION['last_comment_pull'] = serialize($now);

		$video = Video::model()->findOne(array("_id"=>new MongoId(glue::http()->param('id'))));

		if(!glue::roles()->checkRoles(array('deletedView' => $video))){
			$this->pageTitle = 'Video Not Found - StageX';
			$this->render('videos/deleted', array('video'=>$video));
			exit();
		}

		$this->pageTitle = $video->title.' - StageX';
		if(strlen($video->desc) > 0){
			$this->pageDescription = $video->desc;
		}
		$this->pageKeywords = is_array($video->tags) ? implode(",", $video->tags) : "";

		if(!glue::roles()->checkRoles(array('deniedView' => $video))){
			glue::route(glue::config('404', 'errorPages'));
		}

		if(isset($_SESSION['age_confirmed']) && glue::http()->param('av', '1') == "1") // This allows us to stop malicious people from sending mature links to kids without having to use two pages
			$_SESSION['age_confirmed'] = $video->_id;

		$age_confirm = isset($_SESSION['age_confirmed']) ? $_SESSION['age_confirmed'] : 0;
		$safe_search = glue::session()->authed ? glue::session()->user->safe_srch : "S";

		if((bool)$video->adult_content && $age_confirm != strval($video->_id)){

			$_SESSION['age_confirmed'] = 0;
			if((!Glue::roles()->checkRoles(array("^@", "^"=>$video))) && $safe_search == "S"){
				$this->render('videos/age_verification', array('video'=>$video));
				exit();
			}
		}

		// ELSE play the video
		$video->recordHit();
		if(glue::session()->authed){
			if(strval(glue::session()->user->_id) != strval($video->user_id) && $video->listing != 'u' && $video->listing != 'n'){
				Stream::videoWatch(glue::session()->user->_id, $video->_id);
			}
			glue::db()->watched_history->update(array('user_id' => glue::session()->user->_id, 'item' => $video->_id), array('$set' => array('ts' => new MongoDate())), array('upsert' => true));
		}

		$playlist_id = isset($_GET['plid']) ? $_GET['plid'] : null;
		$playlist = $playlist_id ? Playlist::model()->findOne(array('_id' => new MongoId($playlist_id))) : null;
//var_dump($video->getAttributes());
		$this->layout = 'watch_video_layout';
		$this->render('videos/watch', array( "model"=>$video, 'playlist' => $playlist,
			'comments' => Glue::roles()->checkRoles(array("^"=>$video)) ? VideoResponse::findAllComments($video, array('$lte' => $now)) : VideoResponse::findPublicComments($video, array('$lte' => $now))
		));
	}

	function action_embedded(){
		$this->layout = 'black_blank_page';

		$video = Video::model()->findOne(array("_id"=>new MongoId($_GET['id'])));
		$this->pageTitle = $video ? $video->title : 'Not found';

		$video->recordHit();
		$this->render('videos/embedded', array('model' => $video));
	}

	function action_batchSave(){

		$this->title = 'Save Video - StageX';
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');
		if(isset($_POST['Video'])&&($ids=glue::http()->param('ids',null))!==null){
			$updated=0;
			foreach($ids as $id){
				$video = Video::model()->findOne(array('_id' => new MongoId($id)));
				if(!glue::auth()->check(array('^' => $video)))
					continue;
				$video->attributes=$_POST['Video'];
				if($video->validate()&&$video->save())
					$updated++;
			}
			$this->json_success(array('updated'=>$updated,'failed'=>count($ids)-$updated,'total'=>count($ids)));
		}
		$this->json_error(self::UNKNOWN);
	}

	function action_deleteResponses(){
		$this->pageTitle = 'Delete Video Responses - StageX';

		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');

		$video = Video::model()->findOne(array("_id"=>new MongoId($_GET['id'])));
		if($video){
			if(!glue::auth()->check(array('^' => $video)))
				$this->json_error(self::DENIED);

			switch($_GET['type']){
				case "video":
					$video->removeVideoResponses();
					$this->json_success('All video responses were deleted');
					break;
				case "text":
					$video->removeTextResponses();
					$this->json_success('All text responses were deleted');
					break;
				default:
					$this->json_error(self::UNKNOWN);
					break;
			}
		}else{
			$this->json_error('Video could not be found');
		}
	}

	function action_report(){
		$this->title = 'Report Video - StageX';
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');
		
		$id=glue::http()->param('id',null);
		$reason=glue::http()->param('reason',null);

		if($id!==null&&$reason!==null){
			$video = Video::model()->findOne(array('_id' => new MongoId($id)));

			if(!glue::auth()->check(array('deleted' => $video)))
				$this->json_error('That video was not found');
			$video->report($_GET['reason']);
			$this->json_success('The video was reported');
		}else{
			$this->json_error(self::UNKNOWN);
		}
	}

	function action_like(){
		$this->title = 'Like Video - StageX';
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');

		$id=glue::http()->param('id',null);
		if($video = Video::model()->findOne(array('_id' => new MongoId($id)))){
			if(!(bool)$video->voteable)
				$this->json_error('Voting has been disabled on this video');

			$video->like();

			if($video->isPublic())
				app\models\Stream::videoLike($video->_id, glue::user()->_id);
			if(glue::user()->autoshareLikes)
				AutoPublishQueue::add_to_qeue(AutoPublishQueue::LK_V, glue::user()->_id, $video->_id);	

			$total = $video->likes + $video->dislikes;
			$this->json_success(array(
				'likes' => $video->likes,
				'dislikes' => $video->dislikes,
				"like_percent" => ($video->likes/$total)*100,
				'dislike_percent' => ($video->dislikes/$total)*100
			));
		}else{
			$this->json_error('Video could not be found');
		}
	}

	function action_dislike(){
		$this->title = 'Dislike Video - StageX';
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');

		$id=glue::http()->param('id',null);
		if($video = Video::model()->findOne(array('_id' => new MongoId($id)))){
			if(!(bool)$video->voteable)
				$this->json_error('Voting has been disabled on this video');

			$video->dislike();

			if($video->isPublic())
				Stream::videoDislike($video->_id, glue::user()->_id);
			if(glue::user()->autoshareLikes)
				AutoPublishQueue::add_to_qeue(AutoPublishQueue::DL_V, glue::user()->_id, $video->_id);

			$total = $video->likes + $video->dislikes;
			$this->json_success(array(
				'likes' => $video->likes,
				'dislikes' => $video->dislikes,
				"like_percent" => ($video->likes/$total)*100,
				'dislike_percent' => ($video->dislikes/$total)*100
			));
		}else{
			$this->json_error('Video could not be found');
		}
	}

	function action_delete(){
		$this->title = 'Remove Videos - StageX';
		if(!glue::http()->isAjax())
			Glue::route("error/notfound");

		$ids = glue::http()->param('ids',null);
		if(count($ids) <= 0 || count($ids)>1000){
			$this->json_error("No videos were selected");
		}

		foreach($ids as $k => $id){
			$mongoIds[$k] = new MongoId($id);
		}

		$video_rows = Video::model()->find(array('_id' => array('$in' => $mongoIds), 'userId' => glue::user()->_id, 'deleted' => 0));
		
		$ids=array(); // We reset these to know which were actually picked from the DB
		$mongoIds=array();
		foreach($video_rows as $video){
			$ids[]=(string)$video->_id;
			$mongoIds[]=$video->_id;
			$video->author->saveCounters(array('totalUploads'=>-1),0);
			
			Queue::AddMessage($video->collectionName(),$video->_id,Queue::DELETE);
		}
		
		Video::model()->updateAll(array('_id' => array('$in' => $mongoIds)), array('$set'=>array('deleted'=>1)));
		glue::mysql()->query('UPDATE documents SET deleted=1 WHERE _id IN :id', array(':id' => $ids));

		$this->json_success(array('message'=>'The videos you selected were deleted','updated'=>count($ids)));
	}
	
	function action_undoDelete(){
		$this->title = 'Remove Videos - StageX';
		if(!glue::http()->isAjax())
			Glue::route("error/notfound");
		
		$id=glue::http()->param('id',null);
		if(
			$id===null||
			($video=Video::model()->findOne(array('_id'=>new MongoId($id), 'userId'=>glue::user()->_id)))===null
		)
			$this->json_error(self::UNKNOWN);

		$video->deleted=0;
		if($video->save()){
			$video->author->saveCounters(array('totalUploads'=>1));
			glue::mysql()->query('UPDATE documents SET deleted=0 WHERE _id = :id', array(':id' => $id));
			$this->json_success('Video Undeleted');
		}
		$this->json_error(self::UNKNOWN);
	}

	function action_statistics(){
		$video = Video::model()->findOne(array("_id" => new MongoId($_GET['id'])));
		if(!$video || !glue::roles()->checkRoles(array('^' => $video))){ // Only the owner of the video can see this
			glue::route('/error/notfound');
		}

		$this->pageTitle = "View statistics for: ".$video->title;
		$this->pageDescription = $video->desc;
		$this->pageKeywords = is_array($video->tags) ? implode(",", $video->tags) : "";
//var_dump($video->getStatistics_dateRange(mktime(0, 0, 0, date("m")-1, date("d"), date("Y")), mktime(0, 0, 0, date("m"), date("d"), date("Y"))));
		$this->render('videos/statistics', array(
			"model"=>$video,
		));
	}

	function action_get_more_statistics(){
		if(!glue::http()->isAjax())
			glue::route('/error/notfound');

		$fromDate = isset($_GET['from']) ? $_GET['from'] : null;
		$toDate = isset($_GET['to']) ? $_GET['to'] : null;

		if(!$fromDate || !$toDate)
			GJSON::kill('You entered an invalid to and/or from date');

		$split_from = explode('/', $fromDate);
		$split_to = explode('/', $toDate);
		if(mktime(0, 0, 0, $split_from[1], $split_from[0], $split_from[2]) > mktime(0, 0, 0, $split_to[1], $split_to[0], $split_to[2])){
			GJSON::kill('You entered an invalid to and/or from date');
		}

		$model = Video::model()->findOne(array('_id' => new MongoId($_GET['id'])));
		if(!$model)
			GJSON::kill('The video you are looking for could not be found.');

		if(!glue::roles()->checkRoles(array('^' => $model))){
			GJSON::kill(GJSON::DENIED);
		}

		$fromTs = mktime(0, 0, 0, $split_from[1], $split_from[0], $split_from[2]);
		$toTs = mktime(23, 0, 0, $split_to[1], $split_to[0], $split_to[2]);

		if($fromTs == $toTs){
			$toTs = mktime(23, 0, 0, $split_to[1], $split_to[0], $split_to[2]);
		}

		GJSON::kill($model->getStatistics_dateRange($fromTs, $toTs), true);
	}
	
	public function action_searchSuggestions(){
		$this->title = 'Video Search - StageX';
		if(!glue::http()->isAjax())
			glue::trigger('404');
	
		$ret = array();
		$sphinx=Video::model()->search(glue::http()->param('term', ''));
		$sphinx->match('uid', strval(glue::user()->_id));// I do this in case this needs changing later
		$cursor=$sphinx->limit(5)->query();
	
		foreach($cursor as $item)
			$ret[] = array('label' => $item->title);
		echo json_encode($ret);
	}	
}