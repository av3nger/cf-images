<?php
/**
 * Auto offload new images
 *
 * Allow users to automatically offload images on upload to media library.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.3.0  Moved out into its own module.
 */

namespace CF_Images\App\Modules;

use CF_Images\App\Core;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Auto_Offload class.
 *
 * @since 1.3.0
 */
class Auto_Offload extends Module {

	/**
	 * Init the module.
	 *
	 * @since 1.3.0
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'auto_offload' ) );
	}

	/**
	 * Run auto offload.
	 *
	 * @since 1.3.0
	 *
	 * @return void
	 */
	public function auto_offload() {

		$media = Core::get_instance()->admin()->media();

		// If async uploads are disabled, use the default hook.
		if ( get_option( 'cf-images-disable-async', false ) ) {
			add_filter( 'wp_generate_attachment_metadata', array( $media, 'upload_image' ), 10, 2 );
		} else {
			add_filter( 'wp_async_wp_generate_attachment_metadata', array( $media, 'upload_image' ), 10, 2 );
		}

	}

}
