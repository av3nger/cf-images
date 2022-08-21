/**
 * Main JavaScript file.
 *
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.0.0
 */

/* global ajaxurl */

import '../css/app.scss';

( function( $ ) {
	'use strict';

	$( '#cf-images-settings-submit' ).on( 'click', function( e ) {
		e.preventDefault();

		const data = {
			action: 'cf_images_save_settings',
			_ajax_nonce: $( '#_wpnonce' ).val(),
			form: $( 'form#cf-images-setup' ).serialize()
		};

		$.post( ajaxurl, data, function( response ) {
			if ( ! response.success ) {
				//
				return;
			}

			//
			location.reload();
		} );
	} );
}( jQuery ) );
