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
 * @since 1.2.0
 */

namespace CF_Images\App;

use Exception;
use WP_Post;
use WP_Query;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The Media plugin class.
 *
 * @since 1.2.0
 */
class Media {

	use Traits\Ajax;
	use Traits\Helpers;
	use Traits\Stats;

	/**
	 * Class constructor.
	 *
	 * Init all actions and filters for the admin area of the plugin.
	 *
	 * @since 1.2.0
	 */
	public function __construct() {

		if ( ! is_admin() || ! $this->is_set_up() ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_filter( 'manage_media_columns', array( $this, 'media_columns' ) );
		add_action( 'manage_media_custom_column', array( $this, 'media_custom_column' ), 10, 2 );
		add_filter( 'wp_prepare_attachment_for_js', array( $this, 'grid_layout_column' ), 15, 2 );

		// Image actions.
		add_action( 'delete_attachment', array( $this, 'remove_from_cloudflare' ) );

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
			CF_IMAGES_VERSION,
			true
		);

	}

	/**
	 * Filters the Media list table columns.
	 *
	 * @since 1.0.0
	 * @since 1.2.0 Moved from class-admin.php
	 *
	 * @param string[] $posts_columns  An array of columns displayed in the Media list table.
	 *
	 * @return array
	 */
	public function media_columns( array $posts_columns ): array {

		$posts_columns['cf-images-status'] = __( 'Offload status', 'cf-images' );
		return $posts_columns;

	}

