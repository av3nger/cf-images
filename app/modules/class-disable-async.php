<?php
/**
 * Disable async processing
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 *
 * @since 1.4.0 Moved to a separate module
 */

namespace CF_Images\App\Modules;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Disable_Async class.
 *
 * @since 1.4.0
 */
class Disable_Async extends Module {

	/**
	 * Register UI components.
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	protected function register_ui() {
		$this->icon  = 'randomize';
		$this->order = 70;
		$this->title = esc_html__( 'Disable async processing', 'cf-images' );
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
			<?php esc_html_e( 'By default, the plugin will try to offload images in asynchronous mode, meaning that the processing will be done in the background. If, for some reason, the host does not allow async processing, disable this option for backward compatibility.', 'cf-images' ); ?>
		</p>
		<p>
			<?php esc_html_e( 'Note: disabling this option will increase the time to upload new images to the media library.', 'cf-images' ); ?>
		</p>
		<?php

	}

	/**
	 * Init the module.
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	public function init() {}

}
