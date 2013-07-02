<?php
	$this->addCssFile('jui-theme', '/css/overcast/jquery-ui-1.8.18.custom.css');

	glue::clientScript()->addJsScript('statistics', "
		$(function() {
			var dates = $( '#from, #to' ).datepicker({
				defaultDate: '+1w',
				dateFormat: 'dd/mm/yy',
				changeMonth: true,
				numberOfMonths: 1,
				onSelect: function( selectedDate ) {
					var option = this.id == 'from' ? 'minDate' : 'maxDate',
						instance = $( this ).data( 'datepicker' ),
						date = $.datepicker.parseDate(
							instance.settings.dateFormat ||
							$.datepicker._defaults.dateFormat,
							selectedDate, instance.settings );
					dates.not( this ).datepicker( 'option', option, date );
				}
			});
		});
	");

	$video_stats = $model->getStatistics_dateRange(mktime(0, 0, 0, date("m"), date("d"), date("Y")), mktime(23, 0, 0, date("m"), date("d"), date("Y")));
	//echo "here";
?>
<div class='grid_970'>
	<div class='video_stats_body grid_block alpha'>
		<div class='head_outer'>
			<div class='head'>Statistics for <a href='<?php echo glue::url()->create('/video/watch', array('id' => $model->_id)) ?>'><?php echo html::encode($model->title) ?></a></div>
		</div>
		<div class='overview_stats'>
			<div class='head'>Overview:</div>
			<div class='views_status'><div class='float_left'><span><?php echo $model->views ?></span> views</div><div class='float_right'><span><?php echo $model->unique_views ?></span> unique views</div></div>
			<div class='demo_block_outer'>
				<div class='demo_block_left'>
					<h2>Like Demographics</h2>
					<?php if($model->likes <= 0 && $model->dislikes <= 0){ ?>
						<p>No one has liked or disliked this video yet</p>
					<?php }else{ ?>
						<div class='ratings_block like_block'>
							<div style='border:1px solid #006600; background:#5bd85b; width:<?php echo ($model->likes/($model->likes+$model->dislikes))*100 > 0 ? (($model->likes/($model->likes+$model->dislikes))*100)."%;" : "5px;" ?>'></div>
								<span><?php if($model->likes <= 0): echo "No one has liked this video yet"; elseif($model->likes == 1): echo $model->likes." person liked this video"; else: echo $model->likes." people liked this video"; endif; ?></span>
							</div>
						<div class="ratings_block dislike_block">
							<div style='border:1px solid #cc0000; background:#fb5353; width:<?php echo ($model->dislikes/($model->likes+$model->dislikes))*100 > 0 ? (($model->dislikes/($model->likes+$model->dislikes))*100)."%;" : "5px;" ?>'></div>
							<span><?php if($model->dislikes <= 0): echo "No one has disliked this video yet"; elseif($model->dislikes == 1): echo $model->dislikes." person disliked this video"; else: echo $model->dislikes.' people disliked this video'; endif; ?></span>
						</div>
					<?php } ?>
				</div>
				<div class='demo_block_right'>
					<h2>Response Demographics</h2>
					<?php $videoResponseCount = $model->with('responses', array('type' => 'video', 'deleted' => 0))->count()?>
					<?php $textResponseCount = $model->with('responses', array('type' => 'text', 'deleted' => 0))->count()?>
					<p><?php echo $textResponseCount ?> text <?php if($textResponseCount > 1): echo "responses"; else: echo "response"; endif ?></p>
					<p><?php echo $videoResponseCount ?> video <?php if($videoResponseCount > 1): echo "responses"; else: echo "response"; endif ?></p>
				</div>
				<div class="clear"></div>
			</div>
		</div>

		<div class='stats_nav_bar'>
			<label for="from" class='from_label'>From:</label><input type="text" id="from" name="from" value='<?php echo date("d/m/Y") ?>'/>
			<label for="to" class='to_label'>To:</label><input type="text" id="to" name="to" value='<?php echo date('d/m/Y') ?>'/>
			<div class='grey_css_button get_stats_range'>Get Stats</div>
		</div>
		<div id="chartdiv" style='height:250px; width:780px; margin-left:5px;'>
			<?php $this->widget('application/widgets/highcharts.php', array(
				'chartName' => 'video_views_plot',
				'appendTo' => 'chartdiv',
				'series' => $video_stats['hits']
			)) ?>

			<?php glue::clientScript()->addJsScript('chart_stuff', "

				var browser_chart;
				var age_group_chart;

				var browser_chart_config = {
				    chart: {
				    	renderTo: 'browser_container',
				        	plotBackgroundColor: null,
				            plotBorderWidth: null,
				            plotShadow: false
				    },
				    title: {
				    	text: null
				    },
				    tooltip: {
				    	formatter: function() {
				        	return '<b>'+ this.point.name +'</b>: '+ this.percentage +' %';
				        }
				    },
				    plotOptions: {
				    	pie: {
				          	allowPointSelect: true,
				            cursor: 'pointer',
				            dataLabels: {
				              	enabled: false,
				                color: '#000000',
				                connectorColor: '#000000',
				                formatter: function() {
				                  	console.log(this);
				                    return '<b>'+ this.point.name +'</b>: '+ this.percentage +' %';
				                }
				            },
				            //size: 80
				        }
				    },
				    series: [{
				       	type: 'pie',
				        name: 'Browser share',
				    	data: ".GClientScript::encode($video_stats['browsers'])."
				    }]
				};

				var age_groups_chart_config = {
					chart: {
				    	renderTo: 'age_group_container',
				        plotBackgroundColor: null,
				        plotBorderWidth: null,
				        plotShadow: false
				    },
				    title: {
				    	text: null
				    },
				    tooltip: {
				    	formatter: function() {
				        	return '<b>'+ this.point.name +'</b>: '+ this.percentage +' %';
				        }
				    },
				    plotOptions: {
				    	pie: {
				    		//size: 80,
				        	allowPointSelect: true,
				            cursor: 'pointer',
				            dataLabels: {
				            	enabled: false,
				                color: '#000000',
				                connectorColor: '#000000',
				                formatter: function() {
				                	return '<b>'+ this.point.name +'</b>: '+ this.percentage +' %';
				                }
				            },
				       }
				  	},
				    series: [{
				    	type: 'pie',
				        name: 'Age group share',
				        data: ".GClientScript::encode($video_stats['ages'])."
				    }]
				};

				$(function () {
					browser_chart = new Highcharts.Chart(browser_chart_config);
			        age_group_chart = new Highcharts.Chart(age_groups_chart_config);
			    });

				$(document).on('click', '.get_stats_range', function(event){

					var from = $('#from').val(),
						to = $('#to').val();

					$.getJSON('/video/get_more_statistics', {id: '".$model->_id."', from: from, to: to}, function(data){

						if(data.success){

							var new_series = {};

							$.each(data.hits, function(i, item){
								new_series[item.name] = item.data;
							});
	//console.log(data);
							$.each(video_views_plot.series, function(i, item){
								var series_data = [];
								if(item.name == 'Views'){
									series_data = new_series['Views'];
								}else if(item.name == 'Unique Views'){
									series_data = new_series['Unique Views'];
								}
								item.setData(series_data, true);
							});

							//browser_chart.series[0].
							browser_chart_config.series[0].data = data.browsers;
							browser_chart = new Highcharts.Chart(browser_chart_config);

							age_groups_chart_config.series[0].data = data.ages;
							browser_chart = new Highcharts.Chart(age_groups_chart_config);

							$('.video_comments_amnt').html(data.video_comments);
							$('.text_comments_amnt').html(data.text_comments);
							$('.likes_amnt').html(data.video_likes);
							$('.dislikes_amnt').html(data.video_dislikes);

							$('.males_percent').html(data.males+'%');
							$('.females_percent').html(data.females+'%');
						}
					});
				});
			") ?>
		</div>
		<div class='stats_action_unit_row'>
			<div><span class='video_comments_amnt'><?php echo $video_stats['video_comments']?></span> Video Responses</div>
			<div><span class='text_comments_amnt'><?php echo $video_stats['text_comments']?></span> Text Responses</div>
			<div><span class='likes_amnt'><?php echo $video_stats['video_likes']?></span> Likes</div>
			<div><span class='dislikes_amnt'><?php echo $video_stats['video_dislikes']?></span> Dislikes</div>
		</div>
		<div class='stats_sec_head'>Browsers and Age Groups</div>
		<div class='stats_sec_caption'>Be-aware this data is for unique visits only. We do not record recurring visits of different browser types or age group.</div>

		<div style='height:400px;'>
			<div id="browser_container" style="min-width: 300px; height: 300px; margin: 0 auto; float:left; margin-left:20px; margin-right:80px;"></div>
			<div id="age_group_container" style="min-width: 300px; height: 300px; margin: 0 auto; float:left;"></div>
		</div>

		<div class='stats_sec_head'>Gender Groups</div>
		<div class='gender_groups'>
			<div><span class='males_percent'><?php echo $video_stats['males'] ?>%</span> Males</div>
			<div><span class='females_percent'><?php echo $video_stats['females'] ?>%</span> Females</div>
		</div>

		<?php
		// Now get the top 10 referers
		$referers = glue::db()->video_referers->find(array('video_id' => $model->_id))->sort(array('c' => -1))->limit(10); ?>
		<div class='stats_sec_head'>Top 10 Referrers (All time)</div>
		<div class='stats_sec_caption'>This data is not broken down into time spans, it represents the total referrers since you uploaded the video. It also represents
			all views, unique and recurring.</div>

		<table class='concurrent_sessions'>
			<thead>
				<tr>
					<th>Referrer</th>
					<th>Redirects</th>
					<th>Last Redirect</th>
				</tr>
			</thead>
			<tbody>
				<?php
				if($referers->count() > 0){
					foreach($referers as $k=>$v){ ?>
						<tr>
							<td><?php echo $v['referer'] ?></td>
							<td><?php echo $v['c'] ?></td>
							<td><?php echo date('d-m-Y H:i:s') ?></td>
						</tr>
					<?php }
				}else{ ?>
					<tr>
						<td colspan="3">No referrers found yet</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
</div>