<?php

use \glue\Controller;
use app\models\AutoPublishQueue;
use app\models\Video;
use app\models\Playlist;
use glue\Json;

class PlaylistController extends Controller
{
	public $tab='playlists';
	
	public function behaviours()
	{
		return array(
			'auth' => array(
				'class' => 'glue\Auth',
				'rules' => array(
					array("allow",
						"actions"=>array('create', 'edit', 'save', 'delete', 'batchDelete', 'addVideo', 'get_menu', 'batchSave', 'deleteVideo', 'ajaxsearch',
							'clear', 'subscribe', 'unsubscribe'),
						"users"=>array("@*")
					),
					array('allow', 'actions' => array('index', 'view', 'renderBar')),
					array("deny", "users"=>array("*")),
				)
			)
		);
	}

	public function action_index()
	{
		$this->action_view();
	}

	public function action_view()
	{
		$playlist = Playlist::findOne(array('_id' => new MongoId(glue::http()->param('id')), 'deleted' => array('$ne' => 1)));

		if(!glue::auth()->check(array('viewable' => $playlist))){
			$this->title = 'Playlist Not Found - StageX';
			echo $this->render('deleted');
		}else{
			$this->layout='playlist_layout';
			$this->title = 'Playlist: '.$playlist->title.' - StageX';
			$this->metaTag('description', $playlist->description);
			echo $this->render('view', array('model' => $playlist));
		}
	}

	public function action_create()
	{
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');

		$model = new Playlist();
		if(isset($_POST['Playlist'])){
			$model->attributes=$_POST['Playlist'];
			if($model->validate()&&$model->save()){
				$model->author->saveCounters(array('totalPlaylists'=>1));
				glue::sitemap()->addUrl(glue::http()->url('/playlist/view', array('id' => $model->_id)), 'hourly', '1.0');
								
				$this->json_success(array('message' => 'Playlist created', '_id' => strval($model->_id)));
			}
		}
		$this->json_error(array('message'=>'Playlist could not be created because:','messages'=>$model->getErrors()));
	}

	public function action_save()
	{
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');
		
		if(!($playlist = Playlist::findOne(array('_id' => new MongoId(glue::http()->param('id')),'title' => array('$ne' => 'Watch Later')))))
			$this->json_error('That playlist was not found');
		if(!glue::auth()->check(array('^' => $playlist)))
			$this->json_error(self::DENIED);

		if(isset($_POST['Playlist'])){
			$playlist->attributes=$_POST['Playlist'];
			$playlist->videos=array();
			
			if(($videos=glue::http()->param('videos',array()))&&count($videos)>0&&count($videos)<=500){
				foreach($videos as $k => $v){
					if($video = Video::findOne(array('_id' => new MongoId(isset($v['video_id'])?$v['video_id']:''))))
						$playlist->add_video_at_pos($video->_id, $v['position']);
				}
			}
			$playlist->totalVideos=count($playlist->videos);			
			
			if($playlist->validate()&&$playlist->save())
				$this->json_success('Playlist saved');
			else
				$this->json_error(array('message'=>'Playlist could not be saved because:', 'messages'=>$playlist->getErrors()));
		}else
			$this->json_error(self::UNKNOWN);
	}

	function action_delete()
	{
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');
		if(!($playlist = Playlist::findOne(array('_id' => new MongoId(glue::http()->param('id','')), 'title' => array('$ne' => 'Watch Later')))))
			$this->json_error('That playlist could not be found');
		if(!glue::auth()->check(array('^' => $playlist)))
			$this->json_error(self::DENIED);
		$playlist->delete();
		$this->json_success('The playlist was deleted');
	}

	function action_batchDelete()
	{
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');
		$ids = glue::http()->param('ids',null);
		if(count($ids) <= 0 || count($ids)>1000){
			$this->json_error("No playlists were selected");
		}
	
		foreach($ids as $k => $id)
			$mongoIds[$k] = new MongoId($id);
		$playlists = Playlist::find(array('_id' => array('$in' => $mongoIds), 'userId' => glue::user()->_id, 'deleted' => 0));
	
		$ids=array(); // We reset these to know which were actually picked from the DB
		$mongoIds=array();
		foreach($playlists as $playlist){
			$ids[]=(string)$playlist->_id;
			$mongoIds[]=$playlist->_id;
		}
		Playlist::deleteAll(array('_id' => array('$in' => $mongoIds)));
		glue::elasticSearch()->deleteByQuery(array(
			'type' => 'playlist',
			'body' => array(
				"ids" => array("values" => $ids)
			)
		));		
		
		$playlist->author->saveCounters(array('totalPlaylists'=>-count($mongoIds)),0);
		$this->json_success(array('message'=>'The playlists you selected were deleted','updated'=>count($ids)));
	}	
	
