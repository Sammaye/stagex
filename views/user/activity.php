<?php $this->js('remove_session', "
	$(document).ready(function(){
		$('.remove_session').click(function(event){
			event.preventDefault();

			var el = $(this);
			$.getJSON('/user/removesession', {'id': $(this).attr('id')}, function(data){
				if(data.success){
					el.parents('.session_row').fadeOut('slow', function(){
						el.parents('.session_row').remove();
					});
				}
			});
		});
	});
") ?>

<div class="UserActivity_body">
	<h2>Your session</h2>

	<?php $browser = get_browser($model->sessions[session_id()]['agent']); //var_dump($browser); exit(); ?>

	<table class="table">
		<thead>
			<tr>
				<th>Device</th>
				<th>IP</th>
				<th>Last Accessed</th>
				<th>Last Request</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><?php echo $browser ? $browser->browser." on ".$browser->platform : "Unknown" ?></td>
				<td><?php echo $model->sessions[session_id()]['ip'] ?></td>
				<td><?php echo date('d-M-Y h:i:s', $model->sessions[session_id()]['last_active']->sec) ?></td>
				<td><?php echo $model->sessions[session_id()]['last_request'] ?></td>
			</tr>
		</tbody>
	</table>

	<h2>Other sessions</h2>
	<p><b>Please Note:</b> These session may not be currently active but rather dormant. To be safe you can clear dormant connections.</p>

	<table class="table">
		<thead>
			<tr>
				<th>Device</th>
				<th>IP</th>
				<th>Last Accessed</th>
				<th>Last Request</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
		<?php if(count($model->sessions) > 1){ ?>
			<?php foreach($model->sessions as $k=>$v){
				if($k != session_id()){
					if($v['agent']){
						$brows = get_browser($v['agent']);
					} ?>
					<tr class="session_row">
						<td><?php echo $brows ? $brows->browser." on ".$brows->platform : "Unknown" ?></td>
						<td><?php echo $v['ip'] ?></td>
						<td><?php echo date('d-M-Y h:i:s', $v['last_active']->sec) ?></td>
						<td><?php echo $v['last_request'] ?></td>
						<td><a href="#" class="remove_session" id="<?php echo $k ?>">End Session</a></td>
					</tr>
				<?php }
			}
		}else{ ?>
			<tr class="no_results"><td colspan="5">There are no concurrent sessions</td></tr>
		<?php } ?>
		</tbody>
	</table>
</div>
