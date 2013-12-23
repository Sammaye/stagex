<?php

namespace app\widgets;

use glue,
	\glue\Html;

class Menu extends \glue\Widget{

	function render(){ ?>
	
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
	
	function old_render(){ ?>
		<div class='menu' style=''>
			<div class="menu_left">
				<ul>
					<li class='logo'><a href='<?php echo glue::http()->url('/') ?>'><img src='/images/main_logo.png' alt='StageX'/></a></li>
					<li class="search">
						<form action="/search" method="get"><div class="search_input">
							<?php
								$val = '';
								if(preg_match('/\/search/i', $_SERVER['REQUEST_URI'])){
									$val = glue::http()->param('term', '');
								}

								glue::$controller->js('submitsearch_click', "
									$(function(){
										$('.submit_search').click(function(event){
											event.preventDefault();
											$(this).parents('form').submit();
										});
									});
								");

								\app\widgets\Jqautocomplete::widget(array(
									'attribute' => 'term',
									'value' => $val,
									'options' => array(
										'appendTo' => '#mainSearch_results',
										'source' => '/search/suggestions',
										'minLength' => 2,
									),
									'renderItem' => "
										return $( '<li></li>' )
											.data( 'item.autocomplete', item )
											.append( '<a class=\'content\'>' + item.label + '</a>' )
											.appendTo( ul );"
								)) ?></div><button class="submit_search"><span>&nbsp;</span></button>
						</form>
					</li>
					<li class='link'><a href="<?php echo Glue::http()->url("/video") ?>">Browse</a></li>
				</ul>
			</div>
			<?php if(isset($_SESSION)){ ?>
				<div class="menu_right">
					<ul>
						<?php if(glue::session()->authed){ ?>
							<li class="notification_outer">

								<?php $newNotifications = \app\models\Notification::getNewCount_Notifications(); ?>
								<a class='notification_area <?php if($newNotifications > 0): echo "new_notifications"; endif; ?>' href='/stream/notifications'>
								<?php if($newNotifications > 100){ ?>
									100+
								<?php }else{
									echo $newNotifications;
								} ?>
								</a>
							</li>
							<li><img alt='thumbnail' class='user_image' src='<?php echo glue::user()->getAvatar(30,30) ?>'/></li>
							<li><a href="<?php echo Glue::http()->url("/user/videos", array('id' => glue::user()->_id)) ?>"><?php echo glue::user()->getUsername() ?></a></li>
							<li><a href="<?php echo Glue::http()->url("/help") ?>">Help</a></li>
							<li><a target='_blank' href="https://getsatisfaction.com/stagex">Report Bug</a></li>
						<?php }else{ ?>
							<li><a href="<?php echo Glue::http()->url("/user/create") ?>">Create Account</a></li>
							<li><a href="<?php echo Glue::http()->url("/user/login") ?>">Sign In</a></li>
							<li><a href="<?php echo Glue::http()->url("/help") ?>">Help</a></li>
							<li><a target='_blank' href="https://getsatisfaction.com/stagex">Report Bug</a></li>
						<?php } ?>
					</ul>
				</div>
				<div class="clear"></div>
			<?php } ?>
		</div><?php 	
	}
}