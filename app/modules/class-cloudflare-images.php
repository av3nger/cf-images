<?php
/**
 * Cloudflare Images module
 *
 * This class defines all code necessary for offloading media to Cloudflare Images service.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.3.0  Moved out into its own module.
 */

namespace CF_Images\App\Modules;

use CF_Images\App\Image;
use WP_Post;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Cloudflare_Images class.
 *
 * @since 1.3.0
 */
class Cloudflare_Images extends Module {
	/**
	 * This is a core module, meaning it can't be enabled/disabled via options.
	 *
	 * @since 1.3.0
	 *
	 * @var bool
	 */
	protected $core = true;

	/**
	 * Should the module only run on front-end?
	 *
	 * @since 1.3.0
	 * @access protected
	 *
	 * @var bool
	 */
	protected $only_frontend = true;

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
	 * Pre-init actions.
	 *
	 * @since 1.2.1
	 */
	protected function pre_init() {
		if ( $this->is_module_enabled( false, 'full-offload' ) ) {
			$this->only_frontend = false;
		}
	}

	/**
	 * Init the module.
	 *
	 * @since 1.3.0
	 */
	public function init() {
		add_action( 'init', array( $this, 'populate_image_sizes' ) );

		if ( ! $this->can_offload() ) {
			return;
		}

		// Replace images only on front-end.
		add_filter( 'wp_get_attachment_image_src', array( $this, 'get_attachment_image_src' ), 10, 3 );
		add_filter( 'wp_prepare_attachment_for_js', array( $this, 'prepare_attachment_for_js' ), 99, 2 );
		add_filter( 'wp_calculate_image_srcset', array( $this, 'calculate_image_srcset' ), 10, 5 );

		// Support for various Gutenberg blocks.
		add_filter( 'wp_get_attachment_url', array( $this, 'get_attachment_url' ), 10, 2 );

		// This filter is available on WordPress 6.0 or above.
		add_filter( 'wp_content_img_tag', array( $this, 'content_img_tag' ), 10, 3 );

		// Preconnect to CDN.
		add_filter( 'wp_resource_hints', array( $this, 'preconnect' ), 10, 2 );
	}

	/**
	 * Save all required data for faster access later on.
	 *
	 * @since 1.0.0
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
	 * @param array|false      $image         {
	 *     Array of image data, or boolean false if no image is available.
	 *
	 *     @type string $image[0] Image source URL.
	 *     @type int    $image[1] Image width in pixels.
	 *     @type int    $image[2] Image height in pixels.
	 *     @type bool   $image[3] Whether the image is a resized image.
	 * }
	 * @param int|string       $attachment_id Image attachment ID.
	 * @param string|int|int[] $size          Requested image size. Can be any registered image size name, or
	 *                                        an array of width and height values in pixels (in that order),
	 *                                        can also be just a single integer value.
	 *
	 * @return array|false
	 */
	public function get_attachment_image_src( $image, $attachment_id, $size ) {
		if ( ! $this->can_run( (int) $attachment_id ) || ! $image ) {
			do_action( 'cf_images_log', 'Cannot run get_attachment_image_src(), returning original. Attachment ID: %s. Image: %s', $attachment_id, $image );
			return $image;
		}

		// This is used with WPML integration.
		$attachment_id = apply_filters( 'cf_images_media_post_id', $attachment_id );

		list( $hash, $cloudflare_image_id ) = self::get_hash_id_url_string( (int) $attachment_id );

		if ( empty( $cloudflare_image_id ) || ( empty( $hash ) && ! $this->is_module_enabled( false, 'custom-path' ) ) ) {
			do_action( 'cf_images_log', 'Missing Cloudflare Image ID or hash. Attachment ID: %s. Image: %s', $attachment_id, $image );
			return $image;
		}

		do_action( 'cf_images_get_attachment_image_src', $cloudflare_image_id, $attachment_id );

		$cf_image = trailingslashit( $this->get_cdn_domain() . "/$hash" ) . $cloudflare_image_id;

		// If this is a known crop image.
		if ( is_string( $size ) && isset( $this->registered_sizes[ $size ]['crop'] ) && true === $this->registered_sizes[ $size ]['crop'] && ! apply_filters( 'cf_images_disable_crop', false ) ) {
			$image[0] = $cf_image . '/w=' . $this->registered_sizes[ $size ]['width'] . ',h=' . $this->registered_sizes[ $size ]['height'] . ',fit=crop';
			return $image;
		}

		// Image with defined dimensions.
		if ( isset( $image[1] ) && $image[1] > 0 ) {
			$height_str = '';
			if ( isset( $image[2] ) && $image[2] > 0 ) {
				$height_str = ',h=' . $image[2] . ( $image[1] === $image[2] ? ',fit=crop' : '' );
			}

			$image[0] = $cf_image . '/w=' . $image[1] . $height_str;
			return $image;
		}

		preg_match( '/-(\d+)x(\d+)\.[a-zA-Z]{3,4}$/', $image[0], $variant_image );

		// Image with `-<width>x<height>` prefix, for example, image-300x125.jpg.
		if ( isset( $variant_image[1] ) && isset( $variant_image[2] ) && is_array( $this->heights ) && is_array( $this->widths ) ) {
			// Check if the image is a cropped version.
			$height_key = array_search( (int) $variant_image[1], $this->heights, true );
			$width_key  = array_search( (int) $variant_image[2], $this->widths, true );

			if ( $width_key && $height_key && $width_key === $height_key && true === $this->registered_sizes[ $width_key ]['crop'] ) {
				$image[0] = $cf_image . '/w=' . $variant_image[1] . ',h=' . $variant_image[2] . ',fit=crop';
				return $image;
			}

			// Not a cropped image.
			$image[0] = $cf_image . '/w=' . $variant_image[1] . ',h=' . $variant_image[2];
			return $image;
		}

		// Maybe it's not a scaled, but we have the size?
		if ( is_int( $size ) ) {
			$image[0] = $cf_image . '/w=' . $size;
			return $image;
		}

		// Handle `scaled` images.
		if ( false !== strpos( $image[0], '-scaled' ) ) {
			$scaled_size = apply_filters( 'big_image_size_threshold', 2560 );
			$scaled_size = false === $scaled_size ? 2560 : $scaled_size;

			/**
			 * This covers two cases:
			 * 1: scaled sizes are disabled, but we have the size passed to the function
			 * 2: scaled size equals the requested size
			 * In both cases - use the size value.
			 */
			if ( ( ! $scaled_size && is_int( $size ) ) || $scaled_size === $size ) {
				$image[0] = $cf_image . '/w=' . $size;
			} else { // Fallback to scaled size.
				$image[0] = $cf_image . '/w=' . $scaled_size;
			}

			return $image;
		}

		// Image without size prefix and no defined sizes - use the maximum available width.
		if ( ! $variant_image && ! isset( $image[1] ) ) {
			$image[0] = $cf_image . '/w=9999';
			return $image;
		}

		return $image;
	}

