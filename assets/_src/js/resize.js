/**
 * Automatic image resizing, based on DOM size.
 *
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.2.0
 */
( function() {
	'use strict';

	const CFAutoImageResize = {
		class: 'cf-image-auto-resize',
		regex: /w=\d{1,4}(,h=\d{1,4})?/,

		/**
		 * Process images
		 */
		init() {
			const images = document.getElementsByTagName( 'img' );
			for ( const image of images ) {
				if ( this.shouldSkipImage( image ) ) {
					continue;
				}

				// In case image is in correct size - skip.
				if (
					! image.clientWidth * 1.5 < image.naturalWidth &&
					! image.clientHeight * 1.5 < image.naturalHeight &&
					! image.clientWidth > image.naturalWidth &&
					! image.clientHeight > image.naturalHeight
				) {
					continue;
				}

				// If not lazy loaded placeholder.
				if ( ! this.isDataURL( image.src ) ) {
					image.src = image.src.replace( this.regex, `w=${ image.clientWidth }` );
					image.srcset = image.src + ' 1x, ' + image.src.replace( this.regex, `w=${ image.clientWidth * 2 }` ) + ' 2x';
				}
			}
		},

		/**
		 * Various checks to see if the image should be processed.
		 *
		 * @param {Object} image
		 * @return {boolean}  Should skip image or not.
		 */
		shouldSkipImage( image ) {
			// Skip images that do not have our special class.
			if ( ! image.classList.contains( this.class ) ) {
				return true;
			}

			// Skip 1x1px images.
			if ( image.clientWidth === image.clientHeight && 1 === image.clientWidth ) {
				return true;
			}

			// Skip 1x1px placeholders.
			if ( image.naturalWidth === image.naturalHeight && 1 === image.naturalWidth ) {
				return true;
			}

			// If width attribute is not set, do not continue.
			return null === image.clientWidth || null === image.clientHeight;
		},

		/**
		 * Check if the URL is a lazy-loading placeholder.
		 *
		 * @param {string} url
		 * @return {boolean} Is this a placeholder or not.
		 */
		isDataURL( url ) {
			const regex = /^data:image/;
			return regex.test( url );
		}
	};

	window.addEventListener( 'DOMContentLoaded', () => CFAutoImageResize.init() );
}() );
