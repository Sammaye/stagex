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
	<h1>Account Activity</h1>

	<h2 class="account_activity_top">Your session</h2>

	<?php $browser = get_browser($model->sessions[session_id()]['agent']) ?>

	<table class="concurrent_sessions current_session">
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
				<td><?php echo $browser ? $browser->parent." on ".$browser->platform : "Unknown" ?></td>
				<td><?php echo $model->sessions[session_id()]['ip'] ?></td>
				<td><?php echo date('d-M-Y h:i:s', $model->sessions[session_id()]['last_active']->sec) ?></td>
				<td><?php echo $model->sessions[session_id()]['last_request'] ?></td>
			</tr>
		</tbody>
	</table>

	<h2 class="account_activity_mid">Other sessions</h2>
	<p><b>Please Note:</b> These session may not be currently active but rather dormant. To be safe you can clear dormant connections.</p>

	<table class="concurrent_sessions">
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
						<td><?php echo $brows ? $brows->parent." on ".$brows->platform : "Unknown" ?></td>
						<td><?php echo $v['ip'] ?></td>
						<td><?php echo date('d-M-Y h:i:s', $v['last_active']->sec) ?></td>
						<td><?php echo $v['last_request'] ?></td>
						<td><a href="#" class="remove_session" id="<?php echo $k ?>">End Session</a></td>
					</tr>
				<?php }
			}
		}else{ ?>
			<tr><td class="no_border"><span class='no_sess_found'>There are no concurrent sessions</span></td></tr>
		<?php } ?>
		</tbody>
	</table>
</div>
