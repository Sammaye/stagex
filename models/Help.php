<?php
namespace app\models;

use glue;

class Help extends \glue\db\Document{

	public static function collectionName(){
		return "help";
	}

	public static function model($className = __CLASS__){
		return parent::model($className);
	}

	function getFlatTree(){
		$topics = self::find(array("type"=>"topic"))->sort(array("path"=>1));
		$ret = array();

		foreach($topics as $_id => $item){
			$ret[$_id] = $v;
		}
		return $ret;
	}

	static function getRootItems(){
		return self::find(array("path"=>new \MongoRegex("/^[^,]*$/")))->sort(array("seq"=>1));
	}

	function getBreadCrumb(){
		$breadcrumb = explode(",", $this->path);
		$final_breadcrumb = array();

		$c=0;
		foreach($breadcrumb as $i => $item){
			if($item != $this->normalisedTitle){
				$itemModel = self::findOne(array('normalisedTitle' => $item));
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

	public static function findOne($query = array(), $fields = array())
	{
		if(($doc = parent::findOne($query, $fields)) !== null){
			$o = null;
			if($record['type'] == 'topic'){
				$o = new HelpTopic();
			}elseif($record['type'] == 'article'){
				$o = new HelpArticle();
			}
			return $o->populate($record, true, $fields===array() ? false : true);
		}else{
			return null;
		}		
	}
	
	public function search($keywords=''){
	    
	    $search = array('type' => 'help', 'body' =>
	        array('query' => array('filtered' => array(
	            'query' => array()
	        )))
	    );
	    
	    if(glue::http()->param('query')){
    	    $search['body']['query']['filtered']['query'] = array('bool' => array(
    	            'should' => array(
    	                    array('multi_match' => array(
    	                            'query' => glue::http()->param('query',null),
    	                            'fields' => array('title', 'blurb', 'tags', 'normalisedTitle', 'path')
    	                    )),
    	            )
    	    ));

	        $keywords = preg_split('/\s+/', glue::http()->param('query'));
	        foreach($keywords as $keyword){
	            $search['body']['query']['filtered']['query']['bool']['should'][]=array('prefix' => array('title' => $keyword));
	            $search['body']['query']['filtered']['query']['bool']['should'][]=array('prefix' => array('tags' => $keyword));
	            $search['body']['query']['filtered']['query']['bool']['should'][]=array('prefix' => array('normalisedTitle' => $keyword));
	        }
	    }
	    
	    $cursor = glue::elasticSearch()->search($search);
		$cursor->setIteratorCallback(function($doc){
			if($doc['_source']['resourceType']=='article')
				return HelpArticle::findOne(array('_id'=>new \MongoId($doc['_id'])));
			elseif($doc['_source']['resourceType']=='topic')
				return HelpTopic::findOne(array('_id'=>new \MongoId($doc['_id'])));
		});
		return $cursor;		
	}
}