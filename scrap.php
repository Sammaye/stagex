<?php
$search = array('type' => 'playlist', 'body' =>
		array('query' => array('filtered' => array(
				'query' => array(),
				'filter' => array(
						'and' => array(
								array('term' => array(
										'userId' => strval($user->_id),
								)),
								array('term' => array('deleted' => 0))
						)
				)
		)), 'sort' => array(
				array('created' => 'desc')
		))
);

if(!glue::user()->equals($user)){
	$search['body']['query']['filtered']['filter']['and'][]=array('range' => array('listing' => array('lt' => 1)));
}

if(glue::http()->param('query')){
	$search['body']['query']['filtered']['query'] = array('bool' => array(
			'should' => array(
					array('multi_match' => array(
							'query' => glue::http()->param('query',null),
							'fields' => array('title', 'blurb', 'username')
					)),
			)
	));

	$keywords = preg_split('/\s+/', trim(glue::http()->param('query')));
	foreach($keywords as $keyword){
		$search['body']['query']['filtered']['query']['bool']['should'][]=array('prefix' => array('title' => $keyword));
		$search['body']['query']['filtered']['query']['bool']['should'][]=array('prefix' => array('blurb' => $keyword));
		$search['body']['query']['filtered']['query']['bool']['should'][]=array('prefix' => array('username' => $keyword));
	}
}




$sphinx=glue::sphinx()
->match(array('title', 'description', 'tags', 'author_name'),$query)
->filter('listing',array(1, 2), true)
->filter('videos', array('0', '1', '2', '3', '4'), true) // Omits small playlists from the main search
->filter('deleted', array(1), true)
->page(glue::http()->param('page',1))
->setIteratorCallback(function($doc){
	if($doc['type']==='video')
		return app\models\Video::model()->findOne(array('_id'=>new MongoId($doc['_id'])));
	if($doc['type']==='playlist')
		return app\models\Playlist::model()->findOne(array('_id'=>new MongoId($doc['_id'])));
	if($doc['type']==='user')
		return app\models\User::model()->findOne(array('_id'=>new MongoId($doc['_id'])));
});