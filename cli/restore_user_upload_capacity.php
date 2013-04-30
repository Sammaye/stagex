<?php

$users = glue::db()->users->find(array('next_bandwidth_up' => array('$lt' => time())));

foreach($users as $k => $v){
	$v['next_bandwidth_up'] = strtotime('+1 week', mktime(0, 0, 0, date('m'), date('d'), date('Y')));
	$v['upload_left'] = $v['max_upload'] > 0 ? $v['max_upload'] : glue::$params['maxUpload'];
	glue::db()->users->save($v);
}
