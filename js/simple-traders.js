jQuery(document).ready(function() {
	
	jQuery('.widget-traders-list h4').click(function() {
		jQuery(this).next('ul').slideToggle(100);	
	});
	
});