<?php
glue::clientScript()->addJsFile('jquery-expander', "/js/jquery-expander.js");

glue::clientScript()->addJsScript('page_js', "
	$(function(){
		$('.expandable').expander({slicePoint: 200});

		$(document).on('click', '.load_more', function(event){
			event.preventDefault();
			var last_ts = $('.list .streamitem').last().data('ts'),
				filter = $('.list').data('sort');
			$.getJSON('/stream/get_stream', {ts: last_ts, news: 1 }, function(data){
				if(data.success){
					$('.list').append(data.html);
				}else{
					if(data.noneleft){
						$('.load_more').html(data.messages[0]);
					}
				}
			});
		});
	});
");
?>

<div class='grid_5 alpha omega user_news_feed_body float_left'>
	<div class='head_outer'>
		<div class='head'>News Stream</div>
		<div class='subs'><a href='<?php echo glue::url()->create('/user/subscriptions') ?>'><?php echo sizeof($subscriptions).' subscriptions' ?></a></div>
	</div>
	<div class='list'>
		<?php
		if(sizeof($model) > 0){
			foreach($model as $k => $item){
				$this->partialRender('stream/streamitem', array('item' => $item, 'hideDelete' => true));
			}
		}else{ ?>
			<div class='non_found'>
				How do you expect there to be any news when your not following anyone? Try subscribing to some users to get some.
			</div>
		<?php } ?>
	</div>
	<?php if($model->count() > 20){ ?>
		<a class='load_more' href='#'>Load more stream</a>
	<?php } ?>
</div>
<div style='float:left; width:160px; margin-left:25px;'>
	<?php $this->widget("application/widgets/Advertising/Ad_box.php", array( "configuration"=>'skyscraper' )); ?>
</div>