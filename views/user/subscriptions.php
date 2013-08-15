<?php
use glue\Html;

$this->js('user.unsubscribe', "
	$(document).on('keyup', '.form-search_subs .search_input input', function(event){
		event.preventDefault();
		search_subscriptions(true);
	});
		
	$('.form-search_subs form').submit(function(){
		return false;
	});

	$(document).on('click', '.list .pagination a', function(event){
		event.preventDefault();
		search_subscriptions(false, $(this).attr('href').replace(/#page_/, ''));
	});

	function search_subscriptions(refresh, page){
		var act_page = 1;
		if(!refresh)
			act_page = page;
		$('.user_subscription_list').load('/user/searchFollowers', { query: $('.form-search_subs .search_input input').val(), page: act_page }, function(data){});
	}
");
?>
<div class="followers_page">
	<div class="header">   
    	<div class='search form-search form-search_subs'>
		<?php $form = Html::form(array('method' => 'get')); ?>
			<div class="search_input"><?php echo html::textfield('query',htmlspecialchars(glue::http()->param('query',null)),array('placeholder'=>'Search Subscribers', 'autocomplete'=>'off')) ?></div>
			<button class="submit_search"><span>&nbsp;</span></button>
		<?php $form->end() ?>
		</div>    	
		<div class="clear"></div>
    </div>
	<div class='user_subscription_list'>
	<?php if(glue::user()->totalFollowing > 0){
		glue\widgets\ListView::widget(array(
			'pageSize'	 => 20,
			"cursor"	 => app\models\Follower::model()->find(array('fromId'=>glue::user()->_id))->sort(array('username'=>-1)),
			'itemView' => 'user/_subscription.php',
		));
	}else{
		?><div class='no_results_found'>
			You have no Subscriptions! Subscribe to a user to keep upto date with that users activity.
		</div><?php
	}
	?></div>
</div>
