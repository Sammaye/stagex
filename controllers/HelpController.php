<?php

use glue\Controller;
use glue\Json;
use app\models\Help;
use app\models\HelpTopic;
use app\models\HelpArticle;

class HelpController extends Controller
{
	public $selectedTab = '';

	public function behaviours()
	{
		return array(
			'auth' => array(
				'class' => 'glue\Auth',
				'rules' => array(
//					array("allow",
//						"actions"=>array('view_topics', 'add_topic', 'edit_topic', 'remove_topics', 'view_articles', 'add_article', 'edit_article', 'remove_articles'),
//						"users"=>array("@*", '^@')
//					),						
					array('allow', 'users' => array('*')),
				)
			)
		);		
	}

	public function action_index()
	{
		$this->title = 'StageX Help Center';
		echo $this->render("help/index");
	}

	public function action_view()
	{
		$this->title = 'Help Content Not Found - StageX';
		$this->layout = "help_layout";

		$item = Help::findOne(array('normalisedTitle' => glue::http()->param('title')));

		if(!$item){
			echo $this->render('help/notfound');
		}else{
			$this->title = $item->title.' - StageX';
			if(count(explode(',', $item->path)) > 1){
				$this->selectedTab = strstr($item->path, ',', true);
			}else{
				$this->selectedTab = $item->path;
			}

			if($item->type == 'topic'){
				echo $this->render('help/view_topic', array('model' => $item));
			}elseif($item->type == 'article'){
				echo $this->render('help/view_article', array('model' => $item));
			}
		}
	}

	public function action_search()
	{
		$this->title = 'Search StageX Help';
		echo $this->render("help/search", array( "sphinx" => Help::search(glue::http()->param('query')) ));
	}

	public function action_viewTopics()
	{
		$this->title = 'View Help Topics - StageX';
		echo $this->render('help/list_topics', array(
			'items' => app\models\HelpTopic::fts(
				array('title', 'path'), 
				glue::http()->param('query', ''), 
				array('type' => 'topic')
			)
		));
	}

	public function action_addTopic()
	{
		$this->title = 'Add Help Topic - StageX';
		$model = new HelpTopic();

		if(isset($_POST['HelpTopic'])){
			$model->attributes = $_POST['HelpTopic'];
			if($model->save()){
				glue::http()->redirect('/help/viewTopics');
			}
		}
		echo $this->render('help/manage_topic', array('model' => $model));
	}

	public function action_editTopic()
	{
		$this->title = 'Edit Help Topic - StageX';
		$model = HelpTopic::findOne(array('_id' => new MongoId(glue::http()->param('id'))));

		if(isset($_POST['HelpTopic'])){
			$model->attributes = $_POST['HelpTopic'];
			if($model->save()){
				glue::http()->redirect('/help/viewTopics');
			}
		}
		echo $this->render('help/manage_topic', array('model' => $model));
	}

	public function action_deleteTopic()
	{
		if(!glue::http()->isAjax()){
			glue::trigger('404');
		}
		
		$this->title = 'Remove Help Topics - StageX';

		$method = glue::http()->param('method', null);
		$model = HelpTopic::findOne(array('_id' => new MongoId(glue::http()->param('id'))));

		if(!$model){
			Json::error('That topic could no longer be found');
		}

		if($method != 'concat' && $method != 'scrub'){
			Json::error('You supplied an invalid mode. Please specify one.');
		}

		if($model->delete($method)){
			Json::success('The topic you selected was removed');
		}else{
			Json::error(Json::UNKNOWN);
		}
	}

	public function action_viewArticles()
	{
		$this->title = 'View Help Articles - StageX';
		echo $this->render('help/list_articles', array(
			'items' => HelpArticle::fts(
				array('title', 'content', 'path'), 
				glue::http()->param('query', ''), 
				array('type' => 'article')
			)
		));
	}

	public function action_addArticle()
	{
		$this->title = 'Add New Help Article - StageX';

		$model = new HelpArticle;
		if(isset($_POST['HelpArticle'])){
			$model->attributes = $_POST['HelpArticle'];
			if($model->save()){
				glue::http()->redirect('/help/viewArticles');
			}
		}
		echo $this->render('help/manage_article', array('model' => $model));
	}

	public function action_editArticle()
	{
		$this->title = 'Edit Help Article - StageX';

		$model = HelpArticle::findOne(array('_id' => new MongoId(glue::http()->param('id'))));

		if(isset($_POST['HelpArticle'])){
			$model->attributes = $_POST['HelpArticle'];
			if($model->save()){
				glue::http()->redirect('/help/viewArticles');
			}
		}
		echo $this->render('help/manage_article', array('model' => $model));
	}

	public function action_deleteArticle()
	{
		$this->title = 'Remove Help Article - StageX';

		if(!glue::http()->isAjax()){
			glue::trigger('404');
		}
		
		$model = HelpArticle::findOne(array('_id' => new MongoId(glue::http()->param('id'))));
		if(!$model){
			Json::error('That article could no longer be found');
		}

		if($model->delete()){
			Json::success('The article you select was deleted');
		}else{
			Json::error(Json::UNKNOWN);
		}
	}

	public function action_suggestions()
	{
		if(!glue::http()->isAjax()){
			glue::trigger('404');
		}
		
		$ret = array();
		foreach(Help::search(glue::http()->param('term', '')) as $item){
			$ret[] = array(
				'label' => $item->title,
				'description' => '',
				'image' => ''
			);			
		}
		Json::success(array('results' => $ret));
	}
}