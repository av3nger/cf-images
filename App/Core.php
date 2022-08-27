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
 * @link https://vcore.ru
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
	protected $version = '1.0.0';

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
			add_action( 'wp_ajax_cf_images_remove_images', array( $this, 'ajax_remove_images' ) );
			add_action( 'wp_ajax_cf_images_upload_images', array( $this, 'ajax_upload_images' ) );
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
		add_filter( 'wp_async_wp_generate_attachment_metadata', array( $this, 'upload_image' ), 10, 3 );
		add_action( 'delete_attachment', array( $this, 'delete_image' ), 10, 2 );

		// Replace images.
		add_filter( 'wp_get_attachment_image_src', array( $this, 'get_attachment_image_src' ), 10, 3 );
		add_filter( 'wp_prepare_attachment_for_js', array( $this, 'prepare_attachment_for_js' ), 10, 3 );
		add_filter( 'wp_calculate_image_srcset', array( $this, 'calculate_image_srcset' ), 10, 5 );

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
	 * Offload selected image to Cloudflare Images.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_offload_image() {

		check_ajax_referer( 'cf-images-nonce' );

		if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['data'] ) ) {
			wp_die();
		}

		$attachment_id = (int) filter_input( INPUT_POST, 'data', FILTER_SANITIZE_NUMBER_INT );
		$this->upload_image( wp_get_attachment_metadata( $attachment_id ), $attachment_id, 'single' );

		if ( is_wp_error( $this->error ) ) {
			wp_send_json_error( $this->error->get_error_message() );
		}

		wp_send_json_success();

	}

	/**
	 * Remove all images from Cloudflare progress bar handler.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_remove_images() {

		check_ajax_referer( 'cf-images-nonce' );

		if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['data'] ) ) {
			wp_die();
		}

		// Data sanitized later in code.
		$progress = filter_input( INPUT_POST, 'data', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		if ( ! isset( $progress['currentStep'] ) || ! isset( $progress['totalSteps'] ) ) {
			wp_send_json_error( esc_html__( 'No current step or total steps defined', 'cf-images' ) );
		}

		$step  = (int) $progress['currentStep'];
		$total = (int) $progress['totalSteps'];

		// Progress just started.
		if ( 0 === $step && 0 === $total ) {
			$args = array(
				'post_type'   => 'attachment',
				'post_status' => 'inherit',
				'meta_key'    => '_cloudflare_image_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			);

			// Look for images that have been offloaded.
			$images = new WP_Query( $args );
			$total  = $images->found_posts;
		}

		$step++;

		// We have some data left.
		if ( $step <= $total ) {
			$args = array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'meta_key'       => '_cloudflare_image_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'posts_per_page' => 1,
			);

			// Look for images that have been offloaded.
			$images = new WP_Query( $args );
			$this->delete_image( $images->post->ID, $images->post );
		}

		$response = array(
			'currentStep' => $step,
			'totalSteps'  => $total,
			'status'      => sprintf( /* translators: %1$d - current image, %2$d - total number of images */
				esc_html__( 'Removing image %1$d from %2$d...', 'cf-images' ),
				(int) $step,
				$total
			),
		);

		wp_send_json_success( $response );
	}

	/**
	 * Upload all images to Cloudflare progress bar handler.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_upload_images() {

		check_ajax_referer( 'cf-images-nonce' );

		if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['data'] ) ) {
			wp_die();
		}

		// Data sanitized later in code.
		$progress = filter_input( INPUT_POST, 'data', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		if ( ! isset( $progress['currentStep'] ) || ! isset( $progress['totalSteps'] ) ) {
			wp_send_json_error( esc_html__( 'No current step or total steps defined', 'cf-images' ) );
		}

		$step  = (int) $progress['currentStep'];
		$total = (int) $progress['totalSteps'];

		// Progress just started.
		if ( 0 === $step && 0 === $total ) {
			$args = array(
				'post_type'   => 'attachment',
				'post_status' => 'inherit',
				'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => '_cloudflare_image_id',
						'compare' => 'NOT EXISTS',
					),
				),
			);

			// Look for images that have been offloaded.
			$images = new WP_Query( $args );
			$total  = $images->found_posts;
		}

		$step++;

		// We have some data left.
		if ( $step <= $total ) {
			$args = array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => '_cloudflare_image_id',
						'compare' => 'NOT EXISTS',
					),
				),
				'posts_per_page' => 1,
			);

			// Look for images that have been offloaded.
			$image = new WP_Query( $args );
			$this->upload_image( wp_get_attachment_metadata( $image->post->ID ), $image->post->ID, 'single' );
		}

		$response = array(
			'currentStep' => $step,
			'totalSteps'  => $total,
			'status'      => sprintf( /* translators: %1$d - current image, %2$d - total number of images */
				esc_html__( 'Removing image %1$d from %2$d...', 'cf-images' ),
				(int) $step,
				$total
			),
		);

		wp_send_json_success( $response );

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
			update_option( 'cf-images-setup-done', true );
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
	 * @param array  $metadata       An array of attachment meta data.
	 * @param int    $attachment_id  Current attachment ID.
	 * @param string $context        Additional context. Can be 'create' when metadata was initially created for new attachment
	 *                               or 'update' when the metadata was updated.
	 *
	 * @return array
	 */
	public function upload_image( array $metadata, int $attachment_id, string $context ): array {

		$image = new Api\Image();
		$dir   = wp_get_upload_dir();
		$path  = trailingslashit( $dir['basedir'] ) . $metadata['file'];
		$host  = wp_parse_url( get_site_url(), PHP_URL_HOST );
		$name  = trailingslashit( $host ) . $metadata['file'];

		try {
			$results = $image->upload( $path, $attachment_id, $name );
			update_post_meta( $attachment_id, '_cloudflare_image_id', $results->id );
			$this->maybe_save_hash( $results->variants );
		} catch ( Exception $e ) {
			$this->error = new WP_Error( $e->getCode(), $e->getMessage() );
		}

		return $metadata;

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
	 * @param int     $post_id  Attachment ID.
	 * @param WP_Post $post     Post object.
	 *
	 * @return void
	 */
	public function delete_image( int $post_id, WP_Post $post ) {

		$id = get_post_meta( $post_id, '_cloudflare_image_id', true );

		if ( ! $id ) {
			return;
		}

		$image = new Api\Image();

		try {
			$image->delete( $id );
			delete_post_meta( $post_id, '_cloudflare_image_id' );
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
	 * @return array
	 */
	public function get_attachment_image_src( $image, int $attachment_id, $size ): array {

		$domain = 'https://imagedelivery.net';
		if ( get_option( 'cf-images-custom-domain', false ) ) {
			$domain = get_site_url() . '/cdn-cgi/imagedelivery';
		}

		$meta = get_post_meta( $attachment_id, '_cloudflare_image_id', true );

		if ( empty( $meta ) ) {
			return $image;
		}

		$hash = get_option( 'cf-images-hash', '' );

		if ( empty( $hash ) ) {
			return $image;
		}

		// Full size image with defined dimensions.
		if ( 'full' === $size && isset( $image[1] ) && $image[1] > 0 ) {
			$image[0] = "$domain/$hash/$meta/w=" . $image[1];
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
	 * @param array       $response    Array of prepared attachment data. @see wp_prepare_attachment_for_js().
	 * @param WP_Post     $attachment  Attachment object.
	 * @param array|false $meta        Array of attachment metadata, or false if there is none.
	 *
	 * @return array
	 */
	public function prepare_attachment_for_js( array $response, WP_Post $attachment, $meta ): array {

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
