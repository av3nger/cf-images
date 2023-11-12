/**
 * Do AJAX request to WordPress.
 *
 * @since 1.0.0
 * @param {string}        action Registered AJAX action.
 * @param {Object|string} data   Additional data that needs to be passed in POST request.
 * @return {Promise<unknown>} Return data.
 */
export const post = (
	action: string,
	data: object | string = {}
): Promise<unknown> => {
	data = { _ajax_nonce: window.CFImages.nonce, action, data };

	return new Promise((resolve, reject) => {
		jQuery.ajax({
			url: window.ajaxurl,
			type: 'POST',
			data,
			success(response) {
				resolve(response);
			},
			error(error) {
				reject(error);
			},
		});
	});
};
