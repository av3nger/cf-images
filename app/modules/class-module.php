<?php
/**
 * Abstract module class
 *
 * This class defines all the base code, which is inherited by stand-alone classes.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.3.0
 */

namespace CF_Images\App\Modules;

use CF_Images\App\Settings;
use CF_Images\App\Traits\Helpers;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Abstract Module class.
 *
 * @since 1.3.0
 */
abstract class Module {
	use Helpers;

	/**
	 * This is a core module, meaning it can't be enabled/disabled via options.
	 *
	 * @since 1.3.0
	 *
	 * @var bool
	 */
	protected $core = false;

	/**
	 * Module ID.
	 *
	 * @since 1.3.0
	 * @access protected
	 *
	 * @var string $module
	 */
	protected $module = '';

	/**
	 * Should the module only run on front-end?
	 *
	 * @since 1.3.0
	 * @access protected
	 *
	 * @var bool $only_frontend
	 */
	protected $only_frontend = false;

	/**
	 * Module constructor.
	 *
	 * @since 1.3.0
	 *
	 * @param string $module Module ID.
	 */
	public function __construct( string $module ) {
		$this->module = $module;
		$this->pre_init();

		add_filter( 'cf_images_module_enabled', array( $this, 'is_module_enabled' ), 10, 2 );

		if ( ! $this->is_set_up() || ! $this->is_module_enabled() ) {
			return;
		}

		// Some modules are front-end only, make sure we can run them.
		if ( $this->only_frontend && ( is_admin() || ! $this->can_run() ) ) {
			return;
		}

		$this->init();
	}

	/**
	 * Module init.
	 *
	 * @since 1.3.0
	 */
	abstract public function init();

	/**
	 * Module pre-init actions.
	 *
	 * @since 1.2.1
	 */
	protected function pre_init() {}

	/**
	 * Check if we can run the plugin. Not all images should be converted, for example,
	 * SEO images from meta tags should be left untouched.
	 *
	 * @since 1.1.3
	 *
	 * @param int $attachment_id Optional. Attachment ID.
	 *
	 * @return bool
	 */
	protected function can_run( int $attachment_id = 0 ): bool {
		if ( $this->is_rest_request( $attachment_id ) || wp_doing_cron() ) {
			return false;
		}

		if ( apply_filters( 'cf_images_skip_image', false ) ) {
			return false;
		}

		if ( doing_action( 'wp_head' ) && ! $this->is_module_enabled( false, 'process-head' ) ) {
			return false;
		}

		if ( did_action( 'wp' ) && ! $this->is_module_enabled( false, 'rss-feeds' ) && is_feed() ) {
			return false;
		}

		return true;
	}

	/**
	 * This is how WordPress treats us developers - doesn't give a sh*t about is_admin(), so we have to do these
	 * custom checks to make sure we don't break the admin area.
	 *
	 * @since 1.2.0
	 *
	 * @param int $attachment_id Optional. Attachment ID.
	 *
	 * @return bool
	 */
	private function is_rest_request( int $attachment_id = 0 ): bool {
		if ( $attachment_id ) {
			// We must rely on the REST API endpoints if full offload is enabled.
			$deleted = get_post_meta( $attachment_id, '_cloudflare_image_offloaded', true );
			if ( $deleted && apply_filters( 'cf_images_module_enabled', false, 'full-offload' ) ) {
				return false;
			}
		}

		$wordpress_has_no_logic = filter_input( INPUT_GET, '_wp-find-template' );
		$wordpress_has_no_logic = sanitize_key( $wordpress_has_no_logic );

		if ( ! empty( $wordpress_has_no_logic ) && 'true' === $wordpress_has_no_logic ) {
			// And if below was not enough - we also need to check this bs...
			return true;
		}

		$rest_url_prefix = rest_get_url_prefix();
		if ( empty( $rest_url_prefix ) ) {
			return false;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		$is_rest_request = strpos( $request_uri, $rest_url_prefix ) !== false;
		return apply_filters( 'cf_images_is_rest_request', $is_rest_request );
	}

	/**
	 * Filter callback to check if a specific module is enabled.
	 *
	 * @since 1.4.0
	 *
	 * @param bool   $fallback Default status.
	 * @param string $module   Module ID.
	 *
	 * @return bool
	 */
	public function is_module_enabled( bool $fallback = false, string $module = '' ): bool {
		// Core modules cannot be disabled.
		if ( $this->core && empty( $module ) ) {
			return apply_filters( 'cf_images_core_module_status', true, $this->module );
		}

		if ( empty( $module ) ) {
			$module = $this->module;
		}

		$settings = apply_filters( 'cf_images_settings', get_option( 'cf-images-settings', Settings::get_defaults() ) );

		return apply_filters( 'cf_images_module_status', $settings[ $module ] ?? $fallback, $module );
	}

	/**
	 * In certain cases offloading should be disabled.
	 *
	 * @since 1.9.0
	 *
	 * @return bool
	 */
	public function can_offload(): bool {
		if ( filter_input( INPUT_GET, 'cf-images-disable' ) ) {
			return false;
		}

		// Full offload overrides all other settings, because there are no local images available.
		if ( $this->is_module_enabled( false, 'full-offload' ) ) {
			return true;
		}

		if ( $this->is_module_enabled( false, 'no-offload-user' ) && defined( 'LOGGED_IN_COOKIE' ) ) {
			// Check logged-in user cookie.
			return empty( $_COOKIE[ LOGGED_IN_COOKIE ] );
		}

		return true;
	}
}
