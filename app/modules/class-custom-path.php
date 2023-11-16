<?php
/**
 * Custom paths for URLs
 *
 * Allows changing the default cdn-cgi/imagedelivery/<account_hash> path to a custom one.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.7.0
 */

namespace CF_Images\App\Modules;

use CF_Images\App\Traits;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Custom_Path class.
 *
 * @since 1.7.0
 */
class Custom_Path extends Module {
	use Traits\Ajax;

	/**
	 * Init the module.
	 *
	 * @since 1.7.0
	 */
	public function init() {
		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_cf_images_set_custom_path', array( $this, 'ajax_set_custom_path' ) );
		}
	}

	/**
	 * Set custom path.
	 *
	 * @since 1.7.0
	 */
	public function ajax_set_custom_path() {
		$this->check_ajax_request();

		$data = filter_input( INPUT_POST, 'data', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		if ( ! isset( $data['path'] ) ) {
			delete_option( 'cf-images-custom-path' );
		} else {
			update_option( 'cf-images-custom-path', sanitize_key( $data['path'] ), false );
		}

		wp_send_json_success();
	}
}
