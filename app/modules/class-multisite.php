<?php
/**
 * Multisite module.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 *
 * @since 1.9.0
 */

namespace CF_Images\App\Modules;

use CF_Images\App\Settings;
use CF_Images\App\Traits\Empty_Init;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Multisite class.
 *
 * @since 1.9.0
 */
class Multisite extends Module {
	use Empty_Init;

	/**
	 * This is a core module, meaning it can't be enabled/disabled via options.
	 *
	 * @since 1.9.0
	 *
	 * @var bool
	 */
	protected $core = true;

	/**
	 * Run everything regardless of module status.
	 *
	 * @since 1.9.0
	 */
	public function pre_init() {
		add_action( 'cf_images_save_settings', array( $this, 'on_settings_update' ), 10, 2 );
		add_filter( 'cf_images_settings', array( $this, 'network_settings' ) );
	}

	/**
	 * Update the module status.
	 *
	 * @since 1.9.0
	 *
	 * @param array $settings Settings array.
	 * @param array $data     Passed in data from the app.
	 */
	public function on_settings_update( array $settings, array $data ) {
		if ( ! is_multisite() || ! is_main_site() ) {
			return;
		}

		if ( ! isset( $data['network-wide'] ) || ! filter_var( $data['network-wide'], FILTER_VALIDATE_BOOLEAN ) ) {
			delete_site_option( 'cf-images-network-wide' );
		} else {
			update_site_option( 'cf-images-network-wide', true );
		}
	}

	/**
	 * Use network wide settings.
	 *
	 * @since 1.9.0
	 *
	 * @param array $settings Current settings.
	 *
	 * @return array
	 */
	public function network_settings( array $settings ): array {
		if ( ! is_multisite() ) {
			return $settings;
		}

		// Cache the network-wide flag to avoid repeated DB lookups.
		static $network_wide = null;
		if ( null === $network_wide ) {
			$network_wide = (bool) get_site_option( 'cf-images-network-wide' );
		}

		if ( is_main_site() ) {
			$settings['network-wide'] = $network_wide;
			return $settings;
		}

		// Only override subsite settings when network-wide mode is enabled.
		if ( ! $network_wide ) {
			return $settings;
		}

		// Cache main site settings to avoid repeated switch_to_blog calls.
		static $main_settings = null;
		if ( null === $main_settings ) {
			$main_site_id = get_main_site_id();
			switch_to_blog( $main_site_id );
			$main_settings = get_option( 'cf-images-settings', Settings::get_defaults() );
			restore_current_blog();
		}

		return $main_settings;
	}
}
