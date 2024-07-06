<?php
/**
 * Flatsome theme integration class
 *
 * This class adds compatibility with the Flatsome theme.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Integrations
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.9.2
 */

namespace CF_Images\App\Integrations;

use CF_Images\App\Modules\Cloudflare_Images;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Flatsome class.
 *
 * @since 1.9.2
 */
class Flatsome {
	/**
	 * Class constructor.
	 *
	 * @since 1.9.2
	 */
	public function __construct() {
		add_action( 'wp_ajax_flatsome_additional_variation_images_load_images_ajax_frontend', array( $this, 'load_images_ajax' ) );
		add_action( 'wp_ajax_nopriv_flatsome_additional_variation_images_load_images_ajax_frontend', array( $this, 'load_images_ajax' ) );
	}

	/**
	 * Add support for additional variation images (Flatsome gallery).
	 *
	 * @since 1.9.2
	 */
	public function load_images_ajax() {
		$cf_images = new Cloudflare_Images( 'cloudflare-images' );
		add_filter( 'wp_get_attachment_image_src', array( $cf_images, 'get_attachment_image_src' ), 10, 3 );
	}
}
