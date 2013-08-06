;(function($, window, document, undefined){
	var 
	options={
		showFollowerCount: true,
		button: '.button',
		followerCount: '.follower_count',
		user_id: null
	},
	methods={
		init : function(opts){
			settings=$.extend(true,{},options,opts);
			return this.each(function(){
				data = $(this).data('subscribeButton');

				if(!data){
					$(this).data('subscribeButton', settings)
						.on('click', settings.button+" .subscribe", subscribe, {container:this})
						.on('click', settings.button+" .unsubscribe", unsubscribe, {container:this});
					if(!$(this).hasClass('subscribeButton'))
						$(this).addClass('subscribeButton');
				}				
			});
		},
		destroy : function(){
			return this.each(function(){
				$(this).data('subscribeButton');
				$(this).removeClass('subscribeButton');
				$(this).removeData('subscribeButton');
			});			
		}
	},
	subscribe=function(e){
		e.preventDefault();
		var container=$(event.data.container),
			el=$(this),
			user_id=$(this).data().id!=undefined?$(this).data().id:container.data('subscribeButton').user_id;
		
		$.get('/user/subscribe', {id: user_id}, null, 'json').done(function(data){
			if(data.success){
				el.removeClass('btn-success subscribe').addClass('btn unsubscribe').text('Unfollow');
			}else{}
		});		
	},
	unsubscribe=function(e){
		e.preventDefault();
		el=$(event.data.container);
		user_id=$(this).data().id!=undefined?$(this).data().id:el.data('subscribeButton').user_id;
		
		$.get('/user/unsubscribe', {id: user_id}, null, 'json').done(function(data){
			if(data.success){
				el.removeClass('btn unsubscribe').addClass('btn-success subscribe').text('Follow');
			}else{}
		});				
	};
	
	$.subscribeButton = function( method ) {
		if ( methods[method] ) {
			return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.subscribeButton' );
		}
	};	
})( jQuery, window, document );