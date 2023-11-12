<?php
/**
 * ShortPixel integration class
 *
 * Update image, once it's been optimized.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Integrations
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.6.0
 */

namespace CF_Images\App\Integrations;

use CF_Images\App\Traits;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * ShortPixel class.
 *
 * @since 1.6.0
 */
class Shortpixel {
	use Traits\Helpers;

	/**
	 * Class constructor.
	 *
	 * @since 1.6.0
	 */
	public function __construct() {
		add_action( 'shortpixel/image/optimised', array( $this, 'replace_image' ) );
		add_action( 'shortpixel/image/after_restore', array( $this, 'replace_image' ) );
	}

	/**
	 * Update image on Cloudflare when it is optimized or restored with ShortPixel.
	 *
	 * @since 1.6.0
	 *
	 * @param mixed $media_library_object Media library object.
	 */
	public function replace_image( $media_library_object ) {
		if ( 'media' !== $media_library_object->get( 'type' ) ) {
			return;
		}

		$attachment_id = $media_library_object->get( 'id' );

		$meta = wp_get_attachment_metadata( $attachment_id );

		$this->media()->upload_image( $meta, $attachment_id, 'replace' );
	}
}
