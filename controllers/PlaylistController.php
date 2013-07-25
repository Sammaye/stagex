<?php
class PlaylistController extends GController{

	// A set of filters to be run before and after the controller action
	public function filters(){
		return array('rbam');
	}

	public function accessRules(){
		return array(
			array("allow",
				"actions"=>array('add', 'edit', 'save_playlist', 'delete', 'batch_delete', 'add_video', 'add_many_videos', 'get_menu', 'set_detail', 'like', 'unlike', 'clear_all_videos',
					'delete_many_videos'),
				"users"=>array("@*")
			),
			array('allow', 'actions' => array('index', 'view', 'get_playlist_bar')),
			array("deny", "users"=>array("*")),
		);
	}

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

	public function action_add(){

		$this->pageTitle = 'Add New Playlist - StageX';

		if(!glue::http()->isAjax())
			Glue::route("error/notfound");

		$playlist = new Playlist();
		$playlist->_attributes($_POST);

		if($playlist->validate()){
			$playlist->save();
			echo json_encode(array('success' => true, 'id' => strval($playlist->_id)));
		}else{
			echo json_encode(array('success' => false, 'messages' => $playlist->getErrorMessages()));
		}
	}

	public function action_edit(){
		$this->pageTitle = 'Edit Playlist - StageX';

		$playlist = Playlist::model()->findOne(array('_id' => new MongoId($_GET['id']), 'user_id' => glue::session()->user->_id, 'title' => array('$ne' => 'Watch Later')));

		if(!glue::roles()->checkRoles(array('deletedView' => $playlist)) && glue::roles()->checkRoles(array('Owns' => $playlist))){
			$this->pageTitle = 'Playlist Not Found - StageX';
			$this->render('Playlist/deleted', array('playlist'=>$playlist));
			exit();
		}

		$this->render('Playlist/edit', array('playlist' => $playlist));
	}

	public function action_save_playlist(){
		$this->pageTitle = 'Save Playlist - StageX';

		if(!glue::http()->isAjax())
			Glue::route("error/notfound");

		$playlist = Playlist::model()->findOne(array('_id' => new MongoId($_POST['id']), 'deleted' => array('$ne' => 1), 'title' => array('$ne' => 'Watch Later')));

		if(!$playlist){
			GJSON::kill('That playlist was not found');
		}

		if(!glue::roles()->checkRoles(array('^' => $playlist))){
			GJSON::kill(GJSON::DENIED);
		}

		if(isset($_POST['Playlist'])){
			$playlist->_attributes($_POST['Playlist']);
			if($playlist->validate()){

				$playlist->videos = array();
				$video_assign_error = false;

				if(isset($_POST['videos'])){
					if(count($_POST['videos']) > 0){
						foreach($_POST['videos'] as $k => $v){
							$video = Video::model()->findOne(array('_id' => new MongoId($v['video_id'])));
							if($video){
								$playlist->add_video_at_pos($video->_id, $v['position']);
							}else{
								$video_assign_error = true;
								break;
							}
						}
					}
				}

				if(count($playlist->videos) > 200){
					$playlist->addErrorMessage('You cannot have more than 200 videos to a single playlist. Please remove some and continue.');
					echo json_encode(array('success' => false, 'html' => html::form_summary($playlist, array(
						'errorHead' => '<h2>Could not save playlist</h2><p>This playlist could not be saved because:</p>'
					))));
					exit();
				}

				if(!$video_assign_error){
					$playlist->save();
					echo json_encode(array('success' => true));
				}else{
					$playlist->addErrorMessage('One or more videos for this playlist could not be saved because we could not find a valid video to go with it.');
					echo json_encode(array('success' => false, 'html' => html::form_summary($playlist, array(
						'errorHead' => '<h2>Could not save playlist</h2><p>This playlist could not be saved because:</p>'
					))));
				}
				exit();
			}

			echo json_encode(array('success' => false, 'html' => html::form_summary($playlist, array(
				'errorHead' => '<h2>Could not save playlist</h2><p>This playlist could not be saved because:</p>'
			))));
		}else{
			echo json_encode(array('success' => false));
		}
	}

