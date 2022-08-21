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

		const data = {
			action: 'cf_images_save_settings',
			_ajax_nonce: CFImages.nonce,
			form: $( 'form#cf-images-setup' ).serialize()
		};

		$.post( ajaxurl, data, function( response ) {
			if ( ! response.success ) {
				console.log( response );
				return;
			}

			location.reload();
		} );
	} );

	/**
	 * "Sync image sizes" button click.
	 *
	 * @since 1.0.0
	 */
	$( '#cf-images-sync-image-sizes' ).on( 'click', function( e ) {
		e.preventDefault();

		const data = {
			action: 'cf_images_sync_image_sizes',
			_ajax_nonce: CFImages.nonce,
		};

		$.post( ajaxurl, data, function( response ) {
			if ( ! response.success ) {
				console.log( response );
				return;
			}

			location.reload();
		} );
	} );
}( jQuery ) );
