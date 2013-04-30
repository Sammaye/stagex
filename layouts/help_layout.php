<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html xmlns:fb="http://www.facebook.com/2008/fbml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta http-equiv="content-language" content="en"/>

		<meta name="description" content="<?php echo $this->pageDescription ?>" />
		<meta name="keywords" content="<?php echo $this->pageKeywords ?>" />

		<link rel="shortcut icon" href="/images/favicon.ico" />

		<title><?php echo $this->pageTitle ?></title>

		<?php
			glue::clientScript()->addCoreJsFile('jqueryui', '/js/jquery-ui.js');
			glue::clientScript()->addCoreJsFile('jquery', '/js/jquery.js');

			glue::clientScript()->addJsFile('facebox', "/js/facebox.js");
			glue::clientScript()->addJsFile("common", '/js/common.js');

			glue::clientScript()->addCoreCSSFile('reset', "/css/reset.css");
			glue::clientScript()->addCoreCSSFile('960', "/css/960.css");
			glue::clientScript()->addCoreCSSFile('main', "/css/main.css");
			glue::clientScript()->addCoreCSSFile('springhare', "/css/springhare.css");

			glue::clientScript()->addJsScript('ga_script', "var _gaq = _gaq || [];
			  _gaq.push(['_setAccount', 'UA-31049834-1']);
			  _gaq.push(['_trackPageview']);

			  (function() {
			    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		  	})();", GClientScript::HEAD);
		?>

	</head>

	<body>
		<?php $this->widget('application/widgets/presenceBar.php') ?>

		<div class='help_head_outer'>
			<div class='help_head'>StageX Help</div>
			<div class='help_search'>
				<?php $form = html::form(array('action' => '/help/search', 'method' => 'get')) ?>
					<?php //echo html::textfield('help_query', htmlspecialchars($_GET['help_query']), array()) ?>

					<?php $this->widget('application/widgets/Jqautocomplete.php', array(
						'attribute' => 'help_query',
						'value' => htmlspecialchars(glue::http()->param('help_query', '')),
						'options' => array(
							'appendTo' => '#help_search_results',
							'source' => '/help/suggestions',
							'minLength' => 2,
						),
						'renderItem' => "
							return $( '<li></li>' )
								.data( 'item.autocomplete', item )
								.append( '<a class=\'content\'>' + item.label + '</a>' )
								.appendTo( ul );
							")) ?>

					<button class='blue_css_button'>Search Help</button>
				<?php $form->end() ?>
			</div>
		</div>

		<div class='container_16 help_section_body'>
			<div class='grid_5 alpha help_left_menu'>
				<ul class='help_menu_ul help_menu_normal_ul'>
					<li class='hm_item'><a href='<?php echo glue::url()->create('/help') ?>'>Go to Help Home</a></li>
			        <?php foreach(Help::getRootItems() as $item){ ?>
			        	<li><a class='<?php if($this->selectedTab == $item->t_normalised): echo "selected"; endif ?> ' href="<?php echo glue::url()->create('/help/view', array('title' => $item->t_normalised)) ?>"><?php echo $item->title ?></a></li>
			        <?php } ?>
			        <li class='qa_item'><a href='https://getsatisfaction.com/stagex'>Ask A Question</a></li>
				</ul>
			</div>
			<div class='grid_12 omega'>
				<div class='page_breadcrumb'><?php echo isset($model) ? $model->getBreadCrumb() : '' ?></div>
		        <?php echo $page ?>
	      	</div>
	    	<div class='clearer'></div>
	    </div>
	    <div class="playlistBottomBar_outer" id="playlist-root"></div>
		<div id="mainSearch_results"></div><div id='help_search_results'></div>
	</body>
</html>