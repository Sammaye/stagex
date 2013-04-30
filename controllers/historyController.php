<?php
class historyController extends GController{

	public $layout = 'user_section';

	// A set of filters to be run before and after the controller action
	public function filters(){
		return array('rbam');
	}

	public function accessRules(){
		return array(
			array("allow", "users"=>array("@*")),
			array("deny", "users"=>array("*")),
		);
	}

	function action_index(){
		$this->action_watched();
	}

	public function action_watched(){
		$this->pageTitle = 'Watched Videos - StageX';

		$this->tab = 'watched';
		$this->render('stream/watched', array('items' =>
			glue::db()->watched_history->find(array("user_id" => Glue::session()->user->_id))->sort(array('ts' => -1))->limit(20)
		));
	}

	public function action_rated_videos(){
		$this->pageTitle = 'Rated Videos - StageX';

		$this->tab = 'likes';
		$this->subtab = 'liked_videos';

		$_filter = isset($_GET['filter']) ? $_GET['filter'] : null;
		$items = glue::db()->video_likes->find(array("user_id" => Glue::session()->user->_id, 'like' => 1))->sort(array('ts' => -1))->limit(20);

		$this->render('stream/rated_videos', array('items' => $items, '_filter' => $_filter));
	}

	public function action_rated_playlists(){
		$this->pageTitle = 'Rated Playlists - StageX';

		$this->tab = 'likes';
		$this->subtab = 'liked_playlists';

		$_filter = isset($_GET['filter']) ? $_GET['filter'] : null;
		$items = glue::db()->playlist_likes->find(array("user_id" => Glue::session()->user->_id, 'like' => 1))->sort(array('ts' => -1))->limit(20);

		$this->render('stream/rated_playlists', array('items' => $items, '_filter' => $_filter));
	}

	function action_remove_ratings(){
		$this->pageTitle = 'Remove History - StageX';

		if(!glue::http()->isAjax())
			glue::route(glue::config('404', 'errorPages'));

		$_ids = isset($_POST['items']) ? $_POST['items'] : array();
		$type = isset($_POST['type']) ? $_POST['type'] : null;

		if(!is_array($_ids) || sizeof($_ids) <= 0 && ($type != 'video' && $type != 'playlist')){
			GJSON::kill(GJSON::UNKNOWN);
		}

		$mongo_ids = array();
		foreach($_ids as $k=>$v){
			$mongo_ids[$k] = new MongoId($v);
		}

		$like_rows = $type == 'video' ? glue::db()->video_likes->find(array("user_id" => Glue::session()->user->_id, "_id" => array('$in' => $mongo_ids))) :
				glue::db()->playlist_likes->find(array("user_id" => Glue::session()->user->_id, "_id" => array('$in' => $mongo_ids)));

		foreach($like_rows as $k=>$v){

			if($type == 'video'){
				$item_row = Video::model()->findOne(array('_id' => $v['item']));
			}else{
				$item_row = Playlist::model()->findOne(array('_id' => $v['item']));
			}

			if($item_row){
				if($v['like'] == 1){
					$item_row->db()->update(array('_id' => $item_row->_id), array('$inc' => array('likes' => -1)));
				}elseif($v['like'] == 0){
					$item_row->db()->update(array('_id' => $item_row->_id), array('$inc' => array('dislikes' => -1)));
				}//$item_row->save();

				if($type == 'video'){
					glue::db()->video_likes->remove(array('_id' => $v['_id']));
				}else{
					glue::db()->playlist_likes->remove(array('_id' => $v['_id']));
				}
			}
		}
		GJSON::kill('The rated items you selected have been deleted', true);
	}

	function action_remove_watched(){
		$this->pageTitle = 'Remove History - StageX';

		if(!glue::http()->isAjax())
			glue::route(glue::config('404', 'errorPages'));

		$_ids = isset($_POST['items']) ? $_POST['items'] : array();

		if(!is_array($_ids) || sizeof($_ids) <= 0){
			//echo "hwere";
			GJSON::kill(GJSON::UNKNOWN);
		}

		$mongo_ids = array();
		foreach($_ids as $k=>$v){
			$mongo_ids[$k] = new MongoId($v);
		}

		$rows = glue::db()->watched_history->find(array('_id' => array('$in' => $mongo_ids), 'user_id' => glue::session()->user->_id));
		if($rows->count() != sizeof($_ids)){
			GJSON::kill(GJSON::UNKNOWN);
		}
		glue::db()->watched_history->remove(array('_id' => array('$in' => $mongo_ids), 'user_id' => glue::session()->user->_id));

		GJSON::kill('The history items you selected have been deleted', true);
	}

