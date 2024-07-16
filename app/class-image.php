<?php
/**
 * The file that defines the image object
 *
 * When parsing a page, we will do a lot of image manipulations, and it's easier to dedicate an object for each image,
 * rather than try to maintain the data with vars.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.5.0
 */

namespace CF_Images\App;

use CF_Images\App\Modules\Cloudflare_Images;
use CF_Images\App\Traits\Helpers;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Image class.
 *
 * @since 1.5.0
 */
class Image {
	use Helpers;

	/**
	 * Attachment ID in WordPress.
	 *
	 * @since 1.5.0
	 *
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Original image object from DOM.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	protected $image = '';

	/**
	 * Image src attribute value.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	protected $src = '';

	/**
	 * Image srcset attribute value.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	protected $srcset = '';

	/**
	 * Processed image object.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	protected $processed = '';

	/**
	 * Cloudflare image URL.
	 *
	 * Without at least the "w" attribute, this value on its own will return a "Malformed URL" error.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	protected $cf_image_url = '';

	/**
	 * Cloudflare image ID.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	protected $cf_image_id = '';

	/**
	 * Original image width.
	 *
	 * @since 1.5.0
	 *
	 * @var int
	 */
	protected $width = 9999;

	/**
	 * CDN status (based on API).
	 *
	 * @since 1.7.0
	 *
	 * @var bool|string $active CDN hostname if active, false otherwise.
	 */
	protected $cdn_active = false;

	/**
	 * If the image needs the default WordPress wp-image-<id> class.
	 *
	 * @since 1.8.0
	 *
	 * @var bool
	 */
	private $needs_image_class = false;

	/**
	 * Constructor.
	 *
	 * @since 1.5.0
	 *
	 * @param string $image  Image object from DOM.
	 * @param string $src    Image src attribute value.
	 * @param string $srcset Image srcset attribute value.
	 */
	public function __construct( string $image, string $src, string $srcset ) {
		$this->image  = $image;
		$this->src    = $src;
		$this->srcset = $srcset;

		$this->cdn_active = get_option( 'cf-images-cdn-enabled', false );

		$this->get_attachment_id();
		$this->check_if_cf_image();
		$this->process_image();
	}

	/**
	 * Try to get image ID from class attribute.
	 *
	 * @since 1.5.0
	 */
	private function get_attachment_id() {
		if ( $this->cdn_active ) {
			return;
		}

		if ( preg_match( '/wp-image-(\d+)/i', $this->image, $class_id ) ) {
			$this->id = absint( $class_id[1] );
			do_action( 'cf_images_log', 'Found attachment ID %s from image class name.', $this->id );
		} else {
			$this->needs_image_class = true;
		}
	}

	/**
	 * This is a compat method to check if this is already a Cloudflare image and parse out all the required data.
	 *
	 * @since 1.5.0
	 */
	private function check_if_cf_image() {
		if ( $this->cdn_active ) {
			return;
		}

		$domain = $this->get_cdn_domain();
		if ( false === strpos( $this->get_src(), $domain ) ) {
			return;
		}

		$domain = str_replace( '.', '\.', $domain );

		if ( preg_match( '#(' . $domain . '/.*?/)w=(\d+)#', $this->get_src(), $matches ) ) {
			$this->cf_image_url = $matches[1] ?? '';
			$this->processed    = $this->image;
			$this->width        = (int) $matches[2] ?? 9999;
		}
	}

	/**
	 * Process image and generate new src and srcset values.
	 *
	 * @since 1.5.0
	 */
	private function process_image() {
		if ( ! empty( $this->get_src() ) && ! $this->is_source_tag() ) {
			$this->process( $this->get_src(), true );
		}

		if ( ! empty( $this->get_srcset() ) ) {
			$this->process( $this->get_srcset() );
		}
	}

