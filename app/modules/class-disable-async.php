<?php
/**
 * Disable async processing
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 *
 * @since 1.4.0 Moved to a separate module
 */

namespace CF_Images\App\Modules;

use CF_Images\App\Async;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Disable_Async class.
 *
 * @since 1.4.0
 */
class Disable_Async extends Module {
	/**
	 * Init the module.
	 *
	 * @since 1.4.0
	 */
	public function init() {}

	/**
	 * Because the actions need to run if this module is disabled (which is reverse of a typical module),
	 * we need to hook into the pre_init() method.
	 *
	 * @since 1.5.0
	 */
	public function pre_init() {
		if ( ! $this->is_module_enabled() ) {
			require_once __DIR__ . '/../async/class-task.php';
			require_once __DIR__ . '/../async/class-upload.php';
			new Async\Upload();
		}
	}
}
