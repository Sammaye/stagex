<?php
class searchController extends glue\Controller{

	function action_index(){
		extract(glue::http()->param(array('query', 'filter_type', 'filter_time', 'filter_duration', 'filter_category', 'orderby')));
		if($filter_type === 'playlist' || $filter_type === 'user' || $filter_type === 'all' /* If all explicitly */){
			// Omit the normal video searching variables
			$orderby = null;
			$filter_duration = null;
		}
		
		// Now being to render to page after sorting out the GET vars
		if(strlen(trim($query))<=0)
			$this->title = 'Search - StageX';
		else
			$this->title = 'Search results for '.$query.' - StageX';
		
		$sphinx=glue::sphinx()
		->match(array('title', 'description', 'tags', 'author_name'),$query)
		->filter('listing',array(1, 2), true)
		->filter('videos', array('0', '1', '2', '3', '4'), true) // Omits small playlists from the main search
		->filter('deleted', array(1), true)
		->page(glue::http()->param('page',1))
		->setIteratorCallback(function($doc){
			//var_dump($doc);
			if($doc['type']==='video')
				return app\models\Video::model()->findOne(array('_id'=>new MongoId($doc['_id'])));
			if($doc['type']==='playlist')
				return app\models\Playlist::model()->findOne(array('_id'=>new MongoId($doc['_id'])));
			if($doc['type']==='user')
				return app\models\User::model()->findOne(array('_id'=>new MongoId($doc['_id'])));
		});
		
		if(glue::user()->safeSearch || !glue::auth()->check(array('authed'))){
			$sphinx->filter('adult', array(1), true);
		}
		
		$categories=app\models\Video::model()->categories('selectBox');
		if(array_key_exists($filter_category, $categories)){
			$sphinx->filter('category', array($filter_category));
		}else
			$filter_category=null;

		switch($filter_time){
			case "today":
				$sphinx->filterRange('date_uploaded', time()-24*60*60, time());
				//mktime(0, 0, 0, date('n'), date('j'), date('Y'))
				break;
			case "week":
				//var_dump(strtotime('7 days ago'));
				$sphinx->filterRange('date_uploaded', strtotime('7 days ago'), time());
				break;
			case "month":
				$sphinx->filterRange('date_uploaded', mktime(0, 0, 0, date('n'), 1, date('Y')), time());
				break;
		}
		
		switch($filter_duration){
			case "short":
				$sphinx->filterRange('duration', 1, 240000);
				$filter_type = "video";
				break;
			case "long":
				$sphinx->filterRange('duration', 241000, 23456789911122000000);
				$filter_type = "video";
				break;
		}		
		
		switch($orderby){
			case "upload_date":
				$sphinx->sort(SPH_SORT_ATTR_DESC, "date_uploaded");
				$filter_type = "video";
				break;
			case "views":
				$sphinx->sort(SPH_SORT_ATTR_DESC, "views");
				$filter_type = "video";
				break;
			case "rating":
				$sphinx->sort(SPH_SORT_ATTR_DESC, "rating");
				$filter_type = "video";
				break;
		}

		if($filter_type==='video'||$filter_type==='user'||$filter_type==='playlist')
			$sphinx->match('type', $filter_type);
		
		echo $this->render('search/search', array('sphinx' => $sphinx->query('main'), 'query' => $query, 'filter_type' => $filter_type, 'filter_time' => $filter_time, 
				'filter_duration' => $filter_duration, 'filter_category' => $filter_category, 'orderby' => $orderby));
	}

	function action_suggestions(){
		$this->pageTitle = 'Suggest Searches - StageX';
		if(!glue::http()->isAjax()){
			glue::route('error/notfound');
		}

		$ret = array();

		$sphinx = glue::sphinx()->getSearcher();
		$sphinx->limit = 5;
		$sphinx->query(array('select' => glue::http()->param('term')), "main");

		if($sphinx->matches){
			foreach($sphinx->matches as $item){
				if($item instanceof Video){
					$ret[] = array(
						'label' => $item->title,
						'description' => $item->description,
					);
				}elseif($item instanceof User){
					$ret[] = array(
						'label' => $item->username,
						'description' => null,
					);
				}
			}
		}

		echo json_encode($ret);
	}
}