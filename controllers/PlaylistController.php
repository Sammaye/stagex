<?php

use app\models\AutoPublishQueue;

use app\models\Video;

use app\models\Playlist;

class PlaylistController extends glue\Controller{

	public function authRules(){
		return array(
			array("allow",
				"actions"=>array('create', 'edit', 'save', 'delete', 'batchDelete', 'addVideo', 'add_many_videos', 'get_menu', 'batchSave', 'like', 'unlike', 'clear',
					'deleteVideo', 'suggestAddTo'),
				"users"=>array("@*")
			),
			array('allow', 'actions' => array('index', 'view', 'renderBar')),
			array("deny", "users"=>array("*")),
		);
	}
	
	public $layout='user_section';
	public $tab='playlists';

	public function action_index(){
		$this->action_view();
	}

	public function action_view(){
		$playlist = Playlist::model()->findOne(array('_id' => new MongoId(glue::http()->param('id')), 'deleted' => array('$ne' => 1)));

		if(!glue::roles()->checkRoles(array('deletedView' => $playlist, 'deniedView' => $playlist))){
			$this->pageTitle = 'Playlist Not Found - StageX';
			$this->render('Playlist/deleted', array('playlist'=>$playlist));
			exit();
		}

		$this->pageTitle = $playlist->title.' - StageX';
		$this->pageDescription = $playlist->description;
		$this->render('Playlist/view', array('playlist' => $playlist, 'user' => $playlist->author));
	}

	public function action_create(){
		$this->title = 'Create Playlist - StageX';
		$this->layout='user_section';

		$model = new Playlist();
		if(isset($_POST['Playlist'])){
			$model->attributes=$_POST['Playlist'];
			if($model->validate()&&$model->save()){
				glue::http()->redirect('/user/playlists');
			}
		}
		echo $this->render('create',array('model'=>$model));
	}

	public function action_edit(){
		$playlist = Playlist::model()->findOne(array('_id' => new MongoId(glue::http()->param('id','')), 'userId' => glue::user()->_id, 'title' => array('$ne' => 'Watch Later')));
		if(!glue::auth()->check(array('viewable' => $playlist))){
			$this->title = 'Playlist Not Found - StageX';
			echo $this->render('deleted');
		}else{
			$this->title = 'Playlist: '.$playlist->title.' - StageX';
			echo $this->render('edit', array('playlist' => $playlist));
		}
	}

	public function action_save(){
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');
		
		if(!($playlist = Playlist::model()->findOne(array('_id' => new MongoId(glue::http()->param('id')),'title' => array('$ne' => 'Watch Later')))))
			$this->json_error('That playlist was not found');
		if(!glue::auth()->check(array('^' => $playlist)))
			$this->json_error(self::DENIED);

		if(isset($_POST['Playlist'])){
			$playlist->attributes=$_POST['Playlist'];
			$playlist->videos=array();
			
			if(($videos=glue::http()->param('videos',array()))&&count($videos)>0&&count($videos)<=500){
				foreach($videos as $k => $v){
					if($video = Video::model()->findOne(array('_id' => new MongoId(isset($v['video_id'])?$v['video_id']:''))))
						$playlist->add_video_at_pos($video->_id, $v['position']);
				}
			}			
			
			if($playlist->validate()&&$playlist->save())
				$this->json_success('Playlist saved');
			else
				$this->json_error(array('message'=>'Playlist could not be saved because:', 'messages'=>$playlist->getErrors()));
		}else
			$this->json_error(self::UNKNOWN);
	}

	function action_delete(){
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');
		if(!($playlist = Playlist::model()->findOne(array('_id' => new MongoId(glue::http()->param('id','')), 'title' => array('$ne' => 'Watch Later')))))
			$this->json_error('That playlist could not be found');
		if(!glue::auth()->check(array('^' => $playlist)))
			$this->json_error(self::DENIED);
		$playlist->delete();
		$this->json_success('The playlist was deleted');
	}