	function action_delete(){
		$this->pageTitle = 'Delete Playlist - StageX';

		if(!glue::http()->isAjax())
			Glue::route("error/notfound");

		$id = isset($_GET['id']) ? $_GET['id'] : null;
		$playlist = Playlist::model()->findOne(array('_id' => new MongoId($id), 'title' => array('$ne' => 'Watch Later')));

		if(!$playlist)
			GJSON::kill('That playlist could not be found');

		if(!glue::roles()->checkRoles(array('^' => $playlist))){
			GJSON::kill(GJSON::DENIED);
		}

		$playlist->deleted = 1;
		$playlist->save();

		glue::db()->playlist_likes->remove(array('item' => $playlist->_id));

		glue::session()->user->total_playlists = glue::session()->user->total_playlists-1;
		glue::session()->user->save();

		GJSON::kill('The playlist was deleted', true);
	}

	function action_batch_delete(){
		$this->pageTitle = 'Delete Playlists - StageX';
		if(!glue::http()->isAjax())
			Glue::route("error/notfound");

		$playlists = isset($_POST['playlists']) ? $_POST['playlists'] : null;

		if(count($playlists) <= 0){
			GJSON::kill('No playlists were selected');
		}

		foreach($playlists as $k => $id){
			$playlist_ids[$k] = new MongoId($id);
		}

		$playlist_rows = Playlist::model()->find(array('_id' => array('$in' => $playlist_ids), 'user_id' => glue::session()->user->_id, 'title' => array('$ne' => 'Watch Later')));

		if(count($playlist_ids) != $playlist_rows->count()){
			GJSON::kill(GJSON::UNKNOWN);
		}

		glue::mysql()->query('UPDATE documents SET deleted=1 WHERE uid = :user_id AND _id IN :id', array(
			':user_id' => strval(glue::session()->user->_id),
			':id' => $playlists
		));

		//Playlist::model()->remove(array(('_id' => array('$in' => $playlist_ids), 'user_id' => glue::session()->user->_id));

		$playlist = new Playlist();
		$playlist->Db()->update(array('_id' => array('$in' => $playlist_ids), 'user_id' => glue::session()->user->_id), array('$set' => array('deleted' => 1)), array('multiple' => true));

		glue::db()->playlist_likes->remove(array('item' => array('$in' => $playlist_ids)));

		glue::session()->user->total_playlists = glue::session()->user->total_playlists > count($playlist_ids) ? glue::session()->user->total_playlists-count($playlist_ids) : 0;
		glue::session()->user->save();

		GJSON::kill('The playlists you selected were deleted', true);
	}

	function action_set_detail(){
		$this->pageTitle = 'Save Playlist - StageX';
		if(!glue::http()->isAjax())
			Glue::route("error/notfound");

		if(isset($_POST['field'])){
			$field = $_POST['field'] == 'listing' ? $_POST['field'] : null;
		}else{
			$field = null;
		}

		$value = isset($_POST['value']) ? $_POST['value'] : null;
		$playlists = isset($_POST['playlists']) ? $_POST['playlists'] : array();

		if(count($playlists) <= 0){
			GJSON::kill('No playlists were selected');
		}

		if(!$field){
			GJSON::kill('No field was specified for change. Please refresh the page and try again');
		}

		$validated_playlists = array();
		foreach($playlists as $k => $id){
			$playlist = Playlist::model()->findOne(array('_id' => new MongoId($id), 'deleted' => array('$ne' => 1), 'title' => array('$ne' => 'Watch Later')));

			if(glue::roles()->checkRoles(array('^' => $playlist))){
				$playlist->$field = $value;
				if($playlist->validate(array($field))){
					$validated_playlists[] = $playlist;
				}else{
					GJSON::kill(array('messages'=>$playlist->getErrorMessages()));
				}
			}else{
				GJSON::kill(GJSON::DENIED);
			}
		}

		foreach($validated_playlists as $k => $playlist){
			$playlist->save();
		}
		GJSON::kill('The playlists you selected were saved', true);
	}