	/**
	 * Process image element.
	 *
	 * @since 1.4.0
	 * @since 1.5.0 Moved into the Image class.
	 *
	 * @param string $content Which attribute to process.
	 * @param bool   $is_src  Is this the src attribute.
	 */
	private function process( string $content, bool $is_src = false ) {
		preg_match_all( '/https?[^\s\'"]*/i', $content, $urls );
		if ( ! is_array( $urls ) || empty( $urls[0] ) ) {
			return;
		}

		foreach ( $urls[0] as $link ) {
			if ( $this->cdn_active ) {
				/**
				 * Parsing each image individually is not required, however, if there's ever a request to add
				 * the CDN on top of Cloudflare Images, which might be a decent idea, this prevents a refactor.
				 */
				$src = $this->replace_cdn_url( $link );
			} else {
				$src = $this->generate_url( $link, $is_src );
			}

			if ( $src ) {
				$image = str_replace( $link, $src, empty( $this->processed ) ? $this->image : $this->processed );

				// Some themes remove the default wp-image-* class, add it if missing.
				if ( $this->needs_image_class && $this->id ) {
					$class = $this->get_attribute( $image, 'class' );
					if ( empty( $class ) ) {
						$this->add_attribute( $image, 'class', "wp-image-$this->id" );
					} else {
						$this->add_attribute( $image, 'class', $class . " wp-image-$this->id" );
					}
				}
			}

			if ( isset( $image ) ) {
				$this->processed = $image;
			}

			unset( $image );
		}
	}

	/**
	 * Add attribute to selected tag.
	 *
	 * @since 1.8.0
	 *
	 * @param string $element Image element.
	 * @param string $name    Img attribute name (srcset, size, etc).
	 * @param string $value   Attribute value.
	 */
	private function add_attribute( string &$element, string $name, string $value = null ) {
		$closing = false === strpos( $element, '/>' ) ? '>' : ' />';
		$quotes  = false === strpos( $element, '"' ) ? '\'' : '"';

		if ( ! is_null( $value ) ) {
			$element = rtrim( $element, $closing ) . " $name=$quotes$value$quotes$closing";
		} else {
			$element = rtrim( $element, $closing ) . " $name$closing";
		}
	}

	/**
	 * Get attribute from an HTML element.
	 *
	 * @since 1.8.0
	 *
	 * @param string $element HTML element.
	 * @param string $name    Attribute name.
	 *
	 * @return string
	 */
	private function get_attribute( string $element, string $name ): string {
		preg_match( "/$name=['\"]([^'\"]+)['\"]/is", $element, $value );
		return $value['1'] ?? '';
	}

	/**
	 * Add the CDN domain to image URLs.
	 *
	 * @since 1.7.0
	 *
	 * @param string $image_url Image URL.
	 *
	 * @return string
	 */
	private function replace_cdn_url( string $image_url ): string {
		$domain = wp_parse_url( get_site_url(), PHP_URL_HOST );
		return str_replace( $domain, $this->cdn_active, $image_url );
	}

	/**
	 * Generate Cloudflare Image URL.
	 *
	 * @since 1.4.0
	 * @since 1.5.0 Moved into Image class.
	 *
	 * @param string $image_url Image URL.
	 * @param bool   $is_src    Is this the src attribute.
	 *
	 * @return string|bool Cloudflare Image URL or false otherwise.
	 */
	private function generate_url( string $image_url, bool $is_src = false ) {
		/**
		 * Check if an image is already on Cloudflare.
		 *
		 * Ideally, we could have tried to use the get_cdn_domain() helper, however, if custom domains are set
		 * to the site URL, this will cause all images to be flagged. Instead, we check that the image is either
		 * served from imagedelivery.net or has cdn-cgi/imagedelivery part in the URL.
		 */
		if ( false !== strpos( $image_url, $this->get_cdn_domain() ) ) {
			return false;
		}

		if ( preg_match( '/-(\d+)x(\d+)\.(jpg|jpeg|png|gif)$/i', $image_url, $size ) ) {
			$original = preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $image_url );
		} elseif ( false !== strpos( $image_url, '-scaled.' ) ) {
			$original = str_replace( '-scaled.', '.', $image_url );

			$scaled_size = apply_filters( 'big_image_size_threshold', 2560 );
			$scaled_size = false === $scaled_size ? 2560 : $scaled_size;

			$size[1] = $scaled_size;
		} else {
			$original = $image_url;
		}

		$width = $this->calculate_size( 'width', $size );
		$crop  = $this->get_crop_string( $width, $size );

		/**
		 * Keep a reference to the original width, to be used when building srcset values in the auto resize module.
		 */
		if ( $is_src ) {
			$this->width = $width;
		}

		// We already have the image URL, just add the width parameter.
		if ( ! empty( $this->cf_image_url ) ) {
			return "{$this->cf_image_url}w=$width$crop";
		}

		if ( ! $original ) {
			return false;
		}

