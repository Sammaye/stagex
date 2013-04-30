<?php

class HelpArticle extends Help{

	public $tagString;

	protected $title;
	protected $content;
	protected $author;
	protected $tags;

	protected $createtime;
	protected $publishtime;

	protected $type = "article";

	protected $t_keyword;
	protected $t_normalised;

	protected $path;

	protected $seq = "z";

	public static function model($class = __CLASS__){
		return parent::model($class);
	}

	public function afterFind(){
		if(count($this->tags) > 0)
			$this->tagString = implode(",", $this->tags);

		$this->parent_topic = self::getParentTopic_selectedVal();
	}

	public function rules(){
		return array(
			array('tagString, title, content', 'required', 'message' => 'You must fill in at least a title, some content and some tags for this article.'),
			//array('tagString', 'tokenized', 'target' => 'tags', 'divider' => ','),
			array('parent_topic, seq', 'safe')
		);
	}

	function beforeSave(){

		if($this->getIsNewRecord())
			$this->createtime = new MongoDate();

		if(strlen(strip_whitespace($this->tagString)) > 0){
			$this->tags = preg_split("/[\s]*[,][\s]*/", $this->tagString);

			for($i=0;$i<count($this->tags);$i++){
				$this->tags[$i] = strip_whitespace($this->tags[$i]);
			}
		}else{
			unset($this->tags);
		}

		$this->t_normalised = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', trim(str_replace(' ', '-', strip_to_single(make_alpha_numeric($this->title))))));
		$this->t_keyword = str_replace(" ", "-", $this->t_normalised);

		$this->publishtime = new MongoDate();

		$oldPath = $this->path;
		if(strlen($this->parent_topic) <= 0 || !$this->parent_topic){
			$this->path = $this->t_normalised;
		}else{
			$this->path = $this->parent_topic.','.$this->t_normalised;
		}

		$this->author = glue::session()->user->_id;
		//var_dump($this);
		return true;
	}

	function afterSave(){
		//echo "validating";
		if($this->getIsNewRecord()){
			glue::mysql()->query("INSERT INTO help_documents (_id, title, content, tags, path, type) VALUES (:_id, :title, :content, :tags, :path, :type)", array(
				":_id" => strval($this->_id),
				":title" => $this->title,
				":content" => $this->content,
				":tags" => $this->tagString,
				":path" => $this->path,
				":type" => 'article'
			));

			glue::sitemap()->addUrl(glue::url()->create('/help/view', array('title' => $this->t_normalised)), 'hourly', '0.5');
		}else{
			glue::mysql()->query("UPDATE help_documents SET _id=:_id, title=:title, content=:content, tags=:tags, path=:path, type=:type WHERE _id=:_id", array(
				":_id" => strval($this->_id),
				":title" => $this->title,
				":content" => $this->content,
				":tags" => $this->tagString,
				":path" => $this->path,
				":type" => "topic",
			));
		}
		return true;
	}

	function delete(){
		glue::mysql()->query("UPDATE help_documents SET deleted=1 WHERE _id=:_id", array(
			":_id" => strval($this->_id),
		));
		parent::delete();
	}
}