	public function action_add_video(){
		$this->pageTitle = 'Add Video To Playlist - StageX';
		if(!glue::http()->isAjax())
			Glue::route("error/notfound");

		$video = Video::model()->findOne(array('_id' => new MongoId($_POST['id'])));
		if($video){
			$playlist = Playlist::model()->findOne(array('_id' => new MongoId($_POST['p_id']), 'user_id' => glue::session()->user->_id, 'deleted' => array('$ne' => 1)));
			if($playlist){
				if((count($playlist->videos)+1) > 200){
					echo json_encode(array('success' => false, 'html' =>
						$this->get_menu_summary('This video would exceed the 200 slots you have on this playlist')));
					exit();
				}

				if($playlist->video_already_added($video->_id)){
					echo json_encode(array('success' => false, 'html' =>
						$this->get_menu_summary('This video has already been added to '.$playlist->title)));
					exit();
				}

				$playlist->add_video($video->_id);
				$playlist->save();

				if(!($playlist->listing == 'u' && $playlist->listing =='n')){ // If this playlist is not private
					Stream::add_video_2_playlist(glue::session()->user->_id, $playlist->_id, $video->_id);

					if(glue::session()->user->should_autoshare('video_2_pl')){
						AutoPublishQueue::add_to_qeue(AutoPublishQueue::PL_V_ADDED, glue::session()->user->_id, $video->_id, $playlist->_id);
					}
				}

				echo json_encode(array('success' => true, 'html' =>
					$this->get_menu_summary('The video you selected was added to '.$playlist->title)));
			}else{
				echo json_encode(array('success' => false, 'html' =>
					$this->get_menu_summary('The video you selected was not added because of an unknown error.')));
			}
		}else{
			echo json_encode(array('success' => false, 'html' =>
				$this->get_menu_summary('The video you selected was not added because there was an error getting this video.')));
		}
	}

	public function action_add_many_videos(){
		$this->pageTitle = 'Add Videos To Playlist - StageX';
		if(!glue::http()->isAjax())
			Glue::route("error/notfound");

		if(is_array($_POST['id'])){
			$vars_array = array();

			foreach($_POST['id'] as $k => $v){
				$vars_array[] = new MongoId($v);
			}
			$videos = Video::model()->find(array('_id' => array('$in' => $vars_array)));
			$playlist = Playlist::model()->findOne(array('_id' => new MongoId($_POST['p_id']), 'user_id' => glue::session()->user->_id, 'deleted' => array('$ne' => 1)));

			if($videos->count() == count($_POST['id']) && $playlist){
				if((count($playlist->videos)+$videos->count()) > 200){
					echo json_encode(array('success' => false, 'html' =>
						$this->get_menu_summary('You selected '.count($_POST['id']).' videos but only have '.(200-count($playlist->videos)).' free slots on this playlist')));
					exit();
				}

				foreach($videos as $k => $v){
					$playlist->add_video($v->_id);
				}

				$playlist->save();
				echo json_encode(array('success' => true, 'html' =>
					$this->get_menu_summary('The video(s) you selected were added to '.$playlist->title)));
			}else{
				switch(true){
					case !$playlist:
						echo json_encode(array('success' => false, 'html' =>
							$this->get_menu_summary('The video(s) you selected were not added to a playlist because no playlist was selected.')));
						break;
					default:
						echo json_encode(array('success' => false, 'html' =>
							$this->get_menu_summary('The video(s) you selected were not added because of an unknown error.')));
						break;
				}
			}
		}else{
			echo json_encode(array('success' => false, 'html' =>
				$this->get_menu_summary('You selected no video(s) to add to a playlist.')));
		}
	}

	function action_delete_many_videos(){
		$this->pageTitle = 'Remove Videos From Playlist - StageX';
		if(!glue::http()->isAjax())
			Glue::route("error/notfound");

		$_ids = isset($_POST['items']) ? $_POST['items'] : array();
		$playlist = Playlist::model()->findOne(array('_id' => new MongoId($_POST['id']), 'user_id' => glue::session()->user->_id));

		if(count($_ids) <= 0)
			GJSON::kill('You selected no videos to delete');

		if(!$playlist || (bool)$playlist->deleted){
			GJSON::kill('This playlist no longer exists');
		}

		$vars_array = array();
		foreach($_ids as $k => $v){
			$vars_array[] = new MongoId($v);
		}

		for($i=0,$size = count($playlist->videos); $i < $size; $i++){ // Unset the videos
			if($playlist->videos[$i]['_id'] == $vars_array[$i]){
				unset($playlist->videos[$i]);
			}
		}

		//reindex the array
		for($i=0,$size = count($playlist->videos); $i < $size; $i++){
			$playlist->videos[$i]['pos'] = $i;
		}
		$playlist->save();
		GJSON::kill('Videos were deleted', true);
	}

