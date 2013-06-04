<?php
class searchController extends GController{

	function action_index(){
		$this->pageTitle = 'Search StageX';

		$filter = isset($_GET['filter']) ? $_GET['filter'] : null;
		$sort = isset($_GET['sort']) ? $_GET['sort'] : null;
		$time_show = isset($_GET['time']) ? $_GET['time'] : null;
		$duration = isset($_GET['duration']) ? $_GET['duration'] : null;

		if($filter != 'videos'){
			$sort = null;
			$duration = null;
		}

		$sphinx = glue::sphinx()->getSearcher();
		$sphinx->page = isset($_GET['page']) ? $_GET['page'] : null;

		$sphinx->setFilter('listing', array('2', '3'), true);
		$sphinx->setFilter('videos', array('0', '1', '2', '3', '4'), true); // Omits small playlists from the main search

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

		switch($filter){
			case "all":
				$sphinx->query(array('select' => glue::http()->param('mainSearch')), 'main');
				break;
			case "videos":

				$this->pageTitle = 'Search Videos - StageX';

				switch($sort){
					case "upload_date":
						$filter = "videos";
						$sphinx->setSortMode(SPH_SORT_ATTR_DESC, "date_uploaded");
						break;
					case "views":
						$sphinx->setSortMode(SPH_SORT_ATTR_DESC, "views");
						$filter = "videos";
						break;
					case "rating":
						$sphinx->setSortMode(SPH_SORT_ATTR_DESC, "rating");
						$filter = "videos";
						break;
				}

				switch($duration){
					case "ltthree":
						$sphinx->setFilterRange('duration', 1, 240000);
						break;
					case "gtthree":
						$sphinx->setFilterRange('duration', 241000, 23456789911122000000);
						break;
				}

				$sphinx->query(array('select' => glue::http()->param('mainSearch'), 'where' => array('type' => array('video'))), 'main');
				break;
			case "playlists":
				$this->pageTitle = 'Search Playlists - StageX';
				$sphinx->query(array('select' => glue::http()->param('mainSearch'), 'where' => array('type' => array('playlist'))), 'main');
				break;
			case "users":
				$this->pageTitle = 'Search Users - StageX';
				$sphinx->query(array('select' => glue::http()->param('mainSearch'), 'where' => array('type' => array('user'))), 'main');
				break;
			default:
				$sphinx->query(array('select' => glue::http()->param('mainSearch')), 'main');
				break;
		}

		$this->render('search/search', array('sphinx' => $sphinx, 'filter' => $filter, 'sort' => $sort, 'time_show' => $time_show, 'duration' => $duration));
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