	function action_batchDelete(){
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');
		$ids = glue::http()->param('ids',null);
		if(count($ids) <= 0 || count($ids)>1000){
			$this->json_error("No playlists were selected");
		}
	
		foreach($ids as $k => $id)
			$mongoIds[$k] = new MongoId($id);
		$playlists = Playlist::model()->find(array('_id' => array('$in' => $mongoIds), 'userId' => glue::user()->_id, 'deleted' => 0));
	
		$ids=array(); // We reset these to know which were actually picked from the DB
		$mongoIds=array();
		foreach($playlists as $playlist){
			$ids[]=(string)$playlist->_id;
			$mongoIds[]=$playlist->_id;
		}
		Playlist::model()->deleteAll(array('_id' => array('$in' => $mongoIds)));
		$playlist->author->saveCounters(array('totalPlaylists'=>-count($mongoIds)),0);
		glue::mysql()->query('UPDATE documents SET deleted=1 WHERE _id IN :id', array(':id' => $ids));	
		$this->json_success(array('message'=>'The playlists you selected were deleted','updated'=>count($ids)));
	}	

	function action_batchSave(){
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');
		if(isset($_POST['Playlist'])&&($ids=glue::http()->param('ids',null))!==null){
			$updated=0;
			foreach($ids as $id){
				$playlist = Playlist::model()->findOne(array('_id' => new MongoId($id)));
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

	public function action_addVideo(){
		$this->title = 'Add Video To Playlist - StageX';
		if(!glue::http()->isAjax())
			glue::trigger('404');
		extract(glue::http()->param(array('playlist_id', 'video_ids' => array())));

		if($playlist=Playlist::model()->findOne(array('_id'=>new MongoId($playlist_id), 'userId' => glue::user()->_id))){
			
			$mongoIds=array();
			foreach($video_ids as $id)
				$mongoIds[]=new MongoId($id);
			$videos=Video::model()->find(array('_id'=>array('$in'=>$mongoIds)));

			$existingIds=array();
			foreach($videos as $video){
				if(!$playlist->videoAlreadyAdded($video->_id))
					$playlist->addVideo($video->_id);
			}
			if(count($playlist->videos)>500)
				$this->json_error('The video you selected was not added because you aree limited to 500 videos per playlist.');
			if(!$playlist->save())
				$this->json_error('The video you selected was not added because of an unknown error.');
			if($playlist->listing === 0 || $playlist->listing === 1){ // If this playlist is not private
				app\models\Stream::PlaylistAddVideo(glue::user()->_id, $playlist->_id, $video->_id);
				if(glue::user()->autoshareAddToPlaylist)
					app\models\AutoPublishQueue::add_to_qeue(AutoPublishQueue::PL_V_ADDED, glue::user()->_id, $video->_id, $playlist->_id);
			}
			$this->json_success('The video you selected was added to '.$playlist->title);			
		}else
			$this->json_error('The video you selected was not added because of an unknown error.');
	}

	function action_deleteVideo(){
		if(!glue::http()->isAjax())
			glue::trigger('404');

		extract(glue::http()->param(array('ids','playlist_id')));
		$playlist = Playlist::model()->findOne(array('_id' => new MongoId($playlist_id), 'userId' => glue::user()->_id));
		if(!$playlist)
			$this->json_error('This playlist no longer exists');
		if(count($ids) <= 0)
			$this->json_error('You selected no videos to delete');

		$mongoIds=array();
		foreach($ids as $k => $v)
			$mongoIds[] = new MongoId($v);
		
		$playlistVideos=$playlist->videos;
		array_walk($playlistVideos, function(&$n) {
			$n = (string)$n['_id'];
		});		
		foreach(Video::model()->find(array('_id'=>array('$in'=>$mongoIds))) as $_id => $video){
			if(($k=array_search($_id,$playlistVideos))!==false)
				unset($playlist->videos[$k]);
		}
		$playlist->save();
		$this->json_success('Videos removed');
	}

	public function action_get_menu(){
		$this->pageTitle = 'Get Playlists - StageX';
		if(!glue::http()->isAjax())
			Glue::route("error/notfound");

		$watch_later = Playlist::model()->findOne(array('title' => 'Watch Later', 'user_id' => glue::session()->user->_id)); // Get the always top one
		$playlists = Playlist::model()->find(array('user_id' => glue::session()->user->_id, 'title' => array('$ne' => 'Watch Later'),
			'deleted' => array('$ne' => 1)))->sort(array('ts' => -1))->limit(1000);
		?><div>
			<div style='max-height:300px; overflow:auto;'>
				<div class='item' data-playlist='<?php echo $watch_later ? $watch_later->_id : '' ?>'>Watch Later</div>
				<div class='divider'></div>
				<?php
				if(count($playlists) > 0){
					foreach($playlists as $k => $v){ ?>
						<div class='item' data-playlist='<?php echo $v->_id ?>'><?php echo $v->title ?></div>
					<?php }
				}else{ ?>
					<div style='padding:10px; font-size:14px; color:#999999; line-height:17px;'>No Playlists exist</div>
				<?php } ?>
			</div>
		</div><?php

	}

	function get_menu_summary($message){
		ob_start(); ?>
			<div style='padding:15px; text-align:center;'>
				<div style='font-size:16px; font-weight:normal; line-height:20px;'><?php echo $message ?></div>
				<div class='grey_css_button back_reload' style='margin-top:15px;'>Back</div>
			</div>
			<?php $html = ob_get_contents();
		ob_end_clean();

		return $html;
	}
	
	function action_suggestAddTo(){
		$this->title='Suggest Playlists - StageX';
		if(!glue::http()->isAjax())
			glue::trigger('404');
		$term=glue::http()->param('term',null);
		$c=\app\models\Playlist::model()->fts(array('title'),$term,array('deleted'=>0, 'userId' => glue::user()->_id))->limit(100);
	
		$res=array();
		foreach($c as $p){
			$res[]=array(
					'_id'=>(string)$p->_id, 'title'=>$p->title,'userId'=>$p->userId,'description'=>$p->description,
					'listing'=>$p->listing,'totalVideos'=>$p->totalVideos,'likes'=>$p->likes,'created'=>date('d M Y',$p->getTs($p->created)));
		}
		$this->json_success(array('results'=>$res));
	}	

	function action_renderBar(){
		if(!glue::http()->isAjax())
			glue::trigger('404');
		$video_ids = array();
		$user_ids = array();

		$videos_a = array();
		$users_a = array();

		// If there is a playlist lets get it and its videos
		if(!($playlist = Playlist::model()->findOne(array('_id' => new MongoId(glue::http()->param('id')))))){
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
		
		$videos_result = app\models\Video::model()->findAll(array('_id' => array('$in' => $video_ids)));
		foreach($videos_result as $k => $v){
			$videos_a[strval($v['_id'])] = $v;
			$user_ids[] = $v['userId'];
		}

		$users_result = app\models\User::model()->findAll(array('_id' => array('$in' => $user_ids)));
		foreach($users_result as $k => $v){
			$users_a[strval($v['_id'])] = $v;
		}

		// Now lets form the html
		if(count($videos_a) > 0){
			ob_start(); ?>
				<ol>
					<?php foreach($videos_a as $k => $v){
						$video = app\models\Video::model()->populateRecord($v);
						$video->author = app\models\User::model()->populateRecord($users_a[strval($video->userId)]);

						?>
						<li class='playlist_video_item'>
							<?php if(glue::auth()->check(array('viewable' => $video))){ ?>
								<span class='vieo_image'><a href='<?php echo glue::http()->url('/video/watch', array('id' => $video->_id, 'playlist_id' => $playlist->_id)) ?>'><img src='<?php echo $video->getImage(124, 69) ?>' alt='thumbnail'/></a></span>
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