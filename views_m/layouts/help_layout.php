<?php

use \glue\Html;

$this->beginPage() ?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo Html::encode($this->title) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
<link href="/css/bootstrap.min.css" rel="stylesheet">

<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
<script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
<![endif]-->

<link type="text/css" rel="stylesheet" href="/css/mmenu.css" />
<link type="text/css" rel="stylesheet" href="/css/main.css" />
<?php 
	$this->head();
?>
</head>
<body>
	<div id="page">
	<?php $this->beginBody() ?>
		<?php echo app\widgets\userMobileMenu::run() ?>

		<div class="row" style='padding:20px;'>
		<div class="col-md-12">
		<div class="help_page help_layout">
		<div class='head row'>
			<div class="breadcrumb-header col-md-9">
			<ol class="breadcrumb">
				<li><a href="/help">Support</a></li>
				<?php echo isset($model) ? $model->getBreadCrumb() : '' ?>
			</ol>
			<h1 class="jumbo"><?php if(isset($model)&&$model!==null): echo $model->title; else: echo "404 Not Found"; endif; ?></h1>
			</div>
			
    		<div class='search form-search col-md-3'>
			<?php $form = Html::form(array('method' => 'get', 'action'=>glue::http()->url('/help/search'))); ?>
				<?php echo app\widgets\Jqautocomplete::run(array(
					'attribute' => 'query',
					'value' => urldecode(htmlspecialchars(isset($_GET['query']) ? $_GET['query'] : '')),
					'placeholder' => 'Search Help',
					'htmlOptions' => array(
						'class' => 'form-search-input'
					),
					'options' => array(
						'appendTo' => '#help_search_results',
						'source' => '/help/suggestions',
						'minLength' => 2,
					),
					'renderItem' => "
						return $( '<li></li>' )
							.data( 'item.autocomplete', item )
							.append( '<a class=\'content\'><span>' + item.label + '</span></div></a>' )
							.appendTo( ul );
				"))  ?><button class="btn submit_search"><span>&nbsp;</span></button>
			<?php $form->end() ?>
			<p class='ask'><a href='https://getsatisfaction.com/stagex'>Ask a Question on the Forums</a></p>
			</div>
		</div>

		<div class='article row'>
			<div class='col-md-3'>
				<ul class='help_menu'>
					<li><a href='<?php echo $this->createUrl('/help') ?>'>Go to Help Home</a></li>
			        <?php foreach(app\models\Help::getRootItems() as $item){ ?>
			        	<li><a class='<?php if($this->selectedTab == $item->normalisedTitle): echo "selected"; endif ?> ' href="<?php echo $this->createUrl('/help/view', array('title' => $item->normalisedTitle)) ?>"><?php echo $item->title ?></a></li>
			        <?php } ?>
			        <li><a href='https://getsatisfaction.com/stagex'>Ask A Question</a></li>
				</ul>
			</div>
			<div class='body col-md-9'><?php echo $content ?></div>
	    	<div class='clear'></div>
	    </div>	
	    </div>		
		</div>
		</div>
		<div id="mainSearch_results"></div>
		<script src="https://code.jquery.com/jquery.js"></script>
		<script type="text/javascript" src="/js/mmenu.js"></script>
		<script src="/js/bootstrap.min.js"></script>
		<script type="text/javascript">
		$(function() {
			$('nav#menu').mmenu();
		});
		</script>			
	<?php $this->endBody() ?>
	</div>

</body>
</html>
<?php $this->endPage() ?>