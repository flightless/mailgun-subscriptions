(function ( $, window ) {
	$( document ).ready( function ( $ ) {
		var ajaxurl = window.MailgunSubscriptions.ajaxurl;

		var subscriptionWidget = $( '.widget.mailgun-subscriptions' );
		subscriptionWidget.on( 'submit', function ( e ) {
			e.preventDefault();

			var messages = $( this ).find( '.mailgun-message' );
			messages.remove();

			var form = $( this ).find( 'form.mailgun-subscription-form' );

			var spinner = $( '<div class="mailgun-ajax-loading-spinner"></div>' );
			form.append( spinner );


			var data = {
				action:                     'mailgun_subscribe',
				'mailgun-lists':            [],
				'mailgun-subscriber-email': form.find( 'input[name="mailgun-subscriber-email"]' ).val(),
				'mailgun-action':           form.find( 'input[name="mailgun-action"]' ).val()
			};

			form.find( 'input[name="mailgun-lists[]"]:checked' ).each( function () {
				data[ 'mailgun-lists' ].push( $( this ).val() );
			} );

			$.ajax( {
				url:    ajaxurl,
				method: 'POST',
				data:   data
			} )
				.done( function ( data ) {
					console.log( data );
					form.before( data.data.message );
				} )
				.then( function () {
					spinner.remove();
				} );
		} );
	} );
})( jQuery, window );