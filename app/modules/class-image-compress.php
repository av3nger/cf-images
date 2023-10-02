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
	use Traits\Helpers;

	/**
	 * Register UI components.
	 *
	 * @since 1.4.0
	 */
	protected function register_ui() {
		$this->icon  = 'media-archive';
		$this->new   = true;
		$this->title = esc_html__( 'Image Optimization', 'cf-images' );
	}

	/**
	 * Render module description.
	 *
	 * @since 1.5.0
	 *
	 * @param string $module Module ID.
	 */
	public function render_description( string $module ) {
		if ( $module !== $this->module ) {
			return;
		}
		?>
		<p>
			<?php esc_html_e( 'Compress JPEG/PNG images and reduce the file size. Requires the Image AI API to be connected.', 'cf-images' ); ?>
		</p>
		<?php
	}

	/**
	 * Init the module.
	 *
	 * @since 1.5.0
	 */
	public function init() {
		add_action( 'cf_images_media_custom_column', array( $this, 'add_stats_to_media_library' ) );
		add_action( 'cf_images_media_module_actions', array( $this, 'media_lib_actions' ) );

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
		$stats = get_post_meta( $attachment_id, 'cf_images_compressed', true );

		if ( ! $stats ) {
			return;
		}

		if ( ! isset( $stats['stats']['size_before'] ) || ! isset( $stats['stats']['size_after'] ) ) {
			return;
		}

		echo '<br>';

		$metadata      = wp_get_attachment_metadata( $attachment_id, true );
		$original_size = array_sum( wp_list_pluck( $metadata['sizes'], 'filesize' ) ) + $metadata['filesize'];

		$savings = $stats['stats']['size_before'] - $stats['stats']['size_after'];
		printf( /* translators: %1$s - savings, %2$s - savings in percent */
			esc_html__( 'Savings: %1$s (%2$s)', 'cf-images' ),
			esc_html( $this->format_bytes( $savings ) ),
			esc_html( round( $savings / $original_size * 100, 1 ) ) . '%'
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

		$stats    = get_post_meta( $attachment_id, 'cf_images_compressed', true );
		$metadata = wp_get_attachment_metadata( $attachment_id, true );

		$can_compress = false;
		if ( empty( $stats ) ) {
			$can_compress = true;
		} elseif ( ! empty( $stats['sizes'] ) && ! empty( $metadata['sizes'] ) ) {
			$attachment_sizes = count( $metadata['sizes'] ) + isset( $metadata['file'] ) + isset( $metadata['original_image'] );
			$can_compress     = $attachment_sizes > count( $stats['sizes'] );
		}

		if ( ! $can_compress ) {
			return;
		}
		?>
		<li><a href="#" class="cf-images-compress" data-id="<?php echo esc_attr( $attachment_id ); ?>">
			<img src="<?php echo esc_url( CF_IMAGES_DIR_URL . 'assets/images/icons/minimize.svg' ); ?>" alt="<?php esc_attr_e( 'Compress image', 'cf-images' ); ?>" />
			<?php esc_html_e( 'Compress image', 'cf-images' ); ?>
			</a></li>
		<?php
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

		// Check if supported format.
		$mime_type = get_post_mime_type( $attachment_id );
		if ( ! in_array( $mime_type, array( 'image/jpeg', 'image/png' ), true ) ) {
			wp_send_json_error( __( 'Unsupported format.', 'cf-images' ) );
			return;
		}

		try {
			$images  = $this->get_paths( $attachment_id );
			$results = ( new Compress() )->optimize( $images, $mime_type );

			$db_stats = get_post_meta( $attachment_id, 'cf_images_compressed', true );

			foreach ( $results as $size => $response ) {
				if ( ! isset( $images[ $size ] ) || ! $this->write_file( $images[ $size ], $response['image'] ) ) {
					continue;
				}

				// Only save stats if we were able to save the file.
				$stats = $this->get_stats( $response['stats'] );

				$db_stats['stats']['size_before'] += $stats['o'] ?? 0;
				$db_stats['stats']['size_after']  += $stats['c'] ?? 0;

				$db_stats['sizes'][ $size ] = array(
					'size_before' => $stats['o'] ?? 0,
					'size_after'  => $stats['c'] ?? 0,
				);
			}

			update_post_meta( $attachment_id, 'cf_images_compressed', $db_stats );
			wp_send_json_success( $this->media()->get_response_data( $attachment_id ) );
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
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
		// TODO: make sure we're not trying to compress already compressed images.
		$original  = wp_get_original_image_path( $attachment_id );
		$image_dir = dirname( $original );
		$metadata  = wp_get_attachment_metadata( $attachment_id, true );

		$paths = array(
			'full' => path_join( wp_get_upload_dir()['basedir'], $metadata['file'] ),
		);

		if ( ! empty( $metadata['sizes'] ) ) {
			foreach ( $metadata['sizes'] as $size => $image_meta ) {
				if ( apply_filters( 'cf_images_skip_compress_size', false, $size, $attachment_id ) ) {
					continue;
				}

				$file_path = path_join( $image_dir, $image_meta['file'] );

				if ( ! file_exists( $file_path ) ) {
					continue;
				}

				$paths[ $size ] = $file_path;
			}
		}

		// Full size will often be a '-scaled' image, make sure we always have the original.
		if ( isset( $paths['full'] ) && $paths['full'] !== $original ) {
			$paths['original'] = $original;
		}

		return $paths;
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
	private function get_stats( string $stats ): array {
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
