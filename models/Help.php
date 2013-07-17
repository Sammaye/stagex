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

		foreach($breadcrumb as $i => $item){
			if($item != $this->t_normalised){
				$itemModel = self::model()->findOne(array('normalisedTitle' => $item));
				$final_breadcrumb[$i] = html::a(array('href' => glue::http()->createUrl('/help/view', array('title' => $item)), 'text' => $itemModel->title));
			}
		}
		return implode(' '.utf8_decode('&rsaquo;').' ', $final_breadcrumb);
	}

	function getParentTopic_selectedVal(){
		$pieces = explode(",", $this->path);
		unset($pieces[count($pieces)-1]); // Delete the last one which should be the one we are on
		return implode(",", $pieces);
	}

	function getPermaLink(){
		return glue::http()->createUrl('/help/view', array('title' => $this->t_normalised));
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
}