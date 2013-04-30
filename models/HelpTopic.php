<?php
class HelpTopic extends Help{

	protected $title;
	protected $t_normalised;
	protected $t_keyword;
	protected $author;
	protected $createtime;
	protected $publishtime;

	protected $parent;
	protected $path;
	protected $type = "topic";

	protected $seq = "z";

	public static function model($class = __CLASS__){
		return parent::model($class);
	}

	function getDescendants(){
		/** $secondLevel = $this->Db()->find(array("path"=>new MongoRegex("/^".$path.",[^,]*,[^,]*$/")))->sort(array("seq"=>1)); // Second Level **/
		return self::model()->find(array("path"=>new MongoRegex("/^".$this->path.",[^,]*$/")))->sort(array("seq"=>1)); // First Level
	}

	function getChildren(){
		return self::model()->find(array("path"=>new MongoRegex("/^".$this->path.",/")))->sort(array("path"=>1));
	}

	function rules(){
		return array(
			array('title', 'required', 'message' => 'You must enter at least a title'),
			array('parent, seq', 'safe'),
			array('parent', 'objExist',
				'class'=>'HelpTopic', 'field'=>'path', 'allowNull' => true, 'message' => 'That parent topic could not be found'
			),
		);
	}

	function beforeSave(){

		if($this->getIsNewRecord()){
			$this->createtime = new MongoDate();
			$this->author = glue::session()->user->_id;
		}

		$this->t_normalised = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', trim(str_replace(' ', '-', strip_to_single(make_alpha_numeric($this->title))))));
		$this->t_keyword = str_replace(" ", "-", $this->t_normalised);

		$this->publishtime = new MongoDate();

		$oldPath = $this->path;
		if(strlen($this->parent) <= 0 || !$this->parent){
			$this->path = $this->t_normalised;
		}else{
			$this->path = $this->parent.','.$this->t_normalised;
		}

		if($this->getScenario() == 'update' && $oldPath != $this->path){
			// I do not use active record to keep this process as fast as possible
			$helpItems = $this->Db()->find(array("path"=>new MongoRegex("/^".$oldPath.",/"))); // Comma in regex denotes children
			foreach($helpItems as $_id => $item){
				$this->Db()->update(array(
					"_id"=>new MongoId($_id)),
					array("\$set"=>array("path"=>preg_replace("/".$oldPath."/i", $this->path, $item['path']))));
			}
		}
		return true;
	}

	function afterSave(){
		if($this->getIsNewRecord()){
			glue::mysql()->query("INSERT INTO help_documents (_id, title, content, tags, path, type)
								VALUES (:_id, :title, null, null, :path, :type)", array(
				":_id" => strval($this->_id),
				":title" => $this->title,
				":path" => $this->path,
				":type" => 'topic',
			));

			glue::sitemap()->addUrl(glue::url()->create('/help/view', array('title' => $this->t_normalised)), 'hourly', '0.5');
		}else{
			glue::mysql()->query("UPDATE help_documents SET _id=:_id, title=:title, path=:path, type=:type WHERE _id=:_id", array(
				":_id" => strval($this->_id),
				":title" => $this->title,
				":path" => $this->path,
				":type" => "topic",
			));
		}
		return true;
	}

	/**
	 * I extensively use non-activeRecord structures within this method to keep the speed up.
	 *
	 * (non-PHPdoc)
	 * @see htdocs/glue/GModel::remove()
	 */
	function delete($method = 'concat'){

		if($method == 'scrub'){
			$helpItems = $this->Db()->find(array(
				"path"=>new MongoRegex("/^".$this->path.",/"))
			); // Comma in regex denotes children

			foreach($helpItems as $_id => $item){
				$this->Db()->remove(array("_id"=>new MongoId($_id)));
				glue::mysql()->query("UPDATE help_documents SET deleted=1 WHERE _id=:_id", array(
					":_id" => $_id,
				));
			}
		}elseif($method == 'concat'){
			// Concatenate
			$helpItems = $this->Db()->find(array("path"=>new MongoRegex("/^".$this->path.",/"))); // Comma in regex denotes children
			$path_pieces = explode(",", $this->path); // the last part of the explosion should be the string we are looking for

			foreach($helpItems as $_id => $item){
				$infopath = str_replace($this->t_normalised.",", '', $item['path']); // Lets just remove this topic form its children
				$this->Db()->update(array("_id"=>new MongoId($_id)), array("\$set"=>array("path"=>$infopath)));
				glue::mysql()->query("UPDATE help_documents SET path=:path, deleted=1 WHERE _id=:_id", array(
					":_id" => $_id,
					":path" => $infopath
				));
			}
		}

		parent::delete(); // Now use active record to remove this topic
		glue::mysql()->query("UPDATE help_documents SET deleted=1 WHERE _id=:_id", array(
			":_id" => strval($this->_id),
		));

		return true;
	}

	static function getSelectBox_list($exclude = false){

		$topics = self::model()->find(array("type"=>"topic"))->sort(array("path"=>1));
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