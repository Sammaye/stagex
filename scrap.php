<?php

<div class="datevalue-value"><?php echo $form->textfield('value[]', null, 'form-control') ?></div>
				<div class="yesnovalue-value"><?php echo $form->selectbox('value[]', array('No', 'Yes'), null, array('class' => 'form-control')) ?></div>
				<div class="licencevalue-value"><?php echo $form->selectbox('value[]', app\models\Video::licences(), null, array('class' => 'form-control')) ?></div>
				<div class="categoryvalue-value"><?php echo $form->selectbox('value[]', app\models\Video::categories('selectBox'), null, array('class' => 'form-control'))?></div>
				<div class="listingvalue-value"><?php echo $form->selectBox('value[]', array('Public', 'Unlisted', 'Private'), null, array('class' => 'form-control')) ?></div>
