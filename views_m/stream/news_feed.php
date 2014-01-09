<?php
$this->JsFile("/js/jquery.expander.js");

$this->js('page', "
	$('.expandable').expander({slicePoint: 200});

	$(document).on('click', '.load_more', function(event){
		event.preventDefault();
		var last_ts = $('#news_content .streamitem').last().data('ts'),
			filter = $('.list').data('sort');
		$.getJSON('/stream/getStream', {ts: last_ts, news: 1 }, function(data){
			if(data.success){
				$('#news_content').append(data.html);
			}

			if(data.remaining<=0){
				$('.load_more').html(data.message);
			}
		});
	});
");
?>

<div class=''>
	<div id="news_content">
		<?php
		if(count($stream) > 0){
			foreach($stream as $k => $item)
				echo $this->renderPartial('stream/streamitem', array('item' => $item, 'hideDelete' => true));
		}else{ ?>
			<div class='no_results_found'>
				You have no Subscriptions! Subscribe to a user to keep upto date with that users activity.
			</div>
		<?php } ?>
	</div>
	<?php if($stream->count() > 20){ ?>
		<a class='load_more' href='#'>Load more stream</a>
	<?php } ?>
</div>