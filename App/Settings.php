<?php
/**
 * The file that defines the plugin settings.
 *
 * Plugin settings, based on the WordPress Settings API.
 *
 * @link       https://vcore.ru
 * @since      1.0.0
 *
 * @package    CF_Images
 * @subpackage CF_Images/App
 */

namespace CF_Images\App;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The settings plugin class.
 *
 * @since      1.0.0
 * @package    CF_Images
 * @subpackage CF_Images/App
 * @author     Anton Vanyukov <a.vanyukov@vcore.ru>
 */
class Settings {

	use Traits\Helpers;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'admin_init', array( $this, 'register' ) );

	}

	/**
	 * Register plugin settings via the WordPress Settings API.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register() {

		register_setting(
			$this->get_slug(),
			$this->get_slug() . '-settings',
			array( $this, 'validate' )
		);

	}

	public function validate( $settings ) {

	}

}
