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
	 * Available forms.
	 *
	 * Form ID corresponds to `data-type` value on a form, value is the AJAX callback.
	 *
	 * @since 1.0.0
	 * @type {{settings: string, setup: string}}
	 */
	const forms = {
		settings: 'cf_images_save_settings',
		setup: 'cf_images_do_setup'
	};

	/**
	 * Auto hide any pending notices.
	 *
	 * @since 1.0.0
	 */
	$( document ).ready( function() {
		setTimeout( () => $( '#cf-images-notice' ).slideUp( 'slow' ), 5000 );
	} );

	/**
	 * Process form submits.
	 *
	 * Currently, processes the setup and settings forms.
	 *
	 * @since 1.0.0
	 */
	$( 'form#cf-images-form' ).on( 'submit', function( e ) {
		e.preventDefault();

		const action = forms[ $( this ).data( 'type' ) ];
		if ( undefined === action ) {
			return;
		}

		const spinner = $( this ).next( '.spinner' );
		spinner.toggleClass( 'is-active' );

		post( action, $( this ).serialize() )
			.then( ( response ) => {
				if ( ! response.success ) {
					spinner.toggleClass( 'is-active' );
					window.console.log( response );
					return;
				}

				window.location.search += '&saved=true';
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
					showNotice( response.data, 'error' );
					window.console.log( response );
					return;
				}

				window.location.search += '&updated=true';
			} )
			.catch( window.console.log );
	} );

	/**
	 * Process offloading from media library.
	 *
	 * @since 1.0.0
	 */
	$( '.cf-images-offload' ).on( 'click', function( e ) {
		e.preventDefault();

		const divStatus = $( this ).parent();
		divStatus.html( CFImages.strings.inProgress + '<span class="spinner is-active"></span>' );

		post( 'cf_images_offload_image', $( this ).data( 'id' ) )
			.then( ( response ) => {
				if ( ! response.success ) {
					divStatus.html( CFImages.strings.offloadError );
					window.console.log( response );
					return;
				}

				divStatus.html( '<span class="dashicons dashicons-cloud-saved"></span>' + CFImages.strings.offloaded );
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

	/**
	 * Show a notice.
	 *
	 * @since 1.0.0
	 * @param {string} message Message text.
	 * @param {string} type    Notice type.
	 */
	const showNotice = function( message, type = 'success' ) {
		const notice = $( '#cf-images-ajax-notice' );

		notice.addClass( 'notice-' + type );
		notice.find( 'p' ).html( message );

		notice.slideDown().delay( 5000 ).queue( function() {
			$( this ).slideUp( 'slow', function() {
				$( this ).removeClass( 'notice-' + type );
				$( this ).find( 'p' ).html( '' );
			} );
			$.dequeue( this );
		} );
	};
}( jQuery ) );
