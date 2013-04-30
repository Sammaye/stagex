(function( $ ){
	var options = {
		'multiadd_selector': '.add_to_playlist',
		'checkbox_selector': '.video_list .video_item input',
		'multi_seek_parent': false,
		'singleadd_selector': '.playlist_button',
		'video_page': false,
		'video_id': ''
	},
	methods = {
		init : function( settings ) {
			//console.log(settings.video_page);
			options = $.extend(options, settings);
			$('body').append($('<div class=\'playlist_menu\'/>'));

			$(document).on('click', options.singleadd_selector+','+options.multiadd_selector, function(event){
				event.preventDefault();

				if($(this).is(options.singleadd_selector)){
					//console.log(options.video_page);
					if(options.video_page){
						showMenu($(this), { 'video': options.video_id, 'multiple': false });
					}else{
						showMenu($(this), { 'video': $(this).parents('.video_item').data('id'), 'multiple': false });
					}
				}else{
					if(options.video_page){
						showMenu($(this), { 'video': options.video_id, 'multiple': false });
					}else{
						var vals_array = [];
						$(options.checkbox_selector+':checked').each(function(){
							if(options.multi_seek_parent){
								vals_array[vals_array.length] = $(this).parents('.video_item').data('id');
							}else{
								vals_array[vals_array.length] = $(this).attr('name');
							}
						});
						showMenu($(this), { 'video': vals_array, 'multiple': true });
					}
				}
			});

			$(document).on('click', '.playlist_menu .item', function(event){
				var url = '/playlist/add_video';
				if($(this).parents('.playlist_menu').data('multiple')){
					url = '/playlist/add_many_videos';
				}
				var el = $(this).parents('.playlist_menu');
				$.post(url, { id: $(this).parents('.playlist_menu').data('video'), p_id: $(this).data('playlist') }, function(data){
					if(data.success){
						el.html(data.html);
					}else{
						el.html(data.html);
					}
				}, 'json');

				$(document).on('click', '.playlist_menu .back_reload', function(event){
					event.preventDefault();
					$('.playlist_menu').load('/playlist/get_menu');
				});
			});
		},
		destroy : function( ) {}
	},
	showMenu = function($this, data){
		if($this.hasClass('active-pmenu')){
			close();
		}else{
			var offset = $this.offset();

			$('.playlist_menu').css({ position: 'absolute',
				'left': offset.left,
				'top': (offset.top + $this.outerHeight())+3, 'z-index': '2147483647',
				'display': 'block'}).load('/playlist/get_menu').data($.extend(data, {'options': options}));

			$this.addClass('active-pmenu');
			$(options.singleadd_selector+','+options.multiadd_selector).not($this).removeClass('active-pmenu');
		}
	},
	close = function(){
		$(options.singleadd_selector+','+options.multiadd_selector).removeClass('active-pmenu');
		$('.playlist_menu').css({display: 'none'}).removeData('video').html('');
	};

	$(document).on('click', function(e) {
		// Lets hide the menu when the page is clicked anywhere but the menu.
		var clicked = $(e.target),
			options = $('.playlist_menu').data('options');

		if(!$.isEmptyObject(options)){ // If options is not set then clearly the menu has not been shown
			if (!clicked.parents().is('.playlist_menu,.'+options.singleadd_selector+',.'+options.multiadd_selector) &&
				!clicked.is('.playlist_menu,.'+options.singleadd_selector+',.'+options.multiadd_selector)){
					close();
			}
		}
	});

	$(window).resize(function(){
		if($('.active-pmenu').length > 0){
			var offset = $('.active-pmenu').offset();

			$('.playlist_menu').css({ position: 'absolute',
				'left': offset.left,
				'top': (offset.top + $('.active-pmenu').outerHeight())+3, 'z-index': '2147483647',
				'display': 'block'});
		}
	});

	$.playlist_dropdown = function( method ) {

		if ( methods[method] ) {
			return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.playlist_dropdown' );
		}

	};

})( jQuery );