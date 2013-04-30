<table cellpadding="0" cellspacing="0" style='width:100%; width:600px; margin:auto;'>
	<tr>
		<td style='font-family:Arial; font-size:12px;'>
			<p><b>Hello <?php echo $username ?></b></p>

			<p>A user has logged onto your StageX account using these device credentials:</p>

			<table style='margin:15px 0px; width:100%;' border="1" cellspacing="0" cellpadding="4">
				<thead>
					<tr>
						<td style='font-size:12.8px; padding:5px; background:#C3D9FF; font-weight:bold;'>IP</td>
						<td style='font-size:12.8px; padding:5px; background:#C3D9FF; font-weight:bold;'>Device Signature</td>
						<td style='font-size:12.8px; padding:5px; background:#C3D9FF; font-weight:bold;'>Last Active</td>
						<td style='font-size:12.8px; padding:5px; background:#C3D9FF; font-weight:bold;'>Last Access</td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td style='font-size:12.8px; padding:5px;'><?php echo $ip ?></td>
						<td style='font-size:12.8px; padding:5px;'>
							<?php
							if($agent){
								$brows = get_browser($agent);
							}
							echo $brows ? $brows->parent." on ".$brows->platform : "FATAL: NOT SURE (RAW: ".$agent.")";
							?>
						</td>
						<td style='font-size:12.8px; padding:5px;'><?php echo date('d-M-Y h:i:s', $last_active->sec) ?></td>
						<td style='font-size:12.8px; padding:5px;'><?php echo $last_request ?></td>
					</tr>
				</tbody>
			</table>

			<p>Note: Please be advised that if you see "FATAL: NOT SURE" within the "Device Signature" field this means
			the device could not be identified. This might be bengin but it might also be a hacker hiding his footprints.</p>
		</td>
	</tr>
</table>