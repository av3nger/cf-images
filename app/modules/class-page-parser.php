<?php
/**
 * Page parser
 *
 * Instead of replacing the images via hooks, replace images by parsing the page on the front-end.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.4.0
 */

namespace CF_Images\App\Modules;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Page_Parser class.
 *
 * @since 1.4.0
 */
class Page_Parser extends Module {

	/**
	 * Should the module only run on front-end?
	 *
	 * @since 1.4.0
	 * @access protected
	 *
	 * @var bool
	 */
	protected $only_frontend = true;

	/**
	 * Register UI components.
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	protected function register_ui() {
		$this->icon  = 'format-gallery';
		$this->title = esc_html__( 'Parse page for images', 'cf-images' );
	}

	/**
	 * Render module description.
	 *
	 * @since 1.4.0
	 *
	 * @param string $module  Module ID.
	 *
	 * @return void
	 */
	public function render_description( string $module ) {

		if ( $module !== $this->module ) {
			return;
		}
		?>
		<p>
			<?php esc_html_e( 'Compatibility module to support themes that do not use WordPress hooks and filters. If the images are not replaced on the site, try enabling this module', 'cf-images' ); ?>
		</p>
		<?php

	}

	/**
	 * Init the module.
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'template_redirect', array( $this, 'output_buffering' ), 1 );
	}

	/**
	 * Turn on output buffering.
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	public function output_buffering() {
		ob_start( array( $this, 'replace_images' ) );
	}

	/**
	 * Output buffer callback.
	 *
	 * @since 1.4.0
	 *
	 * @param string $buffer  Contents of the output buffer.
	 *
	 * @return string
	 */
	public function replace_images( string $buffer ): string {

		$images = $this->get_images( $buffer );

		foreach ( $images[0] as $key => $image ) {
			$modified = $this->replace_paths( $image, $images[1][ $key ], $images[2][ $key ] );
			$buffer   = str_replace( $image, $modified, $buffer );
		}

		return $buffer;

	}

	/**
	 * Get images from source code.
	 *
	 * Optimize this regex better than I did - I'll pay for your coffee.
	 *
	 * @since 1.4.0
	 *
	 * @param string $buffer  Output buffer.
	 *
	 * @return array
	 */
	private function get_images( string $buffer ): array {

		if ( preg_match( '/(?=<body).*<\/body>/is', $buffer, $body ) ) {
			$pattern = '/<(?:img|source)\b(?>\s+(?:src=[\'"]([^\'"]*)[\'"]|srcset=[\'"]([^\'"]*)[\'"])|[^\s>]+|\s+)*>/i';
			if ( preg_match_all( $pattern, $body[0], $images ) ) {
				return $images;
			}
		}

		return array();

	}

	/**
	 * Replace image paths with CDN values.
	 *
	 * @since 1.4.0
	 *
	 * @param string $image   Original image element.
	 * @param string $src     Image src attribute value.
	 * @param string $srcset  Image srcset attribute value.
	 *
	 * @return string
	 */
	private function replace_paths( string $image, string $src, string $srcset ): string {

		// Try to get image ID from class attribute.
		$attachment_id = 0;
		if ( preg_match( '/wp-image-([0-9]+)/i', $image, $class_id ) ) {
			$attachment_id = absint( $class_id[1] );
		}

		if ( ! empty( $src ) && 'source' !== substr( $image, 1, 6 ) ) {
			$image = $this->process( $image, $src, $attachment_id );
		}

		if ( ! empty( $srcset ) ) {
			$image = $this->process( $image, $srcset, $attachment_id );
		}

		return $image;

	}

	/**
	 * Process image element.
	 *
	 * @since 1.4.0
	 *
	 * @param string $image          Image element.
	 * @param string $content        Content (value from src or srcset attribute).
	 * @param int    $attachment_id  Attachment ID.
	 *
	 * @return string
	 */
	private function process( string $image, string $content, int $attachment_id = 0 ): string {

		preg_match_all( '/https?[^\s\'"]*/i', $content, $urls );
		if ( ! is_array( $urls ) || empty( $urls[0] ) ) {
			return $image;
		}

		foreach ( $urls[0] as $link ) {
			$src = $this->generate_url( $link, $attachment_id );
			if ( $src ) {
				$image = str_replace( $link, $src, $image );
			}
		}

		return $image;

	}

	/**
	 * Generate Cloudflare Image URL.
	 *
	 * @since 1.4.0
	 *
	 * @param string $image          Image URL.
	 * @param int    $attachment_id  Attachment ID.
	 *
	 * @return string|bool  Cloudflare Image URL or false otherwise.
	 */
	private function generate_url( string $image, int $attachment_id = 0 ) {

		/**
		 * Check if an image is already on Cloudflare.
		 *
		 * Ideally, we could have tried to use the get_cdn_domain() helper, however, if custom domains are set
		 * to the site URL, this will cause all images to be flagged. Instead, we check that the image is either
		 * served from imagedelivery.net or has cdn-cgi/imagedelivery part in the URL.
		 */
		if ( false !== strpos( $image, 'imagedelivery.net' ) || false !== strpos( $image, 'cdn-cgi/imagedelivery' ) ) {
			return false;
		}

		if ( preg_match( '/-(\d+)x(\d+)\.(jpg|jpeg|png|gif)$/i', $image, $size ) ) {
			$original = preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $image );
		} elseif ( false !== strpos( $image, '-scaled.' ) ) {
			$original = str_replace( '-scaled.', '.', $image );
			$size[1]  = apply_filters( 'big_image_size_threshold', 2560 );
		} else {
			$original = $image;
		}

		$width = $size[1] ?? 9999;

		if ( ! $original ) {
			return false;
		}

		if ( 0 === $attachment_id ) {
			// Could not get image ID from class name, try to get it from URL.
			$attachment_id = $this->attachment_url_to_postid( $original );
		}

		if ( 0 === $attachment_id ) {
			return false;
		}

		list( $hash, $cloudflare_image_id ) = Cloudflare_Images::get_hash_id_url_string( $attachment_id );

		if ( empty( $cloudflare_image_id ) || empty( $hash ) ) {
			return false;
		}

		return $this->get_cdn_domain() . "/$hash/$cloudflare_image_id/w=$width";

	}


	/**
	 * Tries to convert an attachment URL into a post ID.
	 *
	 * @since 1.4.0
	 *
	 * @param string $url  The URL to resolve.
	 *
	 * @return int The found post ID, or 0 on failure.
	 */
	private function attachment_url_to_postid( string $url ): int {

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


}
