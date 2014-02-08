<?php
use glue\Html;

$this->jsFile("/js/subscribeButton.js");
$this->js('user.unsubscribe', "
	
	var preVal='',
		t = setInterval(function(){ search(); },1000);
		
	$('.subscribe_widget').subscribeButton();
		
	function search(){
		var term=$('.form-search_subs .form-search-input').val();
		if(term!=preVal)
			search_subscriptions(true);
		preVal=term;
	}		
		
	$('.form-search_subs form').submit(function(){
		return false;
	});

	$(document).on('click', '.list .pagination a', function(event){
		event.preventDefault();
		search_subscriptions(false, $(this).attr('href').replace(/#page_/, ''));
	});

	function search_subscriptions(refresh, page){
		var act_page = 1;
		if(!refresh){
			act_page = page;
		}
		
		$.get('/user/searchFollowing', {query: $('.form-search_subs .form-search-input').val(), page: act_page}, null, 'json')
		.done(function(data){
			if(data.success){
				$('.user_subscription_list').html(data.html);
			}
		});
	}
");
?>
<div class="followers_page">
	<div class="header">
    	<div class='search form-search form-search_subs'>
		<?php $form = Html::form(array('method' => 'get')); ?>
			<?php echo html::textfield('query',htmlspecialchars(glue::http()->param('query',null)),array('placeholder'=>'Search Subscribers', 'autocomplete'=>'off', 'class'=>'form-search-input col-38')) ?>
			<button class="btn submit_search"><span class="search-dark-icon">&nbsp;</span></button>
		<?php $form->end() ?>
		</div>	
    </div>
	<div class='user_subscription_list'>
	<?php if(glue::user()->totalFollowing > 0){
		echo glue\widgets\ListView::run(array(
			'pageSize'	 => 20,
			"cursor"	 => app\models\Follower::find(array('fromId'=>glue::user()->_id))->sort(array('username'=>-1)),
			'itemView' => 'user/_subscription.php',
		));
	}else{
		?><div class='no_results_found'>
			You have no Subscriptions! Subscribe to a user to keep upto date with that users activity.
		</div><?php
	}
	?></div>
</div>
