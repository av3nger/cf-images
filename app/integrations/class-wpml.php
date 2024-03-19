<?php
/**
 * WPML integration class
 *
 * This class adds compatibility with the WPML translation plugin.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Integrations
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.4.0
 */

namespace CF_Images\App\Integrations;

use stdClass;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * WPML class.
 *
 * @since 1.4.0
 */
class Wpml {
	/**
	 * Class constructor.
	 *
	 * @since 1.4.0
	 */
	public function __construct() {
		add_filter( 'cf_images_media_post_id', array( $this, 'get_original_image_id' ) );
		add_action( 'cf_images_before_wp_query', array( $this, 'remove_wpml_filters' ) );
		add_action( 'cf_images_upload_success', array( $this, 'update_image_meta' ), 10, 2 );
		add_action( 'cf_images_remove_success', array( $this, 'image_removed_from_cf' ) );
		add_filter( 'cf_images_wp_query_args', array( $this, 'add_wp_query_args' ), 10, 2 );
	}

	/**
	 * Get the original image ID.
	 *
	 * @since 1.4.0
	 *
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return mixed
	 */
	public function get_original_image_id( int $attachment_id ) {
		global $sitepress;

		if ( ! $sitepress || ! method_exists( $sitepress, 'get_default_language' ) ) {
			return $attachment_id;
		}

		// Get the original language of the image.
		$original_language = $sitepress->get_default_language();

		// Get the ID of the image in the original language.
		$original_image_id = apply_filters( 'wpml_object_id', $attachment_id, 'attachment', false, $original_language );
		return is_null( $original_image_id ) ? $attachment_id : $original_image_id;
	}

	/**
	 * Remove WPML query filters.
	 *
	 * @since 1.4.0
	 */
	public function remove_wpml_filters() {
		global $wpml_query_filter;

		if ( is_object( $wpml_query_filter ) && has_filter( 'posts_join', array( $wpml_query_filter, 'posts_join_filter' ) ) ) {
			remove_filter( 'posts_join', array( $wpml_query_filter, 'posts_join_filter' ) );
			remove_filter( 'posts_where', array( $wpml_query_filter, 'posts_where_filter' ) );
		}
	}

	/**
	 * Get translations for an image.
	 *
	 * @since 1.8.1
	 *
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return array
	 */
	private function get_translations( int $attachment_id ): array {
		global $sitepress;

		if ( ! $sitepress || ! method_exists( $sitepress, 'get_element_trid' ) ) {
			return array();
		}

		$translation_id = $sitepress->get_element_trid( $attachment_id, 'post_attachment' );
		return $sitepress->get_element_translations( $translation_id, 'post_attachment', true );
	}

	/**
	 * Update the meta for all images.
	 *
	 * @since 1.8.1
	 *
	 * @param int      $attachment_id Original attachment ID.
	 * @param stdClass $results       Upload results.
	 */
	public function update_image_meta( int $attachment_id, stdClass $results ) {
		$translations = $this->get_translations( $attachment_id );

		foreach ( $translations as $translation ) {
			if ( $translation->original ) {
				continue;
			}

			update_post_meta( $translation->element_id, '_cloudflare_image_id', $results->id );
		}
	}

	/**
	 * Remove meta from all translatable images when the main image is removed from Cloudflare.
	 *
	 * @since 1.8.1
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function image_removed_from_cf( int $attachment_id ) {
		$translations = $this->get_translations( $attachment_id );

		foreach ( $translations as $translation ) {
			if ( $translation->original ) {
				continue;
			}

			delete_post_meta( $translation->element_id, '_cloudflare_image_id' );
		}
	}

	/**
	 * Adjust the WP_Query args for bulk offload action.
	 *
	 * @since 1.8.1
	 * @see Ajax::get_wp_query_args()
	 *
	 * @param array  $args   WP_Query args.
	 * @param string $action Executing action.
	 *
	 * @return array
	 */
	public function add_wp_query_args( array $args, string $action ): array {
		if ( 'upload' !== $action ) {
			return $args;
		}

		$args['meta_query'][] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'compare' => 'NOT EXISTS',
			'key'     => 'wpml_media_processed',
		);

		return $args;
	}
}