	/**
	 * Fires for each custom column in the Media list table.
	 *
	 * @since 1.0.0
	 * @since 1.2.0 Moved from class-admin.php
	 *
	 * @param string $column_name  Name of the custom column.
	 * @param int    $post_id      Attachment ID.
	 *
	 * @return void
	 */
	public function media_custom_column( string $column_name, int $post_id ) {

		if ( 'cf-images-status' !== $column_name ) {
			return;
		}

		// This is used with WPML integration.
		$post_id = apply_filters( 'cf_images_media_post_id', $post_id );

		// Check if supported format.
		$supported_mimes = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
		if ( ! in_array( get_post_mime_type( $post_id ), $supported_mimes, true ) ) {
			esc_html_e( 'Unsupported format', 'cf-images' );
			return;
		}

		$meta    = get_post_meta( $post_id, '_cloudflare_image_id', true );
		$deleted = get_post_meta( $post_id, '_cloudflare_image_offloaded', true );
		$skipped = get_post_meta( $post_id, '_cloudflare_image_skip', true );

		$status = array();
		if ( ! empty( $meta ) ) {
			$status[] = esc_html__( 'Offloaded', 'cf-images' );
			if ( $deleted ) {
				$status[] = esc_html__( 'Removed from media library', 'cf-images' );
			}
		} elseif ( $skipped ) {
			$status[] = esc_html__( 'Skipped', 'cf-images' );
		} else {
			$status[] = esc_html__( 'Not offloaded', 'cf-images' );
		}
		?>
		<span class="status"><?php echo esc_html( implode( ' | ', $status ) ); ?></span>
		<ul>
			<li role="list" dir="rtl">
				<a href="#" aria-haspopup="listbox"><?php esc_html_e( 'Actions', 'cf-images' ); ?></a>
				<ul role="listbox">
					<?php if ( ! empty( $meta ) ) : ?>
						<li><a href="#" class="cf-images-undo" data-id="<?php echo esc_attr( $post_id ); ?>">
							<img src="<?php echo esc_url( CF_IMAGES_DIR_URL . 'assets/images/icons/cloud-off.svg' ); ?>" alt="<?php esc_attr_e( 'Remove from Cloudflare', 'cf-images' ); ?>" />
							<?php esc_html_e( 'Remove from Cloudflare', 'cf-images' ); ?>
						</a></li>
						<?php if ( $deleted ) : ?>
							<li><a href="#" class="cf-images-restore" data-id="<?php echo esc_attr( $post_id ); ?>">
								<img src="<?php echo esc_url( CF_IMAGES_DIR_URL . 'assets/images/icons/download.svg' ); ?>" alt="<?php esc_attr_e( 'Restore in media library', 'cf-images' ); ?>" />
								<?php esc_html_e( 'Restore in media library', 'cf-images' ); ?>
							</a></li>
						<?php elseif ( $this->full_offload_enabled() ) : ?>
							<li><a href="#" class="cf-images-delete" data-id="<?php echo esc_attr( $post_id ); ?>">
								<img src="<?php echo esc_url( CF_IMAGES_DIR_URL . 'assets/images/icons/delete.svg' ); ?>" alt="<?php esc_attr_e( 'Remove from media library', 'cf-images' ); ?>" />
								<?php esc_html_e( 'Delete files on WordPress', 'cf-images' ); ?>
							</a></li>
						<?php endif; ?>
					<?php else : ?>
						<li><a href="#" class="cf-images-offload" data-id="<?php echo esc_attr( $post_id ); ?>">
							<?php if ( $skipped ) : ?>
								<img src="<?php echo esc_url( CF_IMAGES_DIR_URL . 'assets/images/icons/unpause.svg' ); ?>" alt="<?php esc_attr_e( 'Re-upload to Cloudflare', 'cf-images' ); ?>" />
								<?php esc_html_e( 'Re-upload to Cloudflare', 'cf-images' ); ?>
							<?php else : ?>
								<img src="<?php echo esc_url( CF_IMAGES_DIR_URL . 'assets/images/icons/cloud.svg' ); ?>" alt="<?php esc_attr_e( 'Upload to Cloudflare', 'cf-images' ); ?>" />
								<?php esc_html_e( 'Upload to Cloudflare', 'cf-images' ); ?>
							<?php endif; ?>
						</a></li>
						<?php if ( ! $skipped ) : ?>
							<li><a href="#" class="cf-images-skip" data-id="<?php echo esc_attr( $post_id ); ?>">
								<img src="<?php echo esc_url( CF_IMAGES_DIR_URL . 'assets/images/icons/pause.svg' ); ?>" alt="<?php esc_attr_e( 'Ignore and skip image', 'cf-images' ); ?>" />
								<?php esc_html_e( 'Ignore and skip image', 'cf-images' ); ?>
							</a></li>
						<?php endif; ?>
					<?php endif; ?>
					<?php if ( apply_filters( 'cf_images_module_enabled', false, 'image-ai' ) ) : ?>
						<li><a href="#" class="cf-images-ai-alt" data-id="<?php echo esc_attr( $post_id ); ?>">
							<img src="<?php echo esc_url( CF_IMAGES_DIR_URL . 'assets/images/icons/wand.svg' ); ?>" alt="<?php esc_attr_e( 'Generate alt text', 'cf-images' ); ?>" />
							<?php esc_html_e( 'Generate alt text', 'cf-images' ); ?>
						</a></li>
					<?php endif; ?>
				</ul>
			</li>
		</ul>
		<?php

	}

