<?php
/**
 * Fired during plugin activation
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
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    CF_Images
 * @subpackage CF_Images/App
 * @author     Anton Vanyukov <a.vanyukov@vcore.ru>
 */
class Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// TODO: make sure on first run we remove the default variants and sync up image sizes.
	}

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {

	}

}
