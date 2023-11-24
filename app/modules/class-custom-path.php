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

use CF_Images\App\Api\Ai;
use CF_Images\App\Traits;
use Exception;

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
	 * Run everything regardless of module status.
	 *
	 * @since 1.7.0
	 */
	public function pre_init() {
		add_filter( 'cf_images_default_settings', array( $this, 'add_setting' ) );
		add_action( 'cf_images_save_settings', array( $this, 'on_settings_update' ), 10, 2 );
		add_filter( 'cf_images_module_status', array( $this, 'module_status' ), 15, 2 );

		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_cf_images_get_cf_status', array( $this, 'ajax_get_cloudflare_status' ) );
		}
	}

	/**
	 * Init the module if enabled.
	 *
	 * @since 1.7.0
	 */
	public function init() {
		add_filter( 'cf_images_hash', '__return_empty_string' );
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
	 * This module is only considered enabled when both the option AND the custom path are set.
	 *
	 * @since 1.7.0
	 *
	 * @param bool   $fallback Default status.
	 * @param string $module   Module ID.
	 *
	 * @return bool
	 */
	public function module_status( bool $fallback = false, string $module = '' ): bool {
		if ( 'custom-path' !== $module ) {
			return $fallback;
		}

		return get_option( 'cf-images-custom-path', false );
	}

	/**
	 * Get Cloudflare workers status.
	 *
	 * @since 1.7.0
	 */
	public function ajax_get_cloudflare_status() {
		$this->check_ajax_request( true );

		$data = filter_input( INPUT_POST, 'data', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		// Exit early if we have a cached status.
		$status = get_transient( 'cf-images-custom-path' );
		if ( false !== $status && ! filter_var( $data['force'], FILTER_VALIDATE_BOOLEAN ) ) {
			wp_send_json_success( $status );
			return;
		}

		try {
			$ai_api = new Ai();
			$status = $ai_api->get_cf_status();
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
			return;
		}

		// Use the path from the first worker.
		if ( empty( $status ) ) {
			delete_option( 'cf-images-custom-path' );
			set_transient( 'cf-images-custom-path', '', HOUR_IN_SECONDS );
		} else {
			$status = reset( $status );

			$host = wp_parse_url( get_site_url(), PHP_URL_HOST );

			// If we do not have routing setup for the domain, do not set the path, or it will break the images.
			if ( empty( $status->domains ) || ! in_array( $host, $status->domains, true ) ) {
				delete_option( 'cf-images-custom-path' );
				set_transient( 'cf-images-custom-path', '', HOUR_IN_SECONDS );
				wp_send_json_error( esc_html__( 'No routes found on Cloudflare worker. Please add a route.', 'cf-images' ) );
				return;
			}

			if ( ! empty( $status->path ) ) {
				update_option( 'cf-images-custom-path', $status->path, false );
				set_transient( 'cf-images-custom-path', $status->path, HOUR_IN_SECONDS );
			}
		}

		wp_send_json_success( $status );
	}
}
