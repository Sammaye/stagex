<?php

namespace app\widgets;

use glue,
	\glue\Html;

class Menu extends \glue\Widget{

	function render(){ //var_dump(glue::user()); ?>
	
<div class="menubar">
  <!-- Brand and toggle get grouped for better mobile display -->
  <div class="grid-container">
  <div class="menubar-header">
    <a class="menubar-brand" href="<?php echo glue::http()->url('/') ?>"><img src="/images/main_logo.png" alt="StageX"/></a>
  </div>
	<?php if(glue::http()->path()!=='search'){ ?>
  <form class="menubar-form" action="<?php echo glue::http()->url('/search') ?>">
	<div class="form-search">
		<input type="text" name="query" class="form-search-input"/>
		<button type="submit" class="btn btn-primary"><span>&nbsp;</span></button>
	</div>
  </form>
  <div class="menubar-nav">
    <a href="<?php echo Glue::http()->url("/search", array('filter_trype' => 'video', 'filter_time' => 'month', 'orderby' => 'rating')) ?>">Browse</a>
  </div>
  <?php } ?>

	<div class="menubar-right">
	  <div class="menubar-nav">
		<?php if(glue::session()->authed){ ?>
		<?php $newNotifications = \app\models\Notification::getNewCount_Notifications(); ?>
	  	<a href="/stream/notifications" class="notification <?php if($newNotifications > 0): echo "new_notifications"; endif; ?>">
	  		<?php if($newNotifications > 100){ ?>
				100+
			<?php }else{
				echo $newNotifications;
			} ?>
		</a>
		<img src="<?php echo glue::user()->getAvatar(30,30) ?>" style="width:30px;height:30px;"/>				
	    <a href="<?php echo Glue::http()->url("/user/videos", array('id' => glue::user()->_id)) ?>"><?php echo glue::user()->getUsername() ?></a>
	    <a href="<?php echo Glue::http()->url("/help") ?>">Help</a>
		<?php }else{ ?>	  
	    <a href="<?php echo Glue::http()->url("/user/create") ?>">Signup</a>
	    <a href="<?php echo Glue::http()->url("/user/login") ?>">Login</a>
	    <a href="<?php echo Glue::http()->url("/help") ?>">Help</a>
	    <?php } ?>
	  </div>
  	</div>
  	</div>
</div>	

		<?php if(html::hasFlashMessage()){
			?><div style='width:980px; margin:15px auto;'><?php 
			echo html::getFlashMessage();
			?></div><?php 
		}
	}
}