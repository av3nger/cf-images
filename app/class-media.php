<?php
/**
 * The file that defines the media plugin class
 *
 * This is used to define functionality for the media library: extending attachment detail modals, offload statues, etc.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.1.6
 */

namespace CF_Images\App;

use WP_Post;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The Media plugin class.
 *
 * @since 1.1.6
 */
class Media {

	use Traits\Helpers;

	/**
	 * Class constructor.
	 *
	 * Init all actions and filters for the admin area of the plugin.
	 *
	 * @since 1.1.6
	 */
	public function __construct() {

		if ( ! is_admin() || ! $this->is_set_up() ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_filter( 'manage_media_columns', array( $this, 'media_columns' ) );
		add_action( 'manage_media_custom_column', array( $this, 'media_custom_column' ), 10, 2 );
		add_filter( 'wp_prepare_attachment_for_js', array( $this, 'grid_layout_column' ), 15, 2 );

	}

	/**
	 * Load plugin scripts.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook  The current admin page.
	 *
	 * @return void
	 */
	public function enqueue_scripts( string $hook ) {

		// Run only on plugin pages.
		if ( 'upload.php' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			$this->get_slug() . '-media',
			CF_IMAGES_DIR_URL . 'assets/js/cf-images-media.min.js',
			array( $this->get_slug(), 'media-views' ),
			$this->get_version(),
			true
		);

	}

	/**
	 * Filters the Media list table columns.
	 *
	 * @since 1.0.0
	 * @since 1.1.6 Moved from class-admin.php
	 *
	 * @param string[] $posts_columns  An array of columns displayed in the Media list table.
	 *
	 * @return array
	 */
	public function media_columns( array $posts_columns ): array {

		$posts_columns['cf-images'] = __( 'Offload status', 'cf-images' );
		return $posts_columns;

	}

	/**
	 * Fires for each custom column in the Media list table.
	 *
	 * @since 1.0.0
	 * @since 1.1.6 Moved from class-admin.php
	 *
	 * @param string $column_name  Name of the custom column.
	 * @param int    $post_id      Attachment ID.
	 *
	 * @return void
	 */
	public function media_custom_column( string $column_name, int $post_id ) {

		if ( 'cf-images' !== $column_name ) {
			return;
		}

		$meta = get_post_meta( $post_id, '_cloudflare_image_id', true );

		if ( ! empty( $meta ) ) {
			echo '<span class="dashicons dashicons-cloud-saved"></span>';
			esc_html_e( 'Offloaded', 'cf-images' );
			return;
		}

		$supported_mimes = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );

		if ( ! in_array( get_post_mime_type( $post_id ), $supported_mimes, true ) ) {
			esc_html_e( 'Unsupported format', 'cf-images' );
			return;
		}

		// This image was skipped because of some error during bulk upload.
		if ( get_post_meta( $post_id, '_cloudflare_image_skip', true ) ) {
			esc_html_e( 'Skipped from processing', 'cf-images' );
			echo '<br />';
			printf( /* translators: %1$s - opening <a> tag, %2$s - closing </a> tag */
				esc_html__( '%1$sRetry offload%2$s', 'cf-images' ),
				'<a href="#" class="cf-images-offload" data-id="' . esc_attr( $post_id ) . '">',
				'</a>'
			);
			return;
		}

		printf( /* translators: %1$s - opening <a> tag, %2$s - closing </a> tag */
			esc_html__( '%1$sOffload%2$s', 'cf-images' ),
			'<a href="#" class="cf-images-offload" data-id="' . esc_attr( $post_id ) . '">',
			'</a>'
		);

	}

	/**
	 * Add offload status for the media library grid view.
	 *
	 * @since 1.1.6
	 *
	 * @param array   $response    Array of prepared attachment data. @see wp_prepare_attachment_for_js().
	 * @param WP_Post $attachment  Attachment object.
	 *
	 * @return array
	 */
	public function grid_layout_column( array $response, WP_Post $attachment ): array {

		if ( ! isset( $attachment->ID ) ) {
			return $response;
		}

		ob_start();
		$this->media_custom_column( 'cf-images', $attachment->ID );
		$response['cf-images'] = ob_get_clean();

		return $response;

	}

}