	/**
	 * Add offload status for the media library grid view.
	 *
	 * @since 1.2.0
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
		$this->media_custom_column( 'cf-images-status', $attachment->ID );
		$response['cf-images-status'] = ob_get_clean();

		return $response;

	}

	/**
	 * Offload selected image to Cloudflare Images.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_offload_image() {

		$this->check_ajax_request();

		$attachment_id = (int) filter_input( INPUT_POST, 'data', FILTER_SANITIZE_NUMBER_INT );

		$metadata = wp_get_attachment_metadata( $attachment_id );
		if ( false === $metadata ) {
			$message = sprintf( // translators: %1$s - opening <a> tag, %2$s - closing </a> tag.
				esc_html__( 'Image metadata not found. %1$sSkip image%2$s', 'cf-images' ),
				'<a href="#" data-id="' . $attachment_id . '" class="cf-images-skip">',
				'</a>'
			);

			wp_send_json_error( $message );
		}

		$this->upload_image( $metadata, $attachment_id );

		if ( is_wp_error( Core::get_error() ) ) {
			wp_send_json_error( Core::get_error()->get_error_message() );
		}

		$this->fetch_stats( new Api\Image() );

		wp_send_json_success( $this->get_response_data( $attachment_id ) );

	}

	/**
	 * Bulk upload or bulk remove images progress bar handler.
	 *
	 * @since 1.0.1  Combined from ajax_remove_images() and ajax_upload_images().
	 *
	 * @return void
	 */
	public function ajax_bulk_process() {

		$this->check_ajax_request();

		// Data sanitized later in code.
		$progress = filter_input( INPUT_POST, 'data', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		if ( ! isset( $progress['action'] ) ) {
			wp_send_json_error( esc_html__( 'Incorrect action call', 'cf-images' ) );
		}

		if ( ! isset( $progress['currentStep'] ) || ! isset( $progress['totalSteps'] ) ) {
			wp_send_json_error( esc_html__( 'No current step or total steps defined', 'cf-images' ) );
		}

		$step  = (int) $progress['currentStep'];
		$total = (int) $progress['totalSteps'];

		$action = sanitize_text_field( $progress['action'] );

		if ( ! in_array( $action, array( 'upload', 'remove' ), true ) ) {
			wp_send_json_error( esc_html__( 'Unsupported action', 'cf-images' ) );
		}

		// Progress just started.
		if ( 0 === $step && 0 === $total ) {
			$args = $this->get_wp_query_args( $action );

			// Look for images that have been offloaded.
			$images = new WP_Query( $args );

			// No available images found.
			if ( 0 === $images->found_posts ) {
				$this->update_stats( 0, false ); // Reset stats.
				$this->fetch_stats( new Api\Image() );
				wp_send_json_error( __( 'No images found', 'cf-images' ) );
			}

			$total = $images->found_posts;
		}

		$step++;

		// Something is wrong with the steps count.
		if ( $step > $total ) {
			wp_send_json_error( esc_html__( 'Step error', 'cf-images' ) );
		}

		$args = $this->get_wp_query_args( $action, true );

		// Look for images that have been offloaded.
		$image = new WP_Query( $args );

		if ( 'upload' === $action ) {
			$metadata = wp_get_attachment_metadata( $image->post->ID );
			if ( false === $metadata ) {
				update_post_meta( $image->post->ID, '_cloudflare_image_skip', true );
			} else {
				$this->upload_image( $metadata, $image->post->ID );

				// If there's an error with offloading, we need to mark such an image as skipped.
				if ( is_wp_error( Core::get_error() ) ) {
					update_post_meta( $image->post->ID, '_cloudflare_image_skip', true );
					do_action( 'cf_images_error', 0, '' ); // Reset the error.
				}
			}
		} else {
			$this->remove_from_cloudflare( $image->post->ID );
		}

		// On final step - update API stats.
		if ( $step === $total ) {
			$this->fetch_stats( new Api\Image() );
		}

		$response = array(
			'currentStep' => $step,
			'totalSteps'  => $total,
			'status'      => sprintf( /* translators: %1$d - current image, %2$d - total number of images */
				esc_html__( 'Processing image %1$d out of %2$d...', 'cf-images' ),
				(int) $step,
				$total
			),
		);

		wp_send_json_success( $response );

	}

	/**
	 * Skip image from processing.
	 *
	 * @since 1.1.2
	 *
	 * @return void
	 */
	public function ajax_skip_image() {

		$this->check_ajax_request();

		$attachment_id = (int) filter_input( INPUT_POST, 'data', FILTER_SANITIZE_NUMBER_INT );

		update_post_meta( $attachment_id, '_cloudflare_image_skip', true );

		wp_send_json_success( $this->get_response_data( $attachment_id ) );

	}

	/**
	 * Upload to Cloudflare images.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $metadata       An array of attachment meta data.
	 * @param int   $attachment_id  Current attachment ID.
	 *
	 * @return array
	 */
	public function upload_image( $metadata, int $attachment_id ): array {

		if ( ! isset( $metadata['file'] ) ) {
			do_action( 'cf_images_error', 404, __( 'Media file not found', 'cf-images' ) );
			return $metadata;
		}

		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			do_action( 'cf_images_error', 415, __( 'Unsupported media type', 'cf-images' ) );
			return $metadata;
		}

		$image = new Api\Image();
		$dir   = wp_get_upload_dir();
		$path  = wp_get_original_image_path( $attachment_id );

		$url = wp_parse_url( get_site_url() );
		if ( is_multisite() && ! is_subdomain_install() ) {
			$host = $url['host'] . $url['path'];
		} else {
			$host = $url['host'];
		}

		$name = trailingslashit( $host ) . str_replace( trailingslashit( $dir['basedir'] ), '', $path );

		try {
			$results = $image->upload( $path, $attachment_id, $name );
			$this->update_stats( 1 );
			update_post_meta( $attachment_id, '_cloudflare_image_id', $results->id );
			$this->maybe_save_hash( $results->variants );

			if ( doing_filter( 'wp_async_wp_generate_attachment_metadata' ) ) {
				$this->fetch_stats( new Api\Image() );
			}
		} catch ( Exception $e ) {
			do_action( 'cf_images_error', $e->getCode(), $e->getMessage() );
		}

		return $metadata;

	}

