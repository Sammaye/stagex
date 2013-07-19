<?php

$this->jsFile('/js/modal.js');

$this->js('addTopic', "

	var selectedTopic;

	$(function(){

		$(document).on('click', '.deleteTopic', function(event){
			event.preventDefault();

			selectedTopic = $(this);
			$.modal({
			html: " . js_encode("
				<h2>Delete Help Topic</h2>
				<div class='clear'></div>

				<div class='alert'></div>

				<form method='get' action='#' class='delete_topic_form'>
					<div class='form form-vertical'>
						<input type='hidden' class='id' name='id'/>
						<div class='row'>" . html::label('Deletion Method', 'method').html::selectbox('method', array('concat' => 'Concat children to this ones parent (including articles)', 'scrub' => 'Scrub all Children (including articles)')) . "</div>
						<div class='submit_row'>".html::submitButton('Delete', array('class'=>'btn-success'))."</div>					
					</div>
				</form>
			") . ", wrapperCssClass: 'help_modal'});

			$('.help_modal .delete_topic_form .id').val($(this).parents('.topic').find('._id').text());
		});

		$(document).on('click', '.delete_topic_form .btn-success', function(event){
			event.preventDefault();
			$.post('/help/deleteTopic', $('.delete_topic_form').serialize(), function(data){
				if(!data.success){
					$('.help_modal .alert').summarise({},'error','Poop!');
				}else{
					$.modal('close');
					selectedTopic.parents('.topic').fadeOut('slow', function(){
						$(this).remove();
					});
				}
				$('.help_modal .alert').summarise('reset');
			}, 'json');
		});
	});
") ?>

<div class="help_page">

	<div class="grid_16 alpha omega">
		<div class='help_topic_admin_bar'><h1 class='top_head'>Help Topics</h1>
		<div class='search-form'>
			<div class='search_form'>
				<form method='get' action='<?php echo $this->createUrl('SELF') ?>'><?php echo html::textfield('help_query', glue::http()->param('help_query', ''), array( 'class' => 'search_input' )) ?>
				<?php echo html::submitbutton('Search', array( 'class' => 'invisible_submit' ))?></form>
			</div>
			<a href='<?php echo $this->createUrl('/help/addTopic') ?>' class='create_topic'>Add new topic</a>
		</div>
		<div class="clear"></div></div>
	</div>

	<div class="">
		<?php if($items->count() > 0){ ?>
		<table class="table" width="100%">
			<thead>
				<tr>
					<th>Title</th>
					<th>Path</th>
					<th>Sequence</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach($items as $model){ ?>
				<tr class='topic'>
					<td><span class='_id' style='display:none;'><?php echo $model->_id ?></span>
					<a href="<?php echo $this->createUrl('/help/editTopic', array( 'id' => strval($model->_id) )) ?>"><?php echo $model->title ?></a></td>
					<td><?php echo $model->path ?></td>
					<td><?php echo $model->seq ?></td>
					<td><a href='' class='deleteTopic'>Delete</a></td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		<?php }else{ ?>
			<p class='no_topic_found'>No topics were found</p>
		<?php } ?>
	</div>
</div>