/**
 * Main JavaScript file.
 *
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.0.0
 */

/* global ajaxurl */
/* global CFImages */

import '../css/app.scss';
import { toggleModal } from './modal';

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
	$( '#save-settings' ).on( 'click', function( e ) {
		e.preventDefault();

		const form = $( 'form#cf-images-form' );
		const action = forms[ form.data( 'type' ) ];

		if ( undefined === action ) {
			return;
		}

		$( this )
			.attr( 'aria-busy', true )
			.html( CFImages.strings.inProgress + '...' );

		post( action, form.serialize() )
			.then( ( response ) => {
				if ( ! response.success ) {
					$( this )
						.attr( 'aria-busy', false )
						.html( CFImages.strings.saveChange );

					if ( 'undefined' !== typeof response.data ) {
						showNotice( response.data, 'error' );
					}

					return;
				}

				window.location.search += '&saved=true';
			} )
			.catch( window.console.log );
	} );

	/**
	 * Process offloading from media library.
	 *
	 * @since 1.0.0
	 */
	$( document ).on( 'click', '.cf-images-offload', function( e ) {
		e.preventDefault();

		const divStatus = $( this ).parent();
		divStatus.html( CFImages.strings.inProgress + '<span class="spinner is-active"></span>' );

		post( 'cf_images_offload_image', $( this ).data( 'id' ) )
			.then( ( response ) => {
				if ( ! response.success ) {
					const message = response.data || CFImages.strings.offloadError;
					divStatus.html( message );
					return;
				}

				divStatus.html( response.data );
			} )
			.catch( window.console.log );
	} );

	/**
	 * Upload all images to Cloudflare.
	 *
	 * @since 1.0.0
	 */
	$( '#cf-images-upload-all' ).on( 'click', function( e ) {
		e.preventDefault();

		$( '.media_page_cf-images [role=button]' ).attr( 'disabled', true );
		runProgressBar( 'upload' );
	} );

	/**
	 * Remove all images from Cloudflare.
	 *
	 * @since 1.0.0
	 */
	$( '#cf-images-remove-all' ).on( 'click', function( e ) {
		e.preventDefault();

		toggleModal( e );
		$( '.media_page_cf-images form#cf-images-form [role=button]' ).attr( 'disabled', true );

		runProgressBar( 'remove' );
	} );

	/**
	 * Show confirm modal.
	 *
	 * @since 1.2.0
	 */
	$( '#cf-images-show-modal' ).on( 'click', function( e ) {
		e.preventDefault();
		toggleModal( e );
	} );

	/**
	 * Disconnect from Cloudflare.
	 *
	 * @since 1.1.2
	 */
	$( '#cf-images-disconnect' ).on( 'click', function( e ) {
		e.preventDefault();

		$( this ).attr( 'aria-busy', true ).html( CFImages.strings.disconnecting );
		post( 'cf_images_disconnect' )
			.then( () => window.location.reload() )
			.catch( window.console.log );
	} );

	/**
	 * Toggle custom domain input.
	 *
	 * @since 1.1.2
	 */
	$( '#custom_domain' ).on( 'change', function( e ) {
		$( 'input[name="custom_domain_input"]' ).toggleClass( 'hidden', ! e.target.checked );
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

	/**
	 * Run progress bar (remove or upload all images).
	 *
	 * @since 1.0.0
	 * @param {string} action      AJAX action.
	 * @param {number} currentStep Current step.
	 * @param {number} totalSteps  Total steps.
	 * @param {number} progress    Progress in percent.
	 */
	const runProgressBar = function( action, currentStep = 0, totalSteps = 0, progress = 0 ) {
		$( '.cf-images-progress.' + action ).show();
		$( '.cf-images-progress.' + action + ' > progress' ).val( progress );

		const args = {
			currentStep,
			totalSteps,
			action
		};

		post( 'cf_images_bulk_process', args )
			.then( ( response ) => {
				if ( ! response.success ) {
					$( '.cf-images-progress.' + action ).hide();
					$( '.media_page_cf-images [role=button]' ).attr( 'disabled', false );
					showNotice( response.data, 'error' );
					return;
				}

				progress = Math.round( 100 / response.data.totalSteps * response.data.currentStep );
				$( '.cf-images-progress.' + action + ' > progress' ).val( progress );
				$( '.cf-images-progress.' + action + ' > p > small' ).html( response.data.status );

				if ( response.data.currentStep < response.data.totalSteps ) {
					runProgressBar( action, response.data.currentStep, response.data.totalSteps, progress );
				} else if ( 'upload' === action ) {
					window.location.search += '&updated=true';
				} else {
					window.location.search += '&deleted=true';
				}
			} )
			.catch( window.console.log );
	};

	/**
	 * Skip image from processing.
	 *
	 * @since 1.1.2
	 *
	 * @param {Object} el Link element.
	 */
	window.cfSkipImage = ( el ) => {
		const divStatus = $( el ).parent();
		divStatus.html( CFImages.strings.inProgress + '<span class="spinner is-active"></span>' );

		post( 'cf_images_skip_image', $( el ).data( 'id' ) )
			.then( ( response ) => {
				if ( ! response.success ) {
					const message = response.data || CFImages.strings.offloadError;
					divStatus.html( message );
					return;
				}

				divStatus.html( response.data );
			} )
			.catch( window.console.log );
	};

	/**
	 * Process undo offloading from media library.
	 *
	 * @since 1.2.1
	 */
	$( document ).on( 'click', '.cf-images-undo', function( e ) {
		e.preventDefault();

		const divStatus = $( this ).parent();
		divStatus.html( CFImages.strings.inProgress + '<span class="spinner is-active"></span>' );

		post( 'cf_images_undo_image', $( this ).data( 'id' ) )
			.then( ( response ) => {
				if ( ! response.success ) {
					const message = response.data || CFImages.strings.offloadError;
					divStatus.html( message );
					return;
				}

				divStatus.html( response.data );
			} )
			.catch( window.console.log );
	} );
}( jQuery ) );