	/**
	 * Fires before an attachment is deleted, at the start of wp_delete_attachment().
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id  Attachment ID.
	 *
	 * @return void
	 */
	public function remove_from_cloudflare( int $post_id ) {

		$id = get_post_meta( $post_id, '_cloudflare_image_id', true );

		if ( ! $id ) {
			return;
		}

		$image = new Api\Image();

		try {
			$image->delete( $id );
			$this->update_stats( -1 );
			delete_post_meta( $post_id, '_cloudflare_image_id' );
			delete_post_meta( $post_id, '_cloudflare_image_skip' );

			if ( doing_action( 'delete_attachment' ) ) {
				$this->fetch_stats( new Api\Image() );
			}
		} catch ( Exception $e ) {
			do_action( 'cf_images_error', $e->getCode(), $e->getMessage() );
		}

	}

	/**
	 * Remove selected image from Cloudflare Images.
	 *
	 * @since 1.3.0
	 *
	 * @return void
	 */
	public function ajax_undo_image() {

		$this->check_ajax_request();

		$attachment_id = (int) filter_input( INPUT_POST, 'data', FILTER_SANITIZE_NUMBER_INT );
		$this->remove_from_cloudflare( $attachment_id );

		wp_send_json_success( $this->get_response_data( $attachment_id ) );

	}

	/**
	 * Remove (physically delete the files) selected image from WordPress media library.
	 *
	 * @since 1.2.1
	 *
	 * @return void
	 */
	public function ajax_delete_image() {

		$this->check_ajax_request();

		$attachment_id = (int) filter_input( INPUT_POST, 'data', FILTER_SANITIZE_NUMBER_INT );

		// This is a backward compat check to make sure we have the original offloaded before removing it.
		$metadata = wp_get_attachment_metadata( $attachment_id );
		$original = wp_get_original_image_path( $attachment_id );

		if ( false === strpos( $original, $metadata['file'] ) ) {
			try {
				// This is a safety mechanism to make sure the image on Cloudflare is not a scaled image.
				$image   = new Api\Image();
				$results = $image->details( $attachment_id );
			} catch ( Exception $e ) {
				wp_send_json_error( $e->getMessage() );
			}

			if ( empty( $results->result->filename ) ) {
				wp_send_json_error( esc_html__( 'Cannot map local image to image on Cloudflare.', 'cf-images' ) );
			}

			if ( false !== strpos( $results->result->filename, $metadata['file'] ) ) {
				wp_send_json_error( esc_html__( 'Cannot remove image, scaled image offloaded.', 'cf-images' ) );
			}
		}

		$this->delete_image( $attachment_id );

		wp_send_json_success( $this->get_response_data( $attachment_id ) );

	}

