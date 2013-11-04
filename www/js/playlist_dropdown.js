(function( $ ){
	
	var preVal='',
		t = setInterval(function(){ search(); },1000),
		options = {
	},
	methods={
		init: function(){
		
			return this.each(function(){

				if(!$(this).data('jdropdown'))
					$(this).jdropdown();

				$(document).on('click', '.playlist-dropdown .playlist_link', function(e){
					e.preventDefault();
					var params = [{name:'playlist_id',value:$(this).data().id}];

					id_length=0;
					list_path='.video_list .video .checkbox_col';
					if($('.video_list .video').length<=0){
						list_path='.video_list .video_row .checkbox_col';
					}
					
					$(list_path+' input:checked').each(function(i,item){
						params[params.length]={name:['video_ids['+id_length+']'],value:$(item).val()};
						id_length++;
					});						
					
					$.post('/playlist/addVideo', params, null, 'json').done(function(data){
						$('.playlists-panel').css({display:'none'});
						if(data.success){
							$('.message-panel').css({display:'block'}).find('p').addClass('text-success').html((id_length>1?'Videos ':'Video ')+'added to playlist');
						}else{
							$('.message-panel').css({display:'block'}).find('p').addClass('text-error')
							.html((id_length>1?'Videos ':'Video ')+'could not be added to playlist due to an internal error');
						}
					});
				});

				$('.playlist-dropdown').on('jdropdown.open', function(){
					var menu=$(this);
					menu.find('.message-panel').css({display:'none'});
					menu.find('.playlists-panel').css({display:'block'});
				});

				$(document).on('click','.playlist-dropdown .message-back',function(e){
					e.preventDefault();
					var menu=$(this).parents('.playlist-dropdown');
					menu.find('.message-panel').css({display:'none'});
					menu.find('.playlists-panel').css({display:'block'});
				});

				$(document).on('click','.playlist-dropdown .message-close',function(e){
					e.preventDefault();
					var menu=$(this).parents('.playlist-dropdown');
					menu.find('.message-panel').css({display:'none'});
					menu.find('.playlists-panel').css({display:'block'});

					// trigger close
					$('.playlist-dropdown').jdropdown('close');
				});
			});

		}		
	};

	function search(){
		var term=$('.playlist-dropdown .playlists-panel .head_ribbon input').val();
		if(term!=preVal){
			//perform search
			$.get('/playlist/ajaxsearch',{term:term},null,'json').done(function(data){
				if(data.success){
					$('.playlist-dropdown .playlists-panel .playlist_results').html('');
					$.each(data.results, function(i,item){
						$('<div/>').addClass('item playlist_link')
						.data({id:item._id})
						.html(item.title+' ('+item.totalVideos+')')
						.appendTo('.playlist-dropdown .playlists-panel .playlist_results');
					});
				}
			});
		}
		preVal=term;
	}

	$.fn.playlist_dropdown = function( method ) {
	
		if ( methods[method] ) {
			return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.playlist_dropdown' );
		}
	
	};

})( jQuery );