	function action_batchSave()
	{
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');
		if(isset($_POST['Playlist'])&&($ids=glue::http()->param('ids',null))!==null){
			$updated=0;
			foreach($ids as $id){
				$playlist = Playlist::findOne(array('_id' => new MongoId($id)));
				if(!glue::auth()->check(array('^' => $playlist)))
					continue;
				$playlist->attributes=$_POST['Playlist'];
				if($playlist->validate()&&$playlist->save())
					$updated++;
			}
			$this->json_success(array('updated'=>$updated,'failed'=>count($ids)-$updated,'total'=>count($ids)));
		}
		$this->json_error(self::UNKNOWN);		
	}

	public function action_addVideo()
	{
		$this->title = 'Add Video To Playlist - StageX';
		if(!glue::http()->isAjax())
			glue::trigger('404');
		extract(glue::http()->param(array('playlist_id', 'video_ids' => array())));

		if($playlist=Playlist::findOne(array('_id'=>new MongoId($playlist_id), 'userId' => glue::user()->_id))){
			
			$mongoIds=array();
			foreach($video_ids as $id)
				$mongoIds[]=new MongoId($id);
			$videos=Video::find(array('_id'=>array('$in'=>$mongoIds)));

			$existingIds=array();
			foreach($videos as $video){
				if(!$playlist->videoAlreadyAdded($video->_id)){
					$playlist->addVideo($video->_id);
					if($playlist->listing === 0 || $playlist->listing === 1){ // If this playlist is not private
						app\models\Stream::PlaylistAddVideo(glue::user()->_id, $playlist->_id, $video->_id);
						if(glue::user()->autoshareAddToPlaylist)
							app\models\AutoPublishQueue::add_to_qeue(AutoPublishQueue::PL_V_ADDED, glue::user()->_id, $video->_id, $playlist->_id);
					}				
				}
			}
			if(count($playlist->videos)>500)
				$this->json_error('The video you selected was not added because you are limited to 500 videos per playlist.');
			if(!$playlist->save())
				$this->json_error('The video you selected was not added because of an unknown error.');
			$this->json_success('The video you selected was added to '.$playlist->title);			
		}else
			$this->json_error('The video you selected was not added because of an unknown error.');
	}

	function action_deleteVideo()
	{
		if(!glue::http()->isAjax())
			glue::trigger('404');

		extract(glue::http()->param(array('ids','playlist_id')));
		$playlist = Playlist::findOne(array('_id' => new MongoId($playlist_id), 'userId' => glue::user()->_id));
		if(!$playlist)
			$this->json_error('This playlist no longer exists');
		if(count($ids) <= 0)
			$this->json_error('You selected no videos to delete');

		$mongoIds=array();
		foreach($ids as $k => $v)
			$mongoIds[] = new MongoId($v);
		
		$playlistVideos=$playlist->videos;
		$numFound=0;
		
		array_walk($playlistVideos, function(&$n) {
			$n = (string)$n['_id'];
		});		
		foreach(Video::find(array('_id'=>array('$in'=>$mongoIds))) as $_id => $video){
			if(($k=array_search($_id,$playlistVideos))!==false){
				unset($playlist->videos[$k]);
				$numFound++;
			}
		}
		$playlist->totalVideos+=-$numFound;
		$playlist->save();
		$this->json_success('Videos removed');
	}
	
	function action_clear()
	{
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');
		if(!($playlist = Playlist::findOne(array('_id' => new MongoId(glue::http()->param('id',''))))))
			$this->json_error('That playlist could not be found');
		if(!glue::auth()->check(array('^' => $playlist)))
			$this->json_error(self::DENIED);
		$playlist->videos=array();
		$playlist->save();
		$this->json_success('The playlist was cleared');
	}	
	
	function action_subscribe()
	{
		if(!glue::auth()->check('ajax','post')){
			glue::trigger('404');
		}
				
		if(
			($id = glue::http()->param('id',null)) === null ||
			($playlist = Playlist::findOne(array("_id"=>new MongoId($id)))) === null
		){
			Json::error('Playlist not found');
		}
			
		$f = glue::db()->playlist_subscription->update(
			array('user_id' => glue::user()->_id, 'playlist_id' => $playlist->_id),
			array('$set' => array('update_time' => new MongoDate())),
			array('upsert' => true)
		);
		if(isset($f['upserted']) && $f['upserted']){
			$playlist->saveCounters(array('followers' => 1));
			Json::success(array('message' => 'You have subscribed to this playlist', '_id' => strval($f['upserted'])));
		} // Be silent about the relationship already existing
			
		Json::error(Json::UNKNOWN);
	}
	
