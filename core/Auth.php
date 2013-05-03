<?php
/**
 * RBAM Module Rules definitions
 *
 * This file contains a sort-of configuration array for the RBAM Module within the Glue framework.
 * It contains all of the shortcuts and the validation functions needed in order to understand how to process authorisation requests.
 *
 * This data can also be converted to be stored in a DB if you want it to (Will require programming of your own).
 */


//Auth::filter('user', function(){
//
//});
//
//Auth::filter('canViewPost', function($post, $user, $something_else){
//
//});

return array(
	'shortcuts' => array(
		'@' 	=> 'roleLogged',
		'@*' 	=> "loginRequired",
		'^@' 	=> "roleAdmin",
		'*' 	=> "roleUser",
		'^' 	=> "Owns"
	),

	'roles' => array(
		'roleUser' => function(){
			return true;
		},

		'roleLogged' => function(){
			if($_SESSION['logged']){
				return true;
			}
			return false;
		},
		'canView' => function($item){
			if(!$item){
				return false;
			}

			if($item->deleted){
				return false;
			}

			if($item->author instanceof User){
				if((bool)$item->author->deleted){
					return false;
				}
			}

			if($item->listing){
				if($item->listing == 3 && (strval(glue::session()->user->_id) != strval($item->author->_id)))
					return false;
			}
			return true;
		},
		'deletedView' => function($item){
			if(!$item){
				return false;
			}

			if($item->deleted){
				return false;
			}

			if($item->author instanceof User){
				if((bool)$item->author->deleted){
					return false;
				}
			}elseif(!$item instanceof User){
				return false;
			}
			return true;
		},
		'deniedView' => function($item){
			if($item->listing){
				if($item->listing == 3 && strval(glue::session()->user->_id) != strval($item->author->_id))
					return false;
			}
			return true;
		},
		'loginRequired' => function(){
			if($_SESSION['logged']){
				return true;
			}

			if(glue::http()->isAjax()){
				GJSON::kill(GJSON::LOGIN);
				exit();
			}else{
				html::setErrorFlashMessage('You must be logged in to access this page');
				header('Location: /user/login?nxt='.Glue::url()->create('SELF', array(), ''));
				exit();
			}
			return false;
		},

		'roleAdmin' => function(){
		  	if(Glue::session()->user->group == 10 || Glue::session()->user->group == 9){
		  		return true;
		  	}
		  	return false;
		},

		'Owns' => function($object){
			if(is_array($object)){
				foreach($object as $item){
					if(strval(Glue::session()->user->_id) == strval($item->user_id)){
						return true;
					}
				}
			}elseif($object instanceof MongoDocument){
				if(strval(Glue::session()->user->_id) == strval($object->user_id)){
					return true;
				}
			}
			return false;
		},

		'ajax' => function(){
			if(glue::http()->isAjax()){
				return true;
			}
			return false;
		}
	)
);