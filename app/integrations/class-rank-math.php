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

use CF_Images\App\Traits;
use Exception;
use MyThemeShop\Helpers\Str;
use RankMath\Helper;
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
	use Traits\Helpers;

	/**
	 * Rank Math Image SEO active flag.
	 *
	 * @since 1.9.2
	 *
	 * @var bool
	 */
	private $image_seo_active = false;

	/**
	 * Class constructor.
	 *
	 * @since 1.1.5
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'is_image_seo_active' ) );
		add_filter( 'rank_math/replacements', array( $this, 'fix_file_name_replacement' ), 10, 2 );
		add_filter( 'cf_images_can_run', array( $this, 'can_run' ) );
		add_action( 'cf_images_get_attachment_image_src', array( $this, 'cache_image_ids' ), 10, 2 );
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
	 * @param array $replacements The replacements.
	 * @param mixed $args         The object, where some replacement values might come from,
	 *                            could be a post, taxonomy or term.
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
	 * @param string $image Image URL.
	 *
	 * @return int|bool Image ID or false if not found.
	 */
	private function get_image_id_from_url( string $image ) {
		if ( false === strpos( $image, $this->get_cdn_domain() ) ) {
			return false;
		}

		$url_path = wp_parse_url( $image, PHP_URL_PATH );
		$pattern  = '/\/[a-zA-Z0-9-]+\/([^\/]+(?:\/[^\/]+)*\.[a-z]+|[^\/]+)(?:\/.*)?/';

		preg_match( $pattern, $url_path, $matches );

		if ( ! isset( $matches[1] ) ) {
			return false;
		}

		$post_id = wp_cache_get( $matches[1], 'cf_images' );

		if ( false !== $post_id ) {
			return $post_id;
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

		wp_cache_add( $matches[1], $results->posts[0], 'cf_images' );

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
	 * @param string $file Attachment image file path.
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

	/**
	 * Check is Rank Math Image SEO is active.
	 *
	 * @since 1.9.2
	 */
	public function is_image_seo_active() {
		if ( ! method_exists( '\RankMath\Helper', 'get_settings' ) ) {
			$this->image_seo_active = false;
		}

		$is_alt   = Helper::get_settings( 'general.add_img_alt' ) && Helper::get_settings( 'general.img_alt_format' ) && trim( Helper::get_settings( 'general.img_alt_format' ) );
		$is_title = Helper::get_settings( 'general.add_img_title' ) && Helper::get_settings( 'general.img_title_format' ) && trim( Helper::get_settings( 'general.img_title_format' ) );

		$this->image_seo_active = $is_alt || $is_title;
	}

	/**
	 * Cache Cloudflare Image ID and WordPress attachment ID.
	 *
	 * @since 1.9.2
	 *
	 * @param string     $cloudflare_image_id Cloudflare Image ID.
	 * @param int|string $attachment_id       Attachment ID.
	 */
	public function cache_image_ids( string $cloudflare_image_id, $attachment_id ) {
		if ( ! $this->image_seo_active ) {
			return;
		}

		wp_cache_add( $cloudflare_image_id, $attachment_id, 'cf_images' );
	}
}
