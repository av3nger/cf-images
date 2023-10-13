/* global ajaxurl, CFImages, jQuery */

/**
 * Do AJAX request to WordPress.
 *
 * @param {string} action Registered AJAX action.
 * @param {Object} data   Additional data that needs to be passed in POST request.
 * @return {Promise<unknown>} Return data.
 */
export const post = ( action, data = {} ) => {
	return new Promise( ( resolve, reject ) => {
		jQuery.ajax( {
			url: ajaxurl,
			type: 'POST',
			data: { _ajax_nonce: CFImages.nonce, action, ...data },
			success( response ) {
				resolve( response );
			},
			error( error ) {
				reject( error );
			},
		} );
	} );
};
