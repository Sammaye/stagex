$(document).on('click', '.radio,.checkbox', function(e){
	e.preventDefault(); // Whatever default there may be
	input = $(this).find('input:checkbox,input:radio');
	if(input.prop('type')=='radio')
		$('input[name="'+input.prop('name')+'"]').prop('checked',false);
	if(input.prop('checked')===true)
		input.prop('checked',false);
	else
		input.prop('checked',true);
});

/**
 * Open link in new Window Plugin
 *
 * Sam Millman 2011 (sammaye.wordpress.com)
 * Licensed under MIT and GPL Licenses
 *
 * This plugin allows for a person to attach an action to all links with
 * rel = new_window to open that link in a new window.
 *
 * The plugin also supports a direct function call allowing you to call anywhere in your
 * code to open a link or url in a new window with pre-defined constraints.
 */
$(function(){
	$('a[rel=new_window]').click(function(event){
		event.preventDefault();
		$.open_link_in_new_window($(this).attr('href'), $(this).data().title, $(this).data().height, $(this).data().width);
	});
});

(function($) {
    $.open_link_in_new_window = function(url, title, height, width) {
    	width = width ? width : "500";
    	height = height ? height : "400";
    	return window.open(url, title, "location=1,status=1,scrollbars=1,width="+width+",height="+height);
    };
})(jQuery);

;(function($, window, document, undefined){
	
	var options = {
		'base_class' : 'alert',
			
		'error_class' : 'alert-error',
		'success_class' : 'alert-success',
		'warning_class' : 'alert-warning',
		'info_class' : 'alert-info',
		
		'tpl_close' : '<a href="#" class="close">&#215;</a>'
	},
	methods = {
		init : function(){
			
		},
		setType : function(){},
		setMessage : function(){},
		reset : function(){},
		close : function(){}
	};
	
	$.fn.summarise = function(method) {
		// Method calling logic
		if ( methods[method] ) {
			return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' + method + ' does not exist on jQuery.summarise' );
		}
	};	
	
})(jQuery, window, document);

/**
 * Form plugin
 *
 * Sam Millman 2011 (sammaye.wordpress.com)
 * Licensed under MIT and GPL Licenses
 *
 * This plugin allows for the StageX forms to work without me having to constantly
 * copy and paste code around the place.
 *
 * The main shrine of this plugin is the form summary which uses a element pointing to a normal HTML
 * object to form correct summaries of each and every form on the site in realtime.
 */




var forms = {
	htmlSummary: function(el, html, success){
		if(!success){
			el.css({ 'display': 'block' }).html('').append($(html).css({ 'display': 'block' }));
		}else{
			el.css({ 'display': 'block' }).html('').append($(html).css({ 'display': 'block' }));
		}
	},
	summary: function(el, success, html, error_list, singleLine){
		el.empty();
		if(!success){
			el.append("<div class='close'><a href='#'>&#215;</a></div>");
			el.append(html);

			if(!singleLine && !$.isEmptyObject(error_list) && error_list != null){
				el.append($('<ul/>'));

				if(el.find('ul').length > 0){
					for(var i=0;i<error_list.length;i++){
						el.find('ul').append($('<li>').html(error_list[i]));
					}
				}
			}

			el.addClass('error_summary').removeClass('success_summary');
			el.css({ 'display': 'block' });
		}else{
			el.append("<a href='#' class='close'>&#215;</a>");
			el.append(html);
			el.addClass('success_summary').removeClass('error_summary');
			el.css({ 'display': 'block' });
		}
	},
	reset: function(el){
		el.css({ 'display': 'none' }).removeClass('error_message_curved').removeClass('success_message_curved').find('.message_content').html('');
	}
};

$(function(){
	$(document).on('click', '.alert .close', function(event){
		event.preventDefault();
		$(this).parents('.block_summary').css({ 'display': 'none' }).removeClass('error_message_curved').removeClass('success_message_curved').find('.message_content').html('');
	});
});
