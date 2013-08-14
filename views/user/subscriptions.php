<?php

use glue\Html;

$this->js('user.unsubscribe', "

		$(document).on('click', '.subscribe', function(event){
			event.preventDefault();

			var el = $(this),
				id = el.parents('.subscription').data('id');
			$.getJSON('/user/subscribe', {id: id}, function(data){
				if(data.success){
					el.removeClass('green_css_button subscribe').addClass('grey_css_button unsubscribe').html('Unsubscribe');
				}else{}
			});
		});

		$(document).on('click', '.unsubscribe', function(event){
			event.preventDefault();

			var el = $(this),
				id = el.parents('.subscription').data('id');
			$.getJSON('/user/unsubscribe', {id: id}, function(data){
				if(data.success){
					el.removeClass('grey_css_button unsubscribe').addClass('green_css_button subscribe').html('Subscribe');
				}else{

				}
			});
		});

		var searchTimer = null,
			lastVal = null;

		$(function(){
			start_search_timer();
		});

		$(document).on('click', '#Subscriber_search_submit', function(event){
			event.preventDefault();
			search_subscriptions(true);
		});

		$(document).on('click', '.list .GListView_Pager a', function(event){
			event.preventDefault();
			search_subscriptions(false, $(this).attr('href').replace(/#page_/, ''));
		});

		function search_subscriptions(refresh, page){
			var act_page = 1;
			if(!refresh){
				act_page = page;
			}
			$('.user_subscription_list').load('/user/search_subscribers', { query: $('#Search_Subscriptions').val(), page: act_page }, function(data){});
		}

		function start_search_timer(){
			if($('#Search_Subscriptions').val() != lastVal){
				lastVal = $('#Search_Subscriptions').val();
				search_subscriptions(true);
			}
			searchTimer=setTimeout('start_search_timer()',1000);
		}
	");
?>
<div class="followers_page">

	<div class="header" style='margin:20px 0;'>   
    	<div class='search form-search'>
		<?php $form = Html::form(array('method' => 'get')); ?>
			<div class="search_input"><?php echo html::textfield('search',htmlspecialchars(glue::http()->param('query',null)),array('placeholder'=>'Search Subscribers')) ?></div>
			<button class="submit_search"><span>&nbsp;</span></button>
		<?php $form->end() ?>
		</div>    	
		<div class="clear"></div>
    </div>

	<div class='user_subscription_list'>
	<?php if(glue::user()->totalFollowing > 0){
		ob_start();
		?> <div class='list' style='padding:10px 0;'>{items}<div style='margin-top:7px;'>{pager}<div class="clear"></div></div></div> <?php
		$template = ob_get_contents();
		ob_end_clean();
		glue\widgets\ListView::widget(array(
				'pageSize'	 => 20,
				"cursor"	 => app\models\Follower::model()->find(array('fromId'=>glue::user()->_id))->sort(array('username'=>-1)),
				'template' 	 => $template,
				'itemView' => 'user/_subscription.php',
				'pagerCssClass' => 'grid_list_pager'
		));
	}else{
		?><div class='no_results_found'>
			You have no Subscriptions! Subscribe to a user to keep upto date with that users activity.
		</div><?php
	}
	?></div>
</div>
