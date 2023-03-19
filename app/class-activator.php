<?php
/**
 * Fired during plugin activation/deactivation
 *
 * This class defines all code necessary to run during the plugin's activation and deactivation.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.0.0
 */

namespace CF_Images\App;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Fired during plugin activation.
 *
 * @since 1.0.0
 */
class Activator {

	/**
	 * Activation hook.
	 *
	 * @since 1.0.0
	 */
	public static function activate() {

		$activate = filter_input( INPUT_POST, 'action', FILTER_UNSAFE_RAW );
		$checked  = filter_input( INPUT_POST, 'checked', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		if ( 'activate-selected' === $activate && count( $checked ) > 1 ) {
			return; // Do not redirect if bulk activating plugins.
		}

		set_transient( 'cf-images-admin-redirect', 5 * MINUTE_IN_SECONDS );

	}

	/**
	 * Deactivation hook.
	 *
	 * @since 1.0.0
	 */
	public static function deactivate() {
	}

	/**
	 * Check if we need to perform any upgrade actions.
	 *
	 * @sicne 1.2.0
	 *
	 * @return void
	 */
	public static function maybe_upgrade() {

		$version = get_site_option( 'cf-images-version' );

		if ( CF_IMAGES_VERSION === $version ) {
			return;
		}

		if ( ! $version || version_compare( CF_IMAGES_VERSION, '1.2.0', '<' ) ) {
			delete_option( 'cf-images-install-notice' );
		}

		update_site_option( 'cf-images-version', CF_IMAGES_VERSION );

	}

}
