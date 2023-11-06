<?php
/**
 * Logging module.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 *
 * @since 1.6.0
 */

namespace CF_Images\App\Modules;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Logging class.
 *
 * @since 1.6.0
 */
class Logging extends Module {
	/**
	 * Log file.
	 *
	 * @since 1.6.0
	 * @access private
	 * @var string
	 */
	private $log_file = '';

	/**
	 * Init the module.
	 *
	 * @since 1.6.0
	 */
	public function init() {
		$this->init_log_file();

		if ( empty( $this->log_file ) ) {
			return;
		}

		add_action( 'cf_images_log', array( $this, 'log' ), 10, 5 );
	}

	/**
	 * Init log file.
	 *
	 * @since 1.6.0
	 */
	private function init_log_file() {
		$uploads = wp_get_upload_dir();

		if ( empty( $uploads['basedir'] ) ) {
			return;
		}

		$this->log_file = $uploads['basedir'] . '/cf-images.log';
	}

	/**
	 * Log message.
	 *
	 * @since 1.6.0
	 *
	 * @param mixed $message Message.
	 * @param mixed ...$args Additional arguments.
	 *
	 * @return void
	 */
	public function log( $message, ...$args ) {
		if ( is_array( $message ) || is_object( $message ) ) {
			$message = print_r( $message, true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		}

		foreach ( $args as &$arg ) {
			if ( is_array( $arg ) || is_object( $arg ) ) {
				$arg = print_r( $arg, true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
			}
		}
		unset( $arg );

		if ( ! empty( $args ) ) {
			$message = vsprintf( $message, $args );
		}

		$message = '[' . gmdate( 'c' ) . '] ' . $message . PHP_EOL;

		$fp = fopen( $this->log_file, 'a' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		flock( $fp, LOCK_EX );
		fwrite( $fp, $message ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		flock( $fp, LOCK_UN );
		fclose( $fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
}
