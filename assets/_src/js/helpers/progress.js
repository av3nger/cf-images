import { post } from './post';

/**
 * Run progress bar (remove or upload all images).
 *
 * @since 1.0.0
 * @param {string} action      AJAX action.
 * @param {number} currentStep Current step.
 * @param {number} totalSteps  Total steps.
 * @param {number} progress    Progress in percent.
 */
export const runProgressBar = function( action, currentStep = 0, totalSteps = 0, progress = 0 ) {
	jQuery( '.cf-images-progress.' + action ).show();
	jQuery( '.cf-images-progress.' + action + ' > progress' ).val( progress );

	const args = {
		currentStep,
		totalSteps,
		action
	};

	post( 'cf_images_bulk_process', args )
		.then( ( response ) => {
			if ( ! response.success ) {
				jQuery( '.cf-images-progress.' + action ).hide();
				jQuery( '.media_page_cf-images [role=button]' ).attr( 'disabled', false );
				return;
			}

			progress = Math.round( 100 / response.data.totalSteps * response.data.currentStep );
			jQuery( '.cf-images-progress.' + action + ' > progress' ).val( progress );
			jQuery( '.cf-images-progress.' + action + ' > p > small' ).html( response.data.status );

			if ( response.data.currentStep < response.data.totalSteps ) {
				runProgressBar( action, response.data.currentStep, response.data.totalSteps, progress );
			} else if ( 'upload' === action ) {
				window.location.search += '&updated=true';
			} else if ( 'remove' === action ) {
				window.location.search += '&deleted=true';
			} else {
				window.location.search += `&${ action }=true`;
			}
		} )
		.catch( window.console.log );
};
