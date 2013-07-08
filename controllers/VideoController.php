<?php

use app\models\Video;

class videoController extends glue\Controller{

	public $tab='';
	
	public function authRules(){
		return array(
			array("allow",
				"actions"=>array( 'upload', 'addUpload', 'getUploadStatus', 'createUpload', 'saveUpload', 'save', 'set_detail',
					'delete_responses', 'batch_delete', 'remove', 'report', 'like', 'dislike', 'statistics', 'get_more_statistics' ),
				"users"=>array("@*")
			),
			array('allow', 'actions' => array('index', 'watch', 'process_encoding', 'embedded', 'tst_sqs')),
			array("deny", "users"=>array("*")),
		);
	}

	public function action_index(){
		$this->pageTitle = 'Browse All Videos - StageX';

		$sort = isset($_GET['sort']) ? $_GET['sort'] : null;
		$time_show = isset($_GET['time']) ? $_GET['time'] : null;
		$duration = isset($_GET['duration']) ? $_GET['duration'] : null;
		$cat = isset($_GET['cat']) ? $_GET['cat'] : null;

		$sphinx = glue::sphinx()->getSearcher();
		$sphinx->page = isset($_GET['page']) ? $_GET['page'] : 1;

		$video =  new Video();
		$categories = $video->categories();
		if(isset($categories[$cat])){
			$row = $categories[$cat];
			$sphinx->setFilter('category', array($row[1]));
			$this->pageTitle = 'Browse '.$row[0].' videos - StageX';
		}else{
			$cat = null;
		}
		$sphinx->setFilter('listing', array('2', '3'), true);

		if(glue::session()->user->safe_srch == "S" || !glue::session()->authed){
			$sphinx->setFilter('adult', array('1'), true);
		}

		switch($time_show){
			case "today":
				$sphinx->setFilterRange('date_uploaded', time()-24*60*60, time());
				//mktime(0, 0, 0, date('n'), date('j'), date('Y'))
				break;
			case "week":
				//var_dump(strtotime('7 days ago'));
				$sphinx->setFilterRange('date_uploaded', strtotime('7 days ago'), time());
				break;
			case "month":
				$sphinx->setFilterRange('date_uploaded', mktime(0, 0, 0, date('n'), 1, date('Y')), time());
				break;
		}

		switch($sort){
			case "views":
				$sphinx->setSortMode(SPH_SORT_ATTR_DESC, "views");
				$filter = "videos";
				break;
			case "rating":
				$sphinx->setSortMode(SPH_SORT_ATTR_DESC, "rating");
				$filter = "videos";
				break;
			default:
				$filter = "videos";
				$sphinx->setSortMode(SPH_SORT_ATTR_DESC, "date_uploaded");
				break;
		}
//var_dump(glue::url()->_GET('sort', 'time'));
		switch($duration){
			case "ltthree":
				$sphinx->setFilterRange('duration', 1, 240000);
				break;
			case "gtthree":
				$sphinx->setFilterRange('duration', 240000, 23456789911122000000);
				break;
		}
		$sphinx->query(array('select' => isset($_GET['mainSearch']) ? $_GET['mainSearch'] : null, 'where' => array('type' => array('video')), 'results_per_page' => 20), 'main');

		$this->render('videos/browse', array('sphinx' => $sphinx, 'filter' => $filter, 'sort' => $sort, 'time_show' => $time_show, 'duration' => $duration, 'cat' => $cat));

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
		
		if(!glue::http()->isAjax()){
			glue::trigger('404');
			return;	
		}

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

	function action_save(){

		$this->pageTitle = 'Save Video - StageX';
		if(!glue::http()->isAjax())
			glue::route('error/notfound');

		if(isset($_POST['Video'])){
			$video = Video::model()->findOne(array('_id' => new MongoId($_POST['Video']['id'])));

			if(!glue::roles()->checkRoles(array('deletedView' => $video))){
				GJSON::kill('That video was not found');
			}

			if(!glue::roles()->checkRoles(array('^' => $video))){
				GJSON::kill(GJSON::DENIED);
			}
			$video->_attributes($_POST['Video']);

			if($video->validate()){
				//var_dump($video->comments);
				$video->save();
				echo json_encode(array('success' => true, 'errors' => $video->getErrorMessages(), 'data' => array(
					'title' => htmlspecialchars($video->title),
					'description' => nl2br(htmlspecialchars($video->description)),
					'tags' => $video->tags,
					'licence' => $video->get_licence_text(),
					'category' => $video->get_category_text()
				)));
			}else{
				echo json_encode(array('success' => false, 'errors' => $video->getErrorMessages()));
			}
		}
	}

	function action_delete_responses(){
		$this->pageTitle = 'Delete Video Responses - StageX';

		if(!glue::http()->isAjax()){
			glue::route('error/notfound');
		}

		$video = Video::model()->findOne(array("_id"=>new MongoId($_GET['id'])));
		if($video){
			if(!glue::roles()->checkRoles(array('^' => $video, '^@'))){
				GJSON::kill(GJSON::DENIED);
			}

			switch($_GET['type']){
				case "video":
					$count = glue::db()->videoresponse->find(array('vid' => $video->_id, 'type' => 'video'))->count();
					glue::db()->videoresponse->remove(array('vid' => $video->_id, 'type' => 'video'), array('safe' => true));

					$video->total_responses = $video->total_responses-$count;
					$video->vid_responses = $video->vid_responses-$count;
					$video->save();

					GJSON::kill('All video responses were deleted.', true);
					break;
				case "text":
					$count = glue::db()->videoresponse->find(array('vid' => $video->_id, 'type' => 'text'))->count();
					glue::db()->videoresponse->remove(array('vid' => $video->_id, 'type' => 'text'), array('safe' => true));

					$video->total_responses = $video->total_responses-$count;
					$video->txt_responses = $video->txt_responses-$count;
					$video->save();

					GJSON::kill('All text responses were deleted.', true);
					break;
				default:
					echo json_encode(array('success' => false));
					break;
			}
		}else{
			GJSON::kill('Video could not be found');
		}
	}

	function action_report(){
		$this->pageTitle = 'Report Video - StageX';
		if(!glue::http()->isAjax())
			Glue::route("error/notfound");

		if(isset($_GET['reason']) && isset($_GET['id'])){
			$video = Video::model()->findOne(array('_id' => new MongoId($_GET['id'])));

			if(!glue::roles()->checkRoles(array('deletedView' => $video))){
				GJSON::kill('That video was not found');
			}

			$video->report($_GET['reason']);
			echo json_encode(array("success"=>true));
		}else{
			echo json_encode(array("success"=>false));
		}
	}

	function action_like(){
		$this->pageTitle = 'Like Video - StageX';
		if(!glue::http()->isAjax())
			Glue::route("error/notfound");

		$video = Video::model()->findOne(array('_id' => new MongoId($_GET['id'])));
		if($video){
			if(!(bool)$video->voteable)
				GJSON::kill('Voting has been disabled on this video');

			$video->like();

			if($video->listing == 1){
				Stream::videoLike($video->_id, glue::session()->user->_id);
			}

			$total = $video->likes + $video->dislikes;

			if(glue::session()->user->should_autoshare('lk_dl')){
				AutoPublishStream::add_to_qeue(AutoPublishStream::LK_V, glue::session()->user->_id, $video->_id);
			}

			GJSON::kill(array(
				'likes' => $video->likes,
				'dislikes' => $video->dislikes,
				"like_percent" => ($video->likes/$total)*100,
				'dislike_percent' => ($video->dislikes/$total)*100
			), true);
		}else{
			GJSON::kill('Video could not be found');
		}
	}

	function action_dislike(){
		$this->pageTitle = 'Dislike Video - StageX';
		if(!glue::http()->isAjax())
			Glue::route("error/notfound");

		$video = Video::model()->findOne(array('_id' => new MongoId($_GET['id'])));
		if($video){
			if(!(bool)$video->voteable)
				GJSON::kill('Voting has been disabled on this video');

			$video->dislike();

			if($video->listing == 1){
				Stream::videoDislike($video->_id, glue::session()->user->_id);
			}

			$total = $video->likes + $video->dislikes;

			if(glue::session()->user->should_autoshare('lk_dl')){
				AutoPublishStream::add_to_qeue(AutoPublishStream::DL_V, glue::session()->user->_id, $video->_id);
			}

			GJSON::kill(array(
				'likes' => $video->likes,
				'dislikes' => $video->dislikes,
				"like_percent" => ($video->likes/$total)*100,
				'dislike_percent' => ($video->dislikes/$total)*100
			), true);
		}else{
			GJSON::kill('Video could not be found');
		}
	}

	function action_remove(){
		$this->pageTitle = 'Remove Video - StageX';
		if(!glue::http()->isAjax())
			Glue::route("error/notfound");

		$video = Video::model()->findOne(array("_id"=>new MongoId($_GET['id'])));
		if(!$video){
			GJSON::kill('This video could not be found. Most likely refreshing the page will fix this error.');
		}

		if(!glue::roles()->checkRoles(array('^' => $video))){
			GJSON::kill(GJSON::DENIED);
		}

		$responses = new VideoResponse();
		$responses->Db()->remove(array('$or' => array(
			array('vid' => $video->_id), array('xtn_vid' => $video->_id)
		)), array('safe' => true));
		glue::db()->videoresponse_likes->remove(array("video_id"=>$video->_id));
		glue::db()->video_likes->remove(array('item' => $video->_id)); // Still unsure about this since it is touching user data

		$video->deleted = 1;
		$video->save();

		glue::session()->user->total_uploads = glue::session()->user->total_uploads > 1 ? glue::session()->user->total_uploads-1 : 0;
		glue::session()->user->save();

		GJSON::kill('This video was deleted from your account', true);
	}

	function action_batch_delete(){
		$this->pageTitle = 'Remove Videos - StageX';
		if(!glue::http()->isAjax())
			Glue::route("error/notfound");

		$videos = isset($_POST['videos']) ? $_POST['videos'] : null;

		if(count($videos) <= 0){
			GJSON::kill('No videos were selected');
		}

		foreach($videos as $k => $id){
			$video_ids[$k] = new MongoId($id);
		}

		$video_rows = Video::model()->find(array('_id' => array('$in' => $video_ids), 'user_id' => glue::session()->user->_id));
		if(count($video_ids) != $video_rows->count()){
			GJSON::kill(GJSON::UNKNOWN);
		}

		glue::mysql()->query('UPDATE documents SET deleted=1 WHERE uid = :user_id AND _id IN :id', array(
			':user_id' => strval(glue::session()->user->_id),
			':id' => $videos
		));

		foreach($video_rows as $video){
			VideoResponse::model()->Db()->remove(array('$or' => array(
				array('vid' => $video->_id), array('xtn_vid' => $video->_id)
			)), array('safe' => true));
			$video->deleted = 1;
			$video->save();
		}
		glue::db()->videoresponse_likes->remove(array("video_id"=>array('$in' => $video_ids)));
		glue::db()->video_likes->remove(array('item' => array('$in' => $video_ids))); // Same reason as above

		glue::session()->user->total_uploads = glue::session()->user->total_uploads > count($videos) ? glue::session()->user->total_uploads-count($videos) : 0;
		glue::session()->user->save();

		GJSON::kill('The videos you selected were deleted', true);
	}

	function action_set_detail(){
		$this->pageTitle = 'Save Video - StageX';
		if(!glue::http()->isAjax())
			Glue::route("error/notfound");

		$field = null;
		if(isset($_POST['field'])){
			$field = $_POST['field'] == 'listing' || $_POST['field'] == 'licence' ? $_POST['field'] : null;
		}

		$value = isset($_POST['value']) ? $_POST['value'] : null;
		$videos = isset($_POST['videos']) ? $_POST['videos'] : array();
		$video_ids = array();

		if(count($videos) <= 0){
			GJSON::kill('No videos were selected');
		}

		if(!$field){
			GJSON::kill('No field was specified for change. Please refresh the page and try again');
		}

		foreach($videos as $k => $id){
			$video_ids[$k] = new MongoId($id);
		}

		$video_rows = Video::model()->find(array('_id' => array('$in' => $video_ids), 'user_id' => glue::session()->user->_id));
		if(count($video_ids) != $video_rows->count()){
			GJSON::kill(GJSON::UNKNOWN);
		}

		$validated_videos = array();
		foreach($video_rows as $k => $video){
			$video->$field = $value;
			if($video->validate(array($field))){
				$validated_videos[] = $video;
			}else{
				GJSON::kill(array('messages'=>$video->getErrorMessages()));
			}
		}

		foreach($validated_videos as $k => $video){
			$video->save();
		}
		GJSON::kill('The videos you selected were saved', true);
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
}