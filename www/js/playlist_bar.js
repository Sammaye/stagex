(function( $ ){
	var options = {
		url: '/playlist/renderBar',
		container: '.playlist_bar_outer'
	},
	methods = {
		init: function( settings ){
			
			var opts = $.extend({}, options, settings);
			
			if($('.playlist_bar_outer').length > 0){ // If it is on the page
				$.getJSON(opts.url, {id: $('.playlist_bar_outer').data().id}, function(data){
					$('.playlist_bar_outer .playlist_video_list .tray_content').html(data.html);
				});
			}
		},
		show : function(){ show(); },
		hide: function(){ hide(); },
		repopulate: function(){}
	},
	show = function(){
		$('.playlist_bar_outer .playlist_content').css({ 'display': 'block' });
		$('.playlist_bar_outer .view_all_videos').html('Hide All Videos');
	},
	hide = function(){
		$('.playlist_bar_outer .playlist_content').css({ 'display': 'none' });
		$('.playlist_bar_outer .view_all_videos').html('View All Videos');
	},
	moveLeft = function(){
		var first_item_offset = $('.playlist_bar_outer .playlist_video_list .playlist_video_item').first().offset(),
			window_width = $(document).width(),
			list_width = $('.playlist_bar_outer .tray_content').width(),
			outer_offset = $('.playlist_bar_outer .playlist_video_list').offset();

		if(first_item_offset.left > window_width){}else{
			var oldLeft = $('.playlist_bar_outer .tray_content').offset();

			if(first_item_offset.left+list_width > outer_offset.left){
				$('.playlist_bar_outer .tray_content').css({ 'left': '0px' });
			}else{
				$('.playlist_bar_outer .tray_content').css({ 'left': (oldLeft.left+list_width)+'px' });
			}
		}
	},
	moveRight = function(){
		var last_item_offset = $('.playlist_bar_outer .playlist_video_list .playlist_video_item').last().offset(),
			window_width = $(document).width(),
			list_width = $('.playlist_bar_outer .tray_content').width(),
			outer_offset = $('.playlist_bar_outer .playlist_video_list').offset();

		if(last_item_offset.left < window_width){}else{
			var oldLeft = $('.playlist_bar_outer .tray_content').offset();

			if(last_item_offset.left-oldLeft.left < list_width){
				$('.playlist_bar_outer .tray_content').css({ 'left': (last_item_offset.left-list_width)+'px' });
			}else{
				$('.playlist_bar_outer .tray_content').css({ 'left': (oldLeft.left-list_width)+'px' });
			}
		}

	};


	$(document).on('click', '.playlist_bar_outer .view_all_videos', function(event){
		event.preventDefault();
		if($('.playlist_bar_outer .playlist_content').css('display') == 'block'){
			hide();
		}else{
			show();
		}
	});

	$(document).on('click', '.playlist_bar_outer .move_left', function(event){
		event.preventDefault();
		moveLeft();
	});

	$(document).on('click', '.playlist_bar_outer .move_right', function(event){
		event.preventDefault();
		moveRight();
	});


	$.playlist_bar = function( method ) {

		if ( methods[method] ) {
			return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.playlist_dropdown' );
		}

	};

})( jQuery );