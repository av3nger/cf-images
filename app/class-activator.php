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
	 */
	public static function maybe_upgrade() {
		$version = get_site_option( 'cf-images-version' );

		if ( CF_IMAGES_VERSION === $version ) {
			return;
		}

		if ( ! $version || version_compare( CF_IMAGES_VERSION, '1.2.0', '<' ) ) {
			delete_option( 'cf-images-install-notice' );
		}

		if ( version_compare( $version, '1.5.0' ) ) {
			self::upgrade_150();
		}

		update_site_option( 'cf-images-version', CF_IMAGES_VERSION );
	}

	/**
	 * Upgrade to version 1.5.0.
	 *
	 * @since 1.5.0
	 */
	private static function upgrade_150() {
		// We are now storing all the settings in a single option.
		$options = array(
			'auto-offload'       => 'cf-images-auto-offload',
			'auto-resize'        => 'cf-images-auto-resize',
			'custom-id'          => 'cf-images-custom-id',
			'disable-async'      => 'cf-images-disable-async',
			'disable-generation' => 'cf-images-disable-generation',
			'full-offload'       => 'cf-images-full-offload',
			'image-ai'           => 'cf-images-image-ai',
			'image-compress'     => 'cf-images-image-compress',
			'page-parser'        => 'cf-images-page-parser',
		);

		$settings = array_fill_keys( array_keys( $options ), false );
		foreach ( $options as $option_id => $option_key ) {
			$settings[ $option_id ] = (bool) get_option( $option_key );
			delete_option( $option_key );
		}

		update_option( 'cf-images-settings', $settings, false );
	}
}