	function action_remove_all_watched(){
		$this->pageTitle = 'Remove History - StageX';

		if(!glue::http()->isAjax())
			glue::route(glue::config('404', 'errorPages'));

		glue::db()->watched_history->remove(array('user_id' => glue::session()->user->_id));

		ob_start(); ?>
			<div style='font-size:16px; font-weight:normal; padding:45px; text-align:center;'>All history has been cleared</div>
			<?php $html = ob_get_contents();
		ob_end_clean();

		GJSON::kill(array('html' => $html, 'messages' => 'History has been removed'), true);
	}

	function action_get_rated_history(){
		$this->pageTitle = 'Get History - StageX';

		if(!glue::http()->isAjax())
			glue::route(glue::config('404', 'errorPages'));

		$_last_ts = isset($_GET['ts']) ? $_GET['ts'] : null;
		$type = isset($_GET['type']) ? $_GET['type'] : null;
		$_filter = isset($_GET['filter']) ? $_GET['filter'] : null;


		if($type != 'video' && $type != 'playlist'){
			GJSON::kill(GJSON::UNKNOWN);
		}

		$_ts_query = array();
		$_additional = array();

		if($_filter == 'disliked'){
			$_additional = array('liked' => 0);
		}else{
			$_additional = array('liked' => 1);
		}

		if($_last_ts){
			$_ts_query = array('ts' => array('$lt' => new MongoDate($_last_ts)));
		}

		if($type == 'video'){
			$rows = glue::db()->video_likes->find(array_merge(array("user_id" => Glue::session()->user->_id, $_ts_query, $_additional)));
		}else{
			$rows = glue::db()->playlist_likes->find(array_merge(array("user_id" => Glue::session()->user->_id, $_ts_query, $_additional)));
		}

		$html = '';
		if($rows->count() > 0){
			foreach($rows as $k => $item){
				$item = (Object)$item;
				if($_filter == 'video'){
					ob_start();
						$video = Video::model()->findOne(array('_id' => $item->item));
						if($video instanceof Video){
							$this->partialRender('videos/_video_ext', array('model' => $video, 'custid' => $item->_id, 'item' => $item, 'show_checkbox' => true));
						}
						$partial_html = ob_get_contents();
					ob_end_clean();
				}elseif($_filter == 'playlist'){
					ob_start();
						$related_o = Playlist::model()->findOne(array('_id' => $item->item));
						if($related_o instanceof Playlist){
							$this->partialRender('Playlist/_playlist_ext', array('model' => $related_o, 'item' => $item, 'show_checkbox' => true));
						}
					ob_end_clean();
				}
				$html .= $partial_html;
			}
			GJSON::kill(array('html' => $html), true);
		}else{
			GJSON::kill(array('noneleft' => true, 'messages' => array('There are no more history items to load'), 'initMessage' => 'No history could be found'));
		}
	}

	function action_get_watched_history(){
		$this->pageTitle = 'Get History - StageX';

		if(!glue::http()->isAjax())
			glue::route(glue::config('404', 'errorPages'));

		$_last_ts = isset($_GET['ts']) ? $_GET['ts'] : null;

		$_ts_query = array();
		$_additional = array();

		if($_last_ts){
			$_ts_query = array('ts' => array('$lt' => new MongoDate($_last_ts)));
		}

		$rows = glue::db()->watched_history->find(array_merge(array('user_id' => glue::session()->user->_id), $_ts_query, $_additional))->sort(array('ts' => -1))->limit(20);

		$html = '';
		if($rows->count() > 0){
			foreach($rows as $k => $item){
				ob_start();
					$item = (Object)$item;
					$video = Video::model()->findOne(array('_id' => $item->item));
					if($video instanceof Video){
						$this->partialRender('videos/_video_ext', array('model' => $video, 'custid' => $item->_id, 'item' => $item, 'show_checkbox' => true));
					}
					$partial_html = ob_get_contents();
				ob_end_clean();
				$html .= $partial_html;
			}
			GJSON::kill(array('html' => $html), true);
		}else{
			GJSON::kill(array('noneleft' => true, 'messages' => array('There are no more history items to load'), 'initMessage' => 'No history could be found'));
		}
	}
}