<div class='helptopic_body'>
	<h1 class='head'><?php if($model->getIsNewRecord()): echo "Create Help Topic"; else: echo "Edit Help Topic"; endif ?></h1>
	<div class='form_outer'><?php $this->partialRender('help/_topic_form', array( 'model' => $model )) ?></div>
</div>