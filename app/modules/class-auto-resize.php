<?php
/**
 * Auto-resize module
 *
 * This class defines all code necessary, required for auto resizing images on the front-end.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.3.0  Moved out into its own module.
 */

namespace CF_Images\App\Modules;

use CF_Images\App\Image;
use CF_Images\App\Traits\Helpers;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Auto_Resize class.
 *
 * @since 1.3.0
 */
class Auto_Resize extends Module {
	use Helpers;

	/**
	 * Should the module only run on front-end?
	 *
	 * @since 1.3.0
	 * @access protected
	 *
	 * @var bool
	 */
	protected $only_frontend = true;

	/**
	 * Init the module.
	 *
	 * @since 1.3.0
	 */
	public function init() {
		add_filter( 'cf_images_replace_paths', array( $this, 'add_srcset_to_image' ), 10, 2 );
	}

	/**
	 * Add srcset and sizes attributes to the image markup.
	 *
	 * @since 1.5.0
	 *
	 * @param string $image_dom Image markup.
	 * @param Image  $image     Image object.
	 *
	 * @return string
	 */
	public function add_srcset_to_image( string $image_dom, Image $image ): string {
		if ( empty( $image->get_src() ) || ! empty( $image->get_srcset() ) ) {
			return $image_dom;
		}

		if ( empty( $image->get_cf_image() ) ) {
			return $image_dom;
		}

		/**
		 * 1. Get src image with hash.
		 * 2. Extract image ID and w= attribute value.
		 * 3. Generate intermediate sizes.
		 */

		$sizes  = array( 320, 480, 768, 1024, 1280, 1536, 1920, 2048 );
		$srcset = array();
		foreach ( $sizes as $size ) {
			if ( ( $image->get_width() - $size ) < 50 ) {
				break;
			}

			$srcset[] = $image->get_cf_image() . 'w=' . $size . ' ' . $size . 'px';
		}

		unset( $size );

		if ( empty( $srcset ) ) {
			return $image_dom;
		}

		// Add the original image to the srcset.
		$srcset[] = $image->get_cf_image() . 'w=' . $image->get_width() . ' ' . $image->get_width() . 'px';

		// Check if there is already a 'sizes' attribute.
		$sizes = strpos( $image_dom, ' sizes=' );

		if ( ! $sizes ) {
			$sizes = wp_calculate_image_sizes( array( $image->get_width() ), $image->get_src(), null, $image->get_id() );
		}

		if ( $sizes && is_string( $sizes ) ) {
			$attr = sprintf( ' sizes="%s"', esc_attr( $sizes ) );

			// Add the srcset and sizes attributes to the image markup.
			$image_dom = preg_replace( '/<img ([^>]+?)[\/ ]*>/', '<img $1' . $attr . ' />', $image_dom );
			unset( $sizes );
		}

		$attr = sprintf( ' srcset="%s"', esc_attr( implode( ', ', $srcset ) ) );
		return preg_replace( '/<img ([^>]+?)[\/ ]*>/', '<img $1' . $attr . ' />', $image_dom );
	}
}
