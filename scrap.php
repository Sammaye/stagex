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