<?php

namespace app\widgets;

use Glue;
use glue\Widget;
use glue\Html;

class userMobileMenu extends Widget
{
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
		<form class="" role="search">
		<div class="form-group"><input type="text" class="form-control" placeholder="Search"></div>
		<button type="submit" class="btn btn-default">Submit</button>
		</form>
		</div>
		<nav id="menu" class="user_menu">
			<?php if(!glue::session()->authed){ ?>
			<ul>
				<li class="selected"><a href="<?php echo glue::http()->url('/user/create') ?>">Create Account</a></li>
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
				<li class="selected"><a href="<?php echo glue::http()->url('/stream/news') ?>">News Feed</a></li>
				<li><a href="<?php echo glue::http()->url('/user/watchLater') ?>">Watch Later</a></li>
				<li>
					<a href="<?php echo glue::http()->url('/user/videos') ?>">Videos</a>
					<ul>
						<li><a href="<?php echo glue::http()->url('/user/videos') ?>">Uploads</a></li>
						<li><a href="<?php echo glue::http()->url('/user/watched') ?>">Watched</a></li>
						<li><a href="<?php echo glue::http()->url('/user/rated') ?>">Liked</a></li>
						<li><a href="<?php echo glue::http()->url('/user/rated?tab=dislikes') ?>">Disliked</a></li>
					</ul>
				</li>
				<li><a href="<?php echo glue::http()->url('/user/playlists') ?>">Playlists</a>
					<ul>
						<li><a href="<?php echo glue::http()->url('/user/playlists') ?>">My Playlists</a></li>
						<li><a href="<?php echo glue::http()->url('/user/playlistSubscriptions') ?>">Subscribed</a></li>
					</ul>				
				</li>
				<li><a href="<?php echo glue::http()->url('/user/following') ?>">Following</a></li>
				<li><a href="<?php echo glue::http()->url('/stream/notifications') ?>">Notifications</a></li>
				<li><a href="<?php echo glue::http()->url('/user/view') ?>">Profile</a></li>
				<li><a href="<?php echo glue::http()->url('/user/settings') ?>">Settings</a>
					<ul>
						<li><a href="<?php echo glue::http()->url('/user/settings') ?>">Account Settings</a></li>
						<li><a href="<?php echo glue::http()->url('/user/profile') ?>">Profile Settings</a></li>
						<li><a href="<?php echo glue::http()->url('/user/activity') ?>">Account Acivity</a></li>
					</ul>				
				</li>
				<li><a href="<?php echo glue::http()->url('/help') ?>">Help</a></li>
				<li><a href="<?php echo glue::http()->url('/user/logout') ?>">Logout</a></li>
			</ul>
			<?php } ?>
		</nav>	
		<?php 
	}
}