		if ( 0 === $this->id ) {
			// Could not get image ID from class name, try to get it from URL.
			$this->id = $this->attachment_url_to_post_id( $original );
		}

		if ( 0 === $this->id ) {
			return false;
		}

		// This is used with WPML integration.
		$attachment_id = apply_filters( 'cf_images_media_post_id', $this->id );

		list( $hash, $this->cf_image_id ) = Cloudflare_Images::get_hash_id_url_string( $attachment_id );

		if ( empty( $this->cf_image_id ) || ( empty( $hash ) && ! apply_filters( 'cf_images_module_enabled', false, 'custom-path' ) ) ) {
			return false;
		}

		$this->cf_image_url = trailingslashit( $this->get_cdn_domain() . "/$hash" ) . "$this->cf_image_id/";
		return "{$this->cf_image_url}w=$width$crop";
	}

	/**
	 * Get the smallest width or height of the image from the image file name vs the img width/height attribute.
	 *
	 * @since 1.8.0
	 *
	 * @param string $type Width or height. Accepts: width, height.
	 * @param array  $size Size array.
	 *
	 * @return int
	 */
	private function calculate_size( string $type, array $size ): int {
		$index      = 'width' === $type ? 1 : 2;
		$size_value = $size[ $index ] ?? 9999;

		if ( ! apply_filters( 'cf_images_module_enabled', false, 'smallest-size' ) ) {
			return $size_value;
		}

		$img_attribute = $this->get_attribute( $this->image, $type );
		if ( ! empty( $img_attribute ) ) {
			$size_value = min( (int) $img_attribute, (int) $size_value );
		}

		return $size_value;
	}

	/**
	 * Get crop string.
	 *
	 * @since 1.8.0
	 *
	 * @param int   $width Image width.
	 * @param array $size  Size array.
	 *
	 * @return string
	 */
	private function get_crop_string( int $width, array $size ): string {
		$crop_string = '';

		if ( ! apply_filters( 'cf_images_module_enabled', false, 'auto-crop' ) ) {
			return $crop_string;
		}

		$height = $this->calculate_size( 'height', $size );

		if ( 9999 !== $height && $width === $height ) {
			$crop_string = ",h=$height,fit=crop";
		}

		return $crop_string;
	}

	/**
	 * Tries to convert an attachment URL into a post ID.
	 *
	 * @since 1.4.0
	 * @since 1.5.0 Moved to Image class.
	 *
	 * @param string $url The URL to resolve.
	 *
	 * @return int The found post ID, or 0 on failure.
	 */
	private function attachment_url_to_post_id( string $url ): int {
		$post_id = wp_cache_get( $url, 'cf_images' );

		if ( false === $post_id ) {
			global $wpdb;

			$sql = $wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE guid = %s",
				$url
			);

			$results = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB
			$post_id = 0;

			if ( $results ) {
				$post_id = reset( $results )->ID;
			} else {
				// This is a fallback, in case the above doesn't work for some reason.
				$results = attachment_url_to_postid( $url );

				if ( $results ) {
					$post_id = $results;
				}
			}

			// Store this regardless if we have the post ID, prevents duplicate queries.
			wp_cache_add( $url, $post_id, 'cf_images' );
		}

		return $post_id;
	}

	/**
	 * Getter for src attribute value.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	public function get_src(): string {
		return $this->src;
	}

	/**
	 * Getter for srcset attribute value.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	public function get_srcset(): string {
		return $this->srcset;
	}

	/**
	 * Is this a <source> tag?
	 *
	 * @since 1.5.0
	 *
	 * @return bool
	 */
	public function is_source_tag(): bool {
		return 'source' === substr( $this->image, 1, 6 );
	}

	/**
	 * Getter for processed image.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	public function get_processed(): string {
		if ( ! empty( $this->processed ) ) {
			return apply_filters( 'cf_images_replace_paths', $this->processed, $this );
		}

		return $this->image;
	}

	/**
	 * Getter method for Cloudflare image URL.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	public function get_cf_image(): string {
		return $this->cf_image_url;
	}

	/**
	 * Getter method for image width.
	 *
	 * @since 1.5.0
	 *
	 * @return int
	 */
	public function get_width(): int {
		return $this->width;
	}

	/**
	 * Getter method for attachment ID.
	 *
	 * @since 1.5.0
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}
}
