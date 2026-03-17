<?php
/**
 * GeoDirectory integration class
 *
 * Offloads GeoDirectory gallery images to Cloudflare Images
 * and rewrites their URLs via the Page Parser filter so that
 * WordPress can generate srcset before lazy loading kicks in.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Integrations
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.10.0
 */

namespace CF_Images\App\Integrations;

use CF_Images\App\Api;
use CF_Images\App\Traits\Stats;
use Exception;
use GeoDir_Media;
use WP_Post;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Geodirectory class.
 *
 * @since 1.10.0
 */
class Geodirectory {
	use Stats;

	/**
	 * Post meta key for the GeoDirectory image mapping on each listing.
	 *
	 * Stores an array of [ gd_attachment_id => cf_image_id, ... ].
	 *
	 * @since 1.10.0
	 *
	 * @var string
	 */
	const META_KEY = '_cf_images_geodir';

	/**
	 * Runtime cache mapping normalized local URLs to Cloudflare image IDs.
	 *
	 * Populated by resolve_image_sources(), consumed by resolve_external_image_id().
	 *
	 * @since 1.10.0
	 *
	 * @var array<string, string>
	 */
	private static $url_cf_map = array();

	/**
	 * Class constructor.
	 *
	 * @since 1.10.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init_hooks' ) );
	}

	/**
	 * Register hooks once GeoDirectory is loaded.
	 *
	 * @since 1.10.0
	 */
	public function init_hooks(): void {
		if ( ! defined( 'GEODIRECTORY_VERSION' ) ) {
			return;
		}

		if ( is_admin() ) {
			add_action( 'geodir_post_saved', array( $this, 'offload_gallery_images' ), 10, 3 );
			add_action( 'before_delete_post', array( $this, 'cleanup_on_delete' ) );
			add_filter( 'cf_images_wp_query_args', array( $this, 'add_wp_query_args' ), 10, 2 );
			add_filter( 'cf_images_bulk_process_post', array( $this, 'bulk_process_post' ), 10, 3 );
		} else {
			add_filter( 'cf_images_page_parser_sources', array( $this, 'resolve_image_sources' ), 10, 2 );
			add_filter( 'cf_images_external_image_id', array( $this, 'resolve_external_image_id' ), 10, 3 );
		}
	}

	/**
	 * Add GeoDirectory post types to the bulk WP_Query args.
	 *
	 * @since 1.10.0
	 *
	 * @param array  $args   WP_Query args.
	 * @param string $action Executing action.
	 *
	 * @return array Modified WP_Query args.
	 */
	public function add_wp_query_args( array $args, string $action ): array {
		if ( ! in_array( $action, array( 'upload', 'remove' ), true ) ) {
			return $args;
		}

		if ( ! function_exists( 'geodir_get_posttypes' ) ) {
			return $args;
		}

		$gd_types = geodir_get_posttypes();

		if ( empty( $gd_types ) ) {
			return $args;
		}

		// Expand post_type to include GeoDirectory listings.
		$current_type      = isset( $args['post_type'] ) ? (array) $args['post_type'] : array( 'attachment' );
		$args['post_type'] = array_merge( $current_type, $gd_types );

		// GD listings use 'publish', attachments use 'inherit'.
		$current_status = isset( $args['post_status'] ) ? (array) $args['post_status'] : array( 'inherit' );
		if ( ! in_array( 'publish', $current_status, true ) ) {
			$current_status[] = 'publish';
		}
		$args['post_status'] = $current_status;

		return $args;
	}

