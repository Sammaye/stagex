(function(Uploader, $, undefined){
	
	var ts,
		count = 0,
		list = [],
		timer = setTimeout("get_upload_progress()", timeout);
	
	Uploader.el='.file_list';
	Uploader.timeout=10000;
	
	Uploader.construct = function(){
		
		// Add a new upload form
		add_upload();

		$(document).on('change', '.upload_item input[type=file]', function(event){

			reset_bar_message($(this).parents('.upload_item'));

			if(check_upload($(this).parents('.upload_item').data().id)){
				$(this).parents('.upload_item').find('.uploadForm').hide();
				$(this).parents('.upload_item').find('.uploading_pane').show();
				$(this).parents('form').submit();
				add_upload();
			}else{
				alert('The file you selected did not match our requirements. Please try a different file.');
			}
		});

		$(document).on('click', '.upload_item .uploadBar .cancel', function(event){
			event.preventDefault();
			stop_upload($(this).parents('.upload_item').data().id);
		});

		$(document).on('click', '.upload_item .form_top .remove a', function(event){
			event.preventDefault();
			remove_upload($(this).parents('.upload_item').data().id);
		});

		$(document).on('click', '.toggle_panel', function(event){
			event.preventDefault();
			var form = $(this).parents('.upload_item').find('.upload_details');

			if(form.css('display') == 'block'){
				form.css({ 'display': 'none' });
				$(this).text('Show More Options');
			}else{
				form.css({ 'display': 'block' });
				$(this).text('Show Less Options');
			}
		});

		$(document).on('click', '.save_video_details', function(event){
			event.preventDefault();
			var this_o = $(this),
				data_a = this_o.parents('.upload_details').find('select, textarea, input').serializeArray();
			data_a[data_a.length] = {name: 'upload_id', value: this_o.parents('.upload_item').data().id};

			$.post('/video/upload_info_save', data_a, function(data){
				if(data.success){
					forms.summary(this_o.parents('.upload_details').find('.block_summary'), true,
						'The details to this upload were saved.', data.errors);
				}else{
					forms.summary(this_o.parents('.upload_details').find('.block_summary'), false,
						'The details to this upload could not be saved because:', data.errors);
				}
			}, 'json');
		});

		$(document).on('click', '.upload_item .bar_summary .close', function(event){
			event.preventDefault();
			reset_bar_message($(this).parents('.upload_item'));
		});		
	};
		
	Uploader.add = function(){
		// We make a ts as a cache buster for shit browsers cough-Opera-cough
		ts = Math.round(new Date().getTime() / 1000);

		// How many forms already exist?
		c = $(".file").length;

		// Generate the form
		$.get("/video/add_upload", {ts: ts}, function(data){

			// Add to the page
			$("#").append(data);

			// If this is the first form then add the updater iframe
			//if(count == 0 && $('#uInfo_ifr').length == 0){
				//$("#u_iframe_container").html("<iframe id='uInfo_ifr' src='/video/get_upload_info' name='uInfo_ifr'></iframe>");
			//}

			// Add the upload id to the list of IDs
			list[list.length] = $(".file").last().find("input[name=UPLOAD_IDENTIFIER]").val();
		});
	};
	
	Uploader.start = function(){
		
	};
	
	Uploader.stop = function(){
		var answer = confirm("Are you sure you wish to cancel this upload?"), p_id = "#uploading_pane_"+id;

		// Only remove the upload if it has not completed.
		if (answer && ($(p_id+" .uploadProgInner").css("width") != "100%")){
			// Show Cancel message
			show_bar_message($(p_id).parents('.upload_item'), false, 'The upload was cancelled by the user.');

			$(p_id).parents('.upload_item').find('.uploadForm').remove();

			// Hide parts of the form
			$(p_id).parents('.upload_item').find('.uploadBar,.toggle_panel,.upload_details').hide();
			$("#upload_item_"+id).find('.form_top .remove').show();
			u_ids.splice(u_ids.indexOf(id), 1);
		}else{
			show_bar_message($(p_id).parents('.upload_item'), false, 'An unknown error occurred. The upload could not be stopped.');
		}		
		
	};
	
	Uploader.remove = function(){
		$("#upload_item_"+id).remove();
		u_ids.splice(u_ids.indexOf(id), 1);		
	};
	
	Uploader.update = function(){
		// Scrolls through the IDs assigning the information.
		for(i = 0; i < info.length; i++){

			// Sometimes uploadprogress can return null for a upload
			if(info[i] != null){
				// Ascertain the IDs for the elements needing change
				var id = "#uploading_pane_"+info[i].id;
				var message_id = "#upload_status_"+info[i].id;

				if(info[i].hasOwnProperty('message')){
					// Calculate how much has been uploaded
					var done = 90;

					// Change the width of the progress bar to match done and set a message for the upload status
					$(id+" .uploadProgInner").css("width", done+"%");
					$(id+" .percent_complete").html(done+"%");

					$(message_id+" span").html(info[i]['message']);
				}else{
					// Calculate how much has been uploaded
					var done = Math.floor(100 * parseInt(info[i].uploaded) / parseInt(info[i].total));

					// Change the width of the progress bar to match done and set a message for the upload status
					$(id+" .uploadProgInner").css("width", done+"%");
					$(id+" .percent_complete").html(done+"%");

					$(message_id+" span").html("Estimated Time Left: "+info[i].left+" at "+info[i].speed+"ps");
				}
			}
		}		
	};
	
	Uploader.finish = function(){
		reset_bar_message($("#upload_item_"+id));
		show_bar_message($("#upload_item_"+id), success, message);

		$("#upload_item_"+id).find('.uploadBar .uploadProgInner').css("width", "100%");
		$("#upload_item_"+id).find('.uploadBar .percent_complete').html('100%');
		$("#upload_item_"+id).find('.uploadBar .cancel').hide();

		u_ids.splice(u_ids.indexOf(id), 1);
		if(success){
			$("#upload_item_"+id).find('.uploadBar .message span').html('Completed');
		}else{
			$("#upload_item_"+id).find('.toggle_panel').hide();
			$("#upload_item_"+id).find('.upload_details').hide();
			$("#upload_item_"+id).find('.uploadBar .message span').html('Failed');
			$("#upload_item_"+id).find('.form_top .remove').show();
		}		
	};
	
	
	
	function reset_bar_message(selector){
		selector.find('.bar_summary').removeClass('success error').css({ 'display': 'none' }).find('span').html('');
	}

	function show_bar_message(selector, success, message){
		if(success){
			selector.find('.bar_summary').addClass('success').css({ 'display': 'block' }).find('span').html(message);
		}else{
			selector.find('.bar_summary').addClass('error').css({ 'display': 'block' }).find('span').html(message);
		}
	}

	function getProgess(){
		$.getJSON('/video/get_upload_info', {ids: u_ids}, function(data){
			update_progress(data);
		});
		upload_timer = setTimeout("getProgress()", timeout);
	}
	
	
}( window.Uploader = window.Uploader || {}, jQuery ));