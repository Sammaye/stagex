<?php
glue::clientScript()->addJsScript('addTopic', "

	var selectedTopic;

	$(function(){

		$(document).on('click', '.deleteTopic', function(event){
			event.preventDefault();

			selectedTopic = $(this);

			$.facebox(" . glue::clientScript()->encode("
				<h2 class='diag_header'>Delete Help Topic</h2>

				<div class='error_message' style='display:none; margin:10px 10px 0 10px;'>
					<div class='tl'></div><div class='tr'></div><div class='bl'></div><div class='br'></div>
					<div class='message_content'>
						<h2>Could not create Help Topic</h2>
						<p>This help topic could not be created because:</p>
						<ul></ul>
					</div>
				</div>

				<form method='get' action='#' class='delete_topic_form'>
					<div class='form'>
						<input type='hidden' class='id' name='id'/>
						<div class='row'>" . html::label('Deletion Method', 'method').html::selectbox('method', array('concat' => 'Concat children to this ones parent (including articles)', 'scrub' => 'Scrub all Children (including articles)')) . "</div>
						<div class='submitrow'><a href='#' class='_deleteTopic_submit'>Delete Topic</a></div>
					</div>
				</form>
			") . ", 'add_help_topic_diag');

			$('.add_help_topic_diag .delete_topic_form .id').val($(this).parents('.topic').find('._id').text());
		});

		$(document).on('click', '._deleteTopic_submit', function(event){
			event.preventDefault();
			//console.log('form', $('.delete_topic_form').serialize());
			$.post('/help/remove_topics', $('.delete_topic_form').serialize(), function(data){
				if(!data.success){
					//console.log('length', data.errors.length);
					for(var i=0;i<data.errors.length;i++){
						$('.add_help_topic_diag .error_message ul').append($('<li>').html(data.errors[i]));
					}
					$('.add_help_topic_diag .error_message').addClass('error_message_curved');
					$('.add_help_topic_diag .error_message').css({ 'display': 'block' });
				}else{
					jQuery(document).trigger('close.facebox');
					selectedTopic.parents('.topic').fadeOut('slow', function(){
						$(this).remove();
					});
				}
			}, 'json');
			$.facebox.close();
		});
	});
") ?>

<div class="container_16 help_topic_admin_body">

	<div class="grid_16 alpha omega">
		<div class='help_topic_admin_bar'><h1 class='top_head'>Help Topics</h1>
		<div class='right_bar'>
			<div class='search_form'>
				<form method='get' action='<?php echo glue::url()->create('SELF') ?>'><?php echo html::textfield('help_query', glue::http()->param('help_query', ''), array( 'class' => 'search_input' )) ?>
				<?php echo html::submitbutton('Search', array( 'class' => 'invisible_submit' ))?></form>
			</div>
			<a href='<?php echo glue::url()->create('/help/add_topic') ?>' class='create_topic'>Add new topic</a>
		</div>
		<div class="clear"></div></div>
	</div>

	<div class="grid_16 alpha omega">
		<?php if($items->count() > 0){ ?>
		<table class="help_item_list">
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
					<a href="<?php echo glue::url()->create('/help/edit_topic', array( 'id' => strval($model->_id) )) ?>"><?php echo $model->title ?></a></td>
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