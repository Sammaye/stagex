<?php
Glue::import('glue/plugins/googleapi/Google.php');

class google_session extends GApplicationComponent{

	public $client_id;
	public $client_secret;

	public $callback_uri;

	public $Google;

	public function init(){
		$this->Google = new Google($this->client_id, $this->client_secret);
	}

	public function getLoginURI($scopes = array(), $response_type = 'code', $approval_prompt = 'auto', $access_type = 'offline'){
		return $this->Google->getLoginURL($this->callback_uri, $scopes, $response_type, $approval_prompt, $access_type);
	}

	public function authorize(){
		return $this->Google->authorize($this->callback_uri);
	}

	public function getCurrentUser(){
		return $this->Google->getCurrentUser($this->callback_uri);
	}
}