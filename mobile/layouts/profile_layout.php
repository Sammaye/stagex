<?php

use \glue\Html;

glue::controller()->jsFile("/js/jquery.expander.js");
glue::controller()->jsFile('/js/subscribeButton.js');

glue::controller()->js('profile', "
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
<!DOCTYPE html>
<html>
<head>
<title><?php echo Html::encode($this->title) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
<link rel="shortcut icon" href="/images/favicon.ico" />
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<link href="/css/bootstrap.min.css" rel="stylesheet">
<link type="text/css" rel="stylesheet" href="/css/jquery-ui/jquery-ui.css" />
<link type="text/css" rel="stylesheet" href="/css/mmenu.css" />
<link type="text/css" rel="stylesheet" href="/css/mobile.css" />
<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
<script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
<![endif]-->
<?php $this->head(); ?>
</head>
<body>
<?php $this->beginBody() ?>



	<?php echo app\widgets\MobileMenu::run(array('tab' => $this->tab)) ?>
	<div class="container">
	<div class="col-md-12">
				<div class='profile_page grid-col-41'>
				
				<div class='top'>
					<div class='user_image'><img src="<?php echo $user->getAvatar(125, 125); ?>" alt='thumbnail'/></div>
					<div class='user_about'>
						<h1 class='username'><?php echo $user->getUsername() ?></h1>
						<div class='expandable_small'>
							<div class='mini_about'><?php echo substr(htmlspecialchars($user->about), 0, 80); if($user->about): echo '...'; endif; echo html::a(array('href' => '#', 'text' => 'About this user', 'class' => 'expand_user_about')) ?></div>
							<div class='full_about' style='display:none;'>
								<?php if($user->getAbout()){ ?>
								<p><?php echo $user->getAbout(); ?></p>
								<?php } ?>
	
								<?php if(count($user->external_links)>0){ ?>
								<div class="profile_url_list">
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
						<div class="subscribe_widget" data-user_id="<?php echo $user->_id ?>">
							<?php if(glue::session()->authed&&!glue::auth()->check(array('^'=>$user))){ ?>
							<?php if(app\models\Follower::isSubscribed($user->_id)){ ?>
							<button type="button" class='unsubscribe button btn btn-danger'>Unsubscribe</button>
							<?php }else{ ?>
							<button type="button" class='subscribe btn btn-primary button'>Subscribe</button>
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
	</div>
	</div>
	<div id="mainSearch_results"></div>
	<script src="/js/jquery.js"></script>
	<script type="text/javascript" src="/js/jquery-ui.min.js"></script>
	<script type="text/javascript" src="/js/mmenu.js"></script>
	<script src="/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="/js/common.js"></script>
	<script type="text/javascript">
	$(function() {
		$('nav#menu').mmenu();
	});
	</script>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>