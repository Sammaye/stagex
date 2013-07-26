<div class="help_page help_layout">
	<div class='search_head'>
		<div class='head_breadcrumb'><a href="/help">Support</a> &rsaquo;</div>
	    <div class='search hero-search'>
			<?php $form = html::form(array('action' => '/help/search', 'method' => 'get')); ?><div class="search_input">
				<?php app\widgets\Jqautocomplete::widget(array(
					'attribute' => 'query',
					'value' => htmlspecialchars(glue::http()->param('query', '')),				
					'options' => array(
						'appendTo' => '#help_search_results',
						'source' => '/help/suggestions',
						'minLength' => 2,
					), 'placeholder' => 'Type in your question and search',
					'renderItem' => "
						return $( '<li></li>' )
							.data( 'item.autocomplete', item )
							.append( '<a class=\'content\'>' + item.label + '</a>' )
							.appendTo( ul );
				")) ?></div><button class="submit_search"><span>&nbsp;</span></button>
			<?php $form->end() ?>
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
		<div class='body'>
		<?php if($sphinx->totalFound > 0){ ?>
			<div class='search_list'>
				<?php foreach($sphinx as $item){
					if($item){
						if($item->type == 'article'){ ?>
							<div class='result'>
								<h2><a href='<?php echo $item->getPermaLink() ?>'><?php echo $item->title ?></a></h2>
								<div class='breadcrumb'><?php echo $item->getBreadCrumb() ?></div>
								<p class='abstract'><?php echo $item->getAbstract() ?></p>
							</div>
						<?php }elseif($item->type == 'topic'){ ?>
							<div class='result'>
								<h2><a href='<?php echo $item->getPermaLink() ?>'><?php echo $item->title ?></a></h2>
								<div class='breadcrumb'><?php echo $item->getBreadCrumb() ?></div>
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
			<div class='list_pager'><?php //echo $sphinx->renderPager('grid_list_pager') ?><div class="clear"></div></div>
		<?php }else{ ?>
			<p class="no_results_found">You can try searching with different parts of what you entered to see if you get hits</p>
		<?php } ?>			
		</div>
		<div class='clear'></div>
	</div>	
</div>
<div id='help_search_results'></div>