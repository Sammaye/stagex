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