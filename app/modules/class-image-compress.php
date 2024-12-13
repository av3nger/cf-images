<?php
/**
 * Image compression
 *
 * Allow compressing images via the Fuzion API.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 *
 * @since 1.5.0
 */

namespace CF_Images\App\Modules;

use CF_Images\App\Api\Compress;
use CF_Images\App\Traits;
use Exception;
use WP_Error;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Image_Compress class.
 *
 * @since 1.5.0
 */
class Image_Compress extends Module {
	use Traits\Ajax;
	use Traits\Stats;

	/**
	 * Default stats values.
	 */
	const STATS = array(
		'size_before' => 0,
		'size_after'  => 0,
	);

	/**
	 * Init the module.
	 *
	 * @since 1.5.0
	 */
	public function init() {
		add_action( 'cf_images_media_custom_column', array( $this, 'add_stats_to_media_library' ) );
		add_action( 'cf_images_media_module_actions', array( $this, 'media_lib_actions' ) );

		// Bulk compress actions.
		add_filter( 'cf_images_bulk_actions', array( $this, 'add_bulk_action' ) );
		add_filter( 'cf_images_wp_query_args', array( $this, 'add_wp_query_args' ), 10, 2 );
		add_action( 'cf_images_bulk_step', array( $this, 'bulk_step' ), 10, 2 );

		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_cf_images_compress', array( $this, 'ajax_compress' ) );
		}
	}

	/**
	 * Add stats to media library.
	 *
	 * @since 1.5.0
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function add_stats_to_media_library( int $attachment_id ) {
		$stats = get_post_meta( $attachment_id, '_cf_images_stats', true );

		if ( ! $stats ) {
			return;
		}

		if ( ! isset( $stats['size_before'] ) || ! isset( $stats['size_after'] ) ) {
			return;
		}

		echo '<br>';

		$savings = $stats['size_before'] - $stats['size_after'];
		printf( /* translators: %1$s - savings, %2$s - savings in percent */
			esc_html__( 'Savings: %1$s', 'cf-images' ),
			esc_html( $this->format_bytes( $savings ) )
		);
	}

	/**
	 * Add media library action to dropdown menu.
	 *
	 * @since 1.5.0
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function media_lib_actions( int $attachment_id ) {
		if ( ! apply_filters( 'cf_images_module_enabled', false, 'image-compress' ) ) {
			return;
		}

		$mime_type = get_post_mime_type( $attachment_id );
		if ( ! in_array( $mime_type, array( 'image/jpeg', 'image/png' ), true ) ) {
			return;
		}

		if ( get_post_meta( $attachment_id, '_cf_images_compressed', true ) ) {
			return;
		}

		if ( $this->all_sizes_compressed( $attachment_id ) ) {
			return;
		}
		?>
		<a href="#" class="dropdown-item cf-images-compress" data-id="<?php echo esc_attr( $attachment_id ); ?>">
			<img src="<?php echo esc_url( CF_IMAGES_DIR_URL . 'assets/images/icons/minimize.svg' ); ?>" alt="<?php esc_attr_e( 'Compress image', 'cf-images' ); ?>" />
			<?php esc_html_e( 'Compress image', 'cf-images' ); ?>
		</a>
		<?php
	}

	/**
	 * Check if all image sizes have been compressed.
	 *
	 * @since 1.5.0
	 *
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return bool
	 */
	private function all_sizes_compressed( int $attachment_id ): bool {
		$stats = get_post_meta( $attachment_id, '_cf_images_stats', true );
		if ( empty( $stats ) ) {
			return false;
		}

		$metadata = wp_get_attachment_metadata( $attachment_id, true );

		$attachment_sizes = count( $metadata['sizes'] ) + isset( $metadata['file'] ) + isset( $metadata['original_image'] );
		return count( $stats['sizes'] ) === $attachment_sizes;
	}

	/**
	 * Extend bulk action so that the AJAX callback accepts the bulk compress requests.
	 *
	 * @since 1.5.0
	 * @see Media::ajax_bulk_process()
	 *
	 * @param array $actions Supported actions.
	 *
	 * @return array
	 */
	public function add_bulk_action( array $actions ): array {
		if ( ! in_array( 'compress', $actions, true ) ) {
			$actions[] = 'compress';
		}

		return $actions;
	}

	/**
	 * Adjust the WP_Query args for bulk compress action.
	 *
	 * @since 1.5.0
	 * @see Ajax::get_wp_query_args()
	 *
	 * @param array  $args   WP_Query args.
	 * @param string $action Executing action.
	 *
	 * @return array
	 */
	public function add_wp_query_args( array $args, string $action ): array {
		if ( 'compress' !== $action ) {
			return $args;
		}

		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'     => '_cf_images_compressed',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => '_cf_images_skip_compress',
				'compare' => 'NOT EXISTS',
			),
		);

		return $args;
	}

	/**
	 * Perform bulk step.
	 *
	 * @since 1.5.0
	 * @see Media::ajax_bulk_process()
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $action        Executing action.
	 */
	public function bulk_step( int $attachment_id, string $action ) {
		if ( 'compress' !== $action ) {
			return;
		}

		$results = $this->compress( $attachment_id );
		if ( is_wp_error( $results ) ) {
			update_post_meta( $attachment_id, '_cf_images_skip_compress', true );
		}
	}

	/**
	 * Compress image.
	 *
	 * @since 1.5.0
	 */
	public function ajax_compress() {
		$this->check_ajax_request();

		$attachment_id = (int) filter_input( INPUT_POST, 'data', FILTER_SANITIZE_NUMBER_INT );

		if ( ! $attachment_id ) {
			wp_send_json_error( __( 'Attachment ID not defined.', 'cf-images' ) );
			return;
		}

		$results = $this->compress( $attachment_id );
		if ( is_wp_error( $results ) ) {
			wp_send_json_error( $results->get_error_message() );
			return;
		}

		wp_send_json_success( $this->media()->get_response_data( $attachment_id ) );
	}

	/**
	 * Do compression on single image.
	 *
	 * @since 1.5.0
	 *
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return void|WP_Error Returns true on success or WP_Error on failure.
	 */
	private function compress( int $attachment_id ) {
		// Check if supported format.
		$mime_type = get_post_mime_type( $attachment_id );
		if ( ! in_array( $mime_type, array( 'image/jpeg', 'image/png' ), true ) ) {
			do_action( 'cf_images_log', 'Compression error: Unsupported format. Attachment ID: %s.', $attachment_id );
			return new WP_Error( 'unsupported_format', __( 'Unsupported format.', 'cf-images' ) );
		}

		try {
			$images  = $this->get_paths( $attachment_id );
			$results = ( new Compress() )->optimize( $images, $mime_type );

			if ( ! empty( $results ) ) {
				$this->update_images_and_stats( $attachment_id, $images, $results );
			}

			update_post_meta( $attachment_id, '_cf_images_compressed', true );
		} catch ( Exception $e ) {
			do_action( 'cf_images_log', 'Compression error: %s Attachment ID: %s.', $e->getMessage(), $attachment_id );
			return new WP_Error( 'compress_error', $e->getMessage() );
		}
	}

	/**
	 * Update image files and stats.
	 *
	 * @since 1.5.0
	 *
	 * @param int   $attachment_id Attachment ID.
	 * @param array $images        Image paths.
	 * @param array $results       Results.
	 */
	private function update_images_and_stats( int $attachment_id, array $images, array $results ) {
		$image_stats  = get_post_meta( $attachment_id, '_cf_images_stats', true );
		$global_stats = $this->get_stats();

		if ( ! $image_stats ) {
			$image_stats = self::STATS;
		}

		foreach ( $results as $size => $response ) {
			if ( ! isset( $images[ $size ] ) || ! $this->write_file( $images[ $size ], $response['image'] ) ) {
				continue;
			}

			// Only save stats if we were able to save the file.
			$stats = $this->get_stats_from_response( $response['stats'] );

			$image_stats['size_before'] += $stats['o'] ?? 0;
			$image_stats['size_after']  += $stats['c'] ?? 0;

			$global_stats['size_before'] = ( $global_stats['size_before'] ?? 0 ) + ( $stats['o'] ?? 0 );
			$global_stats['size_after']  = ( $global_stats['size_after'] ?? 0 ) + ( $stats['c'] ?? 0 );

			$image_stats['sizes'][ $size ] = array(
				'size_before' => $stats['o'] ?? 0,
				'size_after'  => $stats['c'] ?? 0,
			);
		}

		update_option( 'cf-images-stats', $global_stats );
		update_post_meta( $attachment_id, '_cf_images_stats', $image_stats );
	}

	/**
	 * Get a list of image paths for a given attachment.
	 *
	 * @since 1.5.0
	 *
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return string[]
	 */
	private function get_paths( int $attachment_id ): array {
		$original  = wp_get_original_image_path( $attachment_id );
		$image_dir = dirname( $original );
		$metadata  = wp_get_attachment_metadata( $attachment_id, true );
		$db_stats  = get_post_meta( $attachment_id, '_cf_images_stats', true );

		if ( ! isset( $db_stats['sizes'] ) || ! isset( $db_stats['sizes']['full'] ) ) {
			/**
			 * TODO: until there's a way to rollback - do not compress full size.
			 * Once this is ready to be enabled - remove the if statement.
			 * Fix the commented out code below as well.
			 */
			if ( strpos( $metadata['file'], '-scaled' ) !== false ) {
				$paths = array(
					'full' => path_join( wp_get_upload_dir()['basedir'], $metadata['file'] ),
				);
			}
		}

		if ( ! empty( $metadata['sizes'] ) ) {
			foreach ( $metadata['sizes'] as $size => $image_meta ) {
				if ( apply_filters( 'cf_images_skip_compress_size', false, $size, $attachment_id ) ) {
					continue;
				}

				if ( isset( $db_stats['sizes'][ $size ] ) ) {
					continue;
				}

				$file_path = path_join( $image_dir, $image_meta['file'] );

				if ( ! file_exists( $file_path ) ) {
					continue;
				}

				$paths[ $size ] = $file_path;
			}
		}

		/** Full size will often be a '-scaled' image, make sure we always have the original.
		if ( ( ! isset( $paths['full'] ) || $paths['full'] !== $original ) && ! isset( $db_stats['sizes']['original'] ) ) {
			$paths['original'] = $original;
		}
		*/

		return $paths ?? array();
	}

	/**
	 * Convert stats header into an array of values.
	 *
	 * @param string $stats Stats header.
	 *
	 * @return array {
	 *     @type int $o Original file size.
	 *     @type int $c Compressed file size.
	 * }
	 */
	private function get_stats_from_response( string $stats ): array {
		$result = array();

		if ( empty( $stats ) ) {
			return $result;
		}

		$pairs = explode( ',', $stats );
		foreach ( $pairs as $pair ) {
			list( $key, $value ) = explode( '=', $pair );

			$result[ $key ] = (int) $value;
		}

		return $result;
	}

	/**
	 * Write file.
	 *
	 * @since 1.5.0
	 *
	 * @param string $original_path Original image path.
	 * @param string $new_image     New image data.
	 *
	 * @return bool
	 */
	private function write_file( string $original_path, string $new_image ): bool {
		$temp_file = wp_tempnam( basename( $original_path ) );

		if ( ! $temp_file ) {
			return false;
		}

		file_put_contents( $temp_file, $new_image ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		$success = rename( $temp_file, $original_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( ! $success ) {
			copy( $temp_file, $original_path );
		}

		if ( file_exists( $temp_file ) ) {
			wp_delete_file( $temp_file );
		}

		return true;
	}

	/**
	 * Convert bytes to a readable format.
	 *
	 * @since 1.5.0
	 *
	 * @param int $bytes     Bytes.
	 * @param int $precision Precision.
	 *
	 * @return string
	 */
	private function format_bytes( int $bytes, int $precision = 2 ): string {
		$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );

		$bytes = max( $bytes, 0 );
		$pow   = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
		$pow   = min( $pow, count( $units ) - 1 );

		$bytes /= ( 1 << ( 10 * $pow ) );

		return round( $bytes, $precision ) . ' ' . $units[ $pow ];
	}
}
