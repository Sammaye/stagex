<div>
	<div class='help_index_head'>
		<div class='head'>StageX Help</div>
	</div>
	<div class='help_index_body'>
		<div class='welcome_head'>How may we serve you today?</div>
		<div class='help_search' style=''>
			<?php $form = html::form(array('action' => '/help/search', 'method' => 'get')) ?>
				<?php //echo html::textfield('help_query', htmlspecialchars($_GET['help_query']), array()) ?>

				<?php $this->widget('application/widgets/Jqautocomplete.php', array(
					'attribute' => 'help_query',
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
		<div class='clearer'></div>
		<div class='welcome_qa'>Or <a href='https://getsatisfaction.com/stagex'>You can try asking a direct question...</a></div>
	</div>
	<div class='help_index_footer' style=''>
		<a href='<?php echo glue::url()->create('/help/view', array('title' => 'your-account')) ?>'>Your user section and account</a>
		<span>|</span>
		<a href='<?php echo glue::url()->create('/help/view', array('title' => 'videos')) ?>'>Videos</a>
		<span>|</span>
		<a href='<?php echo glue::url()->create('/help/view', array('title' => 'playlists')) ?>'>Playlists</a>
		<span>|</span>
		<a href='<?php echo glue::url()->create('/help/view', array('title' => 'terms-and-conditions')) ?>'>Terms and Conditions</a>
		<span>|</span>
		<a href='<?php echo glue::url()->create('/help/view', array('title' => 'copyright')) ?>'>Copyright Stuff</a>
	</div>
</div>

<div id='help_search_results'></div>