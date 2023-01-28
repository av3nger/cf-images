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
use WP_Query;

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
	protected $version = '1.1.3';

	/**
	 * Error status.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var bool|WP_Error $error
	 */
	private $error = false;

	/**
	 * Admin instance.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var Admin $admin
	 */
	private $admin;

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
	 * Default stats.
	 *
	 * @since 1.1.0
	 * @access private
	 * @var int[]
	 */
	private $default_stats = array(
		'synced'      => 0,
		'api_current' => 0,
		'api_allowed' => 100000,
	);

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

		if ( is_admin() ) {
			$this->admin = new Admin();
		}

		if ( ! $this->is_set_up() ) {
			return;
		}

		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_cf_images_offload_image', array( $this, 'ajax_offload_image' ) );
			add_action( 'wp_ajax_cf_images_bulk_process', array( $this, 'ajax_bulk_process' ) );
			add_action( 'wp_ajax_cf_images_skip_image', array( $this, 'ajax_skip_image' ) );
		}

		add_action( 'admin_init', array( $this, 'maybe_redirect_to_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'enable_flexible_variants' ) );
		add_action( 'init', array( $this, 'populate_image_sizes' ) );

		// Disable generation of image sizes.
		if ( get_option( 'cf-images-disable-generation', false ) ) {
			add_filter( 'wp_image_editors', '__return_empty_array' );
			add_filter( 'big_image_size_threshold', '__return_false' );
			add_filter( 'intermediate_image_sizes_advanced', '__return_empty_array' );
		}

		// Image actions.
		if ( get_option( 'cf-images-auto-offload', false ) ) {
			add_filter( 'wp_async_wp_generate_attachment_metadata', array( $this, 'upload_image' ), 10, 2 );
		}
		add_action( 'delete_attachment', array( $this, 'delete_image' ) );

		if ( ! is_admin() && $this->can_run() ) {
			// Replace images only on front-end.
			add_filter( 'wp_get_attachment_image_src', array( $this, 'get_attachment_image_src' ), 10, 3 );
			add_filter( 'wp_prepare_attachment_for_js', array( $this, 'prepare_attachment_for_js' ), 10, 2 );
			add_filter( 'wp_calculate_image_srcset', array( $this, 'calculate_image_srcset' ), 10, 5 );

			global $wp_version;
			// This filter is available on WordPress 6.0 or above.
			if ( version_compare( $wp_version, '6.0.0', '>=' ) ) {
				add_filter( 'wp_content_img_tag', array( $this, 'content_img_tag' ), 10, 3 );
			}
			// TODO: add content filtering.
		}

	}

	/**
	 * Load all required libraries.
	 *
	 * @since 1.0.0
	 */
	private function load_libs() {

		require_once __DIR__ . '/Admin.php';
		require_once __DIR__ . '/Settings.php';

		require_once __DIR__ . '/Api/Api.php';
		require_once __DIR__ . '/Api/Image.php';
		require_once __DIR__ . '/Api/Variant.php';

		require_once __DIR__ . '/Async/Task.php';
		require_once __DIR__ . '/Async/Upload.php';
		new Async\Upload();

	}

	/**
	 * Check if this is a valid AJAX request coming from the user.
	 *
	 * @since 1.0.1
	 *
	 * @return void
	 */
	private function check_ajax_request() {

		check_ajax_referer( 'cf-images-nonce' );

		if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['data'] ) ) {
			wp_die();
		}

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
		$a = doing_filter( 'rank_math/head' );
		$a = doing_action( 'rank_math/opengraph/facebook' );

		if ( doing_filter( 'rank_math/head' ) || doing_action( 'rank_math/opengraph/facebook' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Offload selected image to Cloudflare Images.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_offload_image() {

		$this->check_ajax_request();

		$attachment_id = (int) filter_input( INPUT_POST, 'data', FILTER_SANITIZE_NUMBER_INT );

		$metadata = wp_get_attachment_metadata( $attachment_id );
		if ( false === $metadata ) {
			$message = sprintf( // translators: %1$s - opening <a> tag, %2$s - closing </a> tag.
				esc_html__( 'Image metadata not found. %1$sSkip image%2$s', 'cf-images' ),
				'<a href="#" data-id="' . $attachment_id . '" onclick="window.cfSkipImage(this)">',
				'</a>'
			);

			wp_send_json_error( $message );
		}

		$this->upload_image( $metadata, $attachment_id );

		if ( is_wp_error( $this->error ) ) {
			wp_send_json_error( $this->error->get_error_message() );
		}

		$this->fetch_stats();

		wp_send_json_success();

	}

	/**
	 * Bulk upload or bulk remove images progress bar handler.
	 *
	 * @since 1.0.1  Combined from ajax_remove_images() and ajax_upload_images().
	 *
	 * @return void
	 */
	public function ajax_bulk_process() {

		$this->check_ajax_request();

		// Data sanitized later in code.
		$progress = filter_input( INPUT_POST, 'data', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		if ( ! isset( $progress['action'] ) ) {
			wp_send_json_error( esc_html__( 'Incorrect action call', 'cf-images' ) );
		}

		if ( ! isset( $progress['currentStep'] ) || ! isset( $progress['totalSteps'] ) ) {
			wp_send_json_error( esc_html__( 'No current step or total steps defined', 'cf-images' ) );
		}

		$step  = (int) $progress['currentStep'];
		$total = (int) $progress['totalSteps'];

		$action = sanitize_text_field( $progress['action'] );

		if ( ! in_array( $action, array( 'upload', 'remove' ), true ) ) {
			wp_send_json_error( esc_html__( 'Unsupported action', 'cf-images' ) );
		}

		// Progress just started.
		if ( 0 === $step && 0 === $total ) {
			$args = $this->get_wp_query_args( $action );

			// Look for images that have been offloaded.
			$images = new WP_Query( $args );

			// No available images found.
			if ( 0 === $images->found_posts ) {
				$this->update_stats( 0, false ); // Reset stats.
				$this->fetch_stats();
				wp_send_json_error( __( 'No images found', 'cf-images' ) );
			}

			$total = $images->found_posts;
		}

		$step++;

		// Something is wrong with the steps count.
		if ( $step > $total ) {
			wp_send_json_error( esc_html__( 'Step error', 'cf-images' ) );
		}

		$args = $this->get_wp_query_args( $action, true );

		// Look for images that have been offloaded.
		$image = new WP_Query( $args );

		if ( 'upload' === $action ) {
			$metadata = wp_get_attachment_metadata( $image->post->ID );
			if ( false === $metadata ) {
				update_post_meta( $image->post->ID, '_cloudflare_image_skip', true );
			} else {
				$this->upload_image( $metadata, $image->post->ID );

				// If there's an error with offloading, we need to mark such an image as skipped.
				if ( is_wp_error( $this->error ) ) {
					update_post_meta( $image->post->ID, '_cloudflare_image_skip', true );
					$this->error = false; // Reset the error.
				}
			}
		} else {
			$this->delete_image( $image->post->ID );
		}

		// On final step - update API stats.
		if ( $step === $total ) {
			$this->fetch_stats();
		}

		$response = array(
			'currentStep' => $step,
			'totalSteps'  => $total,
			'status'      => sprintf( /* translators: %1$d - current image, %2$d - total number of images */
				esc_html__( 'Processing image %1$d out of %2$d...', 'cf-images' ),
				(int) $step,
				$total
			),
		);

		wp_send_json_success( $response );

	}

	/**
	 * Skip image from processing.
	 *
	 * @since 1.1.2
	 *
	 * @return void
	 */
	public function ajax_skip_image() {

		$this->check_ajax_request();

		$attachment_id = (int) filter_input( INPUT_POST, 'data', FILTER_SANITIZE_NUMBER_INT );

		update_post_meta( $attachment_id, '_cloudflare_image_skip', true );

		wp_send_json_success();

	}

	/**
	 * Get Cloudflare CDN domain.
	 *
	 * @since 1.0.2
	 *
	 * @return string
	 */
	private function get_cdn_domain(): string {

		$domain = 'https://imagedelivery.net';

		$custom_domain = get_option( 'cf-images-custom-domain', false );

		if ( $custom_domain ) {
			$domain  = wp_http_validate_url( $custom_domain ) ? $custom_domain : get_site_url();
			$domain .= '/cdn-cgi/imagedelivery';
		}

		return $domain;

	}

	/**
	 * Get arguments for WP_Query call.
	 *
	 * @since 1.0.1
	 *
	 * @param string $action  Action name. Accepts: upload|remove.
	 * @param bool   $single  Fetch single entry? Default: fetch all.
	 *
	 * @return string[]
	 */
	private function get_wp_query_args( string $action, bool $single = false ): array {

		$args = array(
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
		);

		if ( $single ) {
			$args['posts_per_page'] = 1;
		}

		if ( 'upload' === $action ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_cloudflare_image_id',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_cloudflare_image_skip',
					'compare' => 'NOT EXISTS',
				),
			);
		}

		if ( 'remove' === $action ) {
			$args['meta_key'] = '_cloudflare_image_id'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		}

		return $args;

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
			$this->error = new WP_Error( $e->getCode(), $e->getMessage() );
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
	 * Upload to Cloudflare images.
	 *
	 * @since 1.0.0
	 *
	 * @param array $metadata       An array of attachment meta data.
	 * @param int   $attachment_id  Current attachment ID.
	 *
	 * @return array
	 */
	public function upload_image( array $metadata, int $attachment_id ): array {

		if ( ! isset( $metadata['file'] ) ) {
			$this->error = new WP_Error( 404, __( 'Media file not found', 'cf-images' ) );
			return $metadata;
		}

		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			$this->error = new WP_Error( 415, __( 'Unsupported media type', 'cf-images' ) );
			return $metadata;
		}

		$image = new Api\Image();
		$dir   = wp_get_upload_dir();
		$path  = trailingslashit( $dir['basedir'] ) . $metadata['file'];

		$url = wp_parse_url( get_site_url() );
		if ( is_multisite() && ! is_subdomain_install() ) {
			$host = $url['host'] . $url['path'];
		} else {
			$host = $url['host'];
		}

		$name = trailingslashit( $host ) . $metadata['file'];

		try {
			$results = $image->upload( $path, $attachment_id, $name );
			$this->update_stats( 1 );
			update_post_meta( $attachment_id, '_cloudflare_image_id', $results->id );
			$this->maybe_save_hash( $results->variants );

			if ( doing_filter( 'wp_async_wp_generate_attachment_metadata' ) ) {
				$this->fetch_stats();
			}
		} catch ( Exception $e ) {
			$this->error = new WP_Error( $e->getCode(), $e->getMessage() );
		}

		return $metadata;

	}

	/**
	 * Fetch API stats.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	private function fetch_stats() {

		$image = new Api\Image();

		try {
			$count = $image->stats();

			$stats = get_option( 'cf-images-stats', $this->default_stats );

			if ( isset( $count->current ) ) {
				$stats['api_current'] = $count->current;
			}

			if ( isset( $count->allowed ) ) {
				$stats['api_allowed'] = $count->allowed;
			}

			update_option( 'cf-images-stats', $stats, false );
		} catch ( Exception $e ) {
			$this->error = new WP_Error( $e->getCode(), $e->getMessage() );
		}

	}

	/**
	 * Update image stats.
	 *
	 * @since 1.0.1
	 *
	 * @param int  $count  Add or subtract number from `synced` image count.
	 * @param bool $add    By default, we will add the required number of images. If set to false - replace the value.
	 *
	 * @return void
	 */
	private function update_stats( int $count, bool $add = true ) {

		$stats = get_option( 'cf-images-stats', $this->default_stats );

		if ( $add ) {
			$stats['synced'] += $count;
		} else {
			$stats['synced'] = $count;
		}

		if ( $stats['synced'] < 0 ) {
			$stats['synced'] = 0;
		}

		update_option( 'cf-images-stats', $stats, false );

	}

	/**
	 * Try to get the Cloudflare Images account hash and store it for future use.
	 *
	 * @since 1.0.0
	 *
	 * @param array $variants  Saved variants.
	 *
	 * @return void
	 */
	private function maybe_save_hash( array $variants ) {

		$hash = get_option( 'cf-images-hash', '' );

		if ( ! empty( $hash ) || ! isset( $variants[0] ) ) {
			return;
		}

		preg_match_all( '#/(.*?)/#i', $variants[0], $hash );

		if ( isset( $hash[1] ) && ! empty( $hash[1][1] ) ) {
			update_option( 'cf-images-hash', $hash[1][1], false );
		}

	}

	/**
	 * Fires before an attachment is deleted, at the start of wp_delete_attachment().
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id  Attachment ID.
	 *
	 * @return void
	 */
	public function delete_image( int $post_id ) {

		$id = get_post_meta( $post_id, '_cloudflare_image_id', true );

		if ( ! $id ) {
			return;
		}

		$image = new Api\Image();

		try {
			$image->delete( $id );
			$this->update_stats( -1 );
			delete_post_meta( $post_id, '_cloudflare_image_id' );
			delete_post_meta( $post_id, '_cloudflare_image_skip' );

			if ( doing_action( 'delete_attachment' ) ) {
				$this->fetch_stats();
			}
		} catch ( Exception $e ) {
			$this->error = new WP_Error( $e->getCode(), $e->getMessage() );
		}

	}

	/**
	 * Filters the attachment image source result.
	 *
	 * @since 1.0.0
	 *
	 * @param array|false  $image         {
	 *     Array of image data, or boolean false if no image is available.
	 *
	 *     @type string $0  Image source URL.
	 *     @type int    $1  Image width in pixels.
	 *     @type int    $2  Image height in pixels.
	 *     @type bool   $3  Whether the image is a resized image.
	 * }
	 * @param int          $attachment_id  Image attachment ID.
	 * @param string|int[] $size           Requested image size. Can be any registered image size name, or
	 *                                     an array of width and height values in pixels (in that order).
	 *
	 * @return array|false
	 */
	public function get_attachment_image_src( $image, int $attachment_id, $size ) {

		if ( ! $this->can_run() ) {
			return $image;
		}

		$meta = get_post_meta( $attachment_id, '_cloudflare_image_id', true );

		if ( empty( $meta ) ) {
			return $image;
		}

		$hash = get_option( 'cf-images-hash', '' );

		if ( empty( $hash ) ) {
			return $image;
		}

		$domain = $this->get_cdn_domain();

		// Full size image with defined dimensions.
		if ( 'full' === $size && isset( $image[1] ) && $image[1] > 0 ) {
			$image[0] = "$domain/$hash/$meta/w=" . $image[1];
			return $image;
		}

		// Handle `scaled` images.
		if ( false !== strpos( $image[0], '-scaled' ) && apply_filters( 'big_image_size_threshold', 2560 ) === $size ) {
			$image[0] = "$domain/$hash/$meta/w=" . $size;
			return $image;
		}

		preg_match( '/-(\d+)x(\d+)\.[a-zA-Z]{3,4}$/', $image[0], $variant_image );

		// Image with `-<width>x<height>` prefix, for example, image-300x125.jpg.
		if ( isset( $variant_image[1] ) && isset( $variant_image[2] ) ) {
			// Check if the image is a cropped version.
			$height_key = array_search( (int) $variant_image[1], $this->heights, true );
			$width_key  = array_search( (int) $variant_image[2], $this->widths, true );

			if ( $width_key && $height_key && $width_key === $height_key && true === $this->registered_sizes[ $width_key ]['crop'] ) {
				$image[0] = "$domain/$hash/$meta/w=" . $variant_image[1] . ',h=' . $variant_image[2] . ',fit=crop';
				return $image;
			}

			// Not a cropped image.
			$image[0] = "$domain/$hash/$meta/w=" . $variant_image[1] . ',h=' . $variant_image[2];
			return $image;
		}

		// Image without size prefix and no defined sizes - use the maximum available width.
		if ( ! $variant_image && ! isset( $image[1] ) ) {
			$image[0] = "$domain/$hash/$meta/w=9999";
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
	 *         @type string $descriptor The descriptor type used in the image candidate string,
	 *                                  either 'w' or 'x'.
	 *         @type int    $value      The source width if paired with a 'w' descriptor, or a
	 *                                  pixel density value if paired with an 'x' descriptor.
	 *     }
	 * }
	 * @param array $size_array     {
	 *     An array of requested width and height values.
	 *
	 *     @type int $0 The width in pixels.
	 *     @type int $1 The height in pixels.
	 * }
	 * @param string $image_src     The 'src' of the image.
	 * @param array  $image_meta    The image meta data as returned by 'wp_get_attachment_metadata()'.
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

		$domain = $this->get_cdn_domain();
		if ( false !== strpos( $src[1], $domain ) ) {
			// Image is already served via Cloudflare.
			return $filtered_image;
		}

		// Find `width` attributes in an image.
		preg_match( '/width=[\'"]([^\'"]+)/i', $filtered_image, $size );

		// We will try to find the best possible match based on the `width` attribute.
		$width = isset( $size[1] ) ? (int) $size[1] : 'full';

		/**
		 * Support for Spectra plugins.
		 *
		 * Spectra blocks will remove the default WordPress class that identifies an image, and will replace it with
		 * their own uag-image-<ID> class. Try to get attachment ID from class.
		 *
		 * @since 1.3.0
		 */
		if ( 0 === $attachment_id ) {
			// Find `class` attributes in an image.
			preg_match( '/class=[\'"]([^\'"]+)/i', $filtered_image, $class );
			if ( isset( $class[1] ) && 'uag-image-' === substr( $class[1], 0, 10 ) ) {
				$attachment_id = (int) substr( $class[1], 10 );
			}
		}

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
	 * Retrieve the admin instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Admin  The admin instance of the plugin.
	 */
	public function get_admin(): Admin {
		return $this->admin;
	}

	/**
	 * Retrieve stored error.
	 *
	 * @since 1.0.0
	 *
	 * @return bool|WP_Error
	 */
	public function get_error() {
		return $this->error;
	}

}
