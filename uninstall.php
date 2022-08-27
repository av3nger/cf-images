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
 * @link       https://vcore.ru
 * @since      1.0.0
 *
 * @package    CF_Images
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'cf-images-hash' );
delete_option( 'cf-images-disable-generation' );
delete_option( 'cf-images-custom-domain' );
delete_option( 'cf-images-setup-done' );

// Remove defines from wp-config.php file.
require_once __DIR__ . '/App/Settings.php';
$settings = new CF_Images\App\Settings();
$settings->write_config( 'CF_IMAGES_ACCOUNT_ID' );
$settings->write_config( 'CF_IMAGES_KEY_TOKEN' );
