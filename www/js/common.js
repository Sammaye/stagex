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

/**
 * This plugin basically makes alerts more...responsive
 * 
 * @example
 * 
 * Lets init all our alerts:
 * 
 * $('.alert').summarise();
 * 
 * Let's set some content:
 * 
 * $('.alert').summarise('content', {message: 'Sorry it did not succeed; please try again', list: ['message1','message2']});
 * 
 * After an Ajax request lets show an error message as well as init:
 * 
 * $('.alert').summarise('error','Sorry but it did not succeed; please try again.');
 * 
 * Or just set an error:
 * 
 * $('.alert').summarise('set', 'error', 'Sorry but it did not succeed; please try again.');
 * 
 * To close the alert
 * 
 * $('.alert').alert('close');
 */
;(function($, window, document, undefined){
	
	var options = {
		'base_class' : 'alert',
			
		'error_class' : 'alert-error',
		'success_class' : 'alert-success',
		'warning_class' : 'alert-warning',
		'info_class' : 'alert-info',
		
		'tpl_close' : '<a href="#" class="close">&#215;</a>',
	},
	methods = {
		init : function(type, content, opts){
			
			settings=$.extend(true, {}, options, opts);
			
			return this.each(function(){
				data = $(this).data('summarise');
				$this=$(this);
				
				if(!data){
					$this.data('summarise', {
						'_' : this,
						'options' : settings
					});
					
					if(!$this.hasClass('alert')){
						$this.addClass('alert');
					}
					$this.addClass('summarise-alert');
					
					methods.type(type,$this);
					methods.content(content,$this);
				}
			});
		},
		destroy : function(){
			$this=$(this);
			data=$this.data('summarise');
			
			// TODO Make this more complete
			if(data)
				$this.removeData('summarise');
		},
		set : function(type, content){
			methods.type(type,$(this));
			methods.content(content,$(this));
		},
		type : function(type,el){
			$this=el||$(this);
			settings=$.extend(true, {}, options, $this.data('summarise').options);
			if(type!==null&&type!==undefined){
				type='alert-'+type;			
				$this.removeClass([
				    settings['error_class'],
				    settings['success_class'],
				    settings['warning_class'],
				    settings['info_class']
				].join(' ')).addClass(type);
			}
		},
		content : function(content,el){
			$this=el||$(this);
			settings=$.extend(true, {}, options, $this.data('summarise').options);		
			if(content!==null&&content!==undefined){
				if(typeof content == "object"){
					
					$this.html('');
					
					if(settings.tpl_close!==null&&settings.tpl_close!==undefined)
						$this.append($(settings.tpl_close));					
					if(content['message']!==undefined&&content['message']!==null)
						$this.append(content['message']);
					if(content['list']!==undefined&&content['list']!==null){	
						var list=$('<ul/>').appendTo($this);
						$.each(content['list'], function(i, v){
							list.append($('<li/>').text(v));
						});
					}
				}else
					$this.html(content);
				$this.css({display:'block'});
			}			
		},
		reset : function(){
			reset($(this));
		},
		close : function(){
			$this=$(this);
			reset($this);
			$this.css({display:'none'});			
		}
	},
	reset = function(el){
		$this=el;
		settings=$.extend(true, {}, options, $this.data('summarise').options);
		$this.removeClass([
			settings['error_class'],
			settings['success_class'],
			settings['warning_class'],
			settings['info_class']
		].join(' ')).html('');			
	};
	
	$(document).on('click', '.summarise-alert .close', function(event){
		event.preventDefault();
		$(this).parents('.summarise-alert').summarise('close');
	});
	
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

/** This catches all not covered by the plugin */
$(function(){
	$(document).on('click', '.alert .close', function(event){
		event.preventDefault();
		$(this).parents('.alert').css({ 'display': 'none' }).removeClass('alert-error alert-success alert-warning alert-info').html('');
	});
});