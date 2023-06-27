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

use CF_Images\App\Core;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Auto_Offload class.
 *
 * @since 1.3.0
 */
class Auto_Offload extends Module {

	/**
	 * Register UI components.
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	protected function register_ui() {
		$this->icon  = 'admin-site';
		$this->title = esc_html__( 'Auto offload new images', 'cf-images' );
	}

	/**
	 * Render module description.
	 *
	 * @since 1.4.0
	 *
	 * @param string $module  Module ID.
	 *
	 * @return void
	 */
	public function render_description( string $module ) {

		if ( $module !== $this->module ) {
			return;
		}
		?>
		<p>
			<?php esc_html_e( 'Enable this option if you want to enable automatic offloading for newly uploaded images.', 'cf-images' ); ?>
		</p>
		<p>
			<?php esc_html_e( 'By default, new images will not be auto offloaded to Cloudflare Images.', 'cf-images' ); ?>
		</p>
		<?php

	}

	/**
	 * Init the module.
	 *
	 * @since 1.3.0
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'auto_offload' ) );
	}

	/**
	 * Run auto offload.
	 *
	 * @since 1.3.0
	 *
	 * @return void
	 */
	public function auto_offload() {

		$media = Core::get_instance()->admin()->media();

		// If async uploads are disabled, use the default hook.
		if ( get_option( 'cf-images-disable-async', false ) ) {
			add_filter( 'wp_generate_attachment_metadata', array( $media, 'upload_image' ), 10, 2 );
		} else {
			add_filter( 'wp_async_wp_generate_attachment_metadata', array( $media, 'upload_image' ), 10, 2 );
		}

	}

}
