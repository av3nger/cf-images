<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an Admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link https://vcore.au
 * @since 1.0.0
 * @package CF_Images
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_site_option( 'cf-images-version' );
delete_site_option( 'cf-images-hash' );
delete_site_option( 'cf-images-hide-sidebar' );
delete_site_option( 'cf-images-account-id' );
delete_site_option( 'cf-images-api-token' );
delete_site_option( 'cf-images-network-wide' );
delete_site_option( 'cf-images-browser-ttl' );
delete_option( 'cf-images-custom-domain' );
delete_option( 'cf-images-setup-done' );
delete_option( 'cf-images-config-written' );
delete_option( 'cf-images-stats' );
delete_option( 'cf-images-auth-error' );
delete_option( 'cf-image-ai-api-key' );
delete_option( 'cf-images-settings' );
delete_option( 'cf-images-custom-path' );
delete_option( 'cf-images-cdn-enabled' );
delete_option( 'cf-images-integrations' );


/**
 * These have been removed since version 1.4.0.
 * Keep this just in case we need to clean an old installation.
 */
delete_option( 'cf-images-auto-offload' );
delete_option( 'cf-images-auto-resize' );
delete_option( 'cf-images-custom-id' );
delete_option( 'cf-images-disable-async' );
delete_option( 'cf-images-disable-generation' );
delete_option( 'cf-images-full-offload' );
delete_option( 'cf-images-image-ai' );
delete_option( 'cf-images-page-parser' );
delete_option( 'cf-images-image-compress' );

// Remove defines from wp-config.php file.
require_once __DIR__ . '/app/traits/trait-ajax.php';
require_once __DIR__ . '/app/traits/trait-helpers.php';
require_once __DIR__ . '/app/traits/trait-stats.php';
require_once __DIR__ . '/app/class-settings.php';
$settings = new CF_Images\App\Settings();
$settings->write_config( 'CF_IMAGES_ACCOUNT_ID' );
$settings->write_config( 'CF_IMAGES_KEY_TOKEN' );

require_once __DIR__ . '/app/modules/class-module.php';
require_once __DIR__ . '/app/modules/class-cdn.php';
CF_Images\App\Modules\CDN::remove_cron();
