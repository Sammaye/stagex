<?php

use \glue\Html;

glue::$controller->jsFile("/js/jquery.expander.js");
glue::$controller->jsFile('/js/views/subscribeButton.js');

glue::$controller->js('profile', "
	$(function(){
		$('.expandable').expander({slicePoint: 90});
		$('.subscribe_widget').subscribeButton();
	});

	$(document).on('click', '.expand_user_about', function(event){
		event.preventDefault();
		$('.mini_about').hide();
		$('.full_about').show();
	});

	$(document).on('click', '.shrink_user_about', function(event){
		event.preventDefault();
		$('.full_about').hide();
		$('.mini_about').show();
	});
");

$this->beginPage() ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta http-equiv="content-language" content="en"/>

		<link rel="shortcut icon" href="/images/favicon.ico" />

		<title><?php echo Html::encode($this->title) ?></title>

		<?php
			echo Html::jsFile('/js/jquery.js')."\n";
			echo Html::jsFile('/js/jquery-ui.js')."\n";

			echo Html::jsFile('/js/common.js')."\n";

			echo Html::cssFile("/css/bootstrap.css")."\n";
			echo Html::cssFile("/css/main.css")."\n";
			echo Html::cssFile("/css/jquery-ui/jquery-ui.css")."\n";

			$this->js('ga_script', "var _gaq = _gaq || [];
			  _gaq.push(['_setAccount', 'UA-31049834-1']);
			  _gaq.push(['_trackPageview']);

			  (function() {
			    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		  	})();", self::HEAD);

			$this->js('gplus_one', "(function() {
		    	var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
		    	po.src = 'https://apis.google.com/js/plusone.js';
		    	var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
		  	})();");

			$this->head();
		?>
	</head>
	<body>
		<?php $this->beginBody() ?>
			<?php app\widgets\Menu::widget(); ?>
			<div class='userbody grid-container'>
				<?php app\widgets\UserMenu::widget(array('tab'=>$this->tab)) ?>
				<div class='profile_page grid-col-41'>
				
				<div class='top' style='position:relative; min-height:125px;'>
					<div class='user_image' style='position:absolute;width:125px;height:125px;top:0;left:0;'><img src="<?php echo $user->getAvatar(125, 125); ?>" alt='thumbnail' style='border-radius:5px;'/></div>
					<div class='user_about' style='margin-left:140px;min-height:95px;'>
						<h1 class='username' style='margin-top:0;'><?php echo $user->getUsername() ?></h1>
						<div class='expandable_small'>
							<div class='mini_about'><?php echo substr(htmlspecialchars($user->about), 0, 80); if($user->about): echo '...'; endif; echo html::a(array('href' => '#', 'text' => 'About this user', 'class' => 'expand_user_about')) ?></div>
							<div class='full_about' style='display:none;'>
								<?php if($user->getAbout()){ ?>
								<p><?php echo $user->getAbout(); ?></p>
								<?php } ?>
	
								<?php if(count($user->external_links)>0){ ?>
								<div style='margin-bottom:15px;'>
									<?php $urls = array_chunk(is_array($user->external_links) ? $user->external_links : array(), 3); ?>
									<?php for($i = 0, $size= count($urls); $i < $size; $i++){ ?>
										<ul class='user_profile_url_list'>
										<?php for($j = 0, $url_s = count($urls[$i]); $j < $url_s; $j++){ $row = $urls[$i][$j]; ?>
										<li><?php echo html::a(array('href' => $row['url'], 'text' => $row['title'] ? $row['title'] : $row['url'], 'rel' => 'nofollow')) ?></li>
										<?php } ?>
										</ul>
									<?php } ?>
								</div>
								<?php } ?>
	
								<div class='user_details'>
								<?php if(($user->profile_privacy['gender'] != 1 || glue::session()->user->_id == $user->_id) && $user->gender): 
									?><div><b>Gender:</b> <?php echo $user->gender == 'm' ? "Male" : "Female" ?></div><?php endif; ?>
								<?php if(($user->profile_privacy['birthday'] != 1 || glue::session()->user->_id == $user->_id)
									&& $user->birth_day && $user->birth_month && $user->birth_year): 
									?><div><b>Birthday:</b> <?php echo date('d M Y', mktime(0, 0, 0, $user->birth_month, $user->birth_day, $user->birth_year))?></div><?php endif; ?>
								<?php if(($user->profile_privacy['country'] != 1 || glue::session()->user->_id == $user->_id) && $user->country): 
									?><div><b>Country:</b> <?php $countries = new GListProvider('countries', array("code", "name")); echo $user->country ? $countries[$user->country] : "N/A"; ?></div><?php endif; ?>
								<div><b>Date Joined:</b> <?php echo date('d M Y', $user->getTs($user->created))?></div>
								</div>
								<div class='shrink_about'><?php echo html::a(array('href' => '#', 'text' => 'Show Less', 'class' => 'shrink_user_about'))?></div>
							</div>
						</div>
						<div class="subscribe_widget" data-user_id="<?php echo $user->_id ?>" style='margin:10px 0 0 0;'>
							<?php if(glue::session()->authed&&!glue::auth()->check(array('^'=>$user))){ ?>
							<?php if(app\models\Follower::isSubscribed($user->_id)){ ?>
							<button type="button" class='unsubscribe button btn btn-error' style='margin-right:15px;'>Unsubscribe</button>
							<?php }else{ ?>
							<button type="button" class='subscribe btn btn-primary button' style='margin-right:15px;'>Subscribe</button>
							<?php } ?>
							<?php } ?>			
							<span class="follower_count text-muted"><?php echo $user->totalFollowers ?> Subscribers</span>
						</div>							
					</div>
				</div>		
				<div class="user_profile_tabs">
					<ul class="nav nav-tabs">
					<li class="<?php echo $page=='stream'?'active':'' ?>"><?php echo html::a(array('href'=>array('/user/view','id'=>$user->_id),'text'=>'Stream')) ?></li>
					<li class="<?php echo $page=='videos'?'active':'' ?>"><a href="<?php echo glue::http()->url('/user/viewVideos',array('id'=>$user->_id)) ?>">Videos <span class="badge"><?php echo $user->totalUploads ?></span></a></li>
					<li class="<?php echo $page=='playlists'?'active':'' ?>"><a href="<?php echo glue::http()->url('/user/viewPlaylists', array('id'=>$user->_id)) ?>">Playlists <span class="badge"><?php echo $user->totalPlaylists ?></span></a></li>
					</ul>					
				</div>			
				<?php echo $content ?>
				</div>
				<div class="clear"></div>
			</div>

			<div id="mainSearch_results"></div>
			<div id="user_video_results"></div>
		<?php $this->endBody() ?>
	</body>
</html>
<?php $this->endPage() ?>