<?php
namespace app\models;

use glue;

class Help extends \glue\db\Document{

	function collectionName(){
		return "help";
	}

	public static function model($className = __CLASS__){
		return parent::model($className);
	}

	function getFlatTree(){
		$topics = self::model()->find(array("type"=>"topic"))->sort(array("path"=>1));
		$ret = array();

		foreach($topics as $_id => $item){
			$ret[$_id] = $v;
		}
		return $ret;
	}

	static function getRootItems(){
		return self::model()->find(array("path"=>new \MongoRegex("/^[^,]*$/")))->sort(array("seq"=>1));
	}

	function getBreadCrumb(){
		$breadcrumb = explode(",", $this->path);
		$final_breadcrumb = array();

		$c=0;
		foreach($breadcrumb as $i => $item){
			if($item != $this->normalisedTitle){
				$itemModel = self::model()->findOne(array('normalisedTitle' => $item));
				$final_breadcrumb[$i] = \html::openTag('li')
					.\html::a(array('href' => glue::http()->url('/help/view', array('title' => $item)), 'text' => $itemModel->title))
					.($c<(count($breadcrumb)-2)?\html::openTag('span',array('class'=>'divider')).'/'.\html::closeTag('span'):'')
					.\html::closeTag('li');
			} $c++;
		}
		return implode(' ',$final_breadcrumb);
		//implode(' '.utf8_decode('&rsaquo;').' ', $final_breadcrumb);
	}

	function getParentTopic_selectedVal(){
		$pieces = explode(",", $this->path);
		unset($pieces[count($pieces)-1]); // Delete the last one which should be the one we are on
		return implode(",", $pieces);
	}

	function getPermaLink(){
		return glue::http()->url('/help/view', array('title' => $this->normalisedTitle));
	}

	function getAbstract($amount = 100){
		return truncate_string(htmlspecialchars(strip_all($this->content)), $amount);
		//return substr_replace($truncated, "...", strlen($truncated)-3);
	}

	function findOne($criteria=array(),$fields=array()){
		$this->trace(__FUNCTION__);
		if((
				$record=$this->getCollection()->findOne($this->mergeCriteria(isset($c['condition']) ? $c['condition'] : array(), $criteria),
						$this->mergeCriteria(isset($c['project']) ? $c['project'] : array(), $fields))
		)!==null){
			$this->resetScope();
			
			$o = null;
			if($record['type'] == 'topic'){
				$o = new HelpTopic();
			}elseif($record['type'] == 'article'){
				$o = new HelpArticle();
			}
			return $o->populateRecord($record,true,$fields===array()?false:true);
		}else
			return null;		
	}
	
	public function search($keywords=''){
		$sphinx=glue::sphinx()
		->match(array('title', 'content', 'tags', 'path'),glue::http()->param('query',$keywords))
		->filter('deleted', array(1), true);
		
		$cursor=$sphinx->query('help');
		$cursor->setIteratorCallback(function($doc){
			if($doc['type']=='article')
				return HelpArticle::model()->findOne(array('_id'=>new \MongoId($doc['_id'])));
			elseif($doc['type']=='topic')
				return HelpTopic::model()->findOne(array('_id'=>new \MongoId($doc['_id'])));
		});
		return $cursor;		
	}
}