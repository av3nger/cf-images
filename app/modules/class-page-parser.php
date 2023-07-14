<?php
/**
 * Page parser
 *
 * Instead of replacing the images via hooks, replace images by parsing the page on the front-end.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.4.0
 */

namespace CF_Images\App\Modules;

use CF_Images\App\Core;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Page_Parser class.
 *
 * @since 1.4.0
 */
class Page_Parser extends Module {

	/**
	 * Should the module only run on front-end?
	 *
	 * @since 1.4.0
	 * @access protected
	 *
	 * @var bool
	 */
	protected $only_frontend = true;

	/**
	 * Register UI components.
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	protected function register_ui() {
		$this->icon  = 'format-gallery';
		$this->title = esc_html__( 'Parse page for images', 'cf-images' );
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
			<?php esc_html_e( 'Compatibility module to support themes that do not use WordPress hooks and filters. If the images are not replaced on the site, try enabling this module', 'cf-images' ); ?>
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
	public function init() {
	}

}
