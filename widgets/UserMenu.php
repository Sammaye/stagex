<?php

namespace app\widgets;

use glue;
use glue\Widget;
use \glue\Html;

class UserMenu extends Widget
{
	public $tab;
	
	function render()
	{ 
		?><div class='user_side_menu'>
		<?php if(glue::auth()->check(array('@'))){  ?>
			<div>
			<ul>
			<li><a href='<?php echo glue::http()->url('/stream/news') ?>' <?php echo $this->tab == "news_feed" ? "class='selected'" : "" ?>>News Feed</a></li>
			<li class='wl_item'><a href='<?php echo glue::http()->url('/user/watchLater') ?>' <?php echo $this->tab == "watch_later" ? "class='selected'" : "" ?>>Watch Later</a></li>
			<li>
				<a href='<?php echo glue::http()->url('/user/videos') ?>' <?php echo $this->tab == "videos" ? "class='selected'" : "" ?>>Videos
				<span class="badge"><?php echo glue::user()->totalUploads ?></span></a>
			</li>
			<li>	
				<a href='<?php echo glue::http()->url('/user/playlists') ?>' <?php echo $this->tab == "playlists" ? "class='selected'" : "" ?>>Playlists
				<span class="badge"><?php echo glue::user()->totalPlaylists ?></span></a>
			</li>
			<li>
				<a href='<?php echo glue::http()->url('/user/following') ?>' <?php echo $this->tab == "subscriptions" ? "class='selected'" : "" ?>>Following
				<span class="badge"><?php echo glue::user()->totalFollowing ?></span></a>
			</li>
			<li>
				<a href="<?php echo glue::http()->url('/user/followers') ?>" <?php echo $this->tab == "subscribers" ? "class='selected'" : "" ?>>Followers
				<span class="badge"><?php echo glue::user()->totalFollowers ?></span></a>
			</li>			
			<li>
				<a href='<?php echo glue::http()->url('/stream/notifications') ?>' <?php echo $this->tab == "notifications" ? "class='selected'" : "" ?>>Notifications
				<span class="badge"><?php echo \app\models\Notification::getNewCountNotifications() ?></span></a>
			</li>
			<li><a href='<?php echo glue::http()->url('/user/view') ?>' <?php echo $this->tab == "profile" ? "class='selected'" : "" ?>>Profile</a></li>								
			</ul>
			<h3 class='head_divider'>Settings</h3>
			<ul>
			<li><a href='<?php echo glue::http()->url('/user/settings') ?>' <?php echo $this->tab == "settings" ? "class='selected'" : "" ?>>Account Settings</a></li>
			<li><a href='<?php echo glue::http()->url('/user/profile') ?>' <?php echo $this->tab == "profile_settings" ? "class='selected'" : "" ?>>Profile Settings</a></li>
			<li><a href='<?php echo glue::http()->url('/user/activity') ?>' <?php echo $this->tab == "activity" ? "class='selected'" : "" ?>>Account Activity</a></li>
			</ul>
			<ul class='end_list'>
			<li><a href='<?php echo glue::http()->url('/user/logout') ?>'>Logout</a></li>
			</ul>
			</div>
		<?php }else{
			echo "&nbsp;";
		} ?>
		</div><?php 
	}
}