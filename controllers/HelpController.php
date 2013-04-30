<?php
class HelpController extends GController{

	public $selectedTab = '';

	// A set of filters to be run before and after the controller action
	public function filters(){
		return array('rbam');
	}

	public function accessRules(){
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
		$this->pageTitle = 'StageX Help Center';
		$this->render("help/index");
	}

	function action_view(){
		$this->pageTitle = 'Help Content Not Found - StageX';
		$this->layout = "help_layout";

		$item = Help::model()->findOne(array('t_normalised' => glue::http()->param('title')));

		if(!$item){
			$this->render('help/notfound');
		}else{
			$this->pageTitle = $item->title.' - StageX';
			if(count(explode(',', $item->path)) > 1){
				$this->selectedTab = strstr($item->path, ',', true);
			}else{
				$this->selectedTab = $item->path;
			}

			if($item->type == 'topic'){
				$this->render('help/view_topic', array('model' => $item));
			}elseif($item->type == 'article'){
				$this->render('help/view_article', array('model' => $item));
			}
		}
	}

	function action_search(){
		$this->pageTitle = 'Search StageX Help';

		glue::sphinx()->query(array('select' => glue::http()->param('help_query', '')), "help");
		$this->render("help/search", array( "sphinx" => glue::sphinx()->getSearcher() ));
	}


	function action_view_topics(){
		$this->pageTitle = 'View Help Topics - StageX';

		// Will list all help articles. But only for admins
		$this->render('help/list_topics', array('items' => HelpTopic::model()->search(array('title', 'path'), glue::http()->param('help_query', ''), array('type' => 'topic'))) );
	}

	function action_add_topic(){
		$this->pageTitle = 'Add Help Topic - StageX';
		//var_dump($_POST);
		$model = new HelpTopic();

		if(isset($_POST['HelpTopic'])){
			$model->_attributes($_POST['HelpTopic']);
			if($model->validate()){
				$model->save();
				glue::http()->redirect('/help/view_topics');
			}
		}
		//echo "here";
		$this->render('help/manage_topic', array( 'model' => $model ));
	}

	function action_edit_topic(){
		$this->pageTitle = 'Edit Help Topic - StageX';
		$model = HelpTopic::model()->findOne(array('_id' => new MongoId($_GET['id'])));

		if(isset($_POST['HelpTopic'])){
			$model->_attributes($_POST['HelpTopic']);
			if($model->validate()){
				$model->save();
				glue::http()->redirect('/help/view_topics');
			}
		}
		//echo "here";
		$this->render('help/manage_topic', array( 'model' => $model ));
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

	function action_view_articles(){
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