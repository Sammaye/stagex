<?php

use app\models\Video,
	app\models\Queue;

class videoController extends glue\Controller{
	
	public $tab;
	
	public function authRules(){
		return array(
			array("allow",
				"actions"=>array( 'upload', 'addUpload', 'getUploadStatus', 'createUpload', 'saveUpload', 'save',
					'deleteResponses', 'delete', 'report', 'like', 'dislike', 'analytics', 'getAnalytics', 'undoDelete', 'batchSave' ),
				"users"=>array("@*")
			),
			array('allow', 'actions' => array('index', 'watch', 'embedded')),
			array("deny", "users"=>array("*")),
		);
	}

	public function action_index(){
		glue::runAction('user/videos');
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
		$_SESSION['LastCommentPull'] = serialize($now);

		$video = Video::model()->findOne(array("_id"=>new MongoId(glue::http()->param('id'))));
		if(!glue::auth()->check(array('viewable' => $video))){
			$this->title = 'Video Not Found - StageX';
			echo $this->render('deleted', array('video'=>$video));
			exit(); //glue::end();
		}

		$this->title = $video->title.' - StageX';
		if(strlen($video->description) > 0) $this->metaTag('description', $video->description);
		if(is_array($video->tags)&&!empty($video->tags)) $this->metaTag('tags', $video->tags);

		// This allows us to stop malicious people from sending mature links to kids without having to use two pages
		if(isset($_SESSION['age_confirmed']) && glue::http()->param('av', '1') == "1")
			$_SESSION['age_confirmed'] = $video->_id;
		$age_confirm = isset($_SESSION['age_confirmed']) ? $_SESSION['age_confirmed'] : 0;		
		if(
			!glue::auth()->check(array("^"=>$video)) && glue::user()->safeSearch && 
			$video->mature && $age_confirm != strval($video->_id) 
		){
			$_SESSION['age_confirmed'] = 0;
			$this->render('age_verification', array('video'=>$video));
			glue::end();
		}

		// ELSE play the video
		$video->recordHit();
		if(glue::session()->authed){
			if(strval(glue::user()->_id) != strval($video->userId) && $video->listing != 1 && $video->listing != 2)
				app\models\Stream::videoWatch(glue::user()->_id, $video->_id);
			glue::user()->recordWatched($video);
		}

		if($playlist_id=glue::http()->param('playlist_id',null))
			$playlist = app\models\Playlist::model()->findOne(array('_id' => new MongoId($playlist_id)));
		$this->layout = 'watch_video_layout';
		echo $this->render('watch', array("model"=>$video, 'playlist' => isset($playlist)?$playlist:null, 'LastCommentPull' => $now));
	}

	function action_embedded(){
		$this->layout = 'black_blank_page';

		$video = Video::model()->findOne(array("_id"=>new MongoId($_GET['id'])));
		if(!glue::auth()->check(array('viewable' => $video))){
			$this->title = 'Video Not Found - StageX';
			$this->render('deleted', array('video'=>$video));
			glue::end();
		}		

		$video->recordHit();
		if(glue::session()->authed){
			if(strval(glue::user()->_id) != strval($video->userId) && $video->listing != 1 && $video->listing != 2)
				app\models\Stream::videoWatch(glue::user()->_id, $video->_id);
			glue::user()->recordWatched($video);
		}		
		$this->render('embedded', array('model' => $video));
	}
	
	function action_save(){
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');
		if(
			(isset($_POST['Video'])&&($id=glue::http()->param('id',null))) &&
			($video = Video::model()->findOne(array('_id' => new MongoId($id))))
		){
			if(!glue::auth()->check(array('^' => $video)))
				$this->json_error(self::UNKNOWN);
			$video->attributes=$_POST['Video'];
			if($video->validate()&&$video->save())
				$this->json_success(array('model'=>$video->getJSONDocument()));
			else
				$this->json_error(array('messages'=>$video->getErrors(),'message'=>'This video could not be saved:'));
		}
		$this->json_error(self::UNKNOWN);
	}