	/**
	 * Handle bulk processing for GeoDirectory listings.
	 *
	 * @since 1.10.0
	 *
	 * @param bool    $handled Whether the post was already handled.
	 * @param WP_Post $post    The post object being processed.
	 * @param string  $action  The bulk action: 'upload' or 'remove'.
	 *
	 * @return bool True if handled, false to fall through to attachment logic.
	 */
	public function bulk_process_post( bool $handled, WP_Post $post, string $action ): bool {
		if ( $handled ) {
			return true;
		}

		if ( ! function_exists( 'geodir_is_gd_post_type' ) || ! geodir_is_gd_post_type( $post->post_type ) ) {
			return false;
		}

		if ( 'upload' === $action ) {
			$this->offload_listing( $post->ID );
		} elseif ( 'remove' === $action ) {
			$this->remove_listing( $post->ID );
		}

		return true;
	}

	/**
	 * Offload gallery images to Cloudflare after a GeoDirectory listing is saved.
	 *
	 * @since 1.10.0
	 *
	 * @param array   $postarr Post array.
	 * @param array   $gd_post GeoDirectory post array.
	 * @param WP_Post $post    WordPress post object.
	 */
	public function offload_gallery_images( array $postarr, array $gd_post, WP_Post $post ): void {
		if ( ! class_exists( 'GeoDir_Media' ) ) {
			return;
		}

		$images = GeoDir_Media::get_post_images( $post->ID );

		$old_map = get_post_meta( $post->ID, self::META_KEY, true );

		if ( ! is_array( $old_map ) ) {
			$old_map = array();
		}

		$new_map = $this->build_image_map( $images, $old_map );

		// Delete images that were removed from the listing.
		$removed = array_diff_key( $old_map, $new_map );

		if ( ! empty( $removed ) ) {
			$api = new Api\Image();

			foreach ( $removed as $cf_id ) {
				try {
					$api->delete( $cf_id );
					$this->decrement_stat( 'synced' );
				} catch ( Exception $e ) {
					do_action( 'cf_images_log', $e->getMessage() );
				}
			}
		}

		// Persist or clean up meta.
		if ( ! empty( $new_map ) ) {
			update_post_meta( $post->ID, self::META_KEY, $new_map );
		} elseif ( ! empty( $old_map ) ) {
			delete_post_meta( $post->ID, self::META_KEY );
		}

		// Set the universal marker so the bulk pipeline sees this listing as processed.
		if ( ! empty( $new_map ) ) {
			update_post_meta( $post->ID, '_cloudflare_image_id', 1 );
		}
	}

	/**
	 * Build a CF image mapping for the given GeoDirectory images.
	 *
	 * Iterates each image, carries forward already-offloaded entries from
	 * $existing_map, and uploads new images to Cloudflare.
	 *
	 * @since 1.10.0
	 *
	 * @param array $images       GD image objects from GeoDir_Media::get_post_images().
	 * @param array $existing_map Already-offloaded [ gd_attachment_id => cf_image_id ].
	 *
	 * @return array Updated mapping [ gd_attachment_id => cf_image_id ].
	 */
	private function build_image_map( array $images, array $existing_map ): array {
		$upload_dir = wp_upload_dir();
		$basedir    = $upload_dir['basedir'];
		$host       = $this->get_upload_host();
		$new_map    = array();
		$api        = null;

		foreach ( $images as $image ) {
			$gd_id = (int) $image->ID;

			// Already offloaded — carry forward.
			if ( isset( $existing_map[ $gd_id ] ) ) {
				$new_map[ $gd_id ] = $existing_map[ $gd_id ];
				continue;
			}

			// Skip external URLs.
			if ( function_exists( 'geodir_is_full_url' ) && geodir_is_full_url( $image->file ) ) {
				continue;
			}

			// Validate the file path stays within the upload directory.
			$file_path = realpath( $basedir . '/' . ltrim( $image->file, '/' ) );

			if ( false === $file_path || ! str_starts_with( $file_path, $basedir ) ) {
				continue;
			}

			$mime = wp_check_filetype( $file_path );
			if ( empty( $mime['type'] ) || ! str_starts_with( $mime['type'], 'image/' ) ) {
				continue;
			}

			// Check if this file is already offloaded as a WP attachment.
			$image_url = $upload_dir['baseurl'] . '/' . ltrim( $image->file, '/' );
			$wp_id     = attachment_url_to_postid( $image_url );

			if ( $wp_id ) {
				$existing_cf_id = get_post_meta( $wp_id, '_cloudflare_image_id', true );
				if ( ! empty( $existing_cf_id ) ) {
					$new_map[ $gd_id ] = $existing_cf_id;
				}
				// WP attachment — defer to regular offload pipeline.
				continue;
			}

			if ( null === $api ) {
				$api = new Api\Image();
			}

			// Build the Cloudflare image name with the host prefix (matches Media::upload_image()).
			$name = ( $host ? trailingslashit( $host ) : '' ) . str_replace( trailingslashit( $basedir ), '', $file_path );

			try {
				$result = $api->upload( $file_path, 0, $name );

				if ( ! empty( $result->id ) ) {
					$new_map[ $gd_id ] = $result->id;
					$this->increment_stat( 'synced' );
				}
			} catch ( Exception $e ) {
				do_action( 'cf_images_log', $e->getMessage() );
			}
		}

		return $new_map;
	}