	function action_unsubscribe()
	{
		if(!glue::auth()->check('ajax','post')){
			glue::trigger('404');	
		}

		if(($ids = glue::http()->param('id',null)) && is_array($ids)){
			foreach($ids as $id){
				if(
					($subscription = glue::db()->playlist_subscription->findOne(array("_id"=>new MongoId($id)))) !== null
					&& $response = glue::db()->playlist_subscription->remove(array('user_id' => glue::user()->_id, '_id' => new MongoId($id)))
				){
					if($response['n'] > 0){
						if($playlist = Playlist::findOne($subscription['playlist_id'])){
							// Playlist may not always exist if it has been deleted
							$playlist->saveCounters(array('followers' => -1));
						}
						Json::$succeeded++;
						continue;
					}
				}
				Json::$failed++;
			}
			Json::op(count($ids));
		}
		Json::error(Json::UNKNOWN);
	}
	
	function action_ajaxsearch()
	{
		if(!glue::http()->isAjax())
			glue::trigger('404');
		$term=glue::http()->param('term',null);
		$c=\app\models\Playlist::fts(array('title'),$term,array('deleted'=>0, 'userId' => glue::user()->_id))->limit(100);
	
		$res=array();
		foreach($c as $p){
			$res[]=array(
					'_id'=>(string)$p->_id, 'title'=>$p->title,'userId'=>$p->userId,'description'=>$p->description,
					'listing'=>$p->listing,'totalVideos'=>$p->totalVideos,'likes'=>$p->likes,'created'=>date('d M Y',$p->getTs($p->created)));
		}
		$this->json_success(array('results'=>$res));
	}	

	function action_renderBar()
	{
		if(!glue::http()->isAjax())
			glue::trigger('404');
		$video_ids = array();
		$user_ids = array();

		$videos_a = array();
		$users_a = array();

		// If there is a playlist lets get it and its videos
		if(!($playlist = Playlist::findOne(array('_id' => new MongoId(glue::http()->param('id')))))){
			ob_start();
			?>Playlist not found<?php
			$html = ob_get_contents();
			ob_end_clean();
			$this->json_error(array('html'=>$html));
		}
			
		// Now lets get its videos
		foreach($playlist->videos as $k => $v){
			$video_ids[] = $v['_id'];
		}
		
		$videos_result = app\models\Video::findAll(array('_id' => array('$in' => $video_ids)));
		foreach($videos_result as $k => $v){
			$videos_a[strval($v['_id'])] = $v;
			$user_ids[] = $v['userId'];
		}

		$users_result = app\models\User::findAll(array('_id' => array('$in' => $user_ids)));
		foreach($users_result as $k => $v){
			$users_a[strval($v['_id'])] = $v;
		}

		// Now lets form the html
		if(count($videos_a) > 0){
			ob_start(); ?>
				<ol>
					<?php foreach($videos_a as $k => $v){
						$video = app\models\Video::populate($v);
						$video->author = app\models\User::populate($users_a[strval($video->userId)]);

						?>
						<li class='playlist_video_item'>
							<?php if(glue::auth()->check(array('viewable' => $video))){ ?>
								<span class='vieo_image'><a href='<?php echo glue::http()->url('/video/watch', array('id' => $video->_id, 'playlist_id' => $playlist->_id)) ?>'><img src='<?php echo $video->getImage(88, 49) ?>' alt='thumbnail'/></a></span>
								<span class='info_pane'>
									<a href='<?php echo glue::http()->url('/video/watch', array('id' => $video->_id, 'playlist_id' => $playlist->_id)) ?>'>
										<?php echo strlen($video->title) > 100 ? html::encode(substr_replace(substr($video->title, 0, 50), '...', -3)) : html::encode($video->title) ?></a>
									<span class='uploaded_by'>by <a href='<?php echo glue::http()->url('/user/view', array('id' => $video->userId)) ?>'><?php echo $video->author->getUsername() ?></a></span>
								</span>
							<?php }else{ ?>
								<span class='video_not_exist'>Video Not Available</span>
							<?php } ?>
						</li>
					<?php } ?>
				</ol><?php
				$html = ob_get_contents();
			ob_end_clean();
			$this->json_success(array('html'=>$html));
		}else{
			ob_start();
				?>No videos found<?php
				$html = ob_get_contents();
			ob_end_clean();
			$this->json_error(array('html'=>$html));
		}
	}
}