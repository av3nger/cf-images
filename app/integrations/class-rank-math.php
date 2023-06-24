<?php
/**
 * Rank Math integration class
 *
 * This class adds compatibility with the Rank Math plugin.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Integrations
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.1.5
 */

namespace CF_Images\App\Integrations;

use Exception;
use MyThemeShop\Helpers\Str;
use WP_Query;
use function pathinfo;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Rank_Math class.
 *
 * @since 1.1.5
 */
class Rank_Math {

	/**
	 * Class constructor.
	 *
	 * @since 1.1.5
	 */
	public function __construct() {
		add_filter( 'rank_math/replacements', array( $this, 'fix_file_name_replacement' ), 10, 2 );
		add_filter( 'cf_images_can_run', array( $this, 'can_run' ) );
	}

	/**
	 * Check if we can serve the images from Cloudflare.
	 *
	 * @since 1.3.0 Moved out from the module can_run() method.
	 *
	 * @return bool
	 */
	public function can_run(): bool {
		return doing_filter( 'rank_math/head' ) || doing_action( 'rank_math/opengraph/facebook' );
	}

	/**
	 * When using Image SEO and setting the image title to use the filename data, Cloudflare images will display
	 * the URL params.
	 *
	 * @since 1.1.5
	 *
	 * @param array $replacements  The replacements.
	 * @param mixed $args          The object, where some replacement values might come from,
	 *                             could be a post, taxonomy or term.
	 *
	 * @return array
	 */
	public function fix_file_name_replacement( array $replacements, $args ): array {

		if ( ! isset( $replacements['%filename%'] ) || ! is_object( $args ) || ! isset( $args->filename ) ) {
			return $replacements;
		}

		// Make sure we only run this for Cloudflare images.
		if ( ! preg_match( '/^w=[0-9]+/', $replacements['%filename%'] ) ) {
			return $replacements;
		}

		try {
			$attachment_id = $this->get_image_id_from_url( $args->filename );
			$file_name     = get_post_meta( $attachment_id, '_wp_attached_file', true );

			$replacements['%filename%'] = $this->get_filename( $file_name );
			return $replacements;
		} catch ( Exception $e ) {
			return $replacements;
		}

	}

	/**
	 * Get WordPress image ID from Cloudflare image URL.
	 *
	 * @since 1.1.5
	 *
	 * @param string $image  Image URL.
	 *
	 * @return int|bool  Image ID or false if not found.
	 */
	private function get_image_id_from_url( string $image ) {

		if ( false === strpos( $image, 'cdn-cgi/imagedelivery' ) ) {
			return false;
		}

		preg_match( '/\/([^\/]+?)\/[^\/]+$/', $image, $matches );

		if ( ! isset( $matches[1] ) ) {
			return false;
		}

		$args = array(
			'fields'                 => 'ids',
			'meta_key'               => '_cloudflare_image_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'             => $matches[1], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'no_found_rows'          => true,
			'post_type'              => 'attachment',
			'post_status'            => 'inherit',
			'posts_per_page'         => 1,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		$results = new WP_Query( $args );

		if ( ! $results->have_posts() ) {
			return false;
		}

		return $results->posts[0];

	}

	/**
	 * Copy of Rank Math's get_filename() method.
	 * Get the filename of the attachment to use as a replacement.
	 *
	 * @since 1.1.5
	 *
	 * @see \RankMath\Replace_Variables\Basic_Variables::get_filename()
	 *
	 * @param string $file  Attachment image file path.
	 *
	 * @return string|null
	 */
	private function get_filename( string $file ) {

		$name = pathinfo( $file );

		// Remove size if embedded.
		$name = explode( '-', $name['filename'] );
		if ( Str::contains( 'x', end( $name ) ) ) {
			array_pop( $name );
		}

		// Format filename.
		$name = join( ' ', $name );
		$name = trim( str_replace( '_', ' ', $name ) );

		return '' !== $name ? $name : null;

	}

}
