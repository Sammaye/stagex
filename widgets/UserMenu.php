<?php

namespace app\widgets;

use glue,
	\glue\Html;

class UserMenu extends \glue\Widget{

	public $tab;
	
	function render(){ ?>
		<div class='user_side_menu'>
		<ul>
		<li><a href='<?php echo glue::http()->url('/stream/news') ?>' <?php echo $this->tab == "news_feed" ? "class='selected'" : "" ?>>News Feed</a></li>
								<li class='wl_item'><a href='<?php echo glue::http()->url('/user/watchLater') ?>' <?php echo $this->tab == "watch_later" ? "class='selected'" : "" ?>>Watch Later</a></li>
								<li><a href='<?php echo glue::http()->url('/user/videos') ?>' <?php echo $this->tab == "videos" ? "class='selected'" : "" ?>>Videos
								<span class="badge"><?php echo glue::user()->totalUploads ?></span></a></li>
								<li><a href='<?php echo glue::http()->url('/user/playlists') ?>' <?php echo $this->tab == "playlists" ? "class='selected'" : "" ?>>Playlists
								<span class="badge"><?php echo glue::user()->totalPlaylists ?></span></a></li>
								<li><a href='<?php echo glue::http()->url('/history/watched') ?>' <?php echo $this->tab == "watched" ? "class='selected'" : "" ?>>Watched</a></li>
								<li><a href='<?php echo glue::http()->url('/history/rated_videos') ?>' <?php echo $this->tab == "likes" ? "class='selected'" : "" ?>>Likes</a></li>
								<?php if($this->tab == "likes"){ ?>
									<li class='indented_row'><a href='<?php echo glue::http()->url('/history/rated_videos') ?>' <?php echo $this->subtab == "liked_videos" ? "class='selected'" : "" ?>>Videos</a></li>
									<li class='indented_row'><a href='<?php echo glue::http()->url('/history/rated_playlists') ?>' <?php echo $this->subtab == "liked_playlists" ? "class='selected'" : "" ?>>Playlists</a></li>
								<?php } ?>
								<li><a href='<?php echo glue::http()->url('/user/follwoing') ?>' <?php echo $this->tab == "subscriptions" ? "class='selected'" : "" ?>>Following
								<span class="badge"><?php echo glue::user()->totalFollowing ?></span></a></li>
								<li><a href='<?php echo glue::http()->url('/stream/notifications') ?>' <?php echo $this->tab == "notifications" ? "class='selected'" : "" ?>>Notifications
								<span class="badge"><?php echo \app\models\Notification::getNewCount_Notifications() ?></span></a></li>
							</ul>
							<h3 class='head_divider'>Settings</h3>
							<ul>
								<li><a href='<?php echo glue::http()->url('/user/settings') ?>' <?php echo $this->tab == "settings" ? "class='selected'" : "" ?>>Account Settings</a></li>
								<li><a href='<?php echo glue::http()->url('/user/profile') ?>' <?php echo $this->tab == "profile" ? "class='selected'" : "" ?>>Profile Settings</a></li>
								<li><a href='<?php echo glue::http()->url('/user/activity') ?>' <?php echo $this->tab == "activity" ? "class='selected'" : "" ?>>Account Activity</a></li>
							</ul>
							<ul class='end_list'>
								<li><a href='<?php echo glue::http()->url('/user/logout') ?>'>Logout</a></li>
							</ul>
						</div>		
						<?php 
	}
}