	function action_clear_all_videos(){
		$this->pageTitle = 'Clear All Videos From Playlist - StageX';
		if(!glue::http()->isAjax())
			Glue::route("error/notfound");

		$playlist = Playlist::model()->findOne(array('_id' => new MongoId($_GET['id']), 'user_id' => glue::session()->user->_id));

		if(!$playlist || (bool)$playlist->deleted){
			GJSON::kill('This playlist no longer exists');
		}

		$playlist->videos = array();
		$playlist->save();

		ob_start(); ?>
			<div class='padded_list_not_found'>No videos were found</div>
			<?php
			$html = ob_get_contents();
		ob_end_clean();

		GJSON::kill(array('html' => $html, 'messages' => array('All videos were removed')), true);
	}

	function action_like(){
		$this->pageTitle = 'Like Playlist - StageX';
		if(!glue::http()->isAjax())
			Glue::route("error/notfound");

		$playlist = Playlist::model()->findOne(array('_id' => new MongoId($_GET['id'])));
		//var_dump($playlist);

		if($playlist){
			$playlist->like();
			$playlist->save();

			if(!($playlist->listing == 'u' && $playlist->listing =='n')){
				Stream::like_playlist(glue::session()->user->_id, $playlist->_id);

				if(glue::session()->user->should_autoshare('lk_dl')){
					AutoPublishQueue::add_to_qeue(AutoPublishQueue::LK_PL, glue::session()->user->_id, null, $playlist->_id);
				}
			}
		}
		GJSON::kill('This playlist was liked', true);
	}

	function action_unlike(){
		$this->pageTitle = 'Unlike Playlist - StageX';
		if(!glue::http()->isAjax())
			Glue::route("error/notfound");

		$playlist = Playlist::model()->findOne(array('_id' => new MongoId($_GET['id'])));

		if($playlist){
			$playlist->unlike();
			$playlist->save();
		}
		GJSON::kill('This playlist was unliked', true);
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

	function action_get_playlist_bar(){

		if(!glue::http()->isAjax())
			Glue::route("error/notfound");

		$video_ids = array();
		$user_ids = array();

		$videos_a = array();
		$users_a = array();

		// If there is a playlist lets get it and its videos
		$playlist_id = isset($_GET['id']) ? $_GET['id'] : null;
		if($playlist_id){
			$playlist = Playlist::model()->findOne(array('_id' => new MongoId($playlist_id)));
			// Now lets get its videos

			foreach($playlist->videos as $k => $v){
				$video_ids[] = $v['_id'];
			}
		}

		$videos_result = glue::db()->videos->find(array('_id' => array('$in' => $video_ids)));
		foreach($videos_result as $k => $v){
			$videos_a[strval($v['_id'])] = $v;
			$user_ids[] = $v['user_id'];
		}

		$users_result = glue::db()->users->find(array('_id' => array('$in' => $user_ids)));
		foreach($users_result as $k => $v){
			$users_a[strval($v['_id'])] = $v;
		}

		// Now lets form the html
		if(count($videos_a) > 0){
			ob_start(); ?>
				<ol>
					<?php foreach($videos_a as $k => $v){
						$video = new Video();
						$video->setAttributes($v);

						$user = new User();
						$user->setAttributes($users_a[strval($v['user_id'])]);

						$video->author = $user;
						?>
						<li class='playlist_video_item'>
							<?php if(glue::roles()->checkRoles(array('canView' => $video))){ ?>
								<span class='vieo_image'><a href='<?php echo glue::url()->create('/video/watch', array('id' => $video->_id, 'plid' => $playlist->_id)) ?>'><img src='<?php echo $video->getImage(124, 69) ?>' alt='thumbnail'/></a></span>
								<span class='info_pane'>
									<a href='<?php echo glue::url()->create('/video/watch', array('id' => $video->_id, 'plid' => $playlist->_id)) ?>'>
										<?php echo strlen($video->title) > 100 ? html::encode(substr_replace(substr($video->title, 0, 50), '...', -3)) : html::encode($video->title) ?></a>
									<span class='uploaded_by'>by <a href='<?php echo glue::url()->create('/user/view', array('id' => $video->user_id)) ?>'><?php echo $video->author->getUsername() ?></a></span>
								</span>
							<?php }else{ ?>
								<span class='video_not_exist'>Video Either Not Available</span>
							<?php } ?>
						</li>
					<?php } ?>
				</ol><?php
				$html = ob_get_contents();
			ob_end_clean();

			GJSON::kill(array(
				'html' => $html
			), true);
		}else{

			ob_start();
				?>No videos found<?php
				$html = ob_get_contents();
			ob_end_clean();

			GJSON::kill(array('html' => $html));
		}
	}
}