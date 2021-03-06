<div class='help_page_view'>
	<div class='content'>
		<?php $children = $model->getDescendants()->sort(array('type' => 1, 'seq' => 1)) ?>

		<?php if(count($children) > 0){ ?>
			<ul class='pages'>
			<?php foreach($children as $child){
				if($child->type == 'article'){ ?>
					<li class='article'>
						<div>
							<h2 class='title'><a href='<?php echo $child->getPermaLink() ?>'><?php echo $child->title ?></a></h2>
							<p class='abstract'><?php echo $child->getAbstract(200) ?></p>
						</div>
					</li>
				<?php }else{
					$sub_children = $child->getDescendants()->sort(array('type' => 1)) ?>
					<li class='topic'>
						<h2><?php echo $child->title ?></h2>
						<ul>
							<?php foreach($sub_children as $sub_child){ ?>
								<li><a href='<?php echo $sub_child->getPermaLink() ?>'><?php echo $sub_child->title ?></a></li>
							<?php } ?>
						</ul>
					</li>
				<?php }
			} ?>
			</ul>			
		<?php }else{ ?>
			<div class='no_results_found'>No items found under this topic</div>
		<?php } ?>
	</div>
	<div class='clearer'></div>
</div>
