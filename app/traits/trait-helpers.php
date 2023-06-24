<?php
/**
 * The file that defines helper traits that are used across all classes
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Traits
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.0.0
 */

namespace CF_Images\App\Traits;

use CF_images\App\Core;
use WP_Error;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The helpers trait class.
 *
 * @since 1.0.0
 */
trait Helpers {

	/**
	 * Get plugin slug.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return Core::get_instance()->get_plugin_name();
	}

	/**
	 * Check if the required settings are present.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_set_up(): bool {

		if ( get_option( 'cf-images-auth-error', false ) ) {
			return false;
		}

		$saved          = filter_input( INPUT_GET, 'saved', FILTER_VALIDATE_BOOLEAN );
		$config_written = get_option( 'cf-images-config-written', false );
		$defines_found  = defined( 'CF_IMAGES_ACCOUNT_ID' ) && defined( 'CF_IMAGES_KEY_TOKEN' );

		return ( $config_written && $saved ) || $defines_found;

	}

	/**
	 * Return error (if set).
	 *
	 * @since 1.0.0
	 *
	 * @return bool|WP_Error
	 */
	public function get_error() {

		if ( get_option( 'cf-images-auth-error', false ) ) {
			return new WP_Error( 401, esc_html__( 'Authentication error. Check and update Cloudflare API key.', 'cf-images' ) );
		}

		return Core::get_error();

	}

	/**
	 * Get CDN URL.
	 *
	 * @since 1.3.0
	 *
	 * @return string
	 */
	public function get_cdn_domain(): string {
		return Core::get_instance()->get_cdn_domain();
	}

	/**
	 * Check if full offload is enabled.
	 *
	 * @since 1.2.1
	 *
	 * @return bool
	 */
	public function full_offload_enabled(): bool {
		return (bool) get_option( 'cf-images-full-offload', false );
	}

}
