<?php
namespace glue\components\phpmailer;

use glue;

require_once str_replace('/', DIRECTORY_SEPARATOR, dirname(__FILE__).'/class.phpmailer.php');

class mailer extends \glue\Component{

	private $_mailer;

	public $viewPath = 'mail';

	function init(){ $this->_mailer = new \PHPMailer(); }

	function __get($k){
		if($this->_mailer instanceof \PHPMailer) return $this->_mailer->$k;
	}

	function __set($k, $v){
		if($this->_mailer instanceof \PHPMailer) $this->_mailer->$k = $v;
	}

	function __call($method, $params){
		if($this->_mailer instanceof \PHPMailer) return call_user_func_array(array($this->_mailer, $method), $params);
	}

	function mail($to, $from, $subject, $view, $vars = array()){

		if(strlen($to) <= 0 || !$to)
			return; // Just return

		if(is_array($from))
			$this->_mailer->SetFrom($from[0], $from[1]);
		else
			$this->_mailer->SetFrom($from);

		$this->_mailer->AddAddress($to);
		$this->_mailer->Subject = $subject;

		foreach ($vars as $key => $value){
        	$$key = $value;
        }
        $filename = glue::getPath('@app').'/'.$this->viewPath.'/'.trim($view, '/');
		if(file_exists($filename)){
			ob_start();
				include $filename;
				$pagecontent=ob_get_contents();
		    ob_end_clean();
		}else{
			$pagecontent=$view;
		}

		$this->_mailer->MsgHTML($pagecontent);
		if($this->_mailer->Send()){  }
	}	
}