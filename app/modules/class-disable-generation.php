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
