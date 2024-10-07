<?php
/**
 * Spectra integration class
 *
 * This class adds compatibility with the Spectra blocks plugin.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Integrations
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.1.5
 */

namespace CF_Images\App\Integrations;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Spectra class.
 *
 * @since 1.1.5
 */
class Spectra {
	/**
	 * Class constructor.
	 *
	 * @since 1.1.5
	 */
	public function __construct() {
		add_filter( 'cf_images_content_attachment_id', array( $this, 'detect_image_id' ), 10, 2 );
		add_filter( 'uagb_block_attributes_for_css_and_js', array( $this, 'replace_background_images' ) );
	}

	/**
	 * Spectra blocks will remove the default WordPress class that identifies an image, and will replace it with
	 * their own uag-image-<ID> class. Try to get attachment ID from class.
	 *
	 * @since 1.1.3
	 * @since 1.1.5 Moved to the Spectra integration class.
	 *
	 * @param int    $attachment_id  The image attachment ID. May be 0 in case the image is not an attachment.
	 * @param string $filtered_image Full img tag with attributes that will replace the source img tag.
	 *
	 * @return int
	 */
	public function detect_image_id( int $attachment_id, string $filtered_image ): int {
		if ( 0 !== $attachment_id ) {
			return $attachment_id;
		}

		// Find `class` attributes in an image.
		preg_match( '/class=[\'"]([^\'"]+)/i', $filtered_image, $class );
		if ( isset( $class[1] ) && 'uag-image-' === substr( $class[1], 0, 10 ) ) {
			$attachment_id = (int) substr( $class[1], 10 );
		}

		return $attachment_id;
	}

	/**
	 * Replace background images in Spectra blocks.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return array
	 */
	public function replace_background_images( array $attributes ): array {
		$device_aliases = array( 'Desktop', 'Tablet', 'Mobile' );

		// Check background images for all devices.
		foreach ( $device_aliases as $device ) {
			$key = 'backgroundImage' . $device;
			if ( ! isset( $attributes[ $key ] ) ) {
				continue;
			}

			$image = apply_filters( 'wp_get_attachment_image_src', array( $attributes[ $key ]['url'] ), $attributes[ $key ]['id'], '', false );

			// Replace the background image URL.
			$attributes[ $key ]['url'] = $image[0];
		}

		return $attributes;
	}
}
