<?php

use \glue\Controller;
use app\models\AutoPublishQueue;
use app\models\User;
use app\models\Video;
use app\models\Playlist;

class UserController extends Controller
{
	public function action_resetUploadBandwith()
	{
		$user=app\models\User::find(array('nextBandwidthTopup' => array('$lt' => time())));
		foreach($users as $k => $v)
			$user->resetUploadBandwidth();		
	}
}