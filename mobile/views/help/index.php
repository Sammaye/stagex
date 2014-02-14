<div class="help_index help_page">
	<h1 class="hero">Support</h1>
    <div class='search row'>
		<?php $form = html::form(array('action' => '/help/search', 'method' => 'get')); ?>
		<div class="col-md-10 form-group">
			<?php echo app\widgets\Autocomplete::run(array(
				'htmlOptions' => array(
					'class' => 'form-control input-lg',
				),
				'attribute' => 'query',
				'value' => htmlspecialchars(glue::http()->param('query', '')),				
				'options' => array(
					'appendTo' => '#help_search_results',
					'source' => "js:function(request, response){
					$.get('/help/suggestions', {term: request.term}, null, 'json')
					.done(function(data){
						ret = [];
						if(data.success){
							$.each(data.results, function(k, v){
								ret[ret.length] = {label: v.title};
							});
						}
						response(ret);
					});
					}",
					'minLength' => 2,
				), 'placeholder' => 'Type in your question and search',
				'renderItem' => "
					return $( '<li></li>' )
						.data( 'item.autocomplete', item )
						.append( '<a class=\'content\'>' + item.label + '</a>' )
						.appendTo( ul );
			")) ?>		
		</div>
		<div class="col-md-2 form-group"><button class="btn btn-primary btn-lg submit_search">Search</button></div>
		<?php $form->end() ?>
	</div> 	
	<div class='other_links'>
		<div class="light">Not sure what your looking for? Try some of these pages to start you off:</div>
		<a href='<?php echo $this->createUrl('/help/view', array('title' => 'your-account')) ?>'>Your Account</a>
		<span>-</span>
		<a href='<?php echo $this->createUrl('/help/view', array('title' => 'videos')) ?>'>Videos</a>
		<span>-</span>
		<a href='<?php echo $this->createUrl('/help/view', array('title' => 'playlists')) ?>'>Playlists</a>
		<span>-</span>
		<a href='<?php echo $this->createUrl('/help/view', array('title' => 'terms-and-conditions')) ?>'>Terms and Conditions</a>
		<span>-</span>
		<a href='<?php echo $this->createUrl('/help/view', array('title' => 'copyright')) ?>'>Copyright Stuff</a>
	</div>		
	<p class="ask">Failing all else (or you have found a bug) <a href='https://getsatisfaction.com/stagex'>you can try asking a question on the forums</a></p>
	<div class="clear"></div>
</div>

<div id='help_search_results'></div>