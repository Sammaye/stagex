var upload_timer;
var timeout = 10000;
var u_ids = [];

$(document).on('change', '.upload input[type=file]', function(event){

	el=$(this).parents('.upload');

	el.find('.alert').summarise('reset');

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
	id=$(this).parents('.upload').data().id;
	$(this).parents('.upload').remove();
	u_ids.splice(u_ids.indexOf(id), 1);
});

$(document).on('click', '.upload .cancel', function(event){
	event.preventDefault();
	
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
});

$(document).on('click', '.upload_details .btn-success', function(event){
	event.preventDefault();
	var $this = $(this),
		data = this.parents('form').find('select, textarea, input').serializeArray();
	data[data.length] = {name: 'upload_id', value: this.parents('.upload').data().id};

	$.post('/video/ajaxSave', data, function(data){
		if(data.success){
			forms.summary(this_o.parents('.upload_details').find('.block_summary'), true,
				'The details to this upload were saved.', data.errors);
		}else{
			forms.summary(this_o.parents('.upload_details').find('.block_summary'), false,
				'The details to this upload could not be saved because:', data.errors);
		}
	}, 'json');
});

function finish_upload(id, success, message){
	el=$('.upload[data-id='+id+']');
	el.find('.upload_details').children('.alert').summarise('set',success?'success':'error',message);
	el.find('.status,.progress_bar').css({display:'none'});
	el.find('.cancel').removeClass('cancel').addClass('remove').text('Remove');
	
	u_ids.splice(u_ids.indexOf(id), 1);
	if(!success)
		el.find('.edit_information').css({display:'none'});
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
		$(".upload").last().find('.upload_details').children('.alert').summarise({tpl_close:''});
		$('.upload').last().find('.edit_information .alert').summarise();
		
		count_ids = u_ids.length;
		u_ids[count_ids] = e;
		
		// trigger
		get_upload_progress();
	}, 'json');
}

function get_upload_progress(){
	if(u_ids.length>0){
		$.getJSON('/video/get_upload_info', {ids: u_ids}, function(data){
			// Scrolls through the IDs assigning the information.
			
			if(data.success)
				info=data.status;
			else
				return;
			
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