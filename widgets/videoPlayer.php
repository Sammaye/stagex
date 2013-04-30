<?php
class videoPlayer extends GWidget{

	public $width;
	public $height;

	public $docDim = false;
	public $embedded = false;

	public $mp4;
	public $ogg;

	function render(){
		if($_SESSION['logged']){
			if(glue::session()->user->use_divx_player){
				$this->divxPlayer();
			}else{
				$this->mediaElementPlayer();
			}
		}else{
			$this->mediaElementPlayer();
		}
	}

	function divxPlayer(){
		?>
		<object width="<?php echo $this->width ?>" height="<?php echo $this->height ?>" data="<?php echo $this->mp4 ?>" id="ie_plugin" classid="clsid:67DABFBF-D0AB-41fa-9C46-CC0F21721616">
		<param value="http://go.divx.com/plugin/DivXBrowserPlugin.cab" name="codebase">
		<param value="<?php if((glue::session()->user->auto_play_vids || !$_SESSION['logged'])): echo "true"; else: echo "false"; endif; ?>" name="autoPlay">
		<param value="<?php echo $this->mp4 ?>" name="src">
		<embed width="<?php echo $this->width ?>" height="<?php echo $this->height ?>" pluginspage="http://go.divx.com/plugin/download/" type="video/divx" src="<?php echo $this->mp4 ?>" id="np_plugin">
		</object>
		<?php
	}

	function mediaElementPlayer(){
		glue::clientScript()->addJsFile('html5_player', '/js/MediaElement/mediaelement-and-player.min.js');
		glue::clientScript()->addCssFile('html5_player', '/js/MediaElement/mediaelementplayer.css');

		if($this->docDim):
			glue::clientScript()->addJsScript('play_video', "
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
			glue::clientScript()->addJsScript('play_video', "
				$(function(){
					var player  = $('video').mediaelementplayer();
				});
			");
		endif;

		$mp4_url = 'http://videos.stagex.co.uk/'.pathinfo($this->mp4, PATHINFO_BASENAME);
		$ogg_url = 'http://videos.stagex.co.uk/'.pathinfo($this->ogg, PATHINFO_BASENAME);
		?>
		<video width="<?php echo $this->width ?>" height="<?php echo $this->height ?>" <?php if((glue::session()->user->auto_play_vids || !$_SESSION['logged']) && !$this->embedded): echo "autoplay"; endif; ?> controls="controls" preload="none">
		    <source type="video/mp4" src="<?php echo $mp4_url ?>" />
		    <source type="video/ogg" src="<?php echo $ogg_url ?>" />
		    <object width="320" height="240" type="application/x-shockwave-flash" data="/js/MediaElement/flashmediaelement.swf">
		        <param name="movie" value="/js/MediaElement/flashmediaelement.swf" />
		        <param name="flashvars" value="controls=true&amp;file=<?php echo $mp4_url ?>" />
		        <!-- <img src="#" width="320" height="240" title="No video playback capabilities" /> -->
		    </object>
		</video>
		<?php
	}
}