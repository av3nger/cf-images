<?php
/**
 * Module loader class
 *
 * This class defines all functionality for initializing plugin modules.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.3.0
 */

namespace CF_Images\App;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Loader class.
 *
 * @since 1.3.0
 */
class Loader {

	/**
	 * Plugin instance.
	 *
	 * @since 1.3.0
	 * @access private
	 * @var null|Loader $instance  Loader instance.
	 */
	private static $instance = null;

	/**
	 * Registered modules.
	 *
	 * @since 1.3.0
	 * @access private
	 *
	 * @var array $modules  Registered modules.
	 */
	private $modules = array();

	/**
	 * Registered integrations.
	 *
	 * @since 1.3.0
	 * @access private
	 *
	 * @var array $integrations  Registered integrations.
	 */
	private $integrations = array();

	/**
	 * Get Loader instance.
	 *
	 * @since 1.3.0
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
	 * @since 1.3.0
	 *
	 * @return void
	 */
	private function __construct() {
		require_once __DIR__ . '/modules/class-module.php';
	}

	/**
	 * Register a selected module.
	 *
	 * @since 1.3.0
	 *
	 * @param string $module  Module ID.
	 *
	 * @return void
	 */
	public function module( string $module ) {

		// If already registered - exit.
		if ( isset( $this->modules[ $module ] ) ) {
			return;
		}

		// Unable to find module file - exit.
		if ( ! file_exists( __DIR__ . '/modules/class-' . $module . '.php' ) ) {
			return;
		}

		require_once __DIR__ . '/modules/class-' . $module . '.php';
		$this->activate( $module );

	}

	/**
	 * Register a selected integration.
	 *
	 * @since 1.3.0
	 *
	 * @param string $module  Module ID.
	 *
	 * @return void
	 */
	public function integration( string $module ) {

		// If already registered - exit.
		if ( isset( $this->integrations[ $module ] ) ) {
			return;
		}

		// Unable to find module file - exit.
		if ( ! file_exists( __DIR__ . '/integrations/class-' . $module . '.php' ) ) {
			return;
		}

		require_once __DIR__ . '/integrations/class-' . $module . '.php';
		$this->activate( $module, 'integrations' );

	}

	/**
	 * Activate module.
	 *
	 * @since 1.3.0
	 *
	 * @param string $module  Module ID.
	 * @param string $type    Module type. Accepts: modules, integrations. Default: modules.
	 *
	 * @return void
	 */
	private function activate( string $module, string $type = 'modules' ) {

		$parts = explode( '-', $module );
		$parts = array_map( 'ucfirst', $parts );
		$class = implode( '_', $parts );

		$class_name = '\\CF_Images\\App\\' . ucfirst( $type ) . '\\' . $class;

		$module_obj = new $class_name( $module );

		if ( $module_obj instanceof $class_name ) {
			if ( 'modules' === $type ) {
				$this->modules[ $module ] = $module_obj;
			} else {
				$this->integrations[ $module ] = $module_obj;
			}
		}

	}

}
