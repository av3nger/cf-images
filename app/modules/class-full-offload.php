<?php
/**
 * Full offload for the media library
 *
 * Allow removing images from media library.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 *
 * @since 1.2.1
 */

namespace CF_Images\App\Modules;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Full_Offload class.
 *
 * @since 1.2.1
 */
class Full_Offload extends Module {

	/**
	 * Register UI components.
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	protected function register_ui() {
		$this->experimental = true;
		$this->icon         = 'images-alt2';
		$this->order        = 60;
		$this->title        = esc_html__( 'Full offload', 'cf-images' );
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
			<?php esc_html_e( 'Setting this option will allow removing original images from the media library.', 'cf-images' ); ?>
		</p>

		<div class="notice notice-warning full-offload-notice">
			<p>

				<?php esc_html_e( 'By enabling this feature, you understand the potential risks of removing media files from the media library. Please ensure you have a backup of your media library.', 'cf-images' ); ?>
			</p>
			<p>
				<?php esc_html_e( 'Note: Previous versions of the plugin offloaded a scaled version to Cloudflare. Scaled images will not restore to original full-size images. Bulk remove all the images from Cloudflare and bulk re-upload before using this feature.' ); ?>
			</p>
		</div>
		<?php

	}

	/**
	 * Init the module.
	 *
	 * @since 1.2.1
	 *
	 * @return void
	 */
	public function init() {

	}

}
