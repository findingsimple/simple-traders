jQuery(document).ready(function() {
	
	jQuery('.widget-traders-list h4').click(function() {
		window.theH = jQuery(this);
		jQuery('.widget-traders-list h4').each(function(index,element) {
			if(!jQuery(this).is(window.theH))
				jQuery(this).next('ul').slideUp(250);	
		});
		jQuery(this).next('ul').slideToggle(250);
	});
	
});