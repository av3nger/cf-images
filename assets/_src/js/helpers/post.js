/* global CFImages */
/* global ajaxurl */

/**
 * Do AJAX request to WordPress.
 *
 * @since 1.0.0
 * @param {string} action Registered AJAX action.
 * @param {Object} data   Additional data that needs to be passed in POST request.
 * @return {Promise<unknown>} Return data.
 */
export const post = function( action, data = {} ) {
	data = { _ajax_nonce: CFImages.nonce, action, data };

	return new Promise( ( resolve, reject ) => {
		jQuery.ajax( {
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
