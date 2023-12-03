<?php
/**
 * CDN functionality
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.7.0
 */

namespace CF_Images\App\Modules;

use CF_Images\App\Api\Ai;
use CF_Images\App\Traits;
use Exception;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * CDN class.
 *
 * @since 1.7.0
 */
class CDN extends Module {
	use Traits\Ajax;

	/**
	 * Run everything regardless of module status.
	 *
	 * @since 1.7.0
	 */
	public function pre_init() {
		add_filter( 'cf_images_default_settings', array( $this, 'add_setting' ) );
		add_filter( 'cf_images_core_module_status', array( $this, 'manage_core_modules' ), 10, 2 );
		add_action( 'cf_images_save_settings', array( $this, 'on_settings_update' ), 10, 2 );
	}

	/**
	 * Init the module if enabled.
	 *
	 * @since 1.7.0
	 */
	public function init() {
		add_filter( 'cf_images_module_status', array( $this, 'manage_modules' ), 10, 2 );
		add_action( 'cf_images_cdn_status', array( $this, 'cdn_status' ) );
		add_action( 'admin_init', array( $this, 'set_cron_schedule' ) );

		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_cf_image_enable_cdn', array( $this, 'enable_cdn' ) );
			add_action( 'wp_ajax_cf_image_purge_cdn_cache', array( $this, 'purge_cache' ) );
			add_action( 'wp_ajax_cf_image_cdn_status', array( $this, 'cdn_status' ) );
		}
	}

	/**
	 * Add default option.
	 *
	 * @since 1.7.0
	 *
	 * @param array $defaults Default settings.
	 *
	 * @return array
	 */
	public function add_setting( array $defaults ): array {
		if ( ! isset( $defaults['cdn'] ) ) {
			$defaults['cdn'] = false;
		}

		return $defaults;
	}


	/**
	 * Disable Cloudflare images, if CDN is enabled.
	 *
	 * @since 1.7.0
	 *
	 * @param bool   $status Current status.
	 * @param string $module Module ID.
	 *
	 * @return bool
	 */
	public function manage_core_modules( bool $status, string $module ): bool {
		if ( 'cloudflare-images' !== $module || ! $this->is_module_enabled() ) {
			return $status;
		}

		return false;
	}

	/**
	 * Page parser module is required for CDN.
	 *
	 * @since 1.7.0
	 *
	 * @param bool   $status Current status.
	 * @param string $module Module ID.
	 *
	 * @return bool
	 */
	public function manage_modules( bool $status, string $module ): bool {
		if ( 'page-parser' !== $module ) {
			return $status;
		}

		return true;
	}

	/**
	 * Remove cron when disabling the module.
	 *
	 * @since 1.7.0
	 *
	 * @param array $settings Settings array.
	 * @param array $data     Passed in data from the app.
	 */
	public function on_settings_update( array $settings, array $data ) {
		if ( ! isset( $data['cdn'] ) || ! filter_var( $data['cdn'], FILTER_VALIDATE_BOOLEAN ) ) {
			self::remove_cron();
			delete_option( 'cf-images-cdn-enabled' );
		}
	}

	/**
	 * Toggle CDN on settings update.
	 *
	 * @since 1.7.0
	 */
	public function enable_cdn() {
		$this->check_ajax_request( true );

		// Exit early, if zone is already created.
		if ( get_option( 'cf-images-cdn-enabled', false ) ) {
			wp_send_json_success();
		}

		try {
			$response = ( new Ai() )->enable_cdn( get_site_url() );
			if ( ! isset( $response['code'] ) ) {
				wp_send_json_error( esc_html__( 'Error enabling CDN', 'cf-images' ) );
			} else {
				// Tracks the successful creation of the CDN zones.
				if ( 201 === $response['code'] && ! empty( $response['zone'] ) ) {
					update_option( 'cf-images-cdn-enabled', $response['zone'], false );
				}

				wp_send_json_success( $response['code'] );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Purge CDN cache.
	 *
	 * @since 1.7.0
	 */
	public function purge_cache() {
		$this->check_ajax_request( true );

		if ( ! get_option( 'cf-images-cdn-enabled', false ) ) {
			wp_send_json_error( esc_html__( 'Error with zone, cannot clear cache.', 'cf-images' ) );
		}

		try {
			( new Ai() )->purge_cdn_cache( get_site_url() );
			wp_send_json_success();
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Sync CDN stats.
	 *
	 * @since 1.7.0
	 */
	public function cdn_status() {
		if ( ! wp_doing_cron() ) {
			$this->check_ajax_request( true );
		}

		try {
			$response = ( new Ai() )->get_cdn_status( get_site_url() );
			if ( isset( $response['remaining'] ) && 0 === $response['remaining'] ) {
				delete_option( 'cf-images-cdn-enabled' );

				if ( ! wp_doing_cron() ) {
					wp_send_json_error( esc_html__( 'CDN quota exceeded. CDN disabled.', 'cf-images' ) );
				}
			}

			if ( ! wp_doing_cron() ) {
				wp_send_json_success();
			}
		} catch ( Exception $e ) {
			if ( ! wp_doing_cron() ) {
				wp_send_json_error( $e->getMessage() );
			}
		}
	}

	/**
	 * Remove scheduled cron event.
	 *
	 * @since 1.7.0
	 */
	public static function remove_cron() {
		$timestamp = wp_next_scheduled( 'cf_images_cdn_status' );
		wp_unschedule_event( $timestamp, 'cf_images_cdn_status' );
	}

	/**
	 * Schedule a cron to make sure that CDN is always synced with the API.
	 *
	 * @since 1.7.0
	 */
	public function set_cron_schedule() {
		if ( ! wp_next_scheduled( 'cf_images_cdn_status' ) ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'cf_images_cdn_status' );
		}
	}
}
