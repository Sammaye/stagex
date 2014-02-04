<?php

namespace app\widgets;

use glue;
use glue\Widget;

class Highcharts extends Widget
{
	public $chartName;
	public $appendTo;

	/**
	 * Defines the series for the graph, if no data is supplied in this array be ready to supply a loader function with the data in.
	 *
	 * @example array(array('name' => 'series1', 'data': array(1,2,3,4,5)),)
	 *
	 * @var array
	 */
	public $series = array();
	public $type = 'line';

	/**
	 * An assoc array defining functions to be called on certain events.
	 *
	 * @example array('load' => 'loadEventStats')
	 * @example array('load' => 'js:function(){}')
	 *
	 * @var unknown_type
	 */
	public $events = array();

	function render()
	{
		list($name, $id) = $this->getAttributeNameId();
		
		glue::controller()->jsFile('/js/highplot/js/highcharts.js');
		glue::controller()->js('highcharts.' . $id, "
		var ".$this->chartName.";
		$(function(){
			".$this->chartName."= new Highcharts.Chart({
				chart: {
					renderTo: '".$this->appendTo."',
					type: '".$this->type."',
					marginRight: 130,
					marginBottom: 25,
					marginTop: 20,
					events: ".js_encode($this->events)."
				},
				title: {
					text: null
				},
				xAxis: {
					type: 'datetime'
				},
				yAxis: {
					title: {
						text: null
					},
					plotLines: [{
						value: 0,
						width: 1,
						color: '#808080'
					}],
					min: 0,
					allowDecimals: false
				},
				tooltip: {
					formatter: function() {
			            return '<b>'+ this.series.name +'</b><br/>'+
						this.y +' Views';
					}
				},
				legend: {
					layout: 'vertical',
					align: 'right',
					verticalAlign: 'top',
					x: 0,
					y: 100,
					borderWidth: 0
				},
				series: ".js_encode($this->series)."
			});
		});");
	}
}