<?php
	glue::clientScript()->addJsScript('user.unsubscribe', "

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

	$subscriptions = Subscription::model()->find(array('from_id' => glue::session()->user->_id))->sort(array('username' => 1));
?>
<div class="user_subscriptions_body">
	<div class="grid_10 alpha usub_left">
		<div class='head_outer'>
			<div class='page_head'>Subscriptions</div>
    		<div class='subs'><?php echo $subscriptions->count() ?> active</div>
    	</div>

		<div class='action_bar'>
			<div class='search_widget'>
				<?php $form = html::form(array('method' => 'get')) ?>
				<div class='middle'><?php
					echo html::textfield('search_subscriptions', null, array('id' => 'Search_Subscriptions')) ?></div><a href='#' id='Subscriber_search_submit' class='submit'><img alt='search' src='/images/search_icon_small.png'/></a>
				<?php echo html::submitbutton('Search', array('class' => 'invisible_submit')); $form->end() ?>
			</div>
			<div class="clearer"></div>
		</div>
		<div class='user_subscription_list'>
		<?php

		if(count($subscriptions) > 0){
			ob_start();
			?> <div class='list' style='padding:7px 10px;'>{items}<div style='margin-top:7px;'>{pager}<div class="clearer"></div></div></div> <?php
			$template = ob_get_contents();
			ob_end_clean();
			$this->widget('glue/widgets/GListView.php', array(
					'pageSize'	 => 20,
					"cursor"	 => $subscriptions,
					'template' 	 => $template,
					'itemView' => 'user/_subscription.php',
					'pagerCssClass' => 'grid_list_pager'
			));
		}else{
			?>
			<div class='none_found'>
				You have no Subscriptions! Subscribe to a user to keep upto date with that users activity.
			</div><?php
		}
		?>
		</div>
	</div>
</div>
