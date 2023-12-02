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
		add_action( 'admin_init', array( $this, 'auto_offload' ) );
		add_action( 'rest_insert_attachment', array( $this, 'handle_api_upload' ) );
	}

	/**
	 * Run auto offload.
	 *
	 * @since 1.3.0
	 */
	public function auto_offload() {
		// If async uploads are disabled, use the default hook.
		if ( $this->is_module_enabled( false, 'disable-async' ) ) {
			add_filter( 'wp_generate_attachment_metadata', array( $this->media(), 'upload_image' ), 10, 3 );
		} else {
			add_filter( 'wp_async_wp_generate_attachment_metadata', array( $this->media(), 'upload_image' ), 10, 3 );
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
