/**
 * Main JavaScript file.
 *
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.0.0
 */

/* global CFImages */

import { post } from './helpers/post';

(function ($) {
	'use strict';

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
		cf_images_compress: '.cf-images-compress', // Compress image.
	};

	/**
	 * Register Ajax actions.
	 */
	Object.keys(ajaxActions).forEach((action) => {
		$(document).on('click', ajaxActions[action], function (e) {
			e.preventDefault();

			const { inProgress, offloadError } = CFImages.strings;

			const divStatus = $(this).closest('.cf-images-status');
			divStatus.html(
				inProgress + '<span class="spinner is-active"></span>'
			);

			post(action, $(this).data('id'))
				.then((response) => {
					if (!response.success) {
						const message = response.data || offloadError;
						divStatus.html(message);
						return;
					}

					divStatus.html(response.data);
				})
				.catch(window.console.log);
		});
	});
})(jQuery);
