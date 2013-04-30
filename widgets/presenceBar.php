<?php
class presenceBar extends GWidget{

	function render(){ ?>
			<div class='presence_bar'>
				<div class='grid_970'>
				<div class="presence_bar-topLeft">
					<ul>
						<li class='logo'><a href='<?php echo glue::url()->create('/') ?>'><img src='/images/main_logo.png' alt='StageX'/></a></li>
						<li class="presenceBar_mainSearch_li">
							<form action="/search" method="get"><div class="search_bar">
									<label><?php
									$val = '';
									if(preg_match('/\/search/i', $_SERVER['REQUEST_URI'])){
										$val = glue::http()->param('mainSearch', '');
									}

									glue::clientScript()->addJsScript('mainSearch_click', "
										$(function(){
											$('#mainSearch_submit').click(function(event){
												event.preventDefault();
												$(this).parents('form').submit();
											});
										});
									");

									$this->widget('application/widgets/Jqautocomplete.php', array(
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

						<li class='link_item'><a href="<?php echo Glue::url()->create("/video") ?>">Videos</a></li>
					</ul>
				</div>
				<?php if(isset($_SESSION)){ ?>
				<div class="presence_bar-topRight" style='margin-top:5px;'>
					<ul>
						<?php if($_SESSION['logged']){ ?>
							<li>

								<?php $newNotifications = Notification::getNewCount_Notifications(); ?>
								<a class='notification_area <?php if($newNotifications > 0): echo "new_nots"; endif; ?>' href='/stream/notifications'>
								<?php
								if($newNotifications > 100){ ?>
									100+
								<?php }else{
									echo $newNotifications;
								} ?>
								</a>
							</li>
							<li><img alt='thumbnail' class='user_image' src='<?php echo glue::session()->user->getPic(30,30) ?>'/></li>
							<li><a href="<?php echo Glue::url()->create("/user/videos", array('id' => glue::session()->user->_id)) ?>"><?php echo glue::session()->user->getUsername() ?></a></li>
							<li><a href="<?php echo Glue::url()->create("/help") ?>">Help</a></li>
							<li><a target='_blank' href="https://getsatisfaction.com/stagex">Report Bug</a></li>
						<?php }else{ ?>
							<li><a href="<?php echo Glue::url()->create("/user/create") ?>">Create Account</a></li>
							<li><a href="<?php echo Glue::url()->create("/user/login") ?>">Sign In</a></li>
							<li><a href="<?php echo Glue::url()->create("/help") ?>">Help</a></li>
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