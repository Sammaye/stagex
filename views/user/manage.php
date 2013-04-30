<div style='width:970px; margin:auto; margin-top:20px;'>
	<?php $this->widget('glue/widgets/GGridView.php', array(
		'cursor' => User::model()->search(),
		'columns' => array(
			array(
				'type' => 'checkbox'
			),
			'_id' => 'ID',
			'username',
			'email',
			'group',
			array(
				'label' => 'Date Created',
				'value' => 'date("d-m-Y H:i:s", !empty($doc->ts) ? $doc->ts->sec : time())'
			),
			array(
				'type' => 'button'
			)
		)
	)); ?>
</div>