	function action_batchSave(){
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
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');
		if(
			($id=glue::http()->param('id',null))!==null &&
			($video=Video::model()->findOne(array('_id'=>new MongoId($id))))!==null
		){
			$type=glue::http()->param('type',null);
			if(!glue::auth()->check(array('^' => $video)))
				$this->json_error(self::DENIED);
			if($type='video'){
				$video->removeVideoResponses();
				$this->json_success('All video responses were deleted');
			}elseif($type='text'){
				$video->removeTextResponses();
				$this->json_success('All text responses were deleted');
			}
		}
		$this->json_error('Video could not be found');
	}

	function action_report(){
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');
		
		$id=glue::http()->param('id',null);
		$reason=glue::http()->param('reason',null);

		if(!$reason||array_search($reason, array('sex', 'abuse', 'religious', 'dirty'))===false)
			$this->json_error('You must enter a valid reporting reason');
		if($id!==null&&$reason!==null){
			$video = Video::model()->findOne(array('_id' => new MongoId($id)));
			if(!glue::auth()->check(array('deleted' => $video)))
				$this->json_error('That video was not found');
			$video->report((string)$reason);
			$this->json_success('The video was reported');
		}else{
			$this->json_error(self::UNKNOWN);
		}
	}

	function action_like(){
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
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');

		$id=glue::http()->param('id',null);
		if($video = Video::model()->findOne(array('_id' => new MongoId($id)))){
			if(!(bool)$video->voteable)
				$this->json_error('Voting has been disabled on this video');

			$video->dislike();

			if($video->isPublic())
				app\models\Stream::videoDislike($video->_id, glue::user()->_id);
			if(glue::user()->autoshareLikes)
				app\models\AutoPublishQueue::add_to_qeue(AutoPublishQueue::DL_V, glue::user()->_id, $video->_id);

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
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');

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
		glue::elasticSearch()->deleteByQuery(array(
			'type' => 'playlist',
			'body' => array('query' => array(
				"ids" => array("type" => "playlist", "values" => $ids)
			))
		));

		$this->json_success(array('message'=>'The videos you selected were deleted','updated'=>count($ids)));
	}
	
	function action_undoDelete(){
		if(!glue::http()->isAjax())
			glue::trigger('404');
		$id=glue::http()->param('id',null);
		if(
			$id===null||
			($video=Video::model()->findOne(array('_id'=>new MongoId($id), 'userId'=>glue::user()->_id)))===null
		)
			$this->json_error(self::UNKNOWN);

		$video->deleted=0;
		if($video->save()){
			$video->author->saveCounters(array('totalUploads'=>1));
			$this->json_success('Video Undeleted');
		}
		$this->json_error(self::UNKNOWN);
	}

	function action_analytics(){
		$video = Video::model()->findOne(array("_id" => new MongoId(glue::http()->param('id',''))));
		if(!$video || !glue::auth()->check(array('^' => $video))) // Only the owner of the video can see this
			glue::trigger('404');

		$this->layout='user_section';
		
		$this->title = "View analytics for: ".$video->title;
		if($video->description) $this->metaTag('description',$video->description);
		if(count($video->tags)>0) $this->metaTag('keywords',$video->stringTags);
		echo $this->render('statistics', array("model"=>$video));
	}

	function action_getAnalytics(){
		if(!glue::http()->isAjax())
			glue::trigger('404');
		extract(glue::http()->param(array('from','to','id')));
		if(!$from || !$to)
			$this->json_error('You entered an invalid to and/or from date');

		$split_from = explode('/', $from);
		$split_to = explode('/', $to);
		if(mktime(0, 0, 0, $split_from[1], $split_from[0], $split_from[2]) > mktime(0, 0, 0, $split_to[1], $split_to[0], $split_to[2])){
			$this->json_error('You entered an invalid to and/or from date');
		}

		if(!($model = Video::model()->findOne(array('_id' => new MongoId($id)))))
			$this->json_error('The video you are looking for could not be found.');
		if(!glue::auth()->check(array('^' => $model)))
			$this->json_error(self::DENIED);

		$fromTs = mktime(0, 0, 0, $split_from[1], $split_from[0], $split_from[2]);
		$toTs = mktime(23, 0, 0, $split_to[1], $split_to[0], $split_to[2]);
		if($fromTs == $toTs){
			$toTs = mktime(23, 0, 0, $split_to[1], $split_to[0], $split_to[2]);
		}
		$this->json_success(array('stats'=>$model->getStatistics_dateRange($fromTs, $toTs)));
	}
	
	public function action_searchSuggestions(){
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