<?php

$this->js('deleteArticle', "
	$(function(){
		$('.delete_article').click(function(event){
			event.preventDefault();

			var el = $(this);
			$.getJSON('/help/deleteArticle', {'id': $(this).parents('.article').data().id}, function(data){
				if(!data.success){
					$('.alert').summarise({},'error','Could not delete');
				}else{
					el.parents('.article').fadeOut('slow', function(){
						$(this).remove();
					});
				}
			});
		});
	});
") ?>

<div class='container_16 manage_articles_body'>

	<div class='alert' style='display:none;'></div>

	<div class="grid_16 alpha omega">
		<div class='top_bar'>
			<h1 class='head'>Help Articles</h1>
			<div class='right_bar'>
				<div class='search_form'>
					<form method='get' action='<?php echo $this->createUrl('SELF') ?>'><?php echo html::textfield('help_query', glue::http()->param('query', ''), array( 'class' => 'search_input' )) ?>
					<?php echo html::submitbutton('Search', array( 'class' => 'invisible_submit' ))?></form>
				</div>
				<a href='<?php echo $this->createUrl('/help/addArticle') ?>' class='create_article'>Create New Help Article</a>
			</div>
			<div class='clearer'></div>
		</div>
	</div>

	<div class='grid_16 alpha omega'>
		<?php if($items->count() > 0){ ?>
			<div class='list'>
				<?php
				$i = 0;

				foreach($items as $article){ ?>
					<div class='article <?php if($i == 0): echo "no_border"; endif ?>' data-id='<?php echo $article->_id ?>'>
						<a class='title' href='<?php echo $this->createUrl('/help/editArticle', array( 'id' => strval($article->_id) )) ?>'><?php echo $article->title ?></a>
						<div class='content'><?php echo $article->content ?></div>
						<div class='path'><?php echo $article->path ?></div>
						<div class='tags'>
							<?php foreach($article->tags as $tag){ ?>
								<a href='<?php echo $this->createUrl('/help/viewArticles', array( 'query' => $tag )) ?>'><?php echo $tag ?></a>
							<?php } ?>
						</div>

						<div class='delete_button'><a href='#' class='delete_article'><?php echo utf8_decode('&#215;') ?></a></div>
					</div>
				<?php $i++;
				} ?>
			</div>
		<?php }else{ ?>
			<h2>No Articles found.</h2>
		<?php } ?>
	</div>
</div>