	/**
	 * Get Cloudflare hash and Cloudflare Image ID.
	 *
	 * @since 1.4.0
	 *
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return array
	 */
	public static function get_hash_id_url_string( int $attachment_id ): array {
		$cloudflare_image_id = get_post_meta( $attachment_id, '_cloudflare_image_id', true );

		/**
		 * Filters the Cloudflare image ID value.
		 *
		 * @since 1.1.5
		 *
		 * @param mixed $cloudflare_image_id Image meta
		 * @param int   $attachment_id       Attachment ID.
		 */
		$cloudflare_image_id = apply_filters( 'cf_images_attachment_meta', $cloudflare_image_id, $attachment_id );

		$hash = apply_filters( 'cf_images_hash', get_site_option( 'cf-images-hash', '' ) );

		return array( $hash, $cloudflare_image_id );
	}

	/**
	 * Filters the attachment data prepared for JavaScript.
	 *
	 * @since 1.0.0
	 *
	 * @param array   $response   Array of prepared attachment data. @see wp_prepare_attachment_for_js().
	 * @param WP_Post $attachment Attachment object.
	 *
	 * @return array
	 */
	public function prepare_attachment_for_js( array $response, WP_Post $attachment ): array {
		if ( empty( $response['sizes'] ) ) {
			return $response;
		}

		foreach ( $response['sizes'] as $id => $size ) {
			if ( ! isset( $size['url'] ) ) {
				continue;
			}

			$image_src = $this->get_attachment_image_src( array( $size['url'] ), $attachment->ID, $id );

			$response['sizes'][ $id ]['url'] = $image_src[0];
		}

		if ( ! empty( $response['url'] ) ) {
			$image_src       = $this->get_attachment_image_src( array( $response['url'] ), $attachment->ID, null );
			$response['url'] = $image_src[0];
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
	 * @param string      $filtered_image Full img tag with attributes that will replace the source img tag.
	 * @param string|bool $context        Additional context, like the current filter name or the function name from where this was called.
	 * @param int         $attachment_id  The image attachment ID. May be 0 in case the image is not an attachment.
	 *
	 * @return string
	 */
	public function content_img_tag( string $filtered_image, $context, int $attachment_id ): string {
		if ( is_feed() && ! apply_filters( 'cf_images_module_enabled', false, 'rss-feeds' ) ) {
			return $filtered_image;
		}

		$pattern = '/<(?:img|source)\b(?>\s+(?:src=[\'"]([^\'"]*)[\'"]|srcset=[\'"]([^\'"]*)[\'"])|[^\s>]+|\s+)*>/i';
		if ( ! preg_match_all( $pattern, $filtered_image, $images ) ) {
			do_action( 'cf_images_log', 'Running content_img_tag(), `src` not found, returning image. Attachment ID: %s. Image: %s', $attachment_id, $filtered_image );
			return $filtered_image;
		}

		$image = new Image( $filtered_image, $images[1][0], $images[2][0] );
		return $image->get_processed();
	}

	/**
	 * Preconnect to CDN URL.
	 *
	 * @param array  $hints         List of URLs.
	 * @param string $relation_type Relation type.
	 *
	 * @return array
	 */
	public function preconnect( array $hints, string $relation_type ): array {
		if ( 'preconnect' !== $relation_type ) {
			return $hints;
		}

		return array_merge(
			$hints,
			array(
				array(
					'href' => $this->get_cdn_domain(),
				),
			)
		);
	}

	/**
	 * Filters the attachment URL.
	 *
	 * @since 1.9.0
	 *
	 * @param string $url           URL for the given attachment.
	 * @param int    $attachment_id Attachment post ID.
	 *
	 * @return string
	 */
	public function get_attachment_url( string $url, int $attachment_id ): string {
		if ( is_admin() ) {
			return $url;
		}

		$image_src = $this->get_attachment_image_src( array( $url ), $attachment_id, null );

		return $image_src[0];
	}
}
