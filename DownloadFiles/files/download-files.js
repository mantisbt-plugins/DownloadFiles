jQuery(document).ready(function($) {

	var click_set_checked = function (e){
		var selection_file;
		selection_file = $(this);
		if (selection_file.attr("checked") != "checked") {
			selection_file.attr({"checked":true});
		} else {
			selection_file.attr({"checked":false});
	};
	
	var click_set_checked_all = function (e){
		if ($('input[class=selection-all-files-df]').attr("checked")){
			$('input[class=selection-all-files-df]').attr({"checked":false});
			$('input[class=selection_file]').attr({"checked":false});
		}else {
			$('input[class=selection-all-files-df]').attr({"checked":true});
			$('input[class=selection_file]').attr({"checked":false});
			$('input[class=selection_file]').click();
		}
	};
	
	var callback_dialog_on_load = function (response, status, xhr) {
		$('input[class=selection_file]').click(click_set_checked); 
		$('input[class=selection-all-files-df]').click(click_set_checked_all); 
		$("#download_files_dialog").dialog({
			modal: true,
			width: "auto",
			height: "auto",
			});
	};
	
	var click_open_dialog = function(ev){
		ev.preventDefault();
		var url = $(".download_files_trigger").data("remote");
		$("#download_files_dialog").load(url,callback_dialog_on_load);
	}
	
	$("a.download_files_trigger").click(click_open_dialog);
	
});
