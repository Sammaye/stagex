<?php

namespace app\models; 

class videoPlayer extends \glue\Widget{

	public $width;
	public $height;

	public $docDim = false;
	public $embedded = false;

	public $mp4;
	public $ogg;

	function render(){
		
		$this->mp4 = 'http://videos.stagex.co.uk/'.pathinfo($this->mp4, PATHINFO_BASENAME);
		$this->ogg = 'http://videos.stagex.co.uk/'.pathinfo($this->ogg, PATHINFO_BASENAME);		
		
		if(glue::session()->authed){
			if(glue::user()->useDivx){
				$this->divxPlayer();
			}else
				$this->mediaElementPlayer();
		}else
			$this->mediaElementPlayer();
	}

	function divxPlayer(){
		?>
		<object width="<?php echo $this->width ?>" height="<?php echo $this->height ?>" data="<?php echo $this->mp4 ?>" id="ie_plugin" classid="clsid:67DABFBF-D0AB-41fa-9C46-CC0F21721616">
		<param value="http://go.divx.com/plugin/DivXBrowserPlugin.cab" name="codebase">
		<param value="<?php if((glue::user()->autoplayVideos || !glue::session()->authed)): echo "true"; else: echo "false"; endif; ?>" name="autoPlay">
		<param value="<?php echo $this->mp4 ?>" name="src">
		<embed width="<?php echo $this->width ?>" height="<?php echo $this->height ?>" pluginspage="http://go.divx.com/plugin/download/" type="video/divx" src="<?php echo $this->mp4 ?>" id="np_plugin">
		</object>
		<?php
	}

	function mediaElementPlayer(){
		glue::$controller->jsFile('/js/MediaElement/mediaelement-and-player.min.js');
		glue::$controller->cssFile('/js/MediaElement/mediaelementplayer.css');

		if($this->docDim):
			glue::$controller->js('play_video', "
				$(function(){
					$('video').mediaelementplayer({
						'videoHeight': $(window).height()-5,
						'videoWidth': $(window).width()-5
					});

					$('video').attr('width', $(window).width()-5);
					$('video').attr('height', $(window).height()-5);
					//player.play();
				});
			");
		else:
			glue::$controller->js('play_video', "
				$(function(){
					var player  = $('video').mediaelementplayer();
				});
			");
		endif;
		?>
		<video width="<?php echo $this->width ?>" height="<?php echo $this->height ?>" <?php if((glue::user()->autoplayVideos || !glue::session()->authed) && !$this->embedded): echo "autoplay"; endif; ?> controls="controls" preload="none">
		    <source type="video/mp4" src="<?php echo $this->mp4 ?>" />
		    <source type="video/ogg" src="<?php echo $this->ogg ?>" />
		    <object width="320" height="240" type="application/x-shockwave-flash" data="/js/MediaElement/flashmediaelement.swf">
		        <param name="movie" value="/js/MediaElement/flashmediaelement.swf" />
		        <param name="flashvars" value="controls=true&amp;file=<?php echo $this->mp4 ?>" />
		        <!-- <img src="#" width="320" height="240" title="No video playback capabilities" /> -->
		    </object>
		</video>
		<?php
	}
}