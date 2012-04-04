(function($) {

	$.ajaxSetup ({ cache: false });

	// clear firefox and safari cache
	$(window).unload( function() {}); 

	function wheres_the_top() {
		// window width
		wnd_width = parseInt( $(window).width() );
		// window height
		wnd_height = parseInt( $(window).height() );
		// how far down the page the use has scrolled
		var st = $('html').scrollTop();
		var top_adjust = wnd_height / 4.6;
		// where message boxes will appear
		if ( st > top_adjust ) {
			var msg_top = st - (wnd_height/4.6);
		} else {
			var msg_top = st;
		}
		
		
		return msg_top;
	}

	$('.show-if-js').css({ 'display' : 'inline-block' });
	$('.hide-if-no-js').removeClass( 'hide-if-no-js' );
	
	
	$('#mer-reg-page-apply-coupon-btn').click(function() {
		var error_msg = "We're sorry but that coupon code does not appear to be vaild. If this is incorrect, please contact the site administrator.";
		show_event_queue_ajax_error_msg( error_msg );
		return false;
	});


	$('#display-more-attendee-copy-options').click(function() {
		if ( $('#mer-reg-page-copy-all-attendee-chk').prop('checked', true) ) {
			$('#mer-reg-page-copy-all-attendee-chk').trigger('click');
			//$('.mer-reg-page-copy-attendee-chk').trigger('click');
		}
	});


	/**
	*		trigger click event on all checkboxes if the Copy All option is selected
	*/
	$('#mer-reg-page-copy-all-attendee-chk').click(function() {
		$('.mer-reg-page-copy-attendee-chk').each(function(index) {
			if ( $(this).prop('checked') != $('#mer-reg-page-copy-all-attendee-chk').prop('checked') ) {
				$(this).trigger('click');
			}
			//$('.mer-reg-page-copy-attendee-chk').trigger('click');
		});
		verify_all_questions_answered('#mer-registration-frm-1');
	});
	
		
	
	/**
	*		do_before_event_queue_ajax
	*/	
	function do_before_event_queue_ajax() {
		// stop any message alerts that are in progress	
		$('.event-queue-msg').stop();
		// spinny things pacify the masses
		var st = $('html').scrollTop();
		var po = $('#mer-ajax-loading').parent().offset();
		
		var wh = $(window).height();
		var mal_top = ( st+(wh/2)-po.top ) - 15;

		var ww = $('#mer-ajax-loading').parent().width();
		var mal_left = ( ww/2 ) -15;
		
		$('#mer-ajax-loading').css({ 'top' : mal_top, 'left' : mal_left }).show();
		
	}	

	
	/**
	*		show event queue ajax success msg
	*/	
	function show_event_queue_ajax_success_msg( success_msg ) {
		
		if ( success_msg != undefined && success_msg != '' )  {
		
			if ( success_msg.success != undefined ) {
				success_msg = success_msg.success;
			}		
			//alert( 'success_msg'+success_msg);
			msg_top = wheres_the_top();
			$('#mer-success-msg').css({ 'top' : msg_top });	
			$('#mer-success-msg > .msg').html( success_msg );
			$('#mer-ajax-loading').fadeOut('fast');
			$('#mer-success-msg').removeClass('hidden').show().delay(4000).fadeOut();			
		} else {
			$('#mer-ajax-loading').fadeOut('fast');
		}	
	}	
	
		
	/**
	*		show event queue ajax error msg
	*/	
	function show_event_queue_ajax_error_msg( error_msg ) {
			
		if ( error_msg != undefined && error_msg != '' ) {
		
			if ( error_msg.error != undefined ) {
				error_msg = error_msg.error;
			} 
			//alert( 'error_msg'+ error_msg);
			msg_top = wheres_the_top();
			$('#mer-error-msg').stop().css({ 'top' : msg_top });				
			$('#mer-error-msg > .msg').html( error_msg );
			$('#mer-ajax-loading').fadeOut('fast');
			$('#mer-error-msg').removeClass('hidden').show().delay(8000).fadeOut();

		} else {
			$('#mer-ajax-loading').fadeOut('fast');
		}
	}
	
	
	$('input[type="text"]').focusout(function() {   
		if ( $.trim(this.value) != '' ){
			$(this).removeClass('requires-value');
		}
	});	
	
		
	$('.mer-reg-page-copy-attendee-chk').click(function() { 

		// the checkbox that was clicked
		var clicked_checkbox = $(this);
		
		// the primary attendee question group
		var prmry_att_qstn_grp = $(this).val();
		// find all of the primaray attendee's questions for this event
		var prmry_att_questions = $( '#mer-reg-page-attendee-wrap-' + prmry_att_qstn_grp ).children( '.event_questions' ).find('input');		

		// the targeted attendee question group
		var trgt_att_qstn_grp = $(this).attr('rel');
		//alert ( 'trgt_att_qstn_grp = ' + trgt_att_qstn_grp );
		
		// set some empty vars (and reset when we loop back)
		var input_id = '';
		var new_input_id = '';
		var input_name = '';
		var input_value = '';
		
		// for each question in the targeted attendee question group
		$( prmry_att_questions ).each(function(index) {
		
			input_id = $(this).attr('id');
			// split the above var
			var input_id_array =  input_id.split('-');

			// grab the current event id
			var event_id = input_id_array[0];		 
			
			input_name = $(this).attr('name');
			input_value = $(this).val();
			
			//alert ( 'input_id = ' + input_id + '\n' + 'input_name = ' + input_name  + '\n' + 'event_id = ' + event_id + '\n' + 'att_nmbr = ' + trgt_att_nmbr );
						
			// if the input is required but has not been filled out
			if ( $(this).hasClass('required') && input_value == '' ) {  
			
				$(this).addClass('requires-value');
				// find label for this input
				var lbl = $(this).prev('label');
				// grab it's text
				var lbl_txt = $(lbl).html();
				//alert(lbl_txt);
				// remove "<em>*</em>" from end
				lbl_txt = lbl_txt.substring(0, lbl_txt.length - 10);
				// show an error msg
				var error_msg = 'The ' + lbl_txt + ' input is a required field. Please enter a value for this field and all other required fields before preceeding.';
				//show_reg_page_copy_attendee_error( event_id, error_msg );	
				show_event_queue_ajax_error_msg( error_msg );	
				// uncheck the checkbox that was clicked
				$(clicked_checkbox).prop('checked', false);
				// fill out yer damn form will ya!!!
				exit;			
			
			} else {

				new_input_id = '#' + trgt_att_qstn_grp + '-' +  input_id_array[5];
				
				if ( $(new_input_id).length > 0 ){
					$(new_input_id).val(input_value);
				}

				var billing = '#reg-page-billing-' + input_id_array[5];
				// copy to billing info
				if ( $(billing).val() == '' ) {
					$(billing).val(input_value);
				}				
			}
		});		
	});	
	

	
	function scroll_to_top_of_form( msg ) {
		//alert('scroll_to_top_of_form');
		var top_of_form = $('#mer-reg-page-steps-display-dv').offset();
		top_of_form = top_of_form.top - 10;		
		$("html, body").animate({ scrollTop: top_of_form }, 'normal', function() {
			if ( msg.success ) {
				show_event_queue_ajax_success_msg( msg.success );
			} else {
				show_event_queue_ajax_error_msg( msg.error );
			}
		});
	}	

	// Registration Steps

	// hide and display steps
	function hide_step_goto( step_to_hide, step_to_show, msg ) {
		//alert('hide_step_goto');
		$('#mer-reg-page-step-'+step_to_hide+'-dv').slideUp( function() {				
			$('#mer-reg-page-step-'+step_to_hide+'-dv').height(0);
			$('#mer-reg-page-edit-step-'+step_to_hide+'-lnk').removeClass('hidden');		
			$('#mer-reg-page-step-'+step_to_show+'-dv').css('display','none').removeClass('hidden').slideDown( function() {
				scroll_to_top_of_form( msg );
			});
		});	
		$('.mer-reg-page-step-display-dv').removeClass('active-step').addClass('inactive-step');	
		$('#mer-reg-page-step-'+step_to_show+'-display-dv').removeClass('inactive-step').addClass('active-step');
		$('#mer-reg-page-edit-step-'+step_to_show+'-lnk').addClass('hidden');	
		$('#mer-ajax-loading').fadeOut('fast');
	}
	
	
	
	// go to step 1
	function mer_reg_page_go_to_step_1( msg ) {	
	
		if ( msg == undefined ) {
			msg ='';
		}
		// set step 1 back to auto height 
		$('#mer-reg-page-step-1-dv').css( 'height', 'auto' );
		// if step 2 is expanded 
		if ( $('#mer-reg-page-step-2-dv').height() > 0 ) {
			// hide step 2
			hide_step_goto( 2, 1, msg );
		} else {
			// must be step 3 that is expanded
			hide_step_goto( 3, 1, msg );
		}	
	}




	// go to step 2
	function mer_reg_page_go_to_step_2( msg ) {	

		if ( msg == undefined ) {
			msg ='';
		}
		//	$('.mer-reg-page-go-to-step-2').click(function() {
		$('#mer-reg-page-step-2-dv').css({ 'display' : 'none' }).removeClass('hidden');
		// set step 2 back to auto height 
		$('#mer-reg-page-step-2-dv').css( 'height', 'auto' );
		// if step 1 is expanded
		if ( $('#mer-reg-page-step-1-dv').height() > 0 ) {
			// hide step 1		
			hide_step_goto( 1, 2, msg );	
		} else {		
			// must be step 3 that is expanded
			hide_step_goto( 3, 2, msg );		
		}	
	}



	// go to step 3
	function mer_reg_page_go_to_step_3( msg ) {	

		if ( msg == undefined ) {
			msg ='';
		}
			
		if ( verify_all_questions_answered('#mer-registration-frm-2')) {
		
			$('#mer-reg-page-step-3-dv').css({ 'display' : 'none' }).removeClass('hidden');		
			// set step 3 back to auto height 
			$('#mer-reg-page-step-3-dv').css( 'height', 'auto' );	
			// if step 1 is expanded
			if ( $('#mer-reg-page-step-1-dv').height() > 0 ) {
				// hide step 1		
				hide_step_goto( 1, 3, msg );	
			} else {
				// must be step 2 that is expanded
				hide_step_goto( 2, 3, msg );	
			}	
			
		} else {
			msg = new Object();
			msg.error = 'Sorry, but you need to answer all required questions before you may proceed.';		
			scroll_to_top_of_form( msg );			
		}

	}
	

	// go to step 4
	function mer_reg_page_go_to_step_4( msg ) {
		scroll_to_top_of_form( msg );
	}	


	// go to step 1 via edit link
	$('.mer-reg-page-go-to-step-1').click(function() {
		mer_reg_page_go_to_step_1('');
		return false;
	});
	
	// go to step 2 via edit link
	$('.mer-reg-page-go-to-step-2').click(function() {
		mer_reg_page_go_to_step_2('');
		return false;
	});

	// go to step 3 via edit link
	$('.mer-reg-page-go-to-step-3').click(function() {
		mer_reg_page_go_to_step_3('');
		return false;
	});

	
	// submit Step 1 of registraion form
	$('#mer-reg-page-go-to-step-2-btn').click(function() {	
		process_reg_step ( 1 );
	});
		
	
	// submit Step 2 of registraion form
	$('#mer-reg-page-go-to-step-3-btn').click(function() {	
		process_reg_step ( 2 );
	});
		
	
	// submit Step 3 of registraion form
	$('#mer-reg-page-confirm-reg-btn').click(function() {	
		process_reg_step ( 3 );
	});
	
	
	
	function mer_reg_page_go_to( step, response ) {
	
		if ( response.error != '' && response.error != undefined ) {
			show_event_queue_ajax_error_msg( response.error );
		} else {
			if ( step == 2 ) {
				mer_reg_page_go_to_step_2( response );
			} else if ( step == 3 ) {
				mer_reg_page_go_to_step_3( response );
			} else if ( step == 4 ) {
				mer_reg_page_go_to_step_4( response );
			}  		
		}	

	}
	
	
		/**
	*		submit a step of registraion form
	*/	
	function process_reg_step ( step ) {

		if ( verify_all_questions_answered('#mer-registration-frm-'+step) ) {

			$('#mer-reg-page-step-'+step+'-ajax').val(1);
			$('#mer-reg-page-step-'+step+'-action').attr( 'name', 'action' );		
			var form_data = $('#mer-registration-frm-'+step).serialize();
			
//alert( '#mer-reg-page-step-'+step+'-action = ' + $('#mer-reg-page-step-'+step+'-action').val() + '\n' + 'espresso.ajax_url = ' + espresso.ajax_url );

			$.ajax({
						type: "POST",
						url:  event_espresso.ajax_url,
						data: form_data,
						dataType: "json",
						beforeSend: function() {
							do_before_event_queue_ajax();
						}, 
						success: function(response){	
							var next = parseInt(step) + 1;
//alert( 'step = ' + step + '\n' + 'response.return_data = ' + response.return_data + '\n' + 'response.success = ' + response.success + '\n' + 'response.error = ' + response.error );
							if ( response.return_data != undefined ) {
								process_return_data( next, response );
							} else {
								mer_reg_page_go_to( next, response );						
							}								
						},
						error: function(response) {
						alert( response.error );
							msg = new Object();
							msg.error = 'An error occured! Registration Step '+step+' could not be completed. Please refresh the page and try again.';
							show_event_queue_ajax_error_msg( msg );
						}			
				});	

		} else {
			msg = new Object();
			msg.error = 'You need to answer all required questions before you can proceed.';		
			scroll_to_top_of_form( msg );
		}
		
		
		return false;
		
	}





	function process_return_data( next, response ) {
	
		for ( key in response.return_data ) {
			if ( key == 'reg-page-confirmation-dv' ) {			
				$( '#reg-page-confirmation-dv' ).html( response.return_data[key] );
			} else if ( key == 'redirect-to-thank-you-page' ) {
				window.location.replace( response.return_data[key] );
				return;
			}			
		}

		msg = new Object();
		msg.success = response.success;
		mer_reg_page_go_to( next, msg );		
			
	}
	

	
	
	
	/**
	*		show reg page copy attendee error msg
	*/	
	function verify_all_questions_answered( whch_form ) {	
	
		if ( $('#reg-page-no-payment-required').val() == 1 ) {
			return true;
		}
		
		 if ( whch_form == '' ){
			whch_form = '#mer-registration-frm-1';
		}
		
		var good_to_go = true;
		$( whch_form + ' .required' ).each(function(index) {
			if ( $(this).val() == '' ) {
				good_to_go = false;
				$(this).addClass('requires-value');
			} else {
				$(this).removeClass('requires-value');
			}	
		});
		
		return good_to_go;
				
	}
	
		
	
	
	/**
	*		show reg page copy attendee error msg
	*/	
	function show_reg_page_copy_attendee_error( event_id, error_msg ) {
		
		$('#mer-error-msg-' + event_id + ' > .msg').html( error_msg );
		$('#mer-ajax-loading').fadeOut('fast');
		$('#mer-error-msg-' + event_id ).show().delay(8000).fadeOut();
	
	}
	
	
	// generic click event for displaying and giving focus to an element and hiding control 
	$('.display-the-hidden').click(function() {
		// get target element from "this" (the control element's) "rel" attribute
		var item_to_display = $(this).attr("rel"); 
		// hide the control element
		$(this).addClass('hidden');  
		// display the target's div container - use slideToggle or removeClass
		$('#'+item_to_display+'-dv').slideToggle(500, function() {
			// display the target div's hide link
			$('#hide-'+item_to_display).removeClass('hidden'); 
			// if hiding/showing a form input, then id of the form input must = item_to_display
			//$('#'+item_to_display).focus(); // add focus to the target
		}); 
		return false;
	});

	// generic click event for re-hiding an element and displaying it's display control 
	$('.hide-the-displayed').click(function() {
		// get target element from "this" (the control element's) "rel" attribute
		var item_to_hide = $(this).attr("rel"); 
		// hide the control element
		$(this).addClass('hidden');  
		// hide the target's div container - use slideToggle or addClass
		$('#'+item_to_hide+'-dv').slideToggle(500, function() {
			//$('#'+item_to_hide+'-dv').delay(250).addClass('hidden'); 
			// display the control element that toggles display of this element
			$('#display-'+item_to_hide).removeClass('hidden');  
		}); 
		return false;
	});	
		


})(jQuery);

