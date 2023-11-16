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
		add_filter( 'cf_images_default_settings', array( $this, 'add_setting' ) );
		add_action( 'cf_images_save_settings', array( $this, 'on_settings_update' ), 10, 2 );

		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_cf_images_set_custom_path', array( $this, 'ajax_set_custom_path' ) );
		}
	}

	/**
	 * Add default option.
	 *
	 * @since 1.7.0
	 *
	 * @param array $defaults Default settings.
	 *
	 * @return array
	 */
	public function add_setting( array $defaults ): array {
		if ( ! isset( $defaults['custom-path'] ) ) {
			$defaults['custom-path'] = false;
		}

		return $defaults;
	}

	/**
	 * Remove custom path option when disabling the module.
	 *
	 * @since 1.7.0
	 *
	 * @param array $settings Settings array.
	 * @param array $data     Passed in data from the app.
	 */
	public function on_settings_update( array $settings, array $data ) {
		if ( ! isset( $data['custom-path'] ) || ! filter_var( $data['custom-path'], FILTER_VALIDATE_BOOLEAN ) ) {
			delete_option( 'cf-images-custom-path' );
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
