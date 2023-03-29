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
	}

	/**
	 * Spectra blocks will remove the default WordPress class that identifies an image, and will replace it with
	 * their own uag-image-<ID> class. Try to get attachment ID from class.
	 *
	 * @since 1.1.3
	 * @since 1.1.5 Moved to the Spectra integration class.
	 *
	 * @param int    $attachment_id   The image attachment ID. May be 0 in case the image is not an attachment.
	 * @param string $filtered_image  Full img tag with attributes that will replace the source img tag.
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

}
