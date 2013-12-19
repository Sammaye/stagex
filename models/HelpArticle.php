<?php
namespace app\models;

use glue;

class HelpArticle extends \app\models\Help{

	/** @virtual */
	public $tagString;

	public $userId;

	public $title;
	public $content;
	public $tags;

	public $published;

	public $type = "article";

	public $keywords;
	public $normalisedTitle;

	public $path;

	public $parent;
	public $seq = "z";

	function behaviours(){
		return array(
			'timestampBehaviour' => array(
				'class' => 'glue\\behaviours\\Timestamp'
			)
		);
	}

	public static function model($class = __CLASS__){
		return parent::model($class);
	}

	public function afterFind(){
		if(count($this->tags) > 0)
			$this->tagString = implode(",", $this->tags);

		$this->parent = self::getParentTopic_selectedVal();
	}

	public function rules(){
		return array(
			array('tagString, title, content', 'required', 'message' => 'You must fill in at least a title, some content and some tags for this article.'),
			//array('tagString', 'tokenized', 'target' => 'tags', 'divider' => ','),
			array('parent, seq', 'safe')
		);
	}

	function beforeSave(){

		if(strlen(strip_whitespace($this->tagString)) > 0){
			$this->tags = preg_split("/[\s]*[,][\s]*/", $this->tagString);

			for($i=0;$i<count($this->tags);$i++){
				$this->tags[$i] = strip_whitespace($this->tags[$i]);
			}
		}else{
			unset($this->tags);
		}

		$this->normalisedTitle = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', trim(str_replace(' ', '-', strip_to_single(preg_replace("/[^a-zA-Z0-9\s]/", "", $this->title))))));
		$this->keywords = str_replace(" ", "-", $this->normalisedTitle);

		$this->published = new \MongoDate();

		$oldPath = $this->path;
		if(strlen($this->parent) <= 0 || !$this->parent){
			$this->path = $this->normalisedTitle;
		}else{
			$this->path = $this->parent.','.$this->normalisedTitle;
		}

		$this->userId = glue::user()->_id;
		//var_dump($this);
		return true;
	}

	function afterSave(){
	    glue::elasticSearch()->index(array(
	        'id' => strval($this->_id),
	        'type' => 'help',
	        'body' => array(
	            'title' => $this->title,
	            'normalisedTitle' => $this->normalisedTitle,
	            'blurb' => $this->content,
	            'tags' => $this->tags,
	            'path' => $this->path,
	            'resourceType' => 'article',
	            'created' => date('c', $this->created->sec)
	        )
	    ));
		return true;
	}

	function delete(){
	    glue::elasticSearch()->delete(array(
	        'id' => strval($this->_id),
	        'type' => 'help'
	    ));
		parent::delete();
	}
}