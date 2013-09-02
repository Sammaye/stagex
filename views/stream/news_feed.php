<?php
$this->jsFile('jquery-expander', "/js/jquery-expander.js");

$this->js('page_js', "
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

<div class=''>
	<div class="tabs-nav">
		<ul>
			<li><a href="/stream/news" id="news_tab" class="selected">News</a></li>
			<li><a href="/stream">Activity</a></li>
		</ul>
	</div>

	<div id="news_content">
		<?php
		if(count($stream) > 0){
			foreach($stream as $k => $item)
				echo $this->renderPartial('stream/streamitem', array('item' => $item, 'hideDelete' => true));
		}else{ ?>
			<div class='no_results_found'>
				How do you expect there to be any news when your not following anyone? Try subscribing to some users to get some.
			</div>
		<?php } ?>
	</div>
	<?php if($stream->count() > 20){ ?>
		<a class='load_more' href='#'>Load more stream</a>
	<?php } ?>
</div>
<div style='float:left; width:160px; margin-left:25px;'>
	<?php //$this->widget("application/widgets/Advertising/Ad_box.php", array( "configuration"=>'skyscraper' )); ?>
</div>