<?php
/**
 * The file that defines settings traits that are used across module classes.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Traits
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.4.0
 */

namespace CF_Images\App\Traits;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The settings trait class.
 *
 * @since 1.4.0
 */
trait Settings {

	/**
	 * Module icon.
	 *
	 * @var string
	 */
	protected $icon = '';

	/**
	 * Module title.
	 *
	 * @var string
	 */
	protected $title = '';

	/**
	 * Module is experimental or beta.
	 *
	 * @var string
	 */
	protected $experimental = false;

	/**
	 * Module is new.
	 *
	 * @var bool
	 */
	protected $new = false;

	/**
	 * Order of the module in the list.
	 *
	 * @var int
	 */
	protected $order = 10;

	/**
	 * Register UI components.
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	protected function register_ui() {}

	/**
	 * Render module description.
	 *
	 * @since 1.4.0
	 *
	 * @param string $module  Module ID.
	 *
	 * @return void
	 */
	public function render_description( string $module ) {}

}
