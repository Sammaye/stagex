<?php
namespace app\models;

use glue;
use app\models\Help;

class HelpTopic extends Help
{
	public $userId;

	public $title;
	public $normalisedTitle;
	public $keywords;
	public $published;

	public $parent;
	public $path;
	public $type = "topic";

	public $seq = "z";

	public function behaviours()
	{
		return array(
			'timestampBehaviour' => array(
				'class' => 'glue\\behaviours\\Timestamp'
			)
		);
	}

	public function getDescendants()
	{
		/** $secondLevel = $this->Db()->find(array("path"=>new MongoRegex("/^".$path.",[^,]*,[^,]*$/")))->sort(array("seq"=>1)); // Second Level **/
		return static::find(array("path"=>new \MongoRegex("/^".$this->path.",[^,]*$/")))->sort(array("seq"=>1)); // First Level
	}

	public function getChildren()
	{
		return static::find(array("path"=>new \MongoRegex("/^".$this->path.",/")))->sort(array("path"=>1));
	}

	public function rules()
	{
		return array(
			array('title', 'required', 'message' => 'You must enter at least a title'),
			array('parent, seq', 'safe'),
			array('parent', 'exists',
				'class'=>'app\\models\\HelpTopic', 'field'=>'path', 'allowNull' => true, 'message' => 'That parent topic could not be found'
			),
		);
	}

	public function beforeSave()
	{
		$this->userId = glue::user()->_id;

		$this->normalisedTitle = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', trim(str_replace(' ', '-', strip_to_single(preg_replace("/[^a-zA-Z0-9\s]/", "", $this->title))))));
		$this->keywords = str_replace(" ", "-", $this->normalisedTitle);

		$this->published = new \MongoDate();

		$oldPath = $this->path;
		if(strlen($this->parent) <= 0 || !$this->parent){
			$this->path = $this->normalisedTitle;
		}else{
			$this->path = $this->parent.','.$this->normalisedTitle;
		}

		if($this->getScenario() == 'update' && $oldPath != $this->path){
			// I do not use active record to keep this process as fast as possible
			$helpItems = $this->getCollection()->find(array("path"=>new \MongoRegex("/^".$oldPath.",/"))); // Comma in regex denotes children
			foreach($helpItems as $_id => $item){
				$this->updateAll(
					array("_id"=>new \MongoId($_id)),
					array("\$set"=>array("path"=>preg_replace("/".$oldPath."/i", $this->path, $item['path'])))
				);
				
				glue::elasticSearch()->update(array(
    				'id' => $_id,
    				'type' => 'help',
    				'body' => array(
    				    'params' => array(
    				    	'path' => preg_replace("/".$oldPath."/i", $this->path, $item['path'])
    				    )
    				)
				));
			}
		}
		return true;
	}

	public function afterSave()
	{
	    glue::elasticSearch()->index(array(
    	    'id' => strval($this->_id),
    	    'type' => 'help',
    	    'body' => array(
        	    'title' => $this->title,
        	    'normalisedTitle' => $this->normalisedTitle,
        	    'path' => $this->path,
        	    'resourceType' => 'topic',
        	    'created' => date('c', $this->created->sec)
    	    )
	    ));
		return true;
	}

	/**
	 * I extensively use non-activeRecord structures within this method to keep the speed up.
	 *
	 * (non-PHPdoc)
	 * @see htdocs/glue/GModel::remove()
	 */
	public function delete($method = 'concat')
	{
		if($method == 'scrub'){
			$helpItems = $this->getCollection()->find(array(
				"path"=>new \MongoRegex("/^".$this->path.",/"))
			); // Comma in regex denotes children

			foreach($helpItems as $_id => $item){
				$this->deleteAll(array("_id"=>new \MongoId($_id)));
				glue::elasticSearch()->delete(array(
    				'id' => $_id,
    				'type' => 'help'
				));				
			}
		}elseif($method == 'concat'){
			// Concatenate
			$helpItems = $this->getCollection()->find(array("path"=>new \MongoRegex("/^".$this->path.",/"))); // Comma in regex denotes children
			$path_pieces = explode(",", $this->path); // the last part of the explosion should be the string we are looking for

			foreach($helpItems as $_id => $item){
				$infopath = str_replace($this->normalisedTitle.",", '', $item['path']); // Lets just remove this topic form its children
				$this->updateAll(array("_id"=>new \MongoId($_id)), array("\$set"=>array("path"=>$infopath)));
				glue::elasticSearch()->update(array(
    				'id' => strval($this->_id),
    				'type' => 'help',
    				'body' => array(
    				    'params' => array(
    				        'path' => $infopath
    				    )
    				)
				));
			}
		}

		parent::delete(); // Now use active record to remove this topic
		glue::elasticSearch()->delete(array(
    		'id' => strval($this->_id),
    		'type' => 'help'
		));		

		return true;
	}

	public static function getSelectBox_list($exclude = false)
	{
		$topics = static::find(array("type"=>"topic"))->sort(array("path"=>1));
		$ret = array();

		if($topics->count() <= 0 || !$topics){
			return array("" => 'None');
		}

		$ret['None'] = "";

		foreach($topics as $_id => $topic){
			if(!$exclude){
				$pieces  = explode(",", $topic->path);
				$prefix = "";
				for($i=0; $i<count($pieces)-1; $i++){
					$prefix .= "-";
				}
				$ret[$prefix." ".$topic->title] = $topic->path;
			}else{ // Do the laggy function
				$show = true;
				foreach($exclude as $path){
					if(preg_match("/".$path."/i", $topic->path)){
						$show = false;
					}
				}

				if($show){
					$pieces  = explode(",", $topic->path);
					$prefix = "";
					for($i=0; $i<count($pieces)-1; $i++){
						$prefix .= "-";
					}
					$ret[$prefix." ".$topic->title] = $topic->path;
				}
			}
		}
		//var_dump($ret);
		return array_flip($ret);
	}
}