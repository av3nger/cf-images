<?php
/**
 * The file that defines helper traits that are used across all classes.
 *
 * @link       https://vcore.ru
 * @since      1.0.0
 *
 * @package    CF_Images
 * @subpackage CF_Images/App/Traits
 */

namespace CF_Images\App\Traits;

use CF_images\App\Core;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The helpers trait class.
 *
 * @since      1.0.0
 * @package    CF_Images
 * @subpackage CF_Images/App/Traits
 * @author     Anton Vanyukov <a.vanyukov@vcore.ru>
 */
trait Helpers {

	/**
	 * Get plugin slug.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_slug(): string {

		return Core::get_instance()->get_plugin_name();

	}

}
