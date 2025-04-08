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
	 * @var array $registered_sizes
	 */
	public static $registered_sizes;

	/**
	 * Widths from the $registered_sizes array.
	 *
	 * @since 1.0.0
	 * @var array $widths
	 */
	public static $widths;

	/**
	 * Heights from the $registered_sizes array.
	 *
	 * @since 1.0.0
	 * @var array $heights
	 */
	public static $heights;

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
		self::$registered_sizes = wp_get_registered_image_subsizes();

		self::$heights = wp_list_pluck( self::$registered_sizes, 'height' );
		self::$widths  = wp_list_pluck( self::$registered_sizes, 'width' );
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

		// Check if this is an R2 offloaded image.
		if ( $this->is_r2_offloaded( (int) $attachment_id ) ) {
			$image[0] = $this->get_r2_url( $image[0], (int) $attachment_id );
			return $image;
		}

		// Process with Cloudflare Images.
		$image[0] = ( new Image( $image[0], $image[0] ) )
			->set_id( $attachment_id )
			->set_dimensions( $image, $size )
			->get_processed();

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
	 * Check if an attachment is offloaded to R2.
	 *
	 * @since 1.9.5
	 *
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return bool
	 */
	public function is_r2_offloaded( int $attachment_id ): bool {
		$r2_offloaded = get_post_meta( $attachment_id, '_cloudflare_image_r2_offloaded', true );
		return ! empty( $r2_offloaded );
	}

	/**
	 * Get R2 URL for an attachment.
	 *
	 * @since 1.9.5
	 *
	 * @param string $url           Original URL.
	 * @param int    $attachment_id Attachment ID.
	 *
	 * @return string R2 URL or original URL if not offloaded.
	 */
	public function get_r2_url( string $url, int $attachment_id ): string {
		if ( ! $this->is_r2_offloaded( $attachment_id ) ) {
			return $url;
		}

		// Get R2 public URL.
		if ( defined( 'CF_IMAGES_R2_PUBLIC_URL' ) ) {
			$r2_public_url = constant( 'CF_IMAGES_R2_PUBLIC_URL' );
		} else {
			$r2_public_url = get_site_option( 'cf-images-r2-public-url', '' );
		}

		if ( empty( $r2_public_url ) ) {
			return $url;
		}

		// Ensure the URL ends with a trailing slash.
		if ( substr( $r2_public_url, -1 ) !== '/' ) {
			$r2_public_url .= '/';
		}

		// Replace the upload URL with the R2 public URL.
		$uploads  = wp_get_upload_dir();
		$base_url = $uploads['baseurl'];

		return str_replace( $base_url, rtrim( $r2_public_url, '/' ), $url );
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
		// Check if this is an R2 offloaded image.
		if ( $this->is_r2_offloaded( $attachment_id ) ) {
			foreach ( $sources as $id => $size ) {
				if ( ! isset( $size['url'] ) ) {
					continue;
				}

				$sources[ $id ]['url'] = $this->get_r2_url( $size['url'], $attachment_id );
			}

			return $sources;
		}

		// Process with Cloudflare Images.
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

		// Check if this is an R2 offloaded image.
		if ( $attachment_id > 0 && $this->is_r2_offloaded( $attachment_id ) ) {
			$pattern = '/<(?:img|source)\b(?:\s+(?:src=[\'"]([^\'"]*)[\'"]|srcset=[\'"]([^\'"]*)[\'"])|[^\s>]+|\s+)*>/i';
			if ( preg_match_all( $pattern, $filtered_image, $images ) ) {
				// Replace src attribute.
				if ( ! empty( $images[1][0] ) ) {
					$r2_url         = $this->get_r2_url( $images[1][0], $attachment_id );
					$filtered_image = str_replace( $images[1][0], $r2_url, $filtered_image );
				}

				// Replace srcset attribute if present.
				if ( ! empty( $images[2][0] ) ) {
					$srcset_values     = explode( ',', $images[2][0] );
					$new_srcset_values = array();

					foreach ( $srcset_values as $srcset_value ) {
						$parts = preg_split( '/\s+/', trim( $srcset_value ) );
						if ( count( $parts ) >= 1 ) {
							$url                 = $parts[0];
							$descriptor          = isset( $parts[1] ) ? ' ' . $parts[1] : '';
							$r2_url              = $this->get_r2_url( $url, $attachment_id );
							$new_srcset_values[] = $r2_url . $descriptor;
						}
					}

					$new_srcset     = implode( ', ', $new_srcset_values );
					$filtered_image = str_replace( $images[2][0], $new_srcset, $filtered_image );
				}

				return $filtered_image;
			}
		}

		$pattern = '/<(?:img|source)\b(?:\s+(?:src=[\'"]([^\'"]*)[\'"]|srcset=[\'"]([^\'"]*)[\'"])|[^\s>]+|\s+)*>/i';
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

		// Check if this is an R2 offloaded image.
		if ( $this->is_r2_offloaded( $attachment_id ) ) {
			return $this->get_r2_url( $url, $attachment_id );
		}

		$image_src = $this->get_attachment_image_src( array( $url ), $attachment_id, null );

		return $image_src[0];
	}
}
