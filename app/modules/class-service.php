<?php
/**
 * Service module.
 *
 * Performs various actions to tackle various issues.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 *
 * @since 1.7.0
 */

namespace CF_Images\App\Modules;

use CF_Images\App\Traits;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Service class.
 *
 * @since 1.7.0
 */
class Service extends Module {
	use Traits\Ajax;

	/**
	 * Init the module.
	 *
	 * @since 1.7.0
	 */
	public function init() {}

	/**
	 * Init the module.
	 *
	 * @since 1.7.0
	 */
	public function pre_init() {
		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_cf_images_reset_ignored', array( $this, 'reset_ignored' ) );
		}
	}

	/**
	 * Reset ignored images meta.
	 *
	 * @since 1.7.0
	 */
	public function reset_ignored() {
		$this->check_ajax_request( true );

		global $wpdb;

		$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_cloudflare_image_skip'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		wp_send_json_success();
	}
}
