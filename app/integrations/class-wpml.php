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
		add_action( 'wpml_after_duplicate_attachment', array( $this, 'ignore_attachment' ), 10, 2 );
		add_action( 'wpml_after_copy_attached_file_postmeta', array( $this, 'ignore_attachment' ), 10, 2 );
	}

	/**
	 * Get the original image ID.
	 *
	 * @since 1.4.0
	 *
	 * @param int $attachment_id  Attachment ID.
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
	 *
	 * @return void
	 */
	public function remove_wpml_filters() {

		global $wpml_query_filter;

		if ( is_object( $wpml_query_filter ) && has_filter( 'posts_join', array( $wpml_query_filter, 'posts_join_filter' ) ) ) {
			remove_filter( 'posts_join', array( $wpml_query_filter, 'posts_join_filter' ) );
			remove_filter( 'posts_where', array( $wpml_query_filter, 'posts_where_filter' ) );
		}

	}

	/**
	 * Fires when an attachment is duplicated.
	 *
	 * Duplicated images do not need to be processed, otherwise this causes double uploads to Cloudflare.
	 *
	 * @param int $attachment_id            The ID of the source/original attachment.
	 * @param int $duplicated_attachment_id The ID of the duplicated attachment.
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	public function ignore_attachment( int $attachment_id, int $duplicated_attachment_id ) {
		update_post_meta( $duplicated_attachment_id, '_cloudflare_image_skip', true );
	}

}
