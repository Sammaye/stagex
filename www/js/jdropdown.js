/**
 * Jdropdown
 *
 * @author Sam Millman
 * @licence MIT License (http://opensource.org/licenses/mit-license.html)
 *
 * This plugin basically allows you to connect a menu to an anchor.
 * This anchor can be literally anything, from a <div/> to an <a/> and even a <li/>.
 */
;(function($, window, document, undefined){
	var options = {
		type : '',
		items : {},
		ajax : {},
		orientation : '',
		button : '.dropdown-anchor',
		menu : '.dropdown-menu',
		item : '.dropdown-menu .item'
	},
	methods = {
		init: function(opts){
			return this.each(function(){
				var settings = $.extend(true, {}, options, opts),
					items = $(this).data('items');

				if(!$(this).data('jdropdown')){

					switch(true){
						case !$.isEmptyObject(options.items): // Load URL
							$(this).addClass('jdropdown-anchor').data('jdropdown', {
								_: $(this),
								menu: $(this).find(settings.menu),
								button: $(this).find(settings.button),
								settings: $.extend(true, {}, settings, {
									type: 'itemised',
									items: typeof items === 'object' ? items : settings.items,
								})
							}).on({ 'click': open });
							break;
						case !$.isEmptyObject(options.ajax): // Then we want an AJAX powered Menu.
							$(this).addClass('jdropdown-anchor').data('jdropdown', {
								_: $(this),
								menu: $(this).find(settings.menu),
								button: $(this).find(settings.button),
								settings: $.extend(true, {}, settings, {
									type: 'ajax',
								})								
							}).on({ 'click': open });
							break;
						default: // Just show the damn menu
							$(this).addClass('jdropdown-anchor').data('jdropdown', {
								_: $(this),
								menu: $(this).find(settings.menu),
								button: $(this).find(settings.button),
								settings: $.extend(true, {}, settings, {
									type: 'normal',
								})									
							});
							$(this).find(settings.button).on({ 'click': open });
							$(document).on('click', $(this).find(settings.item), selectItem);
							break;
					}
					
					//$(this).find(settings.button).on('click', function(){ open; });
					$(this).find(settings.menu).addClass("jdropdown-menu").data('jdropdown', {
						_: $(this).find(settings.menu),
						anchor: $(this), 
						//settings: settings
					}).css({display:'none'});
				}
				return this;
			});
		},
		destroy: function(){}
	},
	open = function(event){
		event.preventDefault();

		var anchor = $(this).parents('.jdropdown-anchor'),
			data  = anchor.data('jdropdown'),
			settings = $.extend(true,{},options,data.settings),
			offset = $(this).offset(),
			container = data.menu;
		
		if(anchor.hasClass('jdropdown-active')){
			close();
			return;
		}else{
			close();
		}		

		switch(true){
			case settings.type == 'ajax':
				data.menu.html($.ajax(settings.ajax));
				break;
			case settings.type == 'itemised':
				container.empty();

				if($.isFunction(settings.renderMenu)){
					if($.isFunction(settings.renderItem)){
						ul = settings.renderItem(settings.renderMenu(), settings.items);
					}else{
						ul = renderItem(settings.renderMenu(), settings.items);
					}
				}else{
					if($.isFunction(settings.renderItem)){
						ul = settings.renderItem($( '<ul></ul>' ), settings.items);
					}else{
						ul = renderItem($( '<ul></ul>' ), settings.items);
					}
				}
				ul.appendTo( container );
				break;
			default:
				break;
		}

		if(settings.orientation == 'above'){
			data.menu.css({
				'position': 'absolute',
				'left': 0,
				'top': (0 - data.menu.outerHeight()),
				'display': 'block',
				'z-index' : 99999999999
			});
		}else if(settings.orientation == 'over'){
			data.menu.css({
				'position': 'absolute',
				left:0,
				top:0,				
				//'left': offset.left,
				//'top': (offset.top),
				'display': 'block',
				'z-index' : 99999999999
			});
		}else{
			data.menu.css({
				'position': 'absolute',
				left:0,
				top:$(this).outerHeight(),
				//'left': (offset.left - container.outerWidth()),
				//'top': (offset.top + $(this).outerHeight()),
				'display': 'block',
				'z-index' : 99999999999
			});
		}
		anchor.addClass('jdropdown-active').trigger('jdropdown.open');
	},
	renderItem = function($menu, $items){
		$.each($items, function(i, item){
			$('<li></li>').data('jdropdown.item', item).append(
				$( "<a></a>" ).attr({
					'href': '#', 'class': item['class']
				}).text( item.label ).on({ 'click': selectItem })
			).appendTo( $menu );
		});
		return $menu;
	},
	selectItem = function(){
		//close();
		$(this).trigger('jdropdown.selectItem');
	},
	close = function(){
    	$('.jdropdown-menu').css({ 'display': 'none' }); //hide all drop downs
    	$('.jdropdown-anchor').removeClass("jdropdown-active");
		$(this).trigger('jdropdown.close');
	};

	$(document).on('click', function(e) {
	    // Lets hide the menu when the page is clicked anywhere but the menu.
	    var $clicked = $(e.target);
	    if (!$clicked.closest(".jdropdown-menu").length && !$clicked.closest(".jdropdown-anchor").length){
	    	//alert("closing");
	    	close();
		}
	});

	$(window).resize(function(){
		if($('.jdropdown-active').length > 0){
			var offset = $('.jdropdown-active').offset(),
				data = $('.jdropdown-active').data('jdropdown'),
				settings  = $.extend(true,{},options,data.settings),
				container = data.menu;

			if(settings.orientation == 'left'){
				data.menu.css({
					'position': 'absolute',
					'left': offset.left,
					'top': (offset.top + $('.jdropdown-active').outerHeight()),
					'display': 'block'
				});
			}else{
				data.menu.css({
					'position': 'absolute',
					'left': (offset.left - container.outerWidth()) + $('.jdropdown-active').outerWidth(),
					'top': (offset.top + $('.jdropdown-active').outerHeight()),
					'display': 'block'
				});
			}
		}
	});

	$.fn.jdropdown = function(method){
		// Method calling logic
		if ( methods[method] ) {
			return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.j_slider' );
		}
	};
})(jQuery, window, document);