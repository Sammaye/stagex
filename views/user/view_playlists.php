<div class='profile_playlists_body'>
    <div class='search form-search form-search_subs user_profile_main_nav'>
	<?php $form = html::form(array('method' => 'get')); ?>
		<?php echo html::hiddenfield('id',$user->_id) ?>
		<?php echo html::textfield('query',htmlspecialchars(glue::http()->param('query',null)),array('placeholder'=>'Search Playlists', 'autocomplete'=>'off', 'class'=>'form-search-input col-40')) ?>
		<button class="btn submit_search"><span>&nbsp;</span></button>
	<?php $form->end() ?>
	</div>	
	<div class='playlists_list'>
		<?php
		if($sphinx->totalFound> 0){
			glue\widgets\ListView::widget(array(
			'pageSize'	 => 20,
			'page' 		 => isset($_GET['page']) ? $_GET['page'] : 1,
			"cursor"	 => $sphinx,
			'itemView' 	 => 'Playlist/_playlist_box.php',
			));
		}else{ ?>
			<div class='no_results_found'>No Playlists were found</div>
		<?php } ?>			
	</div>	
</div>