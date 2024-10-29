jQuery(document).ready(function($) {
	$('.selectUser').click(function () {

		$('.selectUser').parent().removeClass("sb-user-selected");
		$(this).parent().addClass("sb-user-selected");
	
		// User id para la tabla
	    var userId = $(this).data("user_id");
	    $('input[name="user_id"]').val(userId);
	
	    // usuario selecionado
	    var userName = $(this).data("user_name");
	    $('#user-selected').html(userName);
	
	    // Submit buttons
	    $('.appointmentsw-submit').show();
	    
	    // Change tab selected
	    $('#mytabs a[href="#calendario"]').tab('show');
	});

	// by default hidden
	// Submit buttons
	$('.appointmentsw-submit').hide();

	if( $('#button_changes').length ) {
		var check_changes_function = function() {
			
			// AJAX
			jQuery.ajax({
				type: "post",url: my_ajax_object.ajax_url,data: { action: 'check_changes' },
				success: function(html){ //so, if data is retrieved, store it in html
					if ( !isNaN( html ) ) {
						if ( html > 0 ) {
							jQuery("#button_changes").removeClass("btn-success");
							jQuery("#button_changes").addClass("btn-danger");
						} else {
							jQuery("#button_changes").addClass("btn-success");
							jQuery("#button_changes").removeClass("btn-danger");
						}
					}
				}
			});

		};
		var interval = 10000; 
		setInterval(check_changes_function, interval);
	}
	
});

