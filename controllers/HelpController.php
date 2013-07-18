<?php

use app\models\Help,
	app\models\HelpTopic,
	app\models\HelpArticle;

class HelpController extends \glue\Controller{

	public $selectedTab = '';

	public function authRules(){
		return array(
//			array("allow",
//				"actions"=>array( 'view_topics', 'add_topic', 'edit_topic', 'remove_topics', 'view_articles', 'add_article', 'edit_article',
//					'remove_articles' ),
//				"users"=>array("@*", '^@')
//			),
			array('allow', 'users' => array('*')),
			array("deny", "users" => array("*")),
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

		glue::sphinx()->query(array('select' => glue::http()->param('help_query', '')), "help");
		echo $this->render("help/search", array( "sphinx" => glue::sphinx()->getSearcher() ));
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

	function action_remove_topics(){
		$this->pageTitle = 'Remove Help Topics - StageX';
		if(!glue::http()->isAjax())
			glue::route(glue::config('404', 'errorPages'));

		$method = isset($_POST['method']) ? $_POST['method'] : null;
		$model = HelpTopic::model()->findOne(array('_id' => new MongoId($_POST['id'])));

		if(!$model)
			GJSON::kill("That topic could no longer be found");

		if($method != 'concat' && $method != 'scrub')
			GJSON::kill('You supplied an invalid mode. Please specify one.');

		$model->delete($method);
		GJSON::kill('The topics you selected were removed', true);
	}

	function action_viewArticles(){
		$this->pageTitle = 'View Help Articles - StageX';

		// Will list all help articles. But only for admins
		$this->render('help/list_articles', array(
			'items' => HelpArticle::model()->search(array('title', 'content', 'path'), glue::http()->param('help_query', ''), array('type' => 'article'))) );
	}

	function action_add_article(){
		$this->pageTitle = 'Add New Help Article - StageX';

		$model = new HelpArticle;

		if(isset($_POST['HelpArticle'])){
			$model->_attributes($_POST['HelpArticle']);
			if($model->validate()){
				$model->save();
				glue::http()->redirect('/help/view_articles');
			}
		}

		$this->render('help/manage_article', array(
			'model' => $model
		));
	}

	function action_edit_article(){
		$this->pageTitle = 'Edit Help Articles - StageX';

		$model = HelpArticle::model()->findOne(array('_id' => new MongoId($_GET['id'])));

		if(isset($_POST['HelpArticle'])){
			$model->_attributes($_POST['HelpArticle']);
			if($model->validate()){
				$model->save();
				glue::http()->redirect('/help/view_articles');
			}
		}

		$this->render('help/manage_article', array(
			'model' => $model
		));
	}

	function action_remove_articles(){
		$this->pageTitle = 'Remove Help Articles - StageX';

		if(!glue::http()->isAjax())
			glue::route(glue::config('404', 'errorPages'));

		$model = HelpArticle::model()->findOne(array('_id' => new MongoId(glue::http()->param('id'))));

		if(!$model)
			GJSON::kill('That article could no longer be found');

		$model->delete();
		GJSON::kill('The article you selected was deleted', true);
	}

	function action_suggestions(){
		$this->pageTitle = 'Suggest Searches - StageX';
		if(!glue::http()->isAjax()){
			glue::route('error/notfound');
		}

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