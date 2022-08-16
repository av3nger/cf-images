<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that App attributes and functions used across both the
 * public-facing side of the site and the Admin area.
 *
 * @link       https://wpmudev.com
 * @since      1.0.0
 *
 * @package    CF_Images
 * @subpackage CF_Images/App
 */

namespace CF_Images\App;

use Exception;
use WP_Error;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The core plugin class.
 *
 * This is used to define internationalization, Admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    CF_Images
 * @subpackage CF_Images/App
 * @author     Anton Vanyukov <a.vanyukov@vcore.ru>
 */
class Core {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Error status.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      bool|WP_Error
	 */
	private $error = false;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the Admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		if ( defined( 'CF_IMAGES_VERSION' ) ) {
			$this->version = CF_IMAGES_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'cf-perf';

		$this->load_libs();
		add_action( 'admin_init', array( $this, 'register_image_sizes' ) );
		add_action( 'admin_notices', array( $this, 'error_notice' ) );

		// Disable generation of image sizes.
		add_filter( 'wp_image_editors', '__return_empty_array' );
		add_filter( 'big_image_size_threshold', '__return_false' );
		add_filter( 'intermediate_image_sizes_advanced', '__return_empty_array' );

		// Image actions.
		add_filter( 'wp_async_wp_generate_attachment_metadata', array( $this, 'upload_image' ), 10, 3 );
		add_action( 'delete_attachment', array( $this, 'delete_image' ), 10, 2 );

		// Replace images.
		add_filter( 'wp_get_attachment_image_src', array( $this, 'get_attachment_image_src' ), 10, 3 );
		add_filter( 'wp_prepare_attachment_for_js', array( $this, 'prepare_attachment_for_js' ), 10, 3 );

		/**
		 * Filters the attachment URL.
		 *
		 * @since 1.0.0
		 *
		 * @param string $url           URL for the given attachment.
		 * @param int    $attachment_id Attachment post ID.
		 */
		add_filter( 'wp_get_attachment_url', function ( $url, $id ) {
			return $url;
		}, 10, 2 );

	}

	/**
	 * Load all required libraries.
	 *
	 * @since 1.0.0
	 */
	private function load_libs() {

		require_once 'Api/Api.php';
		require_once 'Api/Image.php';
		require_once 'Api/Variant.php';

		require_once 'Async/Task.php';
		require_once 'Async/Upload.php';
		new Async\Upload();

	}

	/**
	 * Show error notice.
	 *
	 * @since 1.0.0
	 */
	public function error_notice() {
		if ( false === $this->error ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p>
				<?php
				printf( /* translators: %1$s - error message, %2$d - error code */
					esc_html__( '%1$s (code: %2$d)', 'cf-images' ),
					esc_html( $this->error->get_error_message() ),
					(int) $this->error->get_error_code()
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Make sure all image sizes, registered in WordPress, are mapped to appropriate image variants.
	 *
	 * @since 1.0.0
	 */
	public function register_image_sizes() {

		$variants = get_option( 'cf-images-variants', array() );
		$sizes    = wp_get_registered_image_subsizes();

		// TODO: add scaled images.

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

			$name = "{$width}x$height";

			// Register size.
			try {
				$variant->create( $name, $width, $height, $fit );
			} catch ( Exception $e ) {
				$this->error = new WP_Error( $e->getCode(), $e->getMessage() );
				break;
			}

			unset( $size['crop'] );

			$updated         = true;
			$size['variant'] = $name;
			$variants[ $id ] = $size;
		}

		if ( $updated ) {
			update_option( 'cf-images-variants', $variants, false );
		}

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
			$this->update_image_meta( $attachment_id, $path );
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
	 * @param int      $post_id  Attachment ID.
	 * @param \WP_Post $post     Post object.
	 */
	public function delete_image( int $post_id, \WP_Post $post ) {

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
	 * @param array|false  $image          {
	 *     Array of image data, or boolean false if no image is available.
	 *
	 *     @type string $0 Image source URL.
	 *     @type int    $1 Image width in pixels.
	 *     @type int    $2 Image height in pixels.
	 *     @type bool   $3 Whether the image is a resized image.
	 * }
	 * @param int          $attachment_id  Image attachment ID.
	 * @param string|int[] $size           Requested image size. Can be any registered image size name, or
	 *                                     an array of width and height values in pixels (in that order).
	 */
	public function get_attachment_image_src( $image, int $attachment_id, $size ): array {

		$meta = get_post_meta( $attachment_id, '_cloudflare_image_id', true );

		if ( empty( $meta ) ) {
			return $image;
		}

		$hash = get_option( 'cf-images-hash', '' );

		if ( empty( $hash ) ) {
			return $image;
		}

		$variants = get_option( 'cf-images-variants', array() );

		if ( is_string( $size ) && array_key_exists( $size, $variants ) ) {
			$image[0] = "https://imagedelivery.net/$hash/$meta/" . $variants[ $size ]['variant'];
			return $image;
		}

		$variant_ids = wp_list_pluck( $variants, 'variant' );

		preg_match( '/[^\/]*$/', $image[0], $variant_image );

		if ( isset( $variant_image[0] ) && in_array( $variant_image[0], $variant_ids, true ) ) {
			$image[0] = "https://imagedelivery.net/$hash/$meta/" . $variant_image[0];
		}

		return $image;

	}

	/**
	 * Filters the attachment data prepared for JavaScript.
	 *
	 * @since 1.0.0
	 *
	 * @param array       $response    Array of prepared attachment data. @see wp_prepare_attachment_for_js().
	 * @param \WP_Post    $attachment  Attachment object.
	 * @param array|false $meta        Array of attachment metadata, or false if there is none.
	 */
	public function prepare_attachment_for_js( array $response, \WP_Post $attachment, $meta ): array {

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
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name(): string {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version(): string {
		return $this->version;
	}

}
