<?php
/**
 * Offload, Store, Resize & Optimize with Cloudflare Images
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * Admin area. This file also App all the dependencies used by the plugin, registers
 * the activation and deactivation functions, and defines a function that starts the plugin.
 *
 * @link              https://vcore.au
 * @since             1.0.0
 * @package           CF_Images
 *
 * @wordpress-plugin
 * Plugin Name:       Offload Media to Cloudflare Images
 * Plugin URI:        https://vcore.au
 * Description:       Offload media library images to the `Cloudflare Images` service.
 * Version:           1.4.1
 * Author:            Anton Vanyukov
 * Author URI:        https://vcore.au
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       cf-images
 * Domain Path:       /languages
 * Network:           true
 */

namespace CF_Images;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'CF_IMAGES_VERSION', '1.4.1' );
define( 'CF_IMAGES_DIR_URL', plugin_dir_url( __FILE__ ) );

require_once 'app/class-activator.php';
register_activation_hook( __FILE__, array( 'CF_Images\App\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'CF_Images\App\Activator', 'deactivate' ) );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_cf_images() {

	require_once __DIR__ . '/app/traits/trait-ajax.php';
	require_once __DIR__ . '/app/traits/trait-helpers.php';
	require_once __DIR__ . '/app/traits/trait-settings.php';
	require_once __DIR__ . '/app/traits/trait-stats.php';
	require_once __DIR__ . '/app/class-core.php';
	App\Activator::maybe_upgrade();
	App\Core::get_instance();

}
run_cf_images();
