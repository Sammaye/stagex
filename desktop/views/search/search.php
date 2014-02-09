<?php

// Canonical URL
if($filter_type == 'video'){
	$this->linkTag(array('rel' => 'canonical', 'href' => glue::http()->url('/search', array('filter' => 'video'))));
}elseif($filter_type == 'playlist'){
	$this->linkTag(array('rel' => 'canonical', 'href' => glue::http()->url('/search', array('filter' => 'playlist'))));
}elseif($filter_type == 'user'){
	$this->linkTag(array('rel' => 'canonical', 'href' => glue::http()->url('/search', array('filter' => 'user'))));
}else{
	$this->linkTag(array('rel' => 'canonical', 'href' => glue::http()->url('/search')));
}

$this->JsFile("/js/jquery.expander.js");
$this->JsFile('/js/jdropdown.js');

$this->js('page', "
	$('.expandable').expander({slicePoint: 120});
	$('.dropdown-group').jdropdown();
") ?>
<div class='search_main_head'>
<div class="grid-container">
	<div>
	<div class="form-search-lg help_search_large">
		<?php $form = html::form(array('action' => '/search', 'method' => 'get')); ?>
		<label class="sr-only" for="query">Site Search</label>
		<?php echo $form->textField('query', glue::http()->param('query'), array('class' => 'form-search-input', 'placeholder' => 'Search StageX')) ?>			
		<button type="submit" class="btn btn-primary btn-search-icon"><span class="search-white-icon">&nbsp;</span></button>
		<?php $form->end() ?>
	</div>
	<div class='text-muted'><?php echo $sphinx->totalFound ?> results found 
	<a class="" style='display:inline; margin-left:15px;' href='<?php echo glue::http()->url('/user/settings') ?>'>
		<?php if(glue::user()->safeSearch || !glue::auth()->check(array('authed'))){ ?>
			Safe Search On
		<?php }else{ ?>
			Safe Search Off
		<?php } ?></a>	
	</div>
	</div>
</div>
</div>
<div class='grid-container site_search_page' style='margin-bottom:250px;'>
	<?php echo app\widgets\UserMenu::run(); ?> 
	<div class='grid-col-41'>

	<div class='search_filter_bar clearfix'>

	<div class="dropdown-group">
	<button class='btn btn-default dropdown-anchor'>Type<?php
		if($filter_type==='video'){
			echo ': Video';
		}elseif($filter_type==='playlist')
			echo ": Playlist";
		elseif($filter_type==='user')
			echo ": User";
		else
			echo ": All";
		?> <span class="caret"></span></button>
		<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu1">
		<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('filter_type'=>'all')) ?>">All</a></li>
		<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('filter_type'=>'video')) ?>">Video</a></li>
		<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('filter_type'=>'playlist')) ?>">Playlist</a></li>
		<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('filter_type'=>'user')) ?>">User</a></li>
		</ul>
	</div>		

	<div class="dropdown-group">
	<button class='btn btn-default dropdown-anchor'>Time<?php
		if($filter_time==='today'){
			echo ': Today';
		}elseif($filter_time==='week')
			echo ": Week";
		elseif($filter_time==='month')
			echo ": Month";
		else
			echo ": All";
		?> <span class="caret"></span></button>
		<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu1">
		<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('filter_time'=>'all')) ?>">All</a></li>
		<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('filter_time'=>'today')) ?>">Today</a></li>
		<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('filter_time'=>'week')) ?>">Week</a></li>
		<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('filter_time'=>'month')) ?>">Month</a></li>
		</ul>
	</div>		

	<?php if($filter_type == 'video' || $filter_type == 'all'){ ?>
	<div class="dropdown-group">
	<button class='btn btn-default dropdown-anchor'>Category<?php
	
		$categories=app\models\Video::categories('selectBox');
	
		if(array_key_exists($filter_category, $categories))
			echo ': '.$categories[$filter_category];
		else
			echo ": All";
		?> <span class="caret"></span></button>
		<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu1">
		<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('filter_category'=>'all')) ?>">All</a></li>
		<?php foreach($categories as $id => $cat){ ?>
		<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('filter_category'=>$id, 'filter_type' => 'video')) ?>"><?php echo $cat ?></a></li>
		<?php } ?>
		</ul>
	</div>	
	
	<div class="dropdown-group">
	<button class='btn btn-default dropdown-anchor'>Length<?php
		if($filter_duration==='short'){
			echo ': Short';
		}elseif($filter_duration==='long')
			echo ": Long";
		else
			echo ": All";
		?> <span class="caret"></span></button>
		<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu1">
		<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('filter_duration'=>'all', 'filter_type' => 'video')) ?>">All</a></li>
		<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('filter_duration'=>'short', 'filter_type' => 'video')) ?>">Short</a></li>
		<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('filter_duration'=>'long', 'filter_type' => 'video')) ?>">Long</a></li>
		</ul>
	</div>		

	<div class="dropdown-group">
	<button class='btn btn-default dropdown-anchor'>Sort<?php
		if($orderby==='upload_date'){
			echo ': Upload Date';
		}elseif($orderby==='views')
			echo ": Viewed";
		elseif($orderby==='rating')
			echo ": Liked";
		else
			echo ": Relevance";
		?> <span class="caret"></span></button>
		<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu1">
		<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('orderby'=>'relevance')) ?>">Relevance</a></li>
		<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('orderby'=>'upload_date', 'filter_type' => 'video')) ?>">Upload Date</a></li>
		<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('orderby'=>'views', 'filter_type' => 'video')) ?>">Viewed</a></li>
		<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo glue::http()->url(array('orderby'=>'rating', 'filter_type' => 'video')) ?>">Liked</a></li>
		</ul>
	</div>		
	<?php } ?>
	</div>
	<div class="search_body">
	<?php
	if($sphinx->count() > 0){
		foreach($sphinx as $k => $model){
			if(!$model)
				continue;
	
			if($model instanceof app\models\Video){
				echo $this->renderPartial('video/_video_ext', array('model' => $model, 'extra_classes' => 'site_search_item'));
			}elseif($model instanceof app\models\User){
				echo $this->renderPartial('user/_user_ext', array('model' => $model, 'extra_classes' => 'site_search_item'));
			}elseif($model instanceof app\models\Playlist){
				echo $this->renderPartial('playlist/_playlist_ext', array('model' => $model, 'extra_classes' => 'site_search_item'));
			}
		}
	}else{ ?>
	<div class="alert alert-warning">
		<h4>No results found for "<?php echo glue::http()->param('query') ?>"</h4>
		<p>Oh noes! You have two choices from here:</p>
		<ul>
		<li>Try a less specific search that might wield results or browse our site further.</li>
		<li>If that fails you can upload the video to this site</li>
		</ul>
	</div>
	<?php } ?>
	<div class='clearfix'><?php echo glue\widgets\Pagination::run(array('totalItems' => $sphinx->totalFound, 'page' => glue::http()->param('page',1), 
		'data' => array('orderby' => $orderby, 'filter_duration' => $filter_duration, 'filter_category' => $filter_category, 
		'filter_time' => $filter_time, 'filter_type' => $filter_type, 'query' => glue::http()->param('query')))) ?></div>
	</div>
	</div>
</div>