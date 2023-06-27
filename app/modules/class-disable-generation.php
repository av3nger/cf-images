<?php
/**
 * Disable generation of WordPress image sizes
 *
 * Allow users to disable generation of WordPress image sizes, which will keep only the original file and rely
 * on sizes from Cloudflare Images.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.3.0  Moved out into its own module.
 */

namespace CF_Images\App\Modules;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Disable_Generation class.
 *
 * @since 1.3.0
 */
class Disable_Generation extends Module {

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
		$this->order        = 50;
		$this->title        = esc_html__( 'Disable WordPress image sizes', 'cf-images' );
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
			<?php esc_html_e( 'Setting this option will disable generation of `-scaled` images and other image sizes. Only the original image will be stored in the media library. Only for newly uploaded files, current images will not be affected.', 'cf-images' ); ?>
		</p>
		<p>
			<?php esc_html_e( 'Note: This feature is experimental. All the image sizes can be restored with the `Regenerate Thumbnails` plugin.', 'cf-images' ); ?>
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

		add_filter( 'wp_image_editors', '__return_empty_array' );
		add_filter( 'big_image_size_threshold', '__return_false' );
		add_filter( 'intermediate_image_sizes_advanced', '__return_empty_array' );

	}

}
