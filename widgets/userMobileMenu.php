<?php

namespace app\widgets;

use Glue;
use glue\Widget;
use glue\Html;

class userMobileMenu extends Widget
{
	public $tab;
	
	function render()
	{
		$menujs = <<<JS
$('.user_menu_header .glyphicon').on('click', function(e){
	if($('.user_menu_header_search').css('display') == 'none'){
		$('.user_menu_header_search').css({display: 'block'});
	}else{
		$('.user_menu_header_search').css({display: 'none'});
	}
});
JS;
		glue::controller()->js('menu', $menujs); ?>
		<div class="user_menu_header">
			<a href="#menu"></a>
			<span class="glyphicon glyphicon-search"></span>
			<?php echo Html::encode(Glue::controller()->title) ?>
		</div>
		<div class="user_menu_header_search">
		<form class="" action="<?php echo glue::http()->url('/search') ?>" role="search">
		<div class="form-group"><input type="text" class="form-control" placeholder="Search"></div>
		<button type="submit" class="btn btn-default">Submit</button>
		</form>
		</div>
		<nav id="menu" class="user_menu">
			<?php if(!glue::session()->authed){ ?>
			<ul>
				<li><a href="<?php echo glue::http()->url('/user/create') ?>">Create Account</a></li>
				<li><a href="<?php echo glue::http()->url('/user/login') ?>">Login</a></li>
				<li><a href="<?php echo glue::http()->url('/help') ?>">Help</a></li>
			</ul>
			<?php }else{ ?>
			<ul>
				<li>
			    <a href="<?php echo Glue::http()->url("/user/videos", array('id' => glue::user()->_id)) ?>">
			    <img src="<?php echo glue::user()->getAvatar(30,30) ?>" style="width:30px;height:30px;margin-right:6px;"/>
			    <b><?php echo glue::user()->getUsername() ?></b>
			    </a>
				</li>			
				<li <?php echo $this->tab == "news_feed" ? "class='mm-selected'" : "" ?>><a href="<?php echo glue::http()->url('/stream/news') ?>">News Feed</a></li>
				<li <?php echo $this->tab == "watch_later" ? "class='mm-selected'" : "" ?>><a href="<?php echo glue::http()->url('/user/watchLater') ?>">Watch Later</a></li>
				<li <?php echo $this->tab == "videos" ? "class='mm-selected'" : "" ?>>
					<a href="<?php echo glue::http()->url('/user/videos') ?>">Videos</a>
					<ul>
						<li><a href="<?php echo glue::http()->url('/user/videos') ?>">Uploads</a></li>
						<li><a href="<?php echo glue::http()->url('/user/watched') ?>">Watched</a></li>
						<li><a href="<?php echo glue::http()->url('/user/rated') ?>">Liked</a></li>
						<li><a href="<?php echo glue::http()->url('/user/rated?tab=dislikes') ?>">Disliked</a></li>
					</ul>
				</li>
				<li <?php echo $this->tab == "playlists" ? "class='mm-selected'" : "" ?>>
					<a href="<?php echo glue::http()->url('/user/playlists') ?>">Playlists</a>
					<ul>
						<li><a href="<?php echo glue::http()->url('/user/playlists') ?>">My Playlists</a></li>
						<li><a href="<?php echo glue::http()->url('/user/playlistSubscriptions') ?>">Subscribed</a></li>
					</ul>
				</li>
				<li <?php echo $this->tab == "subscriptions" ? "class='mm-selected'" : "" ?>><a href="<?php echo glue::http()->url('/user/following') ?>">Following</a></li>
				<li <?php echo $this->tab == "notifications" ? "class='mm-selected'" : "" ?>><a href="<?php echo glue::http()->url('/stream/notifications') ?>">Notifications</a></li>
				<li <?php echo $this->tab == "profile" ? "class='mm-selected'" : "" ?>><a href="<?php echo glue::http()->url('/user/view') ?>">Profile</a></li>
				<li >
					<a href="<?php echo glue::http()->url('/user/settings') ?>">Settings</a>
					<ul>
						<li <?php echo $this->tab == "settings" ? "class='mm-selected'" : "" ?>><a href="<?php echo glue::http()->url('/user/settings') ?>">Account Settings</a></li>
						<li <?php echo $this->tab == "profile_settings" ? "class='mm-selected'" : "" ?>><a href="<?php echo glue::http()->url('/user/profile') ?>">Profile Settings</a></li>
						<li <?php echo $this->tab == "activity" ? "class='mm-selected'" : "" ?>><a href="<?php echo glue::http()->url('/user/activity') ?>">Account Acivity</a></li>
					</ul>				
				</li>
				<li><a href="<?php echo glue::http()->url('/help') ?>">Help</a></li>
				<li><a href="<?php echo glue::http()->url('/user/logout') ?>">Logout</a></li>
			</ul>
			<?php } ?>
		</nav>
		<?php if(html::hasFlashMessage()){
			?><div class="container"><div class="col-md-12" style=''><?php 
			echo html::getFlashMessage();
			?></div></div><?php 
		} ?>
		<?php 
	}
}