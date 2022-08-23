/**
 * Main JavaScript file.
 *
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.0.0
 */

/* global ajaxurl */
/* global CFImages */

import '../css/app.scss';

( function( $ ) {
	'use strict';

	/**
	 * Process setup form.
	 *
	 * @since 1.0.0
	 */
	$( '#cf-images-settings-submit' ).on( 'click', function( e ) {
		e.preventDefault();

		const spinner = $( this ).next( '.spinner' );
		spinner.toggleClass( 'is-active' );

		const data = $( 'form#cf-images-setup' ).serialize();
		post( 'cf_images_save_settings', data )
			.then( ( response ) => {
				if ( ! response.success ) {
					spinner.toggleClass( 'is-active' );
					window.console.log( response );
					return;
				}

				location.reload();
			} )
			.catch( window.console.log );
	} );

	/**
	 * "Sync image sizes" button click.
	 *
	 * @since 1.0.0
	 */
	$( '#cf-images-sync-image-sizes' ).on( 'click', function( e ) {
		e.preventDefault();

		const spinner = $( this ).next( '.spinner' );
		spinner.toggleClass( 'is-active' );

		post( 'cf_images_sync_image_sizes' )
			.then( ( response ) => {
				if ( ! response.success ) {
					spinner.toggleClass( 'is-active' );
					window.console.log( response );
					return;
				}

				location.reload();
			} )
			.catch( window.console.log );
	} );

	/**
	 * Do AJAX request to WordPress.
	 *
	 * @since 1.0.0
	 * @param {string} action Registered AJAX action.
	 * @param {Object} data   Additional data that needs to be passed in POST request.
	 * @return {Promise<unknown>} Return data.
	 */
	const post = function( action, data = {} ) {
		data = { _ajax_nonce: CFImages.nonce, action, data };
		return new Promise( ( resolve, reject ) => {
			$.ajax( {
				url: ajaxurl,
				type: 'POST',
				data,
				success( response ) {
					resolve( response );
				},
				error( error ) {
					reject( error );
				},
			} );
		} );
	};
}( jQuery ) );
