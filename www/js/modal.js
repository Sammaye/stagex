;(function($, window, document, undefined){
	var options = {
		width 	: 'auto',
		height 	: 'auto',
		minWidth: 960,
		minHeight: 400,

		position: 'center',

		fitToView 		: false,
		spaceFromEdge 	: 35,
		fixed			: true,

		showCloseButton : true,
		
		wrapperCssClass : '',

		overlay : {
			show: true,
			opacity: 0.4,
			onclickClose: true
		},

		tpl : {
			overlay: '<div class="modal_overlay"></div>',
			wrapper: '\
				<div class="modal_wrapper">\
					<div class="modal_outer">\
					<div class="modal_inner">&nbsp;</div>\
					</div>\
				</div>',
			close: '<div class="modal_close"><div class="close_button"><div class="close_symbol">&#215;</div><a href="#">Close</a></div></div>',
		},
		_overlay : null,
		_wrapper : null,
		_media  : [],
		isActive : false,
		isOpen 	 : false // TODO: Make this used
	},

	methods = {
		init : function(opts){

			if(typeof opts == 'object')
				opts = $.extend(true, {}, options, opts);
			else
				opts = $.extend(true, {}, options, {html:opts});
			if(opts.html===null||opts.html===undefined)
				$.error('You must supply some content for the modal via the html option')
			// Lets assign the options to the data of the element in question
			// so we know how to operate the pop up
			methods.open(opts);
		},

		/**
		 * Deals with destroying stuff
		 */
		destroy : function(){
			return this.each(function(){
				var $this = $(this),
				data = $this.data('modal');
				$(window).unbind('.modal');
				data.tooltip.remove();
				$this.removeData('modal');
			});
		},

		/**
		 * This removes the overlay and wrapper from the screen on close of the modal
		 */
		close : function(){

			options._overlay.remove();
			options._wrapper.remove();

			options._overlay = null;
			options._wrapper = null;
			options.isActive = false;
		},

		open : function(opts){
			//e.preventDefault();

			if(options._wrapper != null || options._overlay != null)
				methods.close(); // Lets close any previous one

			opts = $.extend(true, {}, options, opts); // Ensure a copy on write to not effect our static options

			if(options.isActive == false){
				options._overlay = $(opts.tpl.overlay).css({ 'opacity' : opts.overlay.opacity, 'display' : opts.overlay.show ? 'block' : 'none' }).appendTo('body');
				options._wrapper = $(opts.tpl.wrapper).addClass(opts.wrapperCssClass).css({ position: opts.fixed ? 'fixed' : 'static' }).appendTo('body');

				if(opts.height=='auto'){
					//opts.height=$(opts.html).height();
				}

				// Add the close hooks
				if(opts.overlay.onclickClose){
					// Lets bind the close to the onlick on the overlay
					options._overlay.on('click', methods.close);
				}

				// I wanted to do something clever here that didn't require the class but it didn't go so well
				// We reset the HTML of the inner and append the close to the very top and then bind the click handler
				if(opts.showCloseButton)
					var close_button = $(opts.tpl.close);
				else
					var close_button = '';
				options._wrapper.find('.modal_inner').html('').append(close_button);
				close_button.find('.close_button').on('click', closeClickHandler);

				// Now lets append the gallery, we will worry about filling it later
				options._wrapper.find('.modal_inner').append(opts.html);

				// We add the current options to the wrapper so that on page resize we know what to do init
				options._wrapper.data('modal', opts);

			}
			methods.resize();

			// It is now active
			options.isActive = true;
		},
		/**
		 * This deals with the resize of the modal and its gallery
		 */
		resize: function(){

			if(!options._wrapper)
				return; // It is not there!

			opts = options._wrapper.data('modal'); // Since this will be called from many places lets do this

			var pagexy = getPageScroll(),
				pagex = pagexy[0] != 'undefined' && pagexy[0] != null ? pagexy[0] : 0,
				pagey = pagexy[1] != 'undefined' && pagexy[1] != null ? pagexy[1] : 0;

			// This will position our dialog initially within the view
			if(opts.fixed==false)
				options._wrapper.css({ top: pagey+opts.spaceFromEdge, left: pagex+opts.spaceFromEdge });
			else{
				if($(window).height()<(opts.spaceFromEdge+options._wrapper.height())){
					options._wrapper.css({ top: 10, left: 0 }); // If it is fixed then pagey becomes damaging
				}else
					options._wrapper.css({ top: opts.spaceFromEdge, left: pagex+opts.spaceFromEdge }); // If it is fixed then pagey becomes damaging
			}

			if(opts.fitToView){
				// Make it fit to current view
				options._wrapper.css({ width: $(window).width()-(opts.spaceFromEdge*2), height: $(window).height()-(opts.spaceFromEdge*2) });

				// Now if the new size is too small lets reset it to the min size
				// If it is a static size we don't want this, maybe? I dunno
				if(parseInt(options._wrapper.css("width")) < opts.minWidth) options._wrapper.css({ width: opts.minWidth });
				if(parseInt(options._wrapper.css("height")) < opts.minHeight) options._wrapper.css({ height: opts.minHeight });
			}else{
				// Use width and height to determine render
				options._wrapper.css({ width: opts.width, height: opts.height });

				// If we are using the width and height lets judge where to put the damn thing
				switch(opts.position){
					case "center":
						options._wrapper.css({ left: Math.max(0, (($(window).width() - options._wrapper.outerWidth()) / 2) + $(window).scrollLeft()) });
						break;
					case "right":
					case "left":
					default:
						break;
				}
			}
		}
	},

	/**
	 * This handles the click of close on elements like <a/>
	 */
	closeClickHandler = function(e){
		e.preventDefault();
		methods.close();
	};

	/**
	 * This function determines where the viewport is in relation to the page so that we can show the
	 * modal at the right position
	 *
	 * getPageScroll() by quirksmode.com
	 */
	function getPageScroll() {
		var xScroll, yScroll;
		if (self.pageYOffset) {
			yScroll = self.pageYOffset;
			xScroll = self.pageXOffset;
		} else if (document.documentElement && document.documentElement.scrollTop) {	 // Explorer 6 Strict
			yScroll = document.documentElement.scrollTop;
			xScroll = document.documentElement.scrollLeft;
		} else if (document.body) {// all other Explorers
			yScroll = document.body.scrollTop;
			xScroll = document.body.scrollLeft;
		}
		return new Array(xScroll,yScroll)
	}

	/**
	 * Lets bind the resize event to make the window nicer
	 */
	$(window).resize(function(){
		methods.resize();
	});

	/**
	 * Key bindings for all sorts of things
	 */
	$(document).on('keydown', function(e){
		if(options._wrapper!==null)
			if (e.keyCode == 27) { methods.close(); } // ESC = close window
	});

	$.modal = function(method) {
		// Method calling logic
		if ( methods[method] ) {
			return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.modal' );
		}
	};

})(jQuery, window, document);