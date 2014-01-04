<?php

use \glue\Controller;

class SearchController extends Controller
{
	public function behaviours()
	{
		return array(
			'auth' => array(
				'class' => 'glue\Auth',
				'rules' => array(
					array('allow', 'users' => array('*'))
				)
			)
		);
	}	

	function action_index()
	{
		extract(glue::http()->param(array('query', 'filter_type', 'filter_time', 'filter_duration', 'filter_category', 'orderby')));
		if($filter_type === 'playlist' || $filter_type === 'user' || $filter_type === 'all' /* If all explicitly */){
			// Omit the normal video searching variables
			$orderby = null;
			$filter_duration = null;
			$filter_category = null;
		}
		
		// Now being to render to page after sorting out the GET vars
		if(strlen(trim($query))<=0)
			$this->title = 'Search - StageX';
		else
			$this->title = 'Search results for '.$query.' - StageX';
		
		$c = new \glue\components\Elasticsearch\Query();
		$c->filtered = true;
		if(glue::http()->param('query')){
			$c->query()->multiPrefix(array('burb', 'title', 'tags', 'username'), glue::http()->param('query'));
		}
		$c->filter()->and('term', array('deleted' => 0))
			->and('range', array('listing' => array('lt' => 1)))
			->and('range', array('videos' => array('gt' => 4)));
		
		$c->page(glue::http()->param('page', 1));
	
		if(glue::user()->safeSearch || !glue::auth()->check(array('authed'))){
			$c->filter()->and('term', array('mature' => 0));
		}
		
		$categories=app\models\Video::categories('selectBox');
		if(array_key_exists($filter_category, $categories)){
			$c->filter()->and('term', array('category' => $filter_category));
		}else
			$filter_category=null;

		switch($filter_time){
			case "today":
				$c->filter()->and('range', array('created' => array('gte' => date('c', time()-24*60*60))));
				break;
			case "week":
				$c->filter()->and('range', array('created' => array('gte' => date('c', strtotime('7 days ago')))));
				break;
			case "month":
				$c->filter()->and('range', array('created' => array('gte' => date('c', mktime(0, 0, 0, date('n'), 1, date('Y'))))));
				break;
		}
		
		switch($filter_duration){
			case "short":
				$c->filter()->and('range', array('duration' => array('gte' => 1, 'lte' => 240000)));
				$filter_type = "video";
				break;
			case "long":
				$c->filter()->and('range', array('duration' => array('gte' => 241000, 'lte' => 23456789911122000000)));
				$filter_type = "video";
				break;
		}		
		
		switch($orderby){
			case "upload_date":
				$c->sort('created', 'desc');
				$filter_type = "video";
				break;
			case "views":
				$c->sort('views', 'desc');
				$filter_type = "video";
				break;
			case "rating":
				$c->sort('rating', 'desc');
				$filter_type = "video";
				break;
		}

		if($filter_type==='video'||$filter_type==='user'||$filter_type==='playlist')
			$c->type = $filter_type;
		else
			$c->type = 'video,user,playlist';
		
		$cursor = glue::elasticSearch()->search($c);
		$cursor->setIteratorCallback(function($doc){
			if($doc['_type']==='video')
				return app\models\Video::findOne(array('_id'=>new MongoId($doc['_id'])));
			if($doc['_type']==='playlist')
				return app\models\Playlist::findOne(array('_id'=>new MongoId($doc['_id'])));
			if($doc['_type']==='user')
				return app\models\User::findOne(array('_id'=>new MongoId($doc['_id'])));
		});		
		
		echo $this->render('search/search', array('sphinx' => $cursor, 'query' => $query, 'filter_type' => $filter_type, 'filter_time' => $filter_time, 
				'filter_duration' => $filter_duration, 'filter_category' => $filter_category, 'orderby' => $orderby));
	}

	function action_suggestions()
	{
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