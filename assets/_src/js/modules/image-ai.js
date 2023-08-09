/* global CFImages */

import { showNotice } from '../helpers/notice';
import { post } from '../helpers/post';

( function( $ ) {
	'use strict';

	// Toggle settings on/off.
	$( '#image_ai' ).on( 'change', ( e ) => $( '.cf-images-ai-settings' ).toggle( e.target.checked ) );

	// Disconnect from Image AI.
	$( '#image-ai-disconnect' ).on( 'click', function( e ) {
		e.preventDefault();

		post( 'cf_images_ai_disconnect' )
			.then( () => window.location.search += '&saved=true' )
			.catch( window.console.log );
	} );

	// Login.
	$( '#image-ai-login' ).on( 'click', function( e ) {
		e.preventDefault();
		const { login, savingChanges } = CFImages.strings;

		$( this ).attr( 'aria-busy', true ).html( savingChanges );

		const args = {
			email: $( '#cf-ai-email-address' ).val(),
			password: $( '#cf-ai-password' ).val(),
		};

		post( 'cf_images_ai_login', args )
			.then( ( response ) => {
				$( this ).attr( 'aria-busy', false ).html( login );
				if ( ! response.success && 'undefined' !== typeof response.data ) {
					showNotice( response.data, 'error' );
					return;
				}

				window.location.search += '&login=true';
			} )
			.catch( window.console.log );
	} );
}( jQuery ) );
