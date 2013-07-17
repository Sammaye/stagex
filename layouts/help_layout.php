<?php

use \glue\Html;

$this->beginPage() ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html xmlns:fb="http://www.facebook.com/2008/fbml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta http-equiv="content-language" content="en"/>

		<link rel="shortcut icon" href="/images/favicon.ico" />

		<title><?php echo Html::encode($this->title) ?></title>

		<?php
			echo Html::jsFile('/js/jquery.js')."\n";
			echo Html::jsFile('/js/jquery-ui.js')."\n";

			echo Html::jsFile("/js/facebox.js")."\n";
			echo Html::jsFile('/js/common.js')."\n";

			echo Html::cssFile("/css/reset.css")."\n";
			echo Html::cssFile("/css/960.css")."\n";
			echo Html::cssFile("/css/main.css")."\n";

			$this->js('ga_script', "var _gaq = _gaq || [];
			  _gaq.push(['_setAccount', 'UA-31049834-1']);
			  _gaq.push(['_trackPageview']);

			  (function() {
			    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		  	})();", self::HEAD);

			$this->js('gplus_one', "(function() {
		    	var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
		    	po.src = 'https://apis.google.com/js/plusone.js';
		    	var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
		  	})();");

			$this->head();
		?>
	</head>
	
	<body>
		<?php $this->beginBody() ?>
			<?php app\widgets\Menu::widget() ?>
		<div class="help_page help_layout">
		<div class='head'>
			<div class="left">
			<div><a href="/help">Support</a> / <?php echo isset($model) ? $model->getBreadCrumb() : '' ?></div>
			<h1 class="hero"><?php if(isset($model)&&$model!==null): echo $model->title; else: echo "404 Not Found"; endif; ?></h1>
			</div>
    		<div class='search form-search'>
			<?php $form = Html::form(array('method' => 'get', 'action' => '/help/search')); ?><div class="search_input">
				<?php app\widgets\Jqautocomplete::widget(array(
					'attribute' => 'query',
					'value' => urldecode(htmlspecialchars(isset($_GET['query']) ? $_GET['query'] : '')),
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
				"))  ?></div><button class="submit_search"><span>&nbsp;</span></button>
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
			<div class='body'><?php echo $content ?></div>
	    	<div class='clear'></div>
	    </div>	
	    </div>
		<div id="mainSearch_results"></div>
		<?php $this->endBody() ?>
	</body>	
</html>