<?php

namespace app\widgets;

use glue,
	\glue\Html;

class Menu extends \glue\Widget{

	function render(){ ?>
			<div class='presence_bar'>
				<div class='grid_970'>
				<div class="presence_bar-topLeft">
					<ul>
						<li class='logo'><a href='<?php echo glue::http()->createUrl('/') ?>'><img src='/images/main_logo.png' alt='StageX'/></a></li>
						<li class="presenceBar_mainSearch_li">
							<form action="/search" method="get"><div class="search_bar">
									<label><?php
									$val = '';
									if(preg_match('/\/search/i', $_SERVER['REQUEST_URI'])){
										$val = glue::http()->param('mainSearch', '');
									}

									glue::$controller->js('mainSearch_click', "
										$(function(){
											$('#mainSearch_submit').click(function(event){
												event.preventDefault();
												$(this).parents('form').submit();
											});
										});
									");

									\app\widgets\Jqautocomplete::widget(array(
										'attribute' => 'mainSearch',
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
												.appendTo( ul );
											"
									)) ?></label></div><a href='#' class='search_submit' id='mainSearch_submit'><img src='/images/search_icon_small.png' alt='search'/></a>
							</form>
						</li>

						<li class='link_item'><a href="<?php echo Glue::http()->createUrl("/video") ?>">Videos</a></li>
					</ul>
				</div>
				<?php if(isset($_SESSION)){ ?>
				<div class="presence_bar-topRight" style='margin-top:5px;'>
					<ul>
						<?php if(glue::session()->authed){ ?>
							<li>

								<?php $newNotifications = \app\models\Notification::getNewCount_Notifications(); ?>
								<a class='notification_area <?php if($newNotifications > 0): echo "new_nots"; endif; ?>' href='/stream/notifications'>
								<?php
								if($newNotifications > 100){ ?>
									100+
								<?php }else{
									echo $newNotifications;
								} ?>
								</a>
							</li>
							<li><img alt='thumbnail' class='user_image' src='<?php echo glue::user()->getPic(30,30) ?>'/></li>
							<li><a href="<?php echo Glue::http()->createUrl("/user/videos", array('id' => glue::user()->_id)) ?>"><?php echo glue::user()->getUsername() ?></a></li>
							<li><a href="<?php echo Glue::http()->createUrl("/help") ?>">Help</a></li>
							<li><a target='_blank' href="https://getsatisfaction.com/stagex">Report Bug</a></li>
						<?php }else{ ?>
							<li><a href="<?php echo Glue::http()->createUrl("/user/create") ?>">Create Account</a></li>
							<li><a href="<?php echo Glue::http()->createUrl("/user/login") ?>">Sign In</a></li>
							<li><a href="<?php echo Glue::http()->createUrl("/help") ?>">Help</a></li>
							<li><a target='_blank' href="https://getsatisfaction.com/stagex">Report Bug</a></li>
						<?php } ?>
					</ul>
				</div>
				<div class="clearer"></div>
				</div>
				<?php } ?>
			</div>

			<?php if(html::hasFlashMessage()){
				echo html::getFlashMessage();
			}
	}
}