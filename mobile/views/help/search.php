<div class="help_page help_layout">
	<div class='search_head'>
		<ol class="breadcrumb">
		  <li><a href="<?php echo glue::http()->url('/help') ?>">Support</a></li>
		  <li class="active"><a href="<?php echo glue::http()->url('/help/search') ?>">Search</a></li>
		</ol>
		<div class="help_search_large row">
			<?php $form = html::form(array('action' => '/help/search', 'method' => 'get')); ?>
			<div class="col-md-10 form-group">
			<label class="sr-only" for="query">Help Search</label>
			<?php echo $form->textField('query', glue::http()->param('query'), 'form-control input-lg')?>
			</div>
			<div class="col-md-2 form-group"><button type="submit" class="btn btn-primary btn-lg btn-search-icon">Search</button></div>
			<?php $form->end() ?>
		</div>
	</div>

	<div class='article row'>
		<div class='col-md-3 col-sm-3'>
			<ul class='help_menu'>
				<li><a href='<?php echo $this->createUrl('/help') ?>'>Go to Help Home</a></li>
		        <?php foreach(app\models\Help::getRootItems() as $item){ ?>
		        	<li><a class='<?php if($this->selectedTab == $item->normalisedTitle): echo "selected"; endif ?> ' href="<?php echo $this->createUrl('/help/view', array('title' => $item->normalisedTitle)) ?>"><?php echo $item->title ?></a></li>
		        <?php } ?>
		        <li><a href='https://getsatisfaction.com/stagex'>Ask A Question</a></li>
			</ul>
		</div>
		<div class='body col-md-9 col-sm-9'>
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
			<h1>No results</h1>
			<p class="no_results_found no_results_help_search">You can try searching with different parts of what you entered to see if you get hits</p>
		<?php } ?>			
		</div>
		<div class='clear'></div>
	</div>	
</div>
<div id='help_search_results'></div>