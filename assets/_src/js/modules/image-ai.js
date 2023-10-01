/* global CFImages */

import { showNotice } from '../helpers/notice';
import { post } from '../helpers/post';

( function( $ ) {
	'use strict';

	// Disconnect from Image AI.
	$( '#image-ai-disconnect' ).on( 'click', function( e ) {
		e.preventDefault();

		post( 'cf_images_ai_disconnect' )
			.then( () => window.location.search += '&saved=true' )
			.catch( window.console.log );
	} );

	// Show API key form.
	$( '#js-add-image-ai-api-key' ).on( 'click', function( e ) {
		e.preventDefault();

		$( '#cf-images-ai-email' ).hide();
		$( '#cf-images-ai-api-key' ).show();
	} );

	// Show login form.
	$( '#js-show-login-pass-form' ).on( 'click', function( e ) {
		e.preventDefault();

		$( '#cf-images-ai-email' ).show();
		$( '#cf-images-ai-api-key' ).hide();
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

	// Save API key.
	$( '#image-ai-save-key' ).on( 'click', function( e ) {
		e.preventDefault();
		const { save, savingChanges } = CFImages.strings;

		$( this ).attr( 'aria-busy', true ).html( savingChanges );

		const args = {
			apikey: $( '#cf-ai-api-key' ).val(),
		};

		post( 'cf_images_ai_save', args )
			.then( ( response ) => {
				$( this ).attr( 'aria-busy', false ).html( save );
				if ( ! response.success && 'undefined' !== typeof response.data ) {
					showNotice( response.data, 'error' );
					return;
				}

				window.location.search += '&saved=true';
			} )
			.catch( window.console.log );
	} );
}( jQuery ) );
