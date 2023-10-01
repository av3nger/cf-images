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
		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_cf_images_compress', array( $this, 'ajax_compress' ) );
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

		// Check if supported format.
		$mime_type = get_post_mime_type( $attachment_id );
		if ( ! in_array( $mime_type, array( 'image/jpeg', 'image/png' ), true ) ) {
			wp_send_json_error( __( 'Unsupported format.', 'cf-images' ) );
			return;
		}

		$image_path = wp_get_original_image_path( $attachment_id );

		try {
			$response = ( new Compress() )->optimize( $image_path, $mime_type );
			$this->write_file( $image_path, $response );
			wp_send_json_success( $this->media()->get_response_data( $attachment_id ) );
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Write file.
	 *
	 * @since 1.5.0
	 *
	 * @param string $original_path Original image path.
	 * @param string $new_image     New image data.
	 *
	 * @throws Exception Error writing file.
	 */
	private function write_file( string $original_path, string $new_image ) {
		$temp_file = wp_tempnam( basename( $original_path ) );

		if ( ! $temp_file ) {
			throw new Exception( esc_html__( 'Could not create temporary file.', 'cf-images' ) );
		}

		file_put_contents( $temp_file, $new_image ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		// TODO: remove .bak extension.
		$success = rename( $temp_file, $original_path . '.bak' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( ! $success ) {
			copy( $temp_file, $original_path . '.bak' ); // TODO: remove .bak extension.
		}

		if ( file_exists( $temp_file ) ) {
			wp_delete_file( $temp_file );
		}
	}
}
