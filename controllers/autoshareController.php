<?php

class autoshareController extends GController{

	public $user;
	public $socialUser;
	public $account;

	public function accessRules(){
		return array(
			array('allow',
				'users' => array('@*')
			),
			array("deny",
				"users"=>array("*")
			),
		);
	}

	function action_index(){}


	function getConnectedUser(){
		$this->user = $this->loadModel();

		switch($_GET['network']){
			case "fb":
				$this->account = glue::facebook();
				glue::facebook()->facebook->setAccessToken(!empty($this->user->fb_autoshare_token) ? $this->user->fb_autoshare_token : null);
				$this->socialUser =glue::facebook()->getCurrentUser();
				break;
			case "twt":
				$this->account = glue::twitter();
				$this->account->connect(array("access_token"=>$this->user->twt_autoshare_token));
				$this->socialUser =$this->account->getCurrentUser();
				break;
		}
	}

	function action_status(){
		$this->pageTitle = 'Autoshare - StageX';
		$this->getConnectedUser();

		if(!$this->socialUser){

			switch($_GET['network']){
				case "fb":
					echo json_encode(array(
						"logged"=>false,
						"response"=>" - <a class='authSocialAccount' id='fb_auth' href='/autoshare/connect?network=fb'>Connect Account</a>")
					);
					break;
				case "twt":
					echo json_encode(array(
						"logged"=>false,
						"response"=>" - <a class='authSocialAccount' id='twt_auth' href='/autoshare/connect?network=twt'>Connect Account</a>")
					);
					break;
			}
		}else{
			switch($_GET['network']){
				case "fb":
					echo json_encode(array(
						"logged"=>true,
						"response"=>" - <a href='{$this->socialUser['link']}'>{$this->socialUser['name']}</a> |
							<a class='openNewWindow' href='/autoshare/disconnect?network=fb'>Disconnect</a>")
					);
					break;
				case "twt":
					echo json_encode(array(
						"logged"=>true,
						"response"=>" - <a href='http://twitter.com/#!/{$this->socialUser->screen_name}'>{$this->socialUser->screen_name}</a> |
							<a class='openNewWindow' href='/autoshare/disconnect?network=twt'>Disconnect</a>")
					);
					break;
			}
		}
	}

	function action_connect(){
		$this->pageTitle = 'Autoshare - StageX';
		$this->getConnectedUser();

		$this->account->preAuth(); // run the pre_auth stuff to get our cookies and what not.

		switch($_GET['network']){
			case "fb":
				header("Location: ".$this->account->getLoginUrl(array(//"cancel_url"=>"http://stagex.co.uk/autoshare/auth?network=fb",
					"redirect_uri"=>"http://www.stagex.co.uk/autoshare/auth?network=fb",
					"scope"=>"read_stream,publish_stream,offline_access,email,user_birthday"
				))
				);
				exit();
				break;
			case "twt":
				header("Location: ".$this->account->connection()->getAuthorizeURL(isset($token) ? $token : null));
				exit();
				break;
		}
	}

	function action_auth(){

		$this->getConnectedUser(); ?>
		<html>
			<head><title><?php echo "Authorise Connected Account" ?></title></head>
			<body>
				<?php

				$token = $this->account->authorize();

				switch($_GET['network']){
					case "twt":
						$this->user->twt_autoshare_token = $token;
						break;
					case 'fb':
						$this->user->fb_autoshare_token = glue::facebook()->facebook->getAccessToken();
						break;
				}

				$this->user->save();
				$this->getConnectedUser();

				if($this->socialUser){ ?>
					<script type="text/javascript">
						window.opener.location.reload();
						window.close();
					</script>
				<?php }else{ ?>There was an unknown error when authorising your account<?php } ?>
			</body>
		</html>
	<?php }

	function action_disconnect(){
		$this->getConnectedUser(); ?>
		<html>
			<head><title><?php echo "Remove Connected Account" ?></title></head>
			<body>
				<?php switch($_GET['network']){
					case "fb":
						glue::facebook()->facebook->destroySession();
						$this->user->fb_autoshare_token = null;
						break;
					case "twt":
						$this->user->twt_autoshare_token = null;
						break;
				}

				$this->user->save();

				?>
				<script type="text/javascript">
					window.opener.location.reload();
					window.close();
				</script>
			</body>
		</html>
	<?php }

	function loadModel(){
		$user = User::model()->findOne(array("_id"=>glue::session()->user->_id));
		return $user;
	}
}