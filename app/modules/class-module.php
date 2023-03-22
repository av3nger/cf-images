<?php
/**
 * Abstract module class
 *
 * This class defines all the base code, which is inherited by stand-alone classes.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.2.1
 */

namespace CF_Images\App\Modules;

use CF_Images\App\Traits\Helpers;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Abstract Module class.
 *
 * @since 1.2.1
 */
abstract class Module {

	use Helpers;

	/**
	 * Module ID.
	 *
	 * @since 1.2.1
	 * @access protected
	 *
	 * @var string $module
	 */
	protected $module = '';

	/**
	 * Module constructor.
	 *
	 * @since 1.2.1
	 *
	 * @return void
	 */
	public function __construct() {

		if ( ! $this->is_set_up() || ! $this->is_enabled() ) {
			return;
		}

		$this->init();

	}

	/**
	 * Module init.
	 *
	 * @since 1.2.1
	 *
	 * @return void
	 */
	abstract public function init();

	/**
	 * Check if module is enabled via plugin settings.
	 *
	 * @since 1.2.1
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return (bool) get_option( 'cf-images-' . $this->module, false );
	}

}
