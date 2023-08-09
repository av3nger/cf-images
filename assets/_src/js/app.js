/**
 * Main JavaScript file.
 *
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.0.0
 */

/* global CFImages */

import '../css/app.scss';
import { toggleModal } from './helpers/modal';
import { showNotice } from './helpers/notice';
import { post } from './helpers/post';
import './modules/image-ai.js';

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
	 * List of ajaxActions.
	 * The first parameter is the Ajax callback, the second is which element to map the click event to.
	 *
	 * @since 1.2.1
	 */
	const ajaxActions = {
		cf_images_offload_image: '.cf-images-offload', // Process offloading from media library.
		cf_images_skip_image: '.cf-images-skip', // Skip image from processing.
		cf_images_undo_image: '.cf-images-undo', // Process undo offloading from media library.
		cf_images_delete_image: '.cf-images-delete', // Process remove image action from media library.
		cf_images_restore_image: '.cf-images-restore', // Download image back to media library.
		cf_images_ai_caption: '.cf-images-ai-alt', // Process AI caption.
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

		const { inProgress, saveChange } = CFImages.strings;

		$( this )
			.attr( 'aria-busy', true )
			.html( inProgress + '...' );

		post( action, form.serialize() )
			.then( ( response ) => {
				if ( ! response.success ) {
					$( this )
						.attr( 'aria-busy', false )
						.html( saveChange );

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

		const { disconnecting } = CFImages.strings;

		$( this ).attr( 'aria-busy', true ).html( disconnecting );
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
	 * Register Ajax actions.
	 */
	Object.keys( ajaxActions ).forEach( ( action ) => {
		$( document ).on( 'click', ajaxActions[ action ], function( e ) {
			e.preventDefault();

			const { inProgress, offloadError } = CFImages.strings;

			const divStatus = $( this ).closest( '.cf-images-status' );
			divStatus.html( inProgress + '<span class="spinner is-active"></span>' );

			post( action, $( this ).data( 'id' ) )
				.then( ( response ) => {
					if ( ! response.success ) {
						const message = response.data || offloadError;
						divStatus.html( message );
						return;
					}

					divStatus.html( response.data );
				} )
				.catch( window.console.log );
		} );
	} );

	/**
	 * Hide the sidebar.
	 *
	 * @since 1.3.0
	 */
	$( document ).on( 'click', '#hide-the-sidebar', () => {
		post( 'cf_images_hide_sidebar' ).catch( window.console.log );
		$( '.cf-images-sidebar' ).slideUp( 'slow', function() {
			$( this ).remove();
		} );
	} );
}( jQuery ) );
