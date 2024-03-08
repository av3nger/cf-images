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
 * @since 1.8.1
 */

namespace CF_Images\App\Modules;

use CF_Images\App\Settings;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Multisite class.
 *
 * @since 1.8.1
 */
class Multisite extends Module {
	/**
	 * This is a core module, meaning it can't be enabled/disabled via options.
	 *
	 * @since 1.8.1
	 *
	 * @var bool
	 */
	protected $core = true;

	/**
	 * Run everything regardless of module status.
	 *
	 * @since 1.8.1
	 */
	public function pre_init() {
		add_action( 'cf_images_save_settings', array( $this, 'on_settings_update' ), 10, 2 );
		add_filter( 'cf_images_settings', array( $this, 'network_settings' ) );
	}

	/**
	 * Init the module if enabled.
	 *
	 * @since 1.8.1
	 */
	public function init() {}

	/**
	 * Update the module status.
	 *
	 * @since 1.8.1
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
	 * @since 1.8.1
	 *
	 * @param array $settings Current settings.
	 *
	 * @return array
	 */
	public function network_settings( array $settings ): array {
		if ( ! is_multisite() ) {
			return $settings;
		}

		if ( is_main_site() ) {
			$settings['network-wide'] = get_site_option( 'cf-images-network-wide' );
			return $settings;
		}

		$main_site_id = get_main_site_id();

		switch_to_blog( $main_site_id );

		$settings = get_option( 'cf-images-settings', Settings::get_defaults() );

		restore_current_blog();

		return $settings;
	}
}
