<div class='grid_16 alpha omega profile_videos_body' style='margin-bottom:250px;'>
	<div class='main_content_outer'>
		<div class='profile_media_top_bar'>
			<div class='profile_media_title'>Videos</div>
			<div class='profile_media_amt_fnd'><?php echo $sphinx->total_found ?> found</div>

			<div class='profile_media_search'>
				<div class='search_widget'>
					<?php $form = html::form(array('method' => 'get')) ?>
					<div class='middle'><?php
						echo html::textfield('query', htmlspecialchars(isset($_GET['query']) ? $_GET['query'] : '')) ?></div><a href='#' id='profile_search_submit' class='submit_search'><img alt='search' src='/images/search_icon_small.png'/></a>
					<?php echo html::hiddenfield('id', strval($user->_id)) ?>
					<?php echo html::submitbutton('Search', array('class' => 'invisible_submit')); $form->end() ?>
				</div>
			</div>
		</div>

		<div class='grid_5 alpha video_list' style='width:740px; padding:10px;'>
			<?php
			$i = 0;
			if($sphinx->matches){
				foreach($sphinx->matches as $k => $model){
					if($model instanceof Video){
						$this->partialRender('videos/_video_large', array('item' => $model, 'last' => $i%3 == 0 ? true : false));
						$i++;
					}
				}
			}else{ ?>
				<div class='profile_media_none_found'>
					Nothing to see here!
				</div>
			<?php } ?>

			<?php if($sphinx->total_found > 21): ?><div class='clearer'></div><div class='profile_media_pager'><?php echo $sphinx->renderPager('grid_list_pager') ?><div class="clearer"></div></div><?php endif ?>
		</div>
		<div class='grid_3 omega' style='width:180px; margin-top:15px;'>
			<?php $this->widget("application/widgets/Advertising/Ad_box.php", array( "configuration"=>'skyscraper' )); ?>
		</div>
		<div class='clearer'></div>
	</div>
</div>