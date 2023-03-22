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
 * @since 1.2.1  Moved out into its own module.
 */

namespace CF_Images\App\Modules;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Auto_Resize class.
 *
 * @since 1.2.1
 */
class Auto_Resize extends Module {

	/**
	 * Module ID.
	 *
	 * @since 1.2.1
	 * @access protected
	 *
	 * @var string $module
	 */
	protected $module = 'auto-resize';

	/**
	 * Init the module.
	 *
	 * @since 1.2.1
	 *
	 * @return void
	 */
	public function init() {
		if ( is_admin() ) { // TODO: add || ! $this->can_run().
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_auto_resize' ) );
		add_filter( 'wp_get_attachment_image_attributes', array( $this, 'add_class_to_attachment' ) );
		add_filter( 'wp_content_img_tag', array( $this, 'add_class_to_img_tag' ), 15 );
	}

}
