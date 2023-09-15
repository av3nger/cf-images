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
	 * @param string $module Module ID.
	 */
	public function render_description( string $module ) {
		if ( $module !== $this->module ) {
			return;
		}
		?>
		<p>
			<?php esc_html_e( 'Make images responsive by adding missing image sizes into the srcset attribute.', 'cf-images' ); ?>
		</p>
		<p>
			<?php esc_html_e( 'Requires the "Parse page for images" module to be enabled.', 'cf-images' ); ?>
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
	 */
	public function init() {
		add_filter( 'cf_images_replace_paths', array( $this, 'add_srcset_to_image' ), 10, 4 );
	}

	/**
	 * Add srcset and sizes attributes to the image markup.
	 *
	 * @since 1.5.0
	 *
	 * @param string $image         Image markup.
	 * @param string $src           Current src attribute value.
	 * @param string $srcset        Current srcset attribute value.
	 * @param int    $attachment_id Attachment ID.
	 *
	 * @return string
	 */
	public function add_srcset_to_image( string $image, string $src, string $srcset, int $attachment_id ): string {
		if ( empty( $src ) || ! empty( $srcset ) ) {
			return $image;
		}

		/**
		 * 1. Get src image with hash.
		 * 2. Extract image ID and w= attribute value.
		 * 3. Generate intermediate sizes.
		 */
		if ( preg_match( '#(https?://[^/]+/[^/]+/[a-zA-Z0-9-]+)/w=(\d+)#', $image, $matches ) ) {
			$sizes  = array( 320, 480, 768, 1024, 1280, 1536, 1920, 2048 );
			$srcset = array(); // Yeah, yeah, I know, we're changing the type.
			foreach ( $sizes as $size ) {
				if ( ( $matches[2] - $size ) < 50 ) {
					break;
				}

				$srcset[] = $matches[1] . '/w=' . $size . ' ' . $size . 'px';
			}
		}

		if ( empty( $srcset ) ) {
			return $image;
		}

		// Add the original image to the srcset.
		$srcset[] = $matches[1] . '/w=' . $matches[2] . ' ' . $matches[2] . 'px';

		// Check if there is already a 'sizes' attribute.
		$sizes = strpos( $image, ' sizes=' );

		if ( ! $sizes ) {
			$sizes = wp_calculate_image_sizes( array( $matches[2] ), $src, null, $attachment_id );
		}

		if ( $sizes && is_string( $sizes ) ) {
			$attr = sprintf( ' sizes="%s"', esc_attr( $sizes ) );

			// Add the srcset and sizes attributes to the image markup.
			$image = preg_replace( '/<img ([^>]+?)[\/ ]*>/', '<img $1' . $attr . ' />', $image );
		}

		return str_replace(
			'src="' . $matches[0] . '"',
			'src="' . $matches[0] . '" srcset="' . implode( ', ', $srcset ) . '"',
			$image
		);
	}
}
