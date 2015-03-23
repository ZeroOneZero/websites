$(function() {
	// Theme management
	if($.cookie('core-theme')) {
		$('#theme-select option[value="'+$.cookie('core-theme')+'"]').attr('selected', 'selected');
		$.get('themes.json', function(data) {
			less.modifyVars(data[$.cookie('core-theme')]);
		});
	}
	$('#theme-select').change(function() {
		var themeName = $('#theme-select option:selected').attr('value'); // 'light' or 'dark'
		$.cookie('core-theme', themeName, {expires: 365});
		$.get('themes.json', function(data) {
			less.modifyVars(data[themeName]);
		});
	});
});