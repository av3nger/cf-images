<?php
/**
 * Auto offload new images
 *
 * Allow users to automatically offload images on upload to media library.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.3.0  Moved out into its own module.
 */

namespace CF_Images\App\Modules;

use CF_Images\App\Async\Edit;
use CF_Images\App\Async\Upload;
use CF_Images\App\Media;
use CF_Images\App\Traits\Helpers;
use WP_Post;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Auto_Offload class.
 *
 * @since 1.3.0
 */
class Auto_Offload extends Module {
	use Helpers;

	/**
	 * Init the module.
	 *
	 * @since 1.3.0
	 */
	public function init() {
		add_action( 'init', array( $this, 'auto_offload' ) );
		add_action( 'rest_insert_attachment', array( $this, 'handle_api_upload' ) );
	}

	/**
	 * Run auto offload.
	 *
	 * @since 1.3.0
	 */
	public function auto_offload() {
		$media = is_admin() ? $this->media() : new Media();

		// If async uploads are disabled, use the default hook.
		if ( $this->is_module_enabled( false, 'disable-async' ) ) {
			add_filter( 'wp_generate_attachment_metadata', array( $media, 'upload_image' ), 10, 3 );
			add_filter( 'wp_update_attachment_metadata', array( $media, 'update_image' ), 10, 2 );
		} else {
			if ( ! is_admin() ) {
				$this->ensure_async_handlers();
			}

			add_filter( 'wp_async_wp_generate_attachment_metadata', array( $media, 'upload_image' ), 10, 3 );
			add_filter( 'wp_async_wp_save_image_editor_file', array( $media, 'upload_image' ), 10, 3 );
		}
	}

	/**
	 * Ensure async handlers are loaded on frontend.
	 *
	 * @since 1.9.7
	 */
	private function ensure_async_handlers() {
		if ( ! class_exists( 'CF_Images\App\Async\Upload' ) ) {
			require_once CF_IMAGES_DIR_PATH . 'app/async/class-task.php';
			require_once CF_IMAGES_DIR_PATH . 'app/async/class-upload.php';
			new Upload();
		}

		if ( ! class_exists( 'CF_Images\App\Async\Edit' ) ) {
			require_once CF_IMAGES_DIR_PATH . 'app/async/class-edit.php';
			new Edit();
		}
	}

	/**
	 * Fires after a single attachment is created or updated via the REST API.
	 *
	 * @since 1.7.0
	 *
	 * @param WP_Post $attachment Inserted or updated attachment object.
	 */
	public function handle_api_upload( WP_Post $attachment ) {
		if ( ! $this->is_module_enabled( false, 'offload-rest-api' ) ) {
			return;
		}

		$file = get_attached_file( $attachment->ID );

		if ( empty( $file ) ) {
			return;
		}

		$metadata = array(
			'file' => $file,
		);

		// We need to call upload_image() this way, because it is not available via $this->media().
		( new Media() )->upload_image( $metadata, $attachment->ID );
	}
}
