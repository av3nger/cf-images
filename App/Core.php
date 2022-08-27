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
			add_action( 'wp_ajax_cf_images_sync_image_sizes', array( $this, 'ajax_sync_image_sizes' ) );
			add_action( 'wp_ajax_cf_images_offload_image', array( $this, 'ajax_offload_image' ) );
		}

		// Disable generation of image sizes.
		if ( get_option( 'cf-images-disable-generation', false ) ) {
			add_filter( 'wp_image_editors', '__return_empty_array' );
			add_filter( 'big_image_size_threshold', '__return_false' );
			add_filter( 'intermediate_image_sizes_advanced', '__return_empty_array' );
		}

		/**
		 * These two functions do the same thing, the only difference is the output.
		 * First one covers wp_get_registered_image_subsizes(), the second one - get_intermediate_image_sizes().
		 */
		add_filter( 'cf_images_attachment_sizes', array( $this, 'manage_image_sizes' ) );
		add_filter( 'cf_images_registered_sizes', array( $this, 'manage_registered_sizes' ) );

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
	 * Make sure we have all the required sizes. Add the full size image size.
	 *
	 * TODO: add scaled images.
	 *
	 * @since 1.0.0
	 *
	 * @param array $sizes  Current array of registered image sizes.
	 *
	 * @return array
	 */
	public function manage_image_sizes( array $sizes ): array {

		// Add the full size image.
		if ( ! isset( $sizes['full'] ) ) {
			$sizes['full'] = array(
				'crop'   => false,
				'height' => 9999,
				'width'  => 9999,
			);
		}

		return $sizes;

	}

	/**
	 * Make sure we have all the required sizes. Add the full size image size.
	 *
	 * @since 1.0.0
	 *
	 * @param array $sizes  Current array of registered image sizes.
	 *
	 * @return array
	 */
	public function manage_registered_sizes( array $sizes ): array {

		if ( ! in_array( 'full', $sizes, true ) ) {
			$sizes[] = 'full';
		}

		return $sizes;

	}

	/**
	 * Make sure all image sizes, registered in WordPress, are mapped to appropriate image variants.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_sync_image_sizes() {

		check_ajax_referer( 'cf-images-nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die();
		}

		$variants = get_option( 'cf-images-variants', array() );
		$sizes    = apply_filters( 'cf_images_attachment_sizes', wp_get_registered_image_subsizes() );

		$variant = new Api\Variant();

		$updated = false;
		foreach ( $sizes as $id => $size ) {
			// We already have that size registered.
			if ( array_key_exists( $id, $variants ) ) {
				continue;
			}

			$fit = isset( $size['crop'] ) && $size['crop'] ? 'crop' : 'scale-down';

			$width  = 0 === $size['width'] ? 9999 : $size['width'];
			$height = 0 === $size['height'] ? 9999 : $size['height'];

			$name = "{$width}x$height"; // TODO: maybe, we should use the WordPress image size ID?

			// Register size.
			try {
				$variant->create( $name, $width, $height, $fit );
			} catch ( Exception $e ) {
				$this->error = new WP_Error( $e->getCode(), $e->getMessage() );
				wp_send_json_error( $e->getMessage() );
				wp_die();
			}

			unset( $size['crop'] );

			$updated         = true;
			$size['variant'] = $name;
			$variants[ $id ] = $size;
		}

		if ( $updated ) {
			update_option( 'cf-images-variants', $variants, false );
		}

		wp_send_json_success();

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

		try {
			$results = $image->upload( $path, $attachment_id, $metadata['file'] );
			update_post_meta( $attachment_id, '_cloudflare_image_id', $results->id );
			//$this->update_image_meta( $attachment_id, $path );
			$this->maybe_save_hash( $results->variants );
		} catch ( Exception $e ) {
			$this->error = new WP_Error( $e->getCode(), $e->getMessage() );
		}

		return $metadata;

	}

	/**
	 * Update image meta with a list of available sizes.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $attachment_id  Attachment ID.
	 * @param string $path           Full image path on server.
	 *
	 * @return void
	 */
	private function update_image_meta( int $attachment_id, string $path ) {

		$registered_sizes = wp_get_registered_image_subsizes();

		$variants = get_option( 'cf-images-variants', array() );

		$data = get_post_meta( $attachment_id, '_wp_attachment_metadata', true );
		$mime = wp_check_filetype_and_ext( $path, basename( $path ) );

		foreach ( $registered_sizes as $id => $size ) {
			// Already exists? Skip.
			if ( isset( $data['sizes'][ $id ] ) ) {
				continue;
			}

			// Do not have that image size yet as a variant.
			if ( ! array_key_exists( $id, $variants ) ) {
				continue;
			}

			$data['sizes'][ $id ] = array(
				'file'      => $variants[ $id ]['variant'],
				'width'     => $size['width'],
				'height'    => $size['height'],
				'mime-type' => $mime['type'],
			);
		}

		update_post_meta( $attachment_id, '_wp_attachment_metadata', $data );

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

		/**
		 * Flexible variants disabled.
		 */
		if ( ! get_option( 'cf-images-flexible-variants', false ) ) {
			$variants = get_option( 'cf-images-variants', array() );

			if ( is_string( $size ) && array_key_exists( $size, $variants ) ) {
				$image[0] = "$domain/$hash/$meta/" . $variants[ $size ]['variant'];
				return $image;
			}

			$variant_ids = wp_list_pluck( $variants, 'variant' );

			preg_match( '/[^\/]*$/', $image[0], $variant_image ); // TODO: the regex here might be incorrect.
			/*
			preg_match( '/-(\d+)x(\d+)\.[a-zA-Z]{3,4}$/', $image[0], $variant_image );

			if ( isset( $variant_image[1] ) && isset( $variant_image[2] ) ) {
				$ldim = max( $variant_image[1], $variant_image[2] );
			}
			*/

			if ( isset( $variant_image[0] ) && in_array( $variant_image[0], $variant_ids, true ) ) {
				$image[0] = "$domain/$hash/$meta/" . $variant_image[0];
			}

			return $image;
		}

		/**
		 * Flexible variants enabled.
		 */

		// Full size image with defined dimensions.
		if ( 'full' === $size && isset( $image[1] ) && $image[1] > 0 ) {
			$image[0] = "$domain/$hash/$meta/w=" . $image[1];
			return $image;
		}

		preg_match( '/-(\d+)x(\d+)\.[a-zA-Z]{3,4}$/', $image[0], $variant_image );

		// Image with `-<width>x<height>` prefix, for example, image-300x125.jpg.
		if ( isset( $variant_image[1] ) && isset( $variant_image[2] ) ) {
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
