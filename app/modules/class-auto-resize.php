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

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Auto_Resize class.
 *
 * @since 1.3.0
 */
class Auto_Resize extends Module {

	/**
	 * Register UI components.
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	protected function register_ui() {
		$this->icon  = 'editor-expand';
		$this->order = 40;
		$this->title = esc_html__( 'Auto resize images on front-end', 'cf-images' );
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
			<?php esc_html_e( 'Set the image size to match the DOM required size. Instead of WordPress attachment sizes, this will attempt to match the image size to the element it is placed in on the front-end.', 'cf-images' ); ?>
		</p>
		<?php

	}

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
	 *
	 * @return void
	 */
	public function init() {

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_auto_resize' ) );
		add_filter( 'wp_get_attachment_image_attributes', array( $this, 'add_class_to_attachment' ) );
		add_filter( 'wp_content_img_tag', array( $this, 'add_class_to_img_tag' ), 15 );

	}

	/**
	 * Enqueue auto resize script on front-end.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function enqueue_auto_resize() {
		wp_enqueue_script( $this->get_slug(), CF_IMAGES_DIR_URL . 'assets/js/cf-auto-resize.min.js', array(), CF_IMAGES_VERSION, true );
	}

	/**
	 * Add special class to images that are served via Cloudflare.
	 *
	 * @since 1.2.0
	 * @see wp_get_attachment_image()
	 *
	 * @param string[] $attr  Array of attribute values for the image markup, keyed by attribute name.
	 *
	 * @return string[]
	 */
	public function add_class_to_attachment( array $attr ): array {

		if ( empty( $attr['src'] ) || false === strpos( $attr['src'], $this->get_cdn_domain() ) ) {
			return $attr;
		}

		if ( empty( $attr['class'] ) ) {
			$attr['class'] = 'cf-image-auto-resize';
		} elseif ( false === strpos( $attr['class'], 'cf-image-auto-resize' ) ) {
			$attr['class'] .= ' cf-image-auto-resize';
		}

		return $attr;

	}

	/**
	 * Add special class to images that are served via Cloudflare.
	 *
	 * @since 1.2.0
	 *
	 * @param string $filtered_image  Full img tag with attributes that will replace the source img tag.
	 *
	 * @return string
	 */
	public function add_class_to_img_tag( string $filtered_image ): string {

		if ( ! get_option( 'cf-images-auto-resize', false ) ) {
			return $filtered_image;
		}

		if ( false === strpos( $filtered_image, $this->get_cdn_domain() ) ) {
			return $filtered_image;
		}

		$this->add_resize_class( $filtered_image );

		return $filtered_image;

	}

	/**
	 * Add attribute to selected tag.
	 *
	 * @since 1.2.0
	 *
	 * @param string $element  HTML element.
	 */
	private function add_resize_class( string &$element ) {

		$closing = false === strpos( $element, '/>' ) ? '>' : ' />';
		$quotes  = false === strpos( $element, '"' ) ? '\'' : '"';

		preg_match( "/class=['\"]([^'\"]+)['\"]/i", $element, $current_value );
		if ( ! empty( $current_value['1'] ) ) {
			// Remove the attribute if it already exists.
			$element = preg_replace( '/class=[\'"](.*?)[\'"]/i', '', $element );

			if ( false === strpos( $current_value['1'], 'cf-image-auto-resize' ) ) {
				$value = $current_value['1'] . ' cf-image-auto-resize';
			} else {
				$value = $current_value['1'];
			}

			$element = rtrim( $element, $closing ) . " class=$quotes$value$quotes$closing";
		}

	}

}
