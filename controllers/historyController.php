<?php
class historyController extends glue\Controller{

	public $layout = 'user_section';

	public function authRules(){
		return array(
			array("allow", "users"=>array("@*")),
			array("deny", "users"=>array("*")),
		);
	}

	function action_index(){
		$this->action_watched();
	}

	public function action_watched(){
		$this->title = 'Watched Videos - StageX';
		$this->tab = 'videos';
		
		extract(glue::http()->param(array('query','from_date','to_date'),null));
		
		$timeRange=array();
		$idRange=array();
		if($from_date)
			$timeRange['ts']['$gte']=new MongoDate(strtotime(str_replace('/','-',$from_date)));
		if($to_date)
			$timeRange['ts']['$lt']=new MongoDate(strtotime(str_replace('/','-',$to_date.' +1 day')));
		if($query){
			$videos=iterator_to_array(\app\models\Video::model()->find(array('title'=>new \MongoRegex("/^$query/")))->sort(array('title'=>1))->limit(1000));
			$mongoIds=array();
			foreach($videos as $_id=>$video)
				$mongoIds[]=new \MongoId($_id);
			$idRange=array('item'=>array('$in'=>$mongoIds));
		}
		echo $this->render('stream/watched', array('items' =>
			glue::db()->watched_history->find(array_merge(array("user_id" => Glue::user()->_id),$timeRange,$idRange))->sort(array('ts' => -1))
		));
	}

	public function action_rated(){
		$this->title = 'Rated Videos - StageX';
		$this->tab = 'videos';
		
		extract(glue::http()->param(array('tab','query','from_date','to_date'),null));
		
		$timeRange=array();
		$idRange=array();
		if($from_date)
			$timeRange['ts']['$gte']=new MongoDate(strtotime(str_replace('/','-',$from_date)));
		if($to_date)
			$timeRange['ts']['$lt']=new MongoDate(strtotime(str_replace('/','-',$to_date.' +1 day')));
		if($query){
			$videos=iterator_to_array(\app\models\Video::model()->find(array('title'=>new \MongoRegex("/^$query/")))->sort(array('title'=>1))->limit(1000));
			$mongoIds=array();
			foreach($videos as $_id=>$video)
				$mongoIds[]=new \MongoId($_id);			
			$idRange=array('item' => array('$in'=>$mongoIds));
		}

		if($tab=='dislikes')
			$rated=glue::db()->video_likes->find(array_merge(array("user_id" => Glue::user()->_id, 'like' => 0),$timeRange,$idRange));
		else
			$rated=glue::db()->video_likes->find(array_merge(array("user_id" => Glue::user()->_id, 'like' => 1),$timeRange,$idRange));
		$rated->sort(array('ts' => -1));

		echo $this->render('stream/rated_videos', array('items' => $rated));
	}

	public function action_followed(){
		$this->title = 'Rated Playlists - StageX';

		$this->tab = 'videos';

		$_filter = isset($_GET['filter']) ? $_GET['filter'] : null;
		$items = glue::db()->playlist_likes->find(array("user_id" => Glue::session()->user->_id, 'like' => 1))->sort(array('ts' => -1))->limit(20);

		$this->render('stream/rated_playlists', array('items' => $items, '_filter' => $_filter));
	}

	function action_deleteRated(){
		$this->title = 'Remove History - StageX';
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');

		extract(glue::http()->param(array('ids')));
		if(!$ids||(is_array($ids)&&count($ids) <= 0))
			$this->json_error(self::UNKNOWN);

		$mongoIds = array();
		foreach($ids as $k=>$v){
			$mongoIds[$k] = new MongoId($v);
		}

		$updated=0; $failed=0;
		$rows=glue::db()->video_likes->find(array('user_id'=>glue::user()->_id, "_id" => array('$in' => $mongoIds)));

		foreach($rows as $k=>$v){
			$item = app\models\Video::model()->findOne(array('_id' => $v['item']));
			if($item instanceof app\models\Video){
				if($v['like'] == 1){
					$item->saveCounters(array('likes'=>-1),0);
				}elseif($v['like'] == 0)
					$item->saveCounters(array('dislikes'=>-1),0);
				$updated++;
			}else
				$failed++;
		}
		glue::db()->video_likes->remove(array('user_id'=>glue::user()->_id, "_id" => array('$in' => $mongoIds)));
		
		$this->json_success(array('message'=>'Videos deleted', 'updated'=>$updated,'failed'=>$failed));
	}

	function action_deleteWatched(){
		$this->title = 'Remove History - StageX';
		if(!glue::auth()->check('ajax','post'))
			glue::trigger('404');
		extract(glue::http()->param('ids'));
		if(!$ids||(is_array($_ids)&&count($_ids) <= 0))
			$this->json_error(self::UNKNOWN);

		$mongoIds = array();
		foreach($_ids as $k=>$v){
			$mongoIds[$k] = new MongoId($v);
		}
		glue::db()->watched_history->remove(array('_id' => array('$in' => $mongoIds), 'user_id' => glue::user()->_id));
		$this->json_success('The history items you selected have been deleted');
	}
}