	/**
	 * Offload all gallery images for a GeoDirectory listing (bulk pipeline).
	 *
	 * Uploads new images only — does not delete removed ones (unlike offload_gallery_images
	 * which handles the full save lifecycle). Sets `_cloudflare_image_id` as the universal
	 * marker so the listing is not re-processed on subsequent bulk runs.
	 *
	 * @since 1.10.0
	 *
	 * @param int $post_id GeoDirectory listing post ID.
	 */
	private function offload_listing( int $post_id ): void {
		if ( ! class_exists( 'GeoDir_Media' ) ) {
			return;
		}

		$images = GeoDir_Media::get_post_images( $post_id );

		if ( empty( $images ) ) {
			// No images — mark as processed so it's not retried.
			update_post_meta( $post_id, '_cloudflare_image_id', 1 );
			return;
		}

		$old_map = get_post_meta( $post_id, self::META_KEY, true );

		if ( ! is_array( $old_map ) ) {
			$old_map = array();
		}

		$new_map = $this->build_image_map( $images, $old_map );

		if ( ! empty( $new_map ) ) {
			update_post_meta( $post_id, self::META_KEY, $new_map );
		}

		// Set the universal marker.
		update_post_meta( $post_id, '_cloudflare_image_id', 1 );
	}

	/**
	 * Remove all Cloudflare images for a GeoDirectory listing (bulk pipeline).
	 *
	 * Deletes each CF image via API, then removes both meta keys.
	 *
	 * @since 1.10.0
	 *
	 * @param int $post_id GeoDirectory listing post ID.
	 */
	private function remove_listing( int $post_id ): void {
		$map = get_post_meta( $post_id, self::META_KEY, true );

		if ( is_array( $map ) && ! empty( $map ) ) {
			$api = new Api\Image();

			foreach ( $map as $cf_id ) {
				try {
					$api->delete( $cf_id );
					$this->decrement_stat( 'synced' );
				} catch ( Exception $e ) {
					do_action( 'cf_images_log', $e->getMessage() );
				}
			}
		}

		delete_post_meta( $post_id, self::META_KEY );
		delete_post_meta( $post_id, '_cloudflare_image_id' );
	}

