<?php echo app\widgets\Videoplayer::run(array(
	"mp4"=>$model->mp4, 
	"ogg"=>$model->ogg,
	"width"=>970, 
	"height"=>444, 
	'docDim' => true, 
	'embedded' => true 
));