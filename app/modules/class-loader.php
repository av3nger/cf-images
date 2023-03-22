<?php
/**
 * Module loader class
 *
 * This class defines all functionality for initializing plugin modules.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.2.1
 */

namespace CF_Images\App\Modules;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Loader class.
 *
 * @since 1.2.1
 */
class Loader {

	/**
	 * Plugin instance.
	 *
	 * @since 1.2.1
	 * @access private
	 * @var null|Loader $instance  Loader instance.
	 */
	private static $instance = null;

	/**
	 * Registered modules.
	 *
	 * @since 1.2.1
	 * @access private
	 *
	 * @var array $modules  Registered modules.
	 */
	private $modules = array();

	/**
	 * Get Loader instance.
	 *
	 * @since 1.2.1
	 *
	 * @return Loader
	 */
	public static function get_instance(): Loader {

		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Module constructor.
	 *
	 * @since 1.2.1
	 *
	 * @return void
	 */
	private function __construct() {
		require_once __DIR__ . '/class-module.php';
	}

	/**
	 * Register the module.
	 *
	 * @since 1.2.1
	 *
	 * @param string $module  Module ID.
	 *
	 * @return void
	 */
	public function register( string $module ) {

		// If already registered - exit.
		if ( isset( $this->modules[ $module ] ) ) {
			return;
		}

		// Unable to find module file - exit.
		if ( ! file_exists( __DIR__ . '/class-' . $module . '.php' ) ) {
			return;
		}

		require_once __DIR__ . '/class-' . $module . '.php';
		$this->activate( $module );

	}

	/**
	 * Activate module.
	 *
	 * @since 1.2.1
	 *
	 * @param string $module  Module ID.
	 *
	 * @return void
	 */
	private function activate( string $module ) {

		$parts = explode( '-', $module );
		$parts = array_map( 'ucfirst', $parts );
		$class = implode( '_', $parts );

		$class_name = '\\CF_Images\\App\\Modules\\' . $class;

		$module_obj = new $class_name( $module );

		if ( $module_obj instanceof $class_name ) {
			$this->modules[ $module ] = $module_obj;
		}

	}

}