	/**
	 * Resolve image sources for GeoDirectory images in the Page Parser.
	 *
	 * Detects GeoDirectory images by the `geodir-image-{id}` class, looks up the
	 * Cloudflare image ID, and caches the URL-to-CF-ID mapping. For lazy-loaded
	 * images, promotes data-src/data-srcset so the Image class can process them.
	 *
	 * No URL building or DOM rewriting happens here — the Image class handles
	 * that via the `cf_images_external_image_id` filter.
	 *
	 * @since 1.10.0
	 *
	 * @param array  $sources { Sources attribute.
	 *     @type string $src    Image src attribute value.
	 *     @type string $srcset Image srcset attribute value.
	 * }
	 * @param string $original_dom Original image DOM element string (unmodified).
	 *
	 * @return array Modified sources or original if not a GeoDirectory image.
	 */
	public function resolve_image_sources( array $sources, string $original_dom ): array {
		// Only process GeoDirectory images.
		if ( ! preg_match( '/geodir-image-(\d+)/', $original_dom, $m ) ) {
			return $sources;
		}

		$gd_id = (int) $m[1];

		// Note: get_the_ID() only works reliably on single listing pages, not archives.
		$post_id = get_the_ID();

		if ( ! $post_id ) {
			return $sources;
		}

		$map = get_post_meta( $post_id, self::META_KEY, true );

		if ( ! is_array( $map ) || ! isset( $map[ $gd_id ] ) ) {
			return $sources;
		}

		$cf_image_id = $map[ $gd_id ];

		if ( empty( $cf_image_id ) || ! is_string( $cf_image_id ) ) {
			return $sources;
		}

		// Cache the raw URL → CF image ID mapping for resolve_external_image_id().
		if ( ! empty( $sources['src'] ) ) {
			self::$url_cf_map[ $sources['src'] ] = $cf_image_id;
		}

		return $sources;
	}

	/**
	 * Return a Cloudflare image ID for URLs that have no WP attachment post.
	 *
	 * Hooked to `cf_images_external_image_id`. The Image class calls this filter
	 * with both the normalized URL and the raw URL.
	 *
	 * @since 1.10.0
	 *
	 * @param string $cf_image_id Cloudflare image ID (empty string by default).
	 * @param string $original    Normalized original image URL.
	 * @param string $image_url   Raw image URL (before normalization).
	 *
	 * @return string Cloudflare image ID or empty string.
	 */
	public function resolve_external_image_id( string $cf_image_id, string $original, string $image_url ): string {
		if ( ! empty( $cf_image_id ) ) {
			return $cf_image_id;
		}

		if ( ! empty( $image_url ) && isset( self::$url_cf_map[ $image_url ] ) ) {
			return self::$url_cf_map[ $image_url ];
		}

		return self::$url_cf_map[ $original ] ?? '';
	}

	/**
	 * Build the host prefix for Cloudflare image names.
	 *
	 * Matches the naming convention used in Media::upload_image().
	 *
	 * @since 1.10.0
	 *
	 * @return string Host prefix (e.g. "example.com" or "example.com/subsite").
	 */
	private function get_upload_host(): string {
		$url = wp_parse_url( get_site_url() );

		if ( is_multisite() && ! is_subdomain_install() && isset( $url['path'] ) ) {
			$host = $url['host'] . $url['path'];
		} else {
			$host = $url['host'];
		}

		/** This filter is documented in app/class-media.php */
		return apply_filters( 'cf_images_upload_host', $host, 0 );
	}

	/**
	 * Clean up Cloudflare images when a GeoDirectory listing is deleted.
	 *
	 * @since 1.10.0
	 *
	 * @param int $post_id Post ID.
	 */
	public function cleanup_on_delete( int $post_id ): void {
		// Only run for GeoDirectory post types.
		if ( ! function_exists( 'geodir_is_gd_post_type' ) ) {
			return;
		}

		$post_type = get_post_type( $post_id );
		if ( ! $post_type || ! geodir_is_gd_post_type( $post_type ) ) {
			return;
		}

		$map = get_post_meta( $post_id, self::META_KEY, true );

		if ( ! is_array( $map ) || empty( $map ) ) {
			return;
		}

		$api = new Api\Image();

		foreach ( $map as $cf_id ) {
			try {
				$api->delete( $cf_id );
				$this->decrement_stat( 'synced' );
			} catch ( Exception $e ) {
				do_action( 'cf_images_log', $e->getMessage() );
			}
		}

		delete_post_meta( $post_id, self::META_KEY );
		delete_post_meta( $post_id, '_cloudflare_image_id' );
	}
}
