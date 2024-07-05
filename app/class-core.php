<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that App attributes and functions used across both the
 * public-facing side of the site and the Admin area.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.0.0
 */

namespace CF_Images\App;

use Exception;
use WP_CLI;
use WP_Error;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The core plugin class.
 *
 * @since 1.0.0
 */
class Core {
	use Traits\Helpers;

	/**
	 * Plugin instance.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var null|Core $instance Plugin instance.
	 */
	private static $instance = null;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name = 'cf-images';

	/**
	 * Error status.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var bool|WP_Error $error
	 */
	private static $error = false;

	/**
	 * Admin instance.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var Admin $admin
	 */
	private $admin;

	/**
	 * CDN domain.
	 *
	 * @since 1.2.0
	 * @access private
	 * @var string
	 */
	private $cdn_domain = 'https://imagedelivery.net';

	/**
	 * Get plugin instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Core
	 */
	public static function get_instance(): Core {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the Admin area and
	 * the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->load_libs();
		$this->init_integrations();
		$this->load_modules();
		$this->set_cdn_domain();

		if ( is_admin() ) {
			$this->admin = new Admin();
		}

		if ( ! $this->is_set_up() ) {
			return;
		}

		add_action( 'cf_images_error', array( $this, 'set_error' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'maybe_redirect_to_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'enable_flexible_variants' ) );
	}

	/**
	 * Load all required libraries.
	 *
	 * @since 1.0.0
	 */
	private function load_libs() {
		require_once __DIR__ . '/class-media.php';
		require_once __DIR__ . '/class-admin.php';
		require_once __DIR__ . '/class-settings.php';
		require_once __DIR__ . '/class-loader.php';
		require_once __DIR__ . '/class-image.php';

		if ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) {
			require_once __DIR__ . '/class-cli.php';
			WP_CLI::add_command( 'cf-images', '\\CF_Images\\App\\CLI' );
		}

		// API classes.
		require_once __DIR__ . '/api/class-api.php';
		require_once __DIR__ . '/api/class-api-multi.php';
		require_once __DIR__ . '/api/class-cloudflare.php';
		require_once __DIR__ . '/api/class-fuzion.php';
		require_once __DIR__ . '/api/class-ai.php';
		require_once __DIR__ . '/api/class-compress.php';
		require_once __DIR__ . '/api/class-image.php';
		require_once __DIR__ . '/api/class-variant.php';
	}

	/**
	 * Get Cloudflare CDN domain.
	 *
	 * @since 1.0.2
	 */
	private function set_cdn_domain() {
		$custom_domain = get_option( 'cf-images-custom-domain', false );

		if ( $custom_domain ) {
			$domain      = trailingslashit( wp_http_validate_url( $custom_domain ) ? $custom_domain : get_site_url() );
			$custom_path = get_option( 'cf-images-custom-path', null );
			$domain     .= $custom_path ?? 'cdn-cgi/imagedelivery';

			$this->cdn_domain = $domain;
		}
	}

	/**
	 * Init integrations.
	 *
	 * @since 1.1.5
	 *
	 * @see Integrations\ACF
	 * @see Integrations\Multisite_Global_Media
	 * @see Integrations\Rank_Math
	 * @see Integrations\Spectra
	 * @see Integrations\Wpml
	 * @see Integrations\Shortpixel
	 * @see Integrations\JS_Composer
	 * @see Integrations\Flatsome
	 */
	private function init_integrations() {
		$loader = Loader::get_instance();

		$loader->integration( 'spectra' );
		$loader->integration( 'multisite-global-media' );
		$loader->integration( 'rank-math' );
		$loader->integration( 'acf' );
		$loader->integration( 'wpml' );
		$loader->integration( 'shortpixel' );
		$loader->integration( 'elementor' );
		$loader->integration( 'js-composer' );
		$loader->integration( 'flatsome' );
	}

	/**
	 * Load modules.
	 *
	 * @since 1.3.0
	 *
	 * @see Modules\Auto_Offload
	 * @see Modules\Auto_Resize
	 * @see Modules\Cloudflare_Images
	 * @see Modules\Custom_Id
	 * @see Modules\Disable_Async
	 * @see Modules\Disable_Generation
	 * @see Modules\Image_Ai
	 * @see Modules\Image_Compress
	 * @see Modules\Page_Parser
	 * @see Modules\Image_Generate
	 * @see Modules\Logging
	 * @see Modules\Custom_Path
	 * @see Modules\Service
	 * @see Modules\CDN
	 * @see Modules\Full_Offload
	 * @see Modules\Multisite
	 * @see Modules\Cache_TTL
	 */
	private function load_modules() {
		$loader = Loader::get_instance();

		$loader->module( 'multisite' ); // This should be loaded before other modules.
		$loader->module( 'cdn' ); // This should be loaded before other modules.
		$loader->module( 'auto-offload' );
		$loader->module( 'auto-resize' );
		$loader->module( 'cloudflare-images' ); // Core module.
		$loader->module( 'custom-id' );
		$loader->module( 'disable-async' );
		$loader->module( 'disable-generation' );
		$loader->module( 'image-ai' );
		$loader->module( 'image-compress' );
		$loader->module( 'page-parser' );
		$loader->module( 'image-generate' );
		$loader->module( 'logging' );
		$loader->module( 'custom-path' );
		$loader->module( 'service' );
		$loader->module( 'full-offload' );
		$loader->module( 'cache-ttl' );
	}

	/**
	 * Setter for error.
	 *
	 * @since 1.2.0
	 *
	 * @param int|mixed $code    Error code.
	 * @param string    $message Error message.
	 */
	public function set_error( $code = '', string $message = '' ) {
		if ( '' === $code ) {
			self::$error = false;
		} else {
			do_action( 'cf_images_log', $message );
			self::$error = new WP_Error( $code, $message );
		}
	}

	/**
	 * Maybe redirect to plugin page on activation.
	 *
	 * @since 1.0.0
	 */
	public function maybe_redirect_to_plugin_page() {
		if ( ! get_transient( 'cf-images-admin-redirect' ) ) {
			return;
		}

		delete_transient( 'cf-images-admin-redirect' );
		wp_safe_redirect( admin_url( 'upload.php?page=cf-images' ) );
		exit;
	}

	/**
	 * Enable flexible variants, which are disabled by default.
	 *
	 * This action is only required once.
	 *
	 * @since 1.0.0
	 */
	public function enable_flexible_variants() {
		// Already done.
		if ( get_option( 'cf-images-setup-done', false ) ) {
			return;
		}

		$variant = new Api\Variant();

		try {
			$variant->toggle_flexible( true );
			update_option( 'cf-images-setup-done', true, false );
		} catch ( Exception $e ) {
			self::$error = new WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since 1.0.0
	 *
	 * @return string The name of the plugin.
	 */
	public function get_plugin_name(): string {
		return $this->plugin_name;
	}

	/**
	 * Retrieve stored error.
	 *
	 * @since 1.0.0
	 * @sicne 1.2.0 Change to static method.
	 *
	 * @return bool|WP_Error
	 */
	public static function get_error() {
		return self::$error;
	}

	/**
	 * Getter method for CDN domain.
	 *
	 * @since 1.3.0
	 *
	 * @return string
	 */
	public function get_cdn_domain(): string {
		return $this->cdn_domain;
	}

	/**
	 * Return Admin instance.
	 *
	 * @since 1.3.0
	 *
	 * @return Admin
	 */
	public function admin(): Admin {
		return $this->admin;
	}
}
