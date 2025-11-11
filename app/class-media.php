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

		// Sortable columns.
		add_filter( 'manage_upload_sortable_columns', array( $this, 'sortable_column' ) );

		// Offload filters media library (list view).
		add_action( 'restrict_manage_posts', array( $this, 'add_filter_dropdown' ) );
		add_action( 'pre_get_posts', array( $this, 'orderby_column' ) );

		// Image actions.
		add_action( 'delete_attachment', array( $this, 'remove_from_cloudflare' ) );

		// Bulk dropdown actions in the media library.
		add_filter( 'bulk_actions-upload', array( $this, 'bulk_media_actions' ) );
		add_filter( 'handle_bulk_actions-upload', array( $this, 'bulk_action_handler' ), 10, 3 );
		add_action( 'admin_notices', array( $this, 'bulk_action_admin_notice' ) );
	}

	/**
	 * Load plugin scripts.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_scripts( string $hook ) {
		// Run only on plugin pages.
		if ( 'upload.php' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			$this->get_slug() . '-media',
			CF_IMAGES_DIR_URL . 'assets/js/cf-images-media.min.js',
			array( 'media-views' ),
			CF_IMAGES_VERSION,
			true
		);

		wp_localize_script(
			$this->get_slug() . '-media',
			'CFImages',
			array(
				'nonce'   => wp_create_nonce( 'cf-images-nonce' ),
				'strings' => array(
					'inProgress'   => esc_html__( 'Processing', 'cf-images' ),
					'offloadError' => esc_html__( 'Processing error', 'cf-images' ),
				),
			)
		);

		wp_enqueue_style(
			$this->get_slug(),
			CF_IMAGES_DIR_URL . 'assets/css/cf-images-media.min.css',
			array(),
			CF_IMAGES_VERSION
		);
	}

	/**
	 * Filters the Media list table columns.
	 *
	 * @since 1.0.0
	 * @since 1.2.0 Moved from class-admin.php
	 *
	 * @param string[] $posts_columns An array of columns displayed in the Media list table.
	 *
	 * @return array
	 */
	public function media_columns( array $posts_columns ): array {
		$posts_columns['cf-images-status'] = __( 'Optimization', 'cf-images' );
		return $posts_columns;
	}

	/**
	 * Fires for each custom column in the Media list table.
	 *
	 * @since 1.0.0
	 * @since 1.2.0 Moved from class-admin.php
	 *
	 * @param string $column_name Name of the custom column.
	 * @param int    $post_id     Attachment ID.
	 */
	public function media_custom_column( string $column_name, int $post_id ) {
		if ( 'cf-images-status' !== $column_name ) {
			return;
		}

		// This is used with WPML integration.
		$post_id = apply_filters( 'cf_images_media_post_id', $post_id );

		// Check if supported format.
		$supported_mimes = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg' );
		if ( ! in_array( get_post_mime_type( $post_id ), $supported_mimes, true ) ) {
			?>
			<span class="status"><?php esc_html_e( 'Unsupported format', 'cf-images' ); ?></span>
			<?php
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
		<span class="status">
			<?php esc_html_e( 'Status:', 'cf-images' ); ?>
			<?php echo esc_html( implode( ' | ', $status ) ); ?>
			<?php do_action( 'cf_images_media_custom_column', (int) $post_id ); ?>
		</span>
		<div class="dropdown is-hoverable">
			<div class="dropdown-trigger">
				<a href="#" aria-haspopup="true" aria-controls="dropdown-menu">
					<?php esc_html_e( 'Actions', 'cf-images' ); ?>
				</a>
			</div>
			<div class="dropdown-menu" id="dropdown-menu" role="menu">
				<div class="dropdown-content">
					<?php if ( ! empty( $meta ) ) : ?>
						<a href="#" class="dropdown-item cf-images-undo" data-id="<?php echo esc_attr( $post_id ); ?>">
							<img src="<?php echo esc_url( CF_IMAGES_DIR_URL . 'assets/images/icons/cloud-off.svg' ); ?>" alt="<?php esc_attr_e( 'Remove from Cloudflare', 'cf-images' ); ?>" />
							<?php esc_html_e( 'Remove from Cloudflare', 'cf-images' ); ?>
						</a>
						<?php if ( $deleted ) : ?>
							<<a href="#" class="dropdown-item cf-images-restore" data-id="<?php echo esc_attr( $post_id ); ?>">
								<img src="<?php echo esc_url( CF_IMAGES_DIR_URL . 'assets/images/icons/download.svg' ); ?>" alt="<?php esc_attr_e( 'Restore in media library', 'cf-images' ); ?>" />
								<?php esc_html_e( 'Restore in media library', 'cf-images' ); ?>
							</a>
						<?php elseif ( apply_filters( 'cf_images_module_enabled', false, 'full-offload' ) ) : ?>
							<a href="#" class="dropdown-item cf-images-delete" data-id="<?php echo esc_attr( $post_id ); ?>">
								<img src="<?php echo esc_url( CF_IMAGES_DIR_URL . 'assets/images/icons/delete.svg' ); ?>" alt="<?php esc_attr_e( 'Remove from media library', 'cf-images' ); ?>" />
								<?php esc_html_e( 'Delete files on WordPress', 'cf-images' ); ?>
							</a>
						<?php endif; ?>
					<?php else : ?>
						<a href="#" class="dropdown-item cf-images-offload" data-id="<?php echo esc_attr( $post_id ); ?>">
							<?php if ( $skipped ) : ?>
								<img src="<?php echo esc_url( CF_IMAGES_DIR_URL . 'assets/images/icons/unpause.svg' ); ?>" alt="<?php esc_attr_e( 'Re-upload to Cloudflare', 'cf-images' ); ?>" />
								<?php esc_html_e( 'Re-upload to Cloudflare', 'cf-images' ); ?>
							<?php else : ?>
								<img src="<?php echo esc_url( CF_IMAGES_DIR_URL . 'assets/images/icons/cloud.svg' ); ?>" alt="<?php esc_attr_e( 'Upload to Cloudflare', 'cf-images' ); ?>" />
								<?php esc_html_e( 'Upload to Cloudflare', 'cf-images' ); ?>
							<?php endif; ?>
						</a>
						<?php if ( ! $skipped ) : ?>
							<a href="#" class="dropdown-item cf-images-skip" data-id="<?php echo esc_attr( $post_id ); ?>">
								<img src="<?php echo esc_url( CF_IMAGES_DIR_URL . 'assets/images/icons/pause.svg' ); ?>" alt="<?php esc_attr_e( 'Ignore and skip image', 'cf-images' ); ?>" />
								<?php esc_html_e( 'Ignore and skip image', 'cf-images' ); ?>
							</a>
						<?php endif; ?>
					<?php endif; ?>
					<?php if ( apply_filters( 'cf_images_module_enabled', false, 'image-ai' ) ) : ?>
						<a href="#" class="dropdown-item cf-images-ai-alt" data-id="<?php echo esc_attr( $post_id ); ?>">
							<img src="<?php echo esc_url( CF_IMAGES_DIR_URL . 'assets/images/icons/wand.svg' ); ?>" alt="<?php esc_attr_e( 'Generate alt text', 'cf-images' ); ?>" />
							<?php esc_html_e( 'Generate alt text', 'cf-images' ); ?>
						</a>
					<?php endif; ?>
					<?php do_action( 'cf_images_media_module_actions', (int) $post_id ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Add offload status for the media library grid view.
	 *
	 * @since 1.2.0
	 *
	 * @param array   $response   Array of prepared attachment data. @see wp_prepare_attachment_for_js().
	 * @param WP_Post $attachment Attachment object.
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
	 * @since 1.0.1 Combined from ajax_remove_images() and ajax_upload_images().
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

		$supported_actions = apply_filters( 'cf_images_bulk_actions', array( 'upload', 'remove' ) );
		if ( ! in_array( $action, $supported_actions, true ) ) {
			wp_send_json_error( esc_html__( 'Unsupported action', 'cf-images' ) );
		}

		// Progress just started.
		if ( 0 === $step && 0 === $total ) {
			$args = $this->get_wp_query_args( $action );

			// Look for images that have been offloaded.
			$images = new WP_Query( $args );

			// No available images found.
			if ( 0 === $images->found_posts ) {
				if ( in_array( $action, array( 'upload', 'remove' ), true ) ) {
					$this->fetch_stats( new Api\Image() );
				}
				wp_send_json_error( __( 'No new images to process', 'cf-images' ) );
			}

			$total = $images->found_posts;
		}

		++$step;

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
				}
			}
		} elseif ( 'remove' === $action ) {
			$this->remove_from_cloudflare( $image->post->ID );
		}

		do_action( 'cf_images_bulk_step', $image->post->ID, $action );

		// On final step - update API stats.
		if ( $step === $total && in_array( $action, array( 'upload', 'remove' ), true ) ) {
			$this->fetch_stats( new Api\Image() );
		}

		$response = array(
			'step'  => $step,
			'total' => $total,
			'stats' => $this->get_stats(),
		);

		// Eventually we should move all errors into the log module, for now - just reset the error.
		do_action( 'cf_images_error', 0, '' );

		wp_send_json_success( $response );
	}

	/**
	 * Skip image from processing.
	 *
	 * @since 1.1.2
	 */
	public function ajax_skip_image() {
		$this->check_ajax_request();

		$attachment_id = (int) filter_input( INPUT_POST, 'data', FILTER_SANITIZE_NUMBER_INT );

		update_post_meta( $attachment_id, '_cloudflare_image_skip', true );

		wp_send_json_success( $this->get_response_data( $attachment_id ) );
	}

	/**
	 * Update the image on Cloudflare after editing.
	 *
	 * @since 1.9.6
	 *
	 * @param array $data          Array of updated attachment meta data.
	 * @param int   $attachment_id Attachment post ID.
	 */
	public function update_image( array $data, int $attachment_id ) {
		// Only process image editor updates.
		if ( ! doing_action( 'wp_ajax_image-editor' ) ) {
			return $data;
		}

		$this->upload_image( $data, $attachment_id, 'replace' );

		return $data;
	}

	/**
	 * Upload to Cloudflare images.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $metadata      An array of attachment meta data.
	 * @param int    $attachment_id Current attachment ID.
	 * @param string $action        Image action.
	 *
	 * @return array
	 */
	public function upload_image( $metadata, int $attachment_id, string $action = '' ): array {
		if ( ! isset( $metadata['file'] ) ) {
			update_post_meta( $attachment_id, '_cloudflare_image_skip', true );
			do_action( 'cf_images_error', 404, __( 'Media file not found', 'cf-images' ) );
			do_action(
				'cf_images_log',
				sprintf( /* translators: %d: attachment ID */
					esc_html__( 'Unable to offload image. Media file not found. Attachment ID: %d.', 'cf-images' ),
					absint( $attachment_id )
				)
			);
			return $metadata;
		}

		// This is used with WPML integration.
		$attachment_id = apply_filters( 'cf_images_media_post_id', $attachment_id );

		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			update_post_meta( $attachment_id, '_cloudflare_image_skip', true );
			do_action( 'cf_images_error', 415, __( 'Unsupported media type', 'cf-images' ) );
			do_action(
				'cf_images_log',
				sprintf( /* translators: %d: attachment ID */
					esc_html__( 'Unable to offload image. Unsupported media type. Attachment ID: %d.', 'cf-images' ),
					absint( $attachment_id )
				)
			);
			return $metadata;
		}

		$image = new Api\Image();
		$dir   = wp_get_upload_dir();
		$path  = wp_get_original_image_path( $attachment_id );

		if ( file_exists( $path ) && ( MB_IN_BYTES * 20 ) <= filesize( $path ) ) {
			$path = get_attached_file( $attachment_id );
		}

		$url = wp_parse_url( get_site_url() );
		if ( is_multisite() && ! is_subdomain_install() && isset( $url['path'] ) ) {
			$host = $url['host'] . $url['path'];
		} else {
			$host = $url['host'];
		}

		/**
		 * This filters allows modifying the host slug in the image path that is used to identify the image on Cloudflare.
		 *
		 * @since 1.9.2
		 *
		 * @param string $host          Site domain.
		 * @param int    $attachment_id Attachment ID.
		 */
		$host = apply_filters( 'cf_images_upload_host', $host, $attachment_id );

		$name = ( $host ? trailingslashit( $host ) : '' ) . str_replace( trailingslashit( $dir['basedir'] ), '', $path );

		try {
			// This allows us to replace the image on Cloudflare.
			if ( 'replace' === $action ) {
				// But first, we must remove it.
				$this->remove_from_cloudflare( $attachment_id );
			}

			$results = $image->upload( $path, $attachment_id, $name );
			$this->increment_stat( 'synced' );
			delete_post_meta( $attachment_id, '_cloudflare_image_skip' );
			update_post_meta( $attachment_id, '_cloudflare_image_id', $results->id );
			$this->maybe_save_hash( $results->variants );

			do_action( 'cf_images_upload_success', $attachment_id, $results );

			if ( doing_filter( 'wp_async_wp_generate_attachment_metadata' ) ) {
				$this->fetch_stats( new Api\Image() );
			}
		} catch ( Exception $e ) {
			do_action( 'cf_images_error', $e->getCode(), $e->getMessage() );
			do_action(
				'cf_images_log',
				sprintf( /* translators: %1$d: attachment ID, %2$s: error message */
					esc_html__( 'Unable to offload image. Attachment ID: %1$d. Error: %2$s', 'cf-images' ),
					absint( $attachment_id ),
					esc_html( $e->getMessage() )
				)
			);
		}

		return $metadata;
	}

	/**
	 * Fires before an attachment is deleted, at the start of wp_delete_attachment().
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Attachment ID.
	 */
	public function remove_from_cloudflare( int $post_id ) {
		$id = get_post_meta( $post_id, '_cloudflare_image_id', true );

		if ( ! $id ) {
			return;
		}

		$image = new Api\Image();

		try {
			$image->delete( $id );
			$this->decrement_stat( 'synced' );
			delete_post_meta( $post_id, '_cloudflare_image_id' );
			delete_post_meta( $post_id, '_cloudflare_image_skip' );

			do_action( 'cf_images_remove_success', $post_id );

			if ( doing_action( 'delete_attachment' ) ) {
				$this->fetch_stats( new Api\Image() );
			}
		} catch ( Exception $e ) {
			do_action( 'cf_images_error', $e->getCode(), $e->getMessage() );
			do_action(
				'cf_images_log',
				sprintf( /* translators: %1$d: attachment ID, %2$s: error message */
					esc_html__( 'Unable to remove image from Cloudflare. Attachment ID: %1$d. Error: %2$s', 'cf-images' ),
					absint( $post_id ),
					esc_html( $e->getMessage() )
				)
			);
		}
	}

	/**
	 * Remove selected image from Cloudflare Images.
	 *
	 * @since 1.3.0
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
	 * @param int|string|null $attachment_id Attachment ID.
	 */
	public function ajax_delete_image( $attachment_id = null ) {
		$this->check_ajax_request();

		if ( empty( $attachment_id ) ) {
			$attachment_id = (int) filter_input( INPUT_POST, 'data', FILTER_SANITIZE_NUMBER_INT );
		}

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

			if ( isset( $results ) && false !== strpos( $results->result->filename, $metadata['file'] ) ) {
				wp_send_json_error( esc_html__( 'Cannot remove image, scaled image offloaded.', 'cf-images' ) );
			}
		}

		$this->delete_image( $attachment_id );

		if ( 'cf_images_delete_image' === filter_input( INPUT_POST, 'action', FILTER_UNSAFE_RAW ) ) {
			wp_send_json_success( $this->get_response_data( $attachment_id ) );
		}
	}

	/**
	 * Delete image from WordPress media library.
	 *
	 * @since 1.2.1
	 *
	 * @param int $attachment_id Attachment ID.
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

		// Set fully-offloaded flag.
		update_post_meta( $attachment_id, '_cloudflare_image_offloaded', true );
	}

	/**
	 * Remove image from uploads directory.
	 *
	 * @since 1.2.1
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $image         Path in uploads folder.
	 * @param bool   $scaled        Whether image is scaled.
	 */
	private function delete( int $attachment_id, string $image, bool $scaled = false ) {
		if ( $scaled ) {
			$uploads = wp_get_upload_dir();
			if ( ! empty( $uploads['basedir'] ) ) {
				$path = trailingslashit( $uploads['basedir'] ) . $image;
				if ( file_exists( $path ) ) {
					wp_delete_file( $path );
				}
			}
			return;
		}

		$path = trailingslashit( dirname( get_attached_file( $attachment_id ) ) ) . $image;
		if ( file_exists( $path ) ) {
			wp_delete_file( $path );
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
	public function get_response_data( int $attachment_id ): string {
		ob_start();
		$this->media_custom_column( 'cf-images-status', $attachment_id );
		return ob_get_clean();
	}

	/**
	 * Restore image to media library from Cloudflare.
	 *
	 * @since 1.2.1
	 *
	 * @param int|string|null $attachment_id Attachment ID.
	 */
	public function ajax_restore_image( $attachment_id = null ) {
		$this->check_ajax_request();

		if ( empty( $attachment_id ) ) {
			$attachment_id = (int) filter_input( INPUT_POST, 'data', FILTER_SANITIZE_NUMBER_INT );
		}

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

			file_put_contents( $original, $image_blob ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			wp_create_image_subsizes( $original, $attachment_id );
		} catch ( Exception $e ) {
			do_action( 'cf_images_error', $e->getCode(), $e->getMessage() );
			do_action(
				'cf_images_log',
				sprintf( /* translators: %1$d: attachment ID, %2$s: error message */
					esc_html__( 'Unable to restore image. Attachment ID: %1$d. Error: %2$s', 'cf-images' ),
					absint( $attachment_id ),
					esc_html( $e->getMessage() )
				)
			);
		}

		delete_post_meta( $attachment_id, '_cloudflare_image_offloaded' );

		if ( 'cf_images_restore_image' === filter_input( INPUT_POST, 'action', FILTER_UNSAFE_RAW ) ) {
			wp_send_json_success( $this->get_response_data( $attachment_id ) );
		}
	}

	/**
	 * Add the Optimization column to sortable list.
	 *
	 * @since 1.6.0
	 *
	 * @param array $columns Columns array.
	 *
	 * @return array
	 */
	public function sortable_column( array $columns ): array {
		$columns['cf-images-status'] = array(
			'cf_offload_status',
			__( 'Optimization', 'cf-images' ),
			false,
			__( 'Table ordered by optimization status', 'cf-images' ),
		);

		return $columns;
	}

	/**
	 * Add filters to the media library.
	 *
	 * @since 1.6.0
	 */
	public function add_filter_dropdown() {
		$screen = get_current_screen();

		if ( 'upload' !== $screen->base ) {
			return;
		}

		$filter = filter_input( INPUT_GET, 'cf_images_filter', FILTER_SANITIZE_SPECIAL_CHARS );

		?>
		<label for="optimization-filter" class="screen-reader-text">
			<?php esc_html_e( 'Filter by optimization status', 'cf-images' ); ?>
		</label>
		<select name="cf_images_filter" id="optimization-filter">
			<option value="" <?php selected( $filter, '' ); ?>><?php esc_html_e( 'Optimization: All images', 'cf-images' ); ?></option>
			<option value="offloaded" <?php selected( $filter, 'offloaded' ); ?>><?php esc_html_e( 'Optimization: Offloaded', 'cf-images' ); ?></option>
			<option value="not-offloaded" <?php selected( $filter, 'not-offloaded' ); ?>><?php esc_html_e( 'Optimization: Not offloaded', 'cf-images' ); ?></option>
			<option value="skipped" <?php selected( $filter, 'skipped' ); ?>><?php esc_html_e( 'Optimization: Skipped', 'cf-images' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Intercept the query and add meta_query values for our filters.
	 *
	 * @since 1.6.0
	 *
	 * @param WP_Query $query The WP_Query instance (passed by reference).
	 *
	 * @return WP_Query
	 */
	public function orderby_column( WP_Query $query ): WP_Query {
		global $current_screen;

		// Filter only media screen.
		if ( ! is_admin() || ( ! empty( $current_screen ) && 'upload' !== $current_screen->base ) ) {
			return $query;
		}

		$filter = filter_input( INPUT_GET, 'cf_images_filter', FILTER_SANITIZE_SPECIAL_CHARS );

		if ( empty( $filter ) ) {
			return $query;
		}

		// Offloaded.
		if ( 'offloaded' === $filter ) {
			$query->set( 'meta_key', '_cloudflare_image_id' );
			return $query;
		}

		// Not offloaded.
		if ( 'not-offloaded' === $filter ) {
			$args = $this->get_wp_query_args( 'upload' );
			$query->set( 'meta_query', $args['meta_query'] );
			return $query;
		}

		// Skipped.
		if ( 'skipped' === $filter ) {
			$query->set( 'meta_key', '_cloudflare_image_skip' );
			return $query;
		}

		return $query;
	}

	/**
	 * Filters the items in the bulk actions menu of the list table.
	 *
	 * @since 1.9.4
	 *
	 * @param array $actions An array of the available bulk actions.
	 *
	 * @return array
	 */
	public function bulk_media_actions( array $actions ): array {
		$actions['cf-offload'] = esc_html__( 'Upload to Cloudflare', 'cf-images' );

		return $actions;
	}

	/**
	 * Fires when a custom bulk action should be handled.
	 *
	 * @since 1.9.4
	 *
	 * @param string $redirect_to The redirect URL.
	 * @param string $action      The action being taken.
	 * @param array  $items       The items to take the action on. Accepts an array of IDs of attachments.
	 */
	public function bulk_action_handler( string $redirect_to, string $action, array $items ): string {
		if ( 'cf-offload' !== $action || empty( $items ) ) {
			return $redirect_to;
		}

		foreach ( $items as $id ) {
			$this->upload_image( wp_get_attachment_metadata( $id ), $id );
		}

		return add_query_arg( 'cf_offload_done', count( $items ), $redirect_to );
	}

	/**
	 * Show success notice.
	 *
	 * @since 1.9.4
	 */
	public function bulk_action_admin_notice() {
		$items_count = filter_input( INPUT_GET, 'cf_offload_done', FILTER_SANITIZE_NUMBER_INT );

		if ( ! $items_count ) {
			return;
		}

		printf(
			'<div id="message" class="updated notice is-dismissible"><p>' .
			/* translators: %d - number of items processed */
			esc_html__( '%d image(s) offloaded to Cloudflare.', 'cf-images' ) . '</p></div>',
			(int) $items_count
		);

		// Remove the query parameter after showing the notice.
		$current_url = remove_query_arg( 'cf_offload_done' );
		echo '<script type="text/javascript">
			if (history.pushState) {
				const newurl = "' . esc_url_raw( $current_url ) . '";
				window.history.pushState({path: newurl}, "", newurl);
			}
		  </script>';
	}
}
