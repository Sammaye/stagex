var upload_timer;
var timeout = 10000;
var u_ids = [];

$(document).on('change', '.upload input[type=file]', function(event){

	el=$(this).parents('.upload');

	reset_bar_message(el);

	var filename = el.find('input[type=file]').val(),
		path_parts = filename.split('\\');		
		
	el.find("#Video_title").val(path_parts[path_parts.length-1]);
	
	el.find('.upload_form').hide();
	el.find('.upload_details').show();
	
	$(this).parents('form').submit();
	add_upload();
});

$(document).on('click', '.upload .remove', function(event){
	event.preventDefault();
	$('.upload[data-id='+id+']').remove();
	u_ids.splice(u_ids.indexOf(id), 1);
});

$(document).on('click', '.upload .cancel', function(event){
	event.preventDefault();
	stop_upload($(this).parents('.upload').data().id);
});

$(document).on('click', '.upload .alert .close', function(event){
	event.preventDefault();
	reset_bar_message($(this).parents('.upload'));
});

function finish_upload(id, success, message){
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
}

/**
 * Stops an upload
 *
 * @param id
 */
function stop_upload(id){

	var answer = confirm("Are you sure you wish to cancel this upload?"), p_id = "#uploading_pane_"+id;

	// Only remove the upload if it has not completed.
	if (answer && ($(p_id+" .uploadProgInner").css("width") != "100%")){
		// Show Cancel message
		show_bar_message($(p_id).parents('.upload_item'), false, 'The upload was cancelled by the user.');

		$(p_id).parents('.upload').find('.uploadForm').remove();

		// Hide parts of the form
		$(p_id).parents('.upload_item').find('.uploadBar,.toggle_panel,.upload_details').hide();
		$("#upload_item_"+id).find('.form_top .remove').show();
		u_ids.splice(u_ids.indexOf(id), 1);
	}else{
		show_bar_message($(p_id).parents('.upload_item'), false, 'An unknown error occurred. The upload could not be stopped.');
	}
}

/**
 * This adds a new upload form to the screen
 */
function add_upload(){
	var ts, count;

	// We make a ts as a cache buster for shit browsers cough-Opera-cough
	ts = Math.round(new Date().getTime() / 1000);

	// How many forms already exist?
	count = $(".upload").length;

	// Generate the form
	$.get("/video/add_upload", {ts: ts}, function(data){

		// Add to the page
		$(".upload_list").append(data.html);

		// Add the upload id to the list of IDs
		var e = $(".upload").last().find("input[name=UPLOAD_IDENTIFIER]").val();
		count_ids = u_ids.length;
		u_ids[count_ids] = e;
		
		// trigger
		get_upload_progress();
	}, 'json');
}

function reset_bar_message(selector){
	selector.find('.alert').removeClass('success error').css({ 'display': 'none' }).find('span').html('');
}

function show_bar_message(selector, success, message){
	if(success){
		selector.find('.alert').addClass('success').css({ 'display': 'block' }).find('span').html(message);
	}else{
		selector.find('.alert').addClass('error').css({ 'display': 'block' }).find('span').html(message);
	}
}

function get_upload_progress(){
	if(u_ids.length>0){
		$.getJSON('/video/get_upload_info', {ids: u_ids}, function(data){
			// Scrolls through the IDs assigning the information.
			for(i = 0; i < info.length; i++){
	
				// Sometimes uploadprogress can return null for a upload
				if(info[i] != null){
					// Ascertain the IDs for the elements needing change
					
					var el = $('.upload[data-id='+info[i].id+']');
	
					if(info[i].hasOwnProperty('message')){
						// Change the width of the progress bar to match done and set a message for the upload status
						el.find('.progress_bar .progress').css("width", 90+"%");
						el.find('.upload_details .status').html('90% - '+info[i]['message']);
					}else{
						// Calculate how much has been uploaded
						var done = Math.floor(100 * parseInt(info[i].uploaded) / parseInt(info[i].total));
	
						// Change the width of the progress bar to match done and set a message for the upload status
						el.find('.progress_bar .progress').css("width", done+"%");
						el.find('.upload_details .status').html(done+'% - '+"Estimated Time Left: "+info[i].left+" at "+info[i].speed+"ps");
					}
				}
			}
		});
	}
	upload_timer = setTimeout("get_upload_progress()", timeout);
}