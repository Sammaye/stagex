<?php

use \glue\Controller;
use app\models\Help,
	app\models\HelpTopic,
	app\models\HelpArticle;

class HelpController extends Controller{

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

	function action_index(){
		$this->title = 'StageX Help Center';
		echo $this->render("help/index");
	}

	function action_view(){
		$this->title = 'Help Content Not Found - StageX';
		$this->layout = "help_layout";

		$item = Help::model()->findOne(array('normalisedTitle' => glue::http()->param('title')));

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

	function action_search(){
		$this->title = 'Search StageX Help';
		echo $this->render("help/search", array( "sphinx" => Help::model()->search() ));
	}

	function action_viewTopics(){
		$this->title = 'View Help Topics - StageX';

		// Will list all help articles. But only for admins
		echo $this->render('help/list_topics', array('items' => app\models\HelpTopic::model()->fts(array('title', 'path'), glue::http()->param('query', ''), array('type' => 'topic'))) );
	}

	function action_addTopic(){
		$this->title = 'Add Help Topic - StageX';
		$model = new HelpTopic();

		if(isset($_POST['HelpTopic'])){
			$model->attributes=$_POST['HelpTopic'];
			if($model->validate()){
				$model->save();
				glue::http()->redirect('/help/viewTopics');
			}
		}
		echo $this->render('help/manage_topic', array( 'model' => $model ));
	}

	function action_editTopic(){
		$this->title = 'Edit Help Topic - StageX';
		$model = HelpTopic::model()->findOne(array('_id' => new MongoId($_GET['id'])));

		if(isset($_POST['HelpTopic'])){
			$model->attributes=$_POST['HelpTopic'];
			if($model->validate()&&$model->save()){
				glue::http()->redirect('/help/viewTopics');
			}
		}
		echo $this->render('help/manage_topic', array( 'model' => $model ));
	}

	function action_deleteTopic(){
		$this->title = 'Remove Help Topics - StageX';
		if(!glue::http()->isAjax())
			glue::trigger('404');

		$method = glue::http()->param('method',null);
		$model = HelpTopic::model()->findOne(array('_id' => new MongoId(glue::http()->param('id',null))));

		if(!$model)
			$this->json_error('That topic could no longer be found');

		if($method != 'concat' && $method != 'scrub')
			$this->json_error('You supplied an invalid mode. Please specify one.');

		$model->delete($method);
		$this->json_success('The topic you selected was removed');
	}

	function action_viewArticles(){
		$this->title = 'View Help Articles - StageX';

		// Will list all help articles. But only for admins
		echo $this->render('help/list_articles', array(
			'items' => HelpArticle::model()->fts(array('title', 'content', 'path'), glue::http()->param('query', ''), array('type' => 'article'))) );
	}

	function action_addArticle(){
		$this->title = 'Add New Help Article - StageX';

		$model = new HelpArticle;
		if(isset($_POST['HelpArticle'])){
			$model->attributes=$_POST['HelpArticle'];
			if($model->validate()&&$model->save()){
				glue::http()->redirect('/help/viewArticles');
			}
		}

		echo $this->render('help/manage_article', array(
			'model' => $model
		));
	}

	function action_editArticle(){
		$this->title = 'Edit Help Article - StageX';

		$model = HelpArticle::model()->findOne(array('_id' => new MongoId($_GET['id'])));

		if(isset($_POST['HelpArticle'])){
			$model->attributes=$_POST['HelpArticle'];
			if($model->validate()&&$model->save()){
				glue::http()->redirect('/help/viewArticles');
			}
		}

		echo $this->render('help/manage_article', array(
			'model' => $model
		));
	}

	function action_deleteArticle(){
		$this->title = 'Remove Help Article - StageX';

		if(!glue::http()->isAjax())
			glue::trigger('404');
		
		$model = HelpArticle::model()->findOne(array('_id' => new MongoId(glue::http()->param('id'))));
		if(!$model)
			$this->json_error('That article could no longer be found');

		$model->delete();
		$this->json_success('The article you select was deleted');
	}

	function action_suggestions(){
		$this->title = 'Suggest Help Pages - StageX';
		if(!glue::http()->isAjax())
			glue::trigger('404');

		$ret = array();

		$sphinx = glue::sphinx()->getSearcher();
		$sphinx->limit = 5;
		$sphinx->query(array('select' => glue::http()->param('term', '')), "help");

		if($sphinx->matches){
			foreach($sphinx->matches as $item){
				$ret[] = array(
					'label' => $item->title,
					'description' => '',
					'image' => ''
				);
			}
		}

		echo json_encode($ret);
	}
}