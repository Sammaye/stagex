<div class='grid_16 alpha omega profile_videos_body profile_playlists_body' style='margin-bottom:250px;'>
    <div class='search form-search form-search_subs' style='padding:10px 0;'>
	<?php $form = html::form(array('method' => 'get')); ?>
		<?php echo html::textfield('query',htmlspecialchars(glue::http()->param('query',null)),array('placeholder'=>'Search Playlists', 'autocomplete'=>'off', 'class'=>'form-search-input col-38')) ?>
		<button class="btn submit_search"><span>&nbsp;</span></button>
	<?php $form->end() ?>
	</div>	
	<div class='video_list'>
		<?php
		if($sphinx->totalFound> 0){
			glue\widgets\ListView::widget(array(
			'pageSize'	 => 20,
			'page' 		 => isset($_GET['page']) ? $_GET['page'] : 1,
			"cursor"	 => $sphinx,
			'itemView' 	 => 'Playlist/_playlist_ext.php',
			));
		}else{ ?>
			<div class='no_results_found'>No Playlists were found</div>
		<?php } ?>			
	</div>	
</div>