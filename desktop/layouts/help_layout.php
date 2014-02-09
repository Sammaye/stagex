<?php

use \glue\Html;

$this->beginPage() ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta http-equiv="content-language" content="en"/>

		<link rel="shortcut icon" href="/images/favicon.ico" />

		<title><?php echo Html::encode($this->title) ?></title>

		<?php
		$this->jsFile(array(
			'/js/jquery.js',
			'/js/jquery-ui.js',
			'/js/bootstrap.js',
			'/js/common.js',
		), self::HEAD);
			
		$this->cssFile(array(
			'/css/springhare.css',
			'/css/jquery-ui/jquery-ui.css',
			'/css/main.css'
		));		
		$this->head();
		?>
	</head>
	
	<body>
		<?php $this->beginBody() ?>
			<?php echo app\widgets\Menu::run(); ?>
		<div class="help_page help_layout">
		<div class='head'>
			<div class="left">
			<div class="breadcrumb-header">
			<ol class="">
			  <li><a href="/help">Support</a> <span class="divider">/</span></li>
			  <?php echo isset($model) ? $model->getBreadCrumb() : '' ?>
			</ol>
			<h1 class="jumbo"><?php if(isset($model)&&$model!==null): echo $model->title; else: echo "404 Not Found"; endif; ?></h1>
			</div>			
			</div>
			
    		<div class='search form-search'>
			<?php $form = Html::form(array('method' => 'get', 'action'=>glue::http()->url('/help/search'))); ?>
				<label class="sr-only" for="query">Help Search</label>
				<?php echo app\widgets\Autocomplete::run(array(
					'attribute' => 'query',
					'value' => urldecode(htmlspecialchars(isset($_GET['query']) ? $_GET['query'] : '')),
					'placeholder' => 'Search Help',
					'htmlOptions' => array(
						'class' => 'form-search-input'
					),
					'options' => array(
					'appendTo' => '#help_search_results',
					'source' => "js:function(request, response){
					$.get('/help/suggestions', {term: request.term}, null, 'json')
					.done(function(data){
						ret = [];
						if(data.success){
							$.each(data.results, function(k, v){
								ret[ret.length] = {label: v.title};
							});
						}
						response(ret);
					});
					}",
					'minLength' => 2,
					),
					'renderItem' => "
						return $( '<li></li>' )
							.data( 'item.autocomplete', item )
							.append( '<a class=\'content\'><span>' + item.label + '</span></div></a>' )
							.appendTo( ul );
				"))  ?><button class="btn submit_search"><span class="search-dark-icon">&nbsp;</span></button>
			<?php $form->end() ?>
			<div class="clear"></div>
			<p class='ask'><a href='https://getsatisfaction.com/stagex'>Ask a Question on the Forums</a></p>
			</div>
			<div class="clear"></div>
		</div>

		<div class='article'>
			<div class='left'>
				<ul class='help_menu'>
					<li><a href='<?php echo $this->createUrl('/help') ?>'>Go to Help Home</a></li>
			        <?php foreach(app\models\Help::getRootItems() as $item){ ?>
			        	<li><a class='<?php if($this->selectedTab == $item->normalisedTitle): echo "selected"; endif ?> ' href="<?php echo $this->createUrl('/help/view', array('title' => $item->normalisedTitle)) ?>"><?php echo $item->title ?></a></li>
			        <?php } ?>
			        <li><a href='https://getsatisfaction.com/stagex'>Ask A Question</a></li>
				</ul>
			</div>
			<div class='body' id="content"><?php echo $content ?></div>
	    	<div class='clear'></div>
	    </div>	
	    </div>
		<div id="mainSearch_results"></div>
		<?php $this->endBody() ?>
	</body>	
</html>
<?php $this->endPage() ?>