	/**
	 * Delete image from WordPress media library.
	 *
	 * @since 1.2.1
	 *
	 * @param int $attachment_id  Attachment ID.
	 *
	 * @return void
	 */
	private function delete_image( int $attachment_id ) {

		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return;
		}

		// Image not offloaded.
		if ( ! get_post_meta( $attachment_id, '_cloudflare_image_id', true ) ) {
			return;
		}

		$metadata = wp_get_attachment_metadata( $attachment_id );

		// Remove original.
		if ( ! empty( $metadata['original_image'] ) ) {
			$this->delete( $attachment_id, $metadata['original_image'] );
		}

		// Remove scaled version.
		$attached_file = get_post_meta( $attachment_id, '_wp_attached_file', true );
		if ( $attached_file ) {
			$this->delete( $attachment_id, $attached_file, true );
		}

		// Remove intermediate sizes.
		if ( ! empty( $metadata['sizes'] ) ) {
			foreach ( $metadata['sizes'] as $size ) {
				$this->delete( $attachment_id, $size['file'] );
			}
		}

		// Set offload flag.
		update_post_meta( $attachment_id, '_cloudflare_image_offloaded', true );

	}

	/**
	 * Remove image from uploads directory.
	 *
	 * @since 1.2.1
	 *
	 * @param int    $attachment_id  Attachment ID.
	 * @param string $image          Path in uploads folder.
	 * @param bool   $scaled         Whether image is scaled.
	 *
	 * @return void
	 */
	private function delete( int $attachment_id, string $image, bool $scaled = false ) {

		if ( $scaled ) {
			$uploads = wp_get_upload_dir();
			if ( ! empty( $uploads['basedir'] ) ) {
				$path = trailingslashit( $uploads['basedir'] ) . $image;
				if ( file_exists( $path ) ) {
					unlink( $path );
				}
			}
			return;
		}

		$path = trailingslashit( dirname( get_attached_file( $attachment_id ) ) ) . $image;
		if ( file_exists( $path ) ) {
			unlink( $path );
		}

	}

	/**
	 * Generate the div layout for Ajax responses.
	 *
	 * @since 1.2.1
	 *
	 * @param int $attachment_id  Attachment ID.
	 *
	 * @return string
	 */
	private function get_response_data( int $attachment_id ): string {

		ob_start();
		$this->media_custom_column( 'cf-images-status', $attachment_id );
		return ob_get_clean();

	}

	/**
	 * Restore image to media library from Cloudflare.
	 *
	 * @since 1.2.1
	 *
	 * @return void
	 */
	public function ajax_restore_image() {

		$this->check_ajax_request();

		$attachment_id = (int) filter_input( INPUT_POST, 'data', FILTER_SANITIZE_NUMBER_INT );

		if ( ! $attachment_id ) {
			return;
		}

		$image = new Api\Image();

		try {
			$image_blob = $image->download( $attachment_id );

			$original = wp_get_original_image_path( $attachment_id );
			if ( file_exists( $original ) ) {
				delete_post_meta( $attachment_id, '_cloudflare_image_offloaded' );
				wp_send_json_error( esc_html__( 'Image already exists in the media library.', 'cf-images' ) );
			}

			file_put_contents( $original, $image_blob ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
			wp_create_image_subsizes( $original, $attachment_id );
		} catch ( Exception $e ) {
			do_action( 'cf_images_error', $e->getCode(), $e->getMessage() );
		}

		delete_post_meta( $attachment_id, '_cloudflare_image_offloaded' );
		wp_send_json_success( $this->get_response_data( $attachment_id ) );

	}

}
