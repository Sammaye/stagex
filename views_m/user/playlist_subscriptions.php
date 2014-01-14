<?php 
$this->JsFile("/js/jquery.expander.js");

$this->js('page', "
	$('.expandable').expander({slicePoint:40});
	$('.grey_sticky_toolbar .block-alert').summarise();
		
	$(document).on('click', '.user_playlist_subscription_item .btn_subscribe', function(){
		
		var btn=$(this),
			container=$(this).parents('.user_playlist_subscription_item');
		
		$.post('".glue::http()->url('/playlist/subscribe')."', {id:container.data('id')}, null, 'json')
		.done(function(data){
			if(data.success){
				btn.css({display:'none'});
			}
		});
	});
		
	$('.selectAll_input').click(function(){
		if($(this).prop('checked')==true){
			$('.playlist_list input:checkbox').prop('checked', false).trigger('click');
		}else{
			$('.playlist_list input:checkbox').prop('checked', true).trigger('click');
		}
	});		

	$(document).on('click', '.btn_unsubscribe', function(event){
		params={'id[]':[]};
		$('.playlist_list .playlist .checkbox_col input:checked').each(function(i,item){
			params['id[]'][params['id[]'].length]=$(item).val();
		});

		$.post('/playlist/unsubscribe', params, null, 'json').done(function(data){
			if(data.success){
				$('.grey_sticky_toolbar .block-alert').summarise('set', 'success','The playlists you selected were deleted');
				$.each(params['ids[]'],function(i,item){
					$('.playlist_list .playlist[data-id='+item+']').find('.btn_subscribe').css({display:'block'});
				});
				$('.grey_sticky_toolbar .block-alert').summarise('set', 'success',
					'You have been unsubscribed from those playlists');		
				reset_checkboxes();
			}else{
				$('.grey_sticky_toolbar .block-alert').summarise('set', 'error','Could not unsubscribe you from those playlists');
			}
		}, 'json');			
	});	
		
	function reset_checkboxes(){
		$('.selectAll_input').prop('checked',true).trigger('click');
	}		
");
?>

<div class="user_playlists_page">
<div class="header">
<div class='search form-search'>
	<?php $form = html::form(array('method' => 'get')); ?>
	<div class="form-group">
	<input class="form-control" placeholder="Search Playlists" type="text" name="query" value="<?php echo urldecode(htmlspecialchars(isset($_GET['query']) ? $_GET['query'] : '')) ?>"/>
	</div><button class="btn btn-default submit_search">Search</button>
	<?php $form->end() ?>
</div>    	
</div>	
<?php ob_start(); ?>
	<div class='stickytoolbar-placeholder grey_sticky_toolbar'>
		<div class='stickytoolbar-bar'>
			<div class='inner_bar'>
				<div class='checkbox_button checkbox_input'><?php echo Html::checkbox('selectAll', 1, 0, array('class' => 'selectAll_input')) ?></div>
				<button class='btn btn-danger selected_actions btn_unsubscribe'>Unsubscribe</button>
			</div>
			<div class="alert block-alert" style='display:none;'></div>
		</div>
	</div>
	<?php $html = ob_get_contents();
ob_end_clean();
echo app\widgets\stickytoolbar::run(array(
	"element" => '.grey_sticky_toolbar',
	"options" => array(
		'onFixedClass' => 'grey_sticky_bar-fixed'
	),
	'html' => $html
)); ?>
<div class='playlist_list'>
<?php if($playlist_rows->count() > 0){
	echo glue\widgets\ListView::run(array(
		'pageSize'	 => 20,
		'page' 		 => glue::http()->param('page',1),
		"cursor"	 => $playlist_rows,
		'itemView' 	 => 'playlist/_playlist_subscription.php',
	));
}else{ ?>
	<div class='no_results_found'>You are not subscribed to any playlists</div>
<?php } ?>
</div>
</div>