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
		if ( preg_match( '/wp-image-(\d+)/i', $this->image, $class_id ) ) {
			$this->id = absint( $class_id[1] );
			do_action( 'cf_images_log', 'Found attachment ID %s from image class name.', $this->id );
		}
	}

	/**
	 * This is a compat method to check if this is already a Cloudflare image and parse out all the required data.
	 *
	 * @since 1.5.0
	 */
	private function check_if_cf_image() {
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
			$src = $this->generate_url( $link, $is_src );
			if ( $src ) {
				$image = str_replace( $link, $src, $this->image );
			}
		}

		if ( isset( $image ) ) {
			$this->processed = $image;
		}
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
			$size[1]  = apply_filters( 'big_image_size_threshold', 2560 );
		} else {
			$original = $image_url;
		}

		$width = $size[1] ?? 9999;

		/**
		 * Keep a reference to the original width, to be used when building srcset values in the auto resize module.
		 */
		if ( $is_src ) {
			$this->width = (int) $width;
		}

		// We already have the image URL, just add the width parameter.
		if ( ! empty( $this->cf_image_url ) ) {
			return "{$this->cf_image_url}w=$width";
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

		list( $hash, $this->cf_image_id ) = Cloudflare_Images::get_hash_id_url_string( $this->id );

		if ( empty( $this->cf_image_id ) || ( empty( $hash ) && ! apply_filters( 'cf_images_module_enabled', false, 'custom-path' ) ) ) {
			return false;
		}

		$this->cf_image_url = trailingslashit( $this->get_cdn_domain() . "/$hash" ) . "$this->cf_image_id/";
		return "{$this->cf_image_url}w=$width";
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

		if ( ! $post_id ) {
			global $wpdb;

			$filename = pathinfo( $url, PATHINFO_FILENAME );

			$sql = $wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE post_name = %s",
				$filename
			);

			$results = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB
			$post_id = 0;

			if ( $results ) {
				$post_id = reset( $results )->ID;
				wp_cache_add( $url, $post_id, 'cf_images' );
			}
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
