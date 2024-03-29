<?php
/**
 * Keep media library structure
 *
 * Preserve the WordPress media library paths, when uploading images to Cloudflare Images, instead of the path
 * automatically generated by Cloudflare Images’ Universal Unique Identifier (UUID).
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
 * Custom_Id class.
 *
 * @since 1.3.0
 */
class Custom_Id extends Module {
	/**
	 * Init the module.
	 *
	 * @since 1.3.0
	 */
	public function init() {
		add_filter( 'cf_images_upload_data', array( $this, 'use_custom_image_path' ) );
	}

	/**
	 * Set custom ID for image to use the custom paths in image URLs.
	 *
	 * @since 1.2.0
	 *
	 * @param array $data Image data sent to the Cloudflare Images API.
	 *
	 * @return array
	 */
	public function use_custom_image_path( array $data ): array {
		if ( ! $this->is_module_enabled() ) {
			return $data;
		}

		if ( ! isset( $data['id'] ) && isset( $data['file']->postname ) ) {
			$data['id'] = $data['file']->postname;
		}

		return $data;
	}
}
