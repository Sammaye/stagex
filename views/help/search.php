<?php
glue::clientScript()->addJsScript('search_page', "
	$(document).on('click', '.help_search button', function(event){
		$(this).parents('form').submit();
	});
");
?>
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
<div class='container_16 help_search_body'>
	<div class='grid_5 alpha help_search_left'>
		<ul class='help_menu_ul'><li><a href='<?php echo glue::url()->create('/help') ?>'>Go to Help Home</a></li></ul>
	</div>
	<div class='grid_12 omega'>
		<div class='amnt_found'>Found <?php echo $sphinx->total_found ?> results for <b><?php echo $_GET['help_query'] ?></b>.</div>

		<?php if($sphinx->total_found > 0){ ?>
			<div class='help_search_list'>
				<?php foreach($sphinx->matches as $item){
					if($item){
						if($item->type == 'article'){ ?>
							<div class='help_search_item'>
								<h2 class='title'><a href='<?php echo $item->getPermaLink() ?>'><?php echo $item->title ?></a></h2>
								<div class='bread'><?php echo $item->getBreadCrumb() ?></div>
								<p class='abstract'><?php echo $item->getAbstract() ?></p>
							</div>
						<?php }elseif($item->type == 'topic'){ ?>
							<div class='help_search_item'>
								<h2 class='title'><a href='<?php echo $item->getPermaLink() ?>'><?php echo $item->title ?></a></h2>
								<div class='bread'><?php echo $item->getBreadCrumb() ?></div>
								<?php $children = $item->getDescendants()->limit(5) ?>
								<ul class='topic_list'>
									<?php foreach($children as $child){ ?>
										<li><a href='<?php echo $child->getPermaLink() ?>'><?php echo $child->title ?></a></li>
									<?php } ?>
								</ul>
							</div>
						<?php }
					} ?>
				<?php } ?>
			</div>
			<div class='list_pager'><?php echo $sphinx->renderPager('grid_list_pager') ?><div class="clear"></div></div>
		<?php }else{ ?>
			<p>You can try searching with different parts of what you entered to see if you get hits</p>
		<?php } ?>
	</div>
</div>

<div id='help_search_results'></div>