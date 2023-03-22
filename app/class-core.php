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
use WP_Error;
use WP_Post;

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
	 * @var null|Core $instance  Plugin instance.
	 */
	private static $instance = null;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string $plugin_name  The string used to uniquely identify this plugin.
	 */
	protected $plugin_name = 'cf-images';

	/**
	 * The current version of the plugin.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string $version  The current version of the plugin.
	 */
	protected $version = '1.2.1-beta.1';

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
	 * Async upload instance.
	 *
	 * @since 1.1.5
	 * @access private
	 * @var Async\Upload $upload
	 */
	private $upload;

	/**
	 * Registered image sizes in WordPress.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var array $registered_sizes
	 */
	private $registered_sizes;

	/**
	 * Widths from the $registered_sizes array.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var array $widths
	 */
	private $widths;

	/**
	 * Heights from the $registered_sizes array.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var array $heights
	 */
	private $heights;

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

		if ( defined( 'CF_IMAGES_VERSION' ) ) {
			$this->version = CF_IMAGES_VERSION;
		}

		$this->load_libs();
		$this->init_integrations();
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
		add_action( 'init', array( $this, 'populate_image_sizes' ) );

		// Use custom paths.
		add_filter( 'cf_images_upload_data', array( $this, 'use_custom_image_path' ) );

		// Disable generation of image sizes.
		if ( get_option( 'cf-images-disable-generation', false ) ) {
			add_filter( 'wp_image_editors', '__return_empty_array' );
			add_filter( 'big_image_size_threshold', '__return_false' );
			add_filter( 'intermediate_image_sizes_advanced', '__return_empty_array' );
		}

		if ( ! is_admin() && $this->can_run() ) {
			// Auto resize functionality.
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_auto_resize' ) );
			add_filter( 'wp_get_attachment_image_attributes', array( $this, 'add_class_to_attachment' ) );
			add_filter( 'wp_content_img_tag', array( $this, 'add_class_to_img_tag' ), 15 );

			// Replace images only on front-end.
			add_filter( 'wp_get_attachment_image_src', array( $this, 'get_attachment_image_src' ), 10, 3 );
			add_filter( 'wp_prepare_attachment_for_js', array( $this, 'prepare_attachment_for_js' ), 10, 2 );
			add_filter( 'wp_calculate_image_srcset', array( $this, 'calculate_image_srcset' ), 10, 5 );

			// This filter is available on WordPress 6.0 or above.
			add_filter( 'wp_content_img_tag', array( $this, 'content_img_tag' ), 10, 3 );
		}

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

		require_once __DIR__ . '/api/class-api.php';
		require_once __DIR__ . '/api/class-image.php';
		require_once __DIR__ . '/api/class-variant.php';

		if ( ! get_option( 'cf-images-disable-async', false ) ) {
			require_once __DIR__ . '/async/class-task.php';
			require_once __DIR__ . '/async/class-upload.php';
			$this->upload = new Async\Upload();
		}

	}

	/**
	 * Init inetgrations.
	 *
	 * @since 1.5.0
	 *
	 * @return void
	 */
	private function init_integrations() {

		require_once __DIR__ . '/integrations/class-spectra.php';
		$spectra = new Integrations\Spectra();

		require_once __DIR__ . '/integrations/class-multisite-global-media.php';
		$mgm = new Integrations\Multisite_Global_Media();

		require_once __DIR__ . '/integrations/class-rank-math.php';
		$rank_math = new Integrations\Rank_Math();

	}

	/**
	 * Check if we can run the plugin. Not all images should be converted, for example,
	 * SEO images from meta tags should be left untouched.
	 *
	 * @since 1.1.3
	 *
	 * @return bool
	 */
	private function can_run(): bool {

		if ( $this->is_rest_request() || wp_doing_cron() ) {
			return false;
		}

		if ( doing_filter( 'rank_math/head' ) || doing_action( 'rank_math/opengraph/facebook' ) ) {
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
	 * @return bool
	 */
	private function is_rest_request(): bool {

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

		return strpos( $request_uri, $rest_url_prefix ) !== false;

	}

	/**
	 * Get Cloudflare CDN domain.
	 *
	 * @since 1.0.2
	 *
	 * @return void
	 */
	private function set_cdn_domain() {

		$custom_domain = get_option( 'cf-images-custom-domain', false );

		if ( $custom_domain ) {
			$domain  = wp_http_validate_url( $custom_domain ) ? $custom_domain : get_site_url();
			$domain .= '/cdn-cgi/imagedelivery';

			$this->cdn_domain = $domain;
		}

	}

	/**
	 * Setter for error.
	 *
	 * @since 1.2.0
	 *
	 * @param int|mixed $code     Error code.
	 * @param string    $message  Error message.
	 *
	 * @return void
	 */
	public function set_error( $code = '', string $message = '' ) {
		if ( '' === $code ) {
			self::$error = false;
		} else {
			self::$error = new WP_Error( $code, $message );
		}
	}

	/**
	 * Maybe redirect to plugin page on activation.
	 *
	 * @since 1.0.0
	 *
	 * @return void
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
	 *
	 * @return void
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
	 * Save all required data for faster access later on.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function populate_image_sizes() {

		$this->registered_sizes = wp_get_registered_image_subsizes();

		$this->heights = wp_list_pluck( $this->registered_sizes, 'height' );
		$this->widths  = wp_list_pluck( $this->registered_sizes, 'width' );

	}

	/**
	 * Filters the attachment image source result.
	 *
	 * @since 1.0.0
	 *
	 * @param array|false  $image         {
	 *     Array of image data, or boolean false if no image is available.
	 *
	 *     @type string $image[0]  Image source URL.
	 *     @type int    $image[1]  Image width in pixels.
	 *     @type int    $image[2]  Image height in pixels.
	 *     @type bool   $image[3]  Whether the image is a resized image.
	 * }
	 * @param int|string   $attachment_id  Image attachment ID.
	 * @param string|int[] $size           Requested image size. Can be any registered image size name, or
	 *                                     an array of width and height values in pixels (in that order).
	 *
	 * @return array|false
	 */
	public function get_attachment_image_src( $image, $attachment_id, $size ) {

		if ( ! $this->can_run() || ! $image ) {
			return $image;
		}

		$cloudflare_image_id = get_post_meta( $attachment_id, '_cloudflare_image_id', true );

		/**
		 * Filters the Cloudflare image ID value.
		 *
		 * @since 1.5.0
		 *
		 * @param mixed $cloudflare_image_id  Image meta
		 * @param int   $attachment_id        Attachment ID.
		 */
		$cloudflare_image_id = apply_filters( 'cf_images_attachment_meta', $cloudflare_image_id, (int) $attachment_id );

		if ( empty( $cloudflare_image_id ) ) {
			return $image;
		}

		$hash = get_site_option( 'cf-images-hash', '' );

		if ( empty( $hash ) ) {
			return $image;
		}

		// Full size image with defined dimensions.
		if ( 'full' === $size && isset( $image[1] ) && $image[1] > 0 ) {
			$image[0] = "$this->cdn_domain/$hash/$cloudflare_image_id/w=" . $image[1];
			return $image;
		}

		// Handle `scaled` images.
		if ( false !== strpos( $image[0], '-scaled' ) ) {
			$scaled_size = apply_filters( 'big_image_size_threshold', 2560 );

			/**
			 * This covers two cases:
			 * 1: scaled sizes are disabled, but we have the size passed to the function
			 * 2: scaled size equals the requested size
			 * In both cases - use the size value.
			 */
			if ( ( ! $scaled_size && is_int( $size ) ) || $scaled_size === $size ) {
				$image[0] = "$this->cdn_domain/$hash/$cloudflare_image_id/w=" . $size;
			} else { // Fallback to scaled size.
				$image[0] = "$this->cdn_domain/$hash/$cloudflare_image_id/w=" . $scaled_size;
			}

			return $image;
		}

		preg_match( '/-(\d+)x(\d+)\.[a-zA-Z]{3,4}$/', $image[0], $variant_image );

		// Image with `-<width>x<height>` prefix, for example, image-300x125.jpg.
		if ( isset( $variant_image[1] ) && isset( $variant_image[2] ) ) {
			// Check if the image is a cropped version.
			$height_key = array_search( (int) $variant_image[1], $this->heights, true );
			$width_key  = array_search( (int) $variant_image[2], $this->widths, true );

			if ( $width_key && $height_key && $width_key === $height_key && true === $this->registered_sizes[ $width_key ]['crop'] ) {
				$image[0] = "$this->cdn_domain/$hash/$cloudflare_image_id/w=" . $variant_image[1] . ',h=' . $variant_image[2] . ',fit=crop';
				return $image;
			}

			// Not a cropped image.
			$image[0] = "$this->cdn_domain/$hash/$cloudflare_image_id/w=" . $variant_image[1] . ',h=' . $variant_image[2];
			return $image;
		}

		// Image without size prefix and no defined sizes - use the maximum available width.
		if ( ! $variant_image && ! isset( $image[1] ) ) {
			$image[0] = "$this->cdn_domain/$hash/$cloudflare_image_id/w=9999";
			return $image;
		}

		return $image;

	}

	/**
	 * Filters the attachment data prepared for JavaScript.
	 *
	 * @since 1.0.0
	 *
	 * @param array   $response    Array of prepared attachment data. @see wp_prepare_attachment_for_js().
	 * @param WP_Post $attachment  Attachment object.
	 *
	 * @return array
	 */
	public function prepare_attachment_for_js( array $response, WP_Post $attachment ): array {

		if ( ! $this->can_run() ) {
			return $response;
		}

		if ( empty( $response['sizes'] ) ) {
			return $response;
		}

		foreach ( $response['sizes'] as $id => $size ) {
			if ( ! isset( $size['url'] ) ) {
				continue;
			}

			$response['sizes'][ $id ]['url'] = $this->get_attachment_image_src( array( $size['url'] ), $attachment->ID, $id );
		}

		return $response;

	}

	/**
	 * Filters an image's 'srcset' sources.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $sources {
	 *     One or more arrays of source data to include in the 'srcset'.
	 *
	 *     @type array $width {
	 *         @type string $url        The URL of an image source.
	 *         @type string $descriptor The descriptor type used in the image candidate string, either 'w' or 'x'.
	 *         @type int    $value      The source width if paired with a 'w' descriptor, or a
	 *                                  pixel density value if paired with an 'x' descriptor.
	 *     }.
	 * }
	 * @param array  $size_array     {
	 *     An array of requested width and height values.
	 *
	 *     @type int $size_array[0] The width in pixels.
	 *     @type int $size_array[1] The height in pixels.
	 * }
	 * @param string $image_src     The 'src' of the image.
	 * @param array  $image_meta    The image metadata as returned by 'wp_get_attachment_metadata()'.
	 * @param int    $attachment_id Image attachment ID or 0.
	 */
	public function calculate_image_srcset( array $sources, array $size_array, string $image_src, array $image_meta, int $attachment_id ): array {

		if ( ! $this->can_run() ) {
			return $sources;
		}

		foreach ( $sources as $id => $size ) {
			if ( ! isset( $size['url'] ) ) {
				continue;
			}

			$image = $this->get_attachment_image_src( array( $size['url'] ), $attachment_id, $id );

			$sources[ $id ]['url'] = $image[0];
		}

		return $sources;

	}

	/**
	 * Filters an <img> tag within the content for a given context.
	 *
	 * Sometimes users or editors will add an <img> tag to the content. And such content will not be processed through
	 * other hooks. Instead of processing all content, we will only focus on filtering <img> elements on the page.
	 *
	 * This hook requires WordPress 6.0 or above.
	 *
	 * @since 1.0.2
	 *
	 * @param string      $filtered_image  Full img tag with attributes that will replace the source img tag.
	 * @param string|bool $context         Additional context, like the current filter name or the function name from where this was called.
	 * @param int         $attachment_id   The image attachment ID. May be 0 in case the image is not an attachment.
	 *
	 * @return string
	 */
	public function content_img_tag( string $filtered_image, $context, int $attachment_id ): string {

		// Find `src` attribute in an image.
		preg_match( '/src=[\'"]([^\'"]+)/i', $filtered_image, $src );

		if ( ! isset( $src[1] ) ) {
			return $filtered_image;
		}

		if ( false !== strpos( $src[1], $this->cdn_domain ) ) {
			// Image is already served via Cloudflare.
			return $filtered_image;
		}

		// Find `width` attributes in an image.
		preg_match( '/width=[\'"]([^\'"]+)/i', $filtered_image, $size );

		// We will try to find the best possible match based on the `width` attribute.
		$width = isset( $size[1] ) ? (int) $size[1] : 'full';

		/**
		 * Filter that allows adjusting the attachment ID.
		 *
		 * Some plugins will replace the WordPress image class and prevent WordPress from getting the correct attachment ID.
		 *
		 * @since 1.3.0
		 *
		 * @param int    $attachment_id   The image attachment ID. May be 0 in case the image is not an attachment.
		 * @param string $filtered_image  Full img tag with attributes that will replace the source img tag.
		 */
		$attachment_id = apply_filters( 'cf_images_content_attachment_id', $attachment_id, $filtered_image );

		$image = $this->get_attachment_image_src( array( $src[1] ), $attachment_id, $width );

		if ( isset( $image[0] ) && $image[0] !== $src[1] ) {
			// Replace the image with a Cloudflare alternative.
			$filtered_image = str_replace( $src[1], $image[0], $filtered_image );
		}

		return $filtered_image;

	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since 1.0.0
	 *
	 * @return string  The name of the plugin.
	 */
	public function get_plugin_name(): string {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return string  The version number of the plugin.
	 */
	public function get_version(): string {
		return $this->version;
	}

	/**
	 * Retrieve stored error.
	 *
	 * @since 1.0.0
	 * @sicne 1.2.0  Change to static method.
	 *
	 * @return bool|WP_Error
	 */
	public static function get_error() {
		return self::$error;
	}

	/**
	 * Set custom ID for image to use the custom paths in image URLs.
	 *
	 * @since 1.2.0
	 *
	 * @param array $data  Image data sent to the Cloudflare Images API.
	 *
	 * @return array
	 */
	public function use_custom_image_path( array $data ): array {

		if ( ! get_option( 'cf-images-custom-id', false ) ) {
			return $data;
		}

		if ( ! isset( $data['id'] ) && isset( $data['file']->postname ) ) {
			$data['id'] = $data['file']->postname;
		}

		return $data;

	}

	/**
	 * Enqueue auto resize script on front-end.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function enqueue_auto_resize() {

		if ( ! get_option( 'cf-images-auto-resize', false ) ) {
			return;
		}

		wp_enqueue_script( $this->get_plugin_name(), CF_IMAGES_DIR_URL . 'assets/js/cf-auto-resize.min.js', array(), $this->version, true );

	}

	/**
	 * Add special class to images that are served via Cloudflare.
	 *
	 * @since 1.2.0
	 * @see wp_get_attachment_image()
	 *
	 * @param string[] $attr  Array of attribute values for the image markup, keyed by attribute name.
	 *
	 * @return string[]
	 */
	public function add_class_to_attachment( array $attr ): array {

		if ( ! get_option( 'cf-images-auto-resize', false ) ) {
			return $attr;
		}

		if ( empty( $attr['src'] ) || false === strpos( $attr['src'], $this->cdn_domain ) ) {
			return $attr;
		}

		if ( empty( $attr['class'] ) ) {
			$attr['class'] = 'cf-image-auto-resize';
		} elseif ( false === strpos( $attr['class'], 'cf-image-auto-resize' ) ) {
			$attr['class'] .= ' cf-image-auto-resize';
		}

		return $attr;

	}

	/**
	 * Add special class to images that are served via Cloudflare.
	 *
	 * @since 1.2.0
	 *
	 * @param string $filtered_image  Full img tag with attributes that will replace the source img tag.
	 *
	 * @return string
	 */
	public function add_class_to_img_tag( string $filtered_image ): string {

		if ( ! get_option( 'cf-images-auto-resize', false ) ) {
			return $filtered_image;
		}

		if ( false === strpos( $filtered_image, $this->cdn_domain ) ) {
			return $filtered_image;
		}

		$this->add_attribute( $filtered_image, 'class', 'cf-image-auto-resize' );

		return $filtered_image;

	}

	/**
	 * Add attribute to selected tag.
	 *
	 * @since 1.2.0
	 *
	 * @param string $element    HTML element.
	 * @param string $attribute  Attribute name (srcset, size, etc).
	 * @param string $value      Attribute value.
	 */
	private function add_attribute( string &$element, string $attribute, string $value = null ) {

		$closing = false === strpos( $element, '/>' ) ? '>' : ' />';
		$quotes  = false === strpos( $element, '"' ) ? '\'' : '"';

		preg_match( "/$attribute=['\"]([^'\"]+)['\"]/is", $element, $current_value );
		if ( ! empty( $current_value['1'] ) ) {
			// Remove the attribute if it already exists.
			$element = preg_replace( '/' . $attribute . '=[\'"](.*?)[\'"]/i', '', $element );

			if ( false === strpos( $current_value['1'], $value ) ) {
				$value = $current_value['1'] . ' ' . $value;
			} else {
				$value = $current_value['1'];
			}
		}

		if ( ! is_null( $value ) ) {
			$element = rtrim( $element, $closing ) . " $attribute=$quotes$value$quotes$closing";
		} else {
			$element = rtrim( $element, $closing ) . " $attribute$closing";
		}

	}

}
