<div class='help_article_create_body'>
	<h1 class='head'><?php if($model->getIsNewRecord()): echo "Create New Help Article"; else: echo "Edit Help Article"; endif ?></h1>
	<div class='form_outer'><?php echo $this->renderPartial('help/_article_form', array( 'model' => $model )) ?></div>
</div>