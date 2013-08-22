<?php
	//$this->addCssFile('jui-theme', '/css/overcast/jquery-ui-1.8.18.custom.css');
$this->js('statistics', "
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
?>
<div class='video_analytics_body'>
	<div class='video_stats_body grid_block alpha'>
		<h1>Statistics for <a href='<?php echo glue::http()->url('/video/watch', array('id' => $model->_id)) ?>'><?php echo html::encode($model->title) ?></a></h1>
		<div class='head'>Overview</div>
		<div class="overview">
			<div class="stats_block stats_block_first"><?php echo $model->views ?> views</div>
			<div class="stats_block"><?php echo $model->uniqueViews ?> unique views</div>
			<div class="stats_block likes"><?php echo $model->likes ?> likes</div>
			<div class="stats_block dislikes"><?php echo $model->dislikes ?> dislikes</div>
			<div class="stats_block response first"><?php echo $model->totalTextReponses ?> text responses</div>
			<div class="stats_block response"><?php echo $model->totalVideoResponses ?> video responses</div>
			<div class="clear"></div>
		</div>
		<div class="clear"></div>
		<div class='stats_filter_bar'>
			<label for="from">Date range:</label>
			<input type="text" id="from" name="from" value='<?php echo date("d/m/Y") ?>'/><span class="sep">-</span>
			<input type="text" id="to" name="to" value='<?php echo date('d/m/Y') ?>'/>
			<input type="button" class="btn apply_range" value="Apply"/>
		</div>
		<div id="chartdiv" style='height:250px; width:780px; margin-left:5px;'>
			<?php app\widgets\highcharts::widget(array(
				'chartName' => 'video_views_plot',
				'appendTo' => 'chartdiv',
				'series' => $video_stats['hits']
			)) ?>

			<?php $this->js('chart_stuff', "

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
				    	data: ".js_encode($video_stats['browsers'])."
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
				        data: ".js_encode($video_stats['ages'])."
				    }]
				};
					
				var male_age_chart_config = {
					chart: {
				    	renderTo: 'male_age_container',
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
				        data: ".js_encode($video_stats['maleAgeChart'])."
				    }]
				};					

				var female_age_chart_config = {
					chart: {
				    	renderTo: 'female_age_container',
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
				        data: ".js_encode($video_stats['femaleAgeChart'])."
				    }]
				};						
					
				$(function () {
					browser_chart = new Highcharts.Chart(browser_chart_config);
			        age_group_chart = new Highcharts.Chart(age_groups_chart_config);
					maleAgeChart = new Highcharts.Chart(male_age_chart_config);
					femaleAgeChart = new Highcharts.Chart(female_age_chart_config);
			    });

				$(document).on('click', '.apply_range', function(event){

					var from = $('#from').val(),
						to = $('#to').val();

					$.getJSON('/video/getAnalytics', {id: '".$model->_id."', from: from, to: to}, function(data){

						if(data.success){

							var stats=data.stats;
					
							var new_series = {};

							$.each(stats.hits, function(i, item){
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
							browser_chart_config.series[0].data = stats.browsers;
							browser_chart = new Highcharts.Chart(browser_chart_config);

							age_groups_chart_config.series[0].data = stats.ages;
							browser_chart = new Highcharts.Chart(age_groups_chart_config);
					
							male_age_chart_config.series[0].data = stats.maleAgeChart;
							browser_chart = new Highcharts.Chart(male_age_chart_config);	

							female_age_chart_config.series[0].data = stats.femaleAgeChart;
							browser_chart = new Highcharts.Chart(female_age_chart_config);					

							$('.video_comments_count').html(stats.video_comments);
							$('.text_comments_count').html(stats.text_comments);
							$('.likes_count').html(stats.video_likes);
							$('.dislikes_count').html(stats.video_dislikes);

							$('.males_percent').html(stats.males);
							$('.females_percent').html(stats.females);
						}
					});
				});
			") ?>
		</div>
		<div class="clear"></div>
		<div style='padding:30px 0;'>
			<div class='stats_block'><span class="video_comments_count"><?php echo $video_stats['video_comments']?></span> Video Responses</div>
			<div class='stats_block'><span class="text_comments_count"><?php echo $video_stats['text_comments']?></span> Text Responses</div>
			<div class='stats_block likes' ><span class="likes_count"><?php echo $video_stats['video_likes']?></span> Likes</div>
			<div class='stats_block dislikes'><span class="dislikes_count"><?php echo $video_stats['video_dislikes']?></span> Dislikes</div>
			<div class="clear"></div>
		</div>
		<h3>Browsers and Age Groups</h3>
		<p>Be-aware this data is for unique visits only. We do not record recurring visits of different browser types or age group.</p>

		<div style='height:400px;'>
			<div id="browser_container" style="min-width: 300px; height: 300px; margin: 0 auto; float:left; margin-left:20px; margin-right:80px;"></div>
			<div id="age_group_container" style="min-width: 300px; height: 300px; margin: 0 auto; float:left;"></div>
		</div>

		<h3>Gender and Age Demographics</h3>
		<div style='padding:20px 0;'>
			<div class="stats_block" style='margin-left:70px;'><span class='males_percent'><?php echo $video_stats['males'] ?></span> Males</div>
			<div class="stats_block" style='margin-left:190px;'><span class='females_percent'><?php echo $video_stats['females'] ?></span> Females</div>
			<div class="clear"></div>
		</div>
		
		<div style='height:400px;'>
			<div id="male_age_container" style="min-width: 300px; height: 300px; margin: 0 auto; float:left; margin-left:20px; margin-right:80px;"></div>
			<div id="female_age_container" style="min-width: 300px; height: 300px; margin: 0 auto; float:left;"></div>
		</div>		

		<?php
		// Now get the top 10 referers
		$referers = glue::db()->video_referers->find(array('video_id' => $model->_id))->sort(array('c' => -1))->limit(10); ?>
		<h3>Top 10 Referrers (All time)</h3>
		<p>This data is not broken down into time spans, it represents the total referrers since you uploaded the video. It also represents
		all views, unique and recurring.</p>

		<table class='table'>
			<thead><tr><th>Referrer</th><th>Redirects</th><th>Last Redirect</th></tr></thead>
			<tbody>
				<?php
				if($referers->count() > 0){
					foreach($referers as $k=>$v){ ?>
						<tr><td><?php echo $v['referer'] ?></td><td><?php echo $v['c'] ?></td><td><?php echo date('d-m-Y H:i:s') ?></td></tr>
					<?php }
				}else{ ?>
					<tr><td colspan="3">No referrers found yet</